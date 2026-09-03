# Lote 1 — Brechas de esquema cerradas

Este documento cubre solo el trabajo de `backend-laravel` en el lote 1
(migraciones, modelos, recursos de Filament, comando de auditoría). El
sistema de diseño y la maquetación del front público se documentan aparte en
`docs/lote-1/00-sistema-diseno.md` (no se tocó desde aquí).

## S1 — Imagen de portada en Destination y Experience

**Decisión: una columna, no una tabla de imágenes.**

`tour_images` existe porque un tour tiene una galería real (varias fotos,
orden, ficha con carrusel). Los mockups de Home ("Destinos imperdibles",
"Experiencias únicas") muestran exactamente **una** foto por destino y por
experiencia — imagen con degradado y el nombre encima, sin carrusel. Una
tabla `destination_images`/`experience_images` replicaría la relación
`hasMany` + Repeater + Filament resource de `tour_images` para un caso de uso
que es, por diseño, 1:1. Eso es complejidad que nadie pidió: más una
migración, más un modelo, más un recorrido de borrado en cascada a mantener,
sin que ningún mockup lo use. Si el brief cambia a "galería de destino" en un
lote futuro, migrar de columna a tabla es sencillo (mover el path a filas de
una tabla nueva) — no es una decisión que encierre el proyecto.

Columnas agregadas a `destinations` y `experiences` (mismas en ambas tablas):

| Columna | Tipo | Notas |
|---|---|---|
| `cover_image_path` | `string`, nullable | Ruta en el disco `public`, nunca URL completa — se resuelve con `coverImageUrl()` |
| `cover_image_alt` | `json`, nullable | Traducible (Spatie), mismo patrón que `tour_images.alt` |

Migraciones: `2026_09_02_090000_add_cover_image_to_destinations_table.php`,
`2026_09_02_090100_add_cover_image_to_experiences_table.php`.

Modelos: `App\Models\Destination` y `App\Models\Experience` ganan
`cover_image_path`/`cover_image_alt` en `$fillable`, `cover_image_alt` en
`$translatable`, y el accessor `coverImageUrl(): ?string` (mismo contrato que
`TourImage::url()` — nunca construir la URL a mano).

Formularios (`DestinationForm`, `ExperienceForm`): sección "Imagen de
portada" con `FileUpload::make('cover_image_path')` blindado (ver S3), más
`cover_image_alt.{locale}` dentro de las mismas pestañas de idioma que
`name`/`slug`/`description`. Tablas: columna `ImageColumn` para verla en el
listado.

## S2 — Entidad "Equipo" (`TeamMember`)

Omisión del contrato de datos del lote 2, no un cambio de alcance: el
mockup de Nosotros tiene "Nuestro equipo" (foto, nombre, rol, descripción
corta, redes) y no existía ninguna tabla para eso.

Tabla `team_members`:

| Columna | Tipo | Traducible |
|---|---|---|
| `name`, `role`, `description` | `json` | sí |
| `photo_path` | `string`, nullable | — |
| `photo_alt` | `json`, nullable | sí |
| `instagram_url`, `facebook_url`, `whatsapp_url` | `string`, nullable | no — un enlace no cambia por idioma |
| `is_published` | `boolean`, default `false` | — |
| `order` | `unsignedInteger`, default `0` | — |

Recurso de Filament completo en `app/Filament/Resources/TeamMembers/`
(form, tabla, páginas), grupo de navegación "Nosotros". El formulario sigue
el mismo patrón `TranslatableTabs` que Destination/Experience/Tour.

**La tabla arranca vacía en todos los entornos, incluido desarrollo.** Las
cuatro personas del mockup (Carlos Mendoza, Ana Lucía Quispe, Luis
Fernández, María Torres) son nombres inventados con caras generadas por IA;
`database/seeders/DatabaseSeeder.php` no las siembra ni con el prefijo
`[MUESTRA]`, y `tests/Feature/TeamMemberCatalogTest::test_the_team_starts_empty_after_seeding`
falla si algún día alguien las agrega a un seeder. `TeamMemberFactory` existe
solo para tests (nunca se invoca desde un seeder).

## S3 — Blindaje de subida en los campos nuevos

`->image()` de Filament solo agrega `mimetypes:image/*`, que deja pasar un
SVG con `<script>` inline y polígotas GIF/PHP o GIF/HTML renombrados
`.pht`/`.html` (M-1, `docs/lote-2/seguridad-2026-09-01.md`, cerrado en su
momento para `tour_images`). Cada campo de imagen nuevo (S1 y S2) es una
superficie de subida nueva y necesita la misma defensa — no se hereda sola.

En vez de copiar el bloque inline de `TourForm` tres veces, se extrajo
`App\Filament\Support\SecureImageUpload::configure(FileUpload $upload,
string $directory)`: aplica la lista blanca cerrada
(`image/jpeg`, `image/png`, `image/webp`), tamaño máximo 4 MB, y el nombre de
archivo en disco derivado del MIME que el servidor detecta
(`UploadedFile::getMimeType()`, vía `finfo` sobre los bytes reales) — nunca
del nombre o la extensión que manda el cliente. La galería de `TourForm`
mantiene su propia copia inline sin tocar: ya tiene su suite verde
(`tests/Feature/TourImageUploadSecurityTest.php`) y refactorizarla no era
parte de este lote.

Usan el helper: `DestinationForm::cover_image_path`,
`ExperienceForm::cover_image_path`, `TeamMemberForm::photo_path`.

Tests de seguridad: la lógica común vive en el trait
`Tests\Feature\Concerns\AssertsSecureImageUploads` (mismos 5 casos que
`TourImageUploadSecurityTest`: acepta PNG legítimo, rechaza SVG con
`<script>`, rechaza polígota `.pht`, rechaza polígota `.html`, la extensión
en disco viene del MIME detectado y no del nombre del cliente). Cada campo
tiene su propia clase de test (`DestinationCoverImageUploadSecurityTest`,
`ExperienceCoverImageUploadSecurityTest`, `TeamMemberPhotoUploadSecurityTest`)
que solo aporta el componente Livewire y los datos base del formulario.

**Verificación de que el check no es un check vacío:** se reemplazó
temporalmente `SecureImageUpload::configure()` por `->image()` (la
vulnerabilidad original) y se corrió la suite de las 3 clases nuevas: las 9
pruebas de rechazo (SVG/`.pht`/`.html` × 3 campos) fallaron con "Component
has no errors" — es decir, el archivo malicioso se guardaba sin objeción, tal
como pasaba antes de M-1. Las 6 pruebas de aceptación de PNG siguieron en
verde (una lista blanca que rechazara todo no habría sido una prueba
honesta). Se revirtió el cambio inmediatamente después.

## S4 — Auditoría repetible de datos de muestra

Comando `php artisan data:audit-sample`
(`app/Console/Commands/AuditSampleDataCommand.php`), nacido de dos
artefactos reales de QA encontrados en `pachaviva`:

- `tours.id=3` ("Camino Inca Corto a Machu Picchu"): `is_featured=1`,
  `order=0`, sin prefijo `[MUESTRA]` — se renderiza primero en el carrusel de
  Home, indistinguible de contenido real.
- `users.id=2` (`noadmin.qa@pachaviva.test`): cuenta de QA.

**Por qué el comando no "adivina" qué borrar por defecto:** una vez que
lleguen los datos reales de la clienta, contenido legítimo también
carecerá del prefijo `[MUESTRA]` — eso es lo esperado, no un bug. El comando
no puede distinguir "artefacto de QA" de "contenido real recién aprobado"
solo mirando los datos. Por default (sin flags) solo **reporta**:

- Tabla de usuarios con columna "Dominio de prueba", más una línea de alerta
  por cada uno que use el dominio reservado `pachaviva.test` (RFC 2606,
  dominio especial que ningún cliente real tendrá jamás — es la única señal
  100% cierta que el comando usa).
- Tabla de contenido de catálogo (Tour/Destino/Experiencia/Equipo) sin
  prefijo `[MUESTRA]`, con una línea de alerta aparte para el caso de mayor
  riesgo: publicado + destacado + `order=0` (aparece primero en el
  carrusel).

Limpieza, solo si se pide explícitamente:

- `--clean-test-users`: borra los usuarios cuyo email está en el dominio
  `pachaviva.test`. Nunca heurística: es un dominio reservado, no una
  suposición.
- `--clean-tour-ids=3,7`: el operador nombra los IDs exactos después de leer
  el reporte; el comando nunca los infiere. Despublica y quita el destacado
  (no borra la fila — queda en borrador para revisión posterior).

**No se corrió `--clean-*` contra `pachaviva`** (la base de desarrollo
compartida) en este lote: el maquetador la está usando en vivo para
verificar el front ahora mismo, y `--clean-tour-ids=3` cambiaría lo que ve en
el carrusel de Home sin avisarle. Correr esto en el servidor antes de cada
despliegue queda pendiente de que Anyerson lo autorice.

Cubierto por `tests/Feature/AuditSampleDataCommandTest.php`, incluida la
prueba que **debe poder fallar**: `test_running_without_flags_does_not_delete_or_modify_anything`
verifica que ejecutar el comando sin flags no borra ni modifica nada. Se
comprobó que efectivamente detecta una regresión: se hizo que el comando
limpiara por defecto (simulando el bug opuesto al que previene) y el test
falló correctamente ("La tabla [users] está vacía"); se revirtió el cambio.

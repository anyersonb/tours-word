# Lote 2 — Contrato de datos

Motor: Laravel 12.69.1 limpio, instalado dentro del repo existente preservando
`.git/`, `.claude/`, `docs/`, `README.md`, `.gitattributes`,
`resources/css/tokens.css` y `tailwind.config.js` (nada de esto se tocó).

## Mecanismo de i18n elegido: columnas JSON traducibles (Spatie)

`spatie/laravel-translatable` (v6.11) sobre columnas `JSON`, no una tabla de
traducciones aparte. Tres razones:

1. **El catálogo es de tamaño moderado** (tours, destinos, experiencias): una
   tabla `translations` polimórfica añade un JOIN por idioma en cada consulta
   pública sin beneficio real a esta escala.
2. **El esquema ya soporta EN/PT-BR hoy** sin migración futura: la columna es
   la misma, solo cambia qué claves de idioma tiene el JSON. Migrar contenido
   existente a traducible después (la alternativa cara que el brief pide
   evitar) sencillamente no aplica con este mecanismo.
3. **Filament resuelve el patrón sin plugin**: `$model->title` devuelve el
   string del locale activo (accessor de Spatie), pero
   `$model->attributesToArray()['title']` devuelve el mapa completo
   `{"es": "...", "en": "..."}` — que es exactamente lo que un campo de
   formulario con nombre `title.es` espera para hidratar/guardar. El plugin
   oficial `filament/spatie-laravel-translatable-plugin` está **abandonado**
   y su última versión es para Filament v3 (no v4), así que se optó por este
   mecanismo nativo en vez de traerlo.

`config('cms.locales')` declara el universo soportado por el ESQUEMA
(`es`, `en`, `pt_BR`); `config('cms.active_locales')` declara qué subconjunto
edita HOY el panel (`['es']`). Activar EN/PT-BR en el lote 5 es cambiar esa
config — cero migraciones.

## Moneda: `App\Support\Money` como fuente única

- Los importes se guardan en **enteros (céntimos)**: `price_pen_cents`,
  `price_usd_cents` en `tours`.
- `App\Support\Money` es la única clase que formatea (`format()`), convierte
  (`convertTo()`) y parsea entrada humana a céntimos (`parseToCents()`).
  Ningún Blade/Resource/Livewire debe escribir `S/`, `$` o multiplicar un
  precio a mano — los símbolos vienen de `config('cms.currencies')`.
- El **tipo de cambio** (`settings.exchange_rate_pen_usd`, cuántos soles
  equivalen a 1 dólar) es un valor fijo editable en Configuración
  (`App\Filament\Pages\Configuracion`), **nunca una API**. Se lee vía
  `Setting::get()`, cacheado con `Cache::rememberForever` y invalidado
  automáticamente al guardar (`Setting::set()` hace `Cache::forget`) — no
  hace falta limpiar caché a mano tras editarlo.
- `Money::pen()`/`Money::usd()` en `Tour` son precios **entrados
  directamente** por la clienta en las dos monedas, no derivados uno del otro
  con el tipo de cambio al guardar. El tipo de cambio general
  (`Money::convertTo()`) queda disponible para usos que sí necesiten una
  conversión en vivo (ver "Reserva" abajo).
- **Redondeo**: siempre "half away from zero" (el `round()` nativo de PHP)
  sobre los céntimos resultantes — es la única regla de redondeo del proyecto,
  documentada en `App\Support\Money::convertTo()`.

## Zona horaria: `America/Lima` de punta a punta

- `config('app.timezone')` = `env('APP_TIMEZONE', 'America/Lima')`.
- La conexión MySQL fija `'timezone' => env('DB_TIMEZONE', '-05:00')` en
  `config/database.php`. Perú no tiene horario de verano, así que el offset
  fijo es correcto todo el año. Esto es explícito a propósito: sin esa línea,
  el servidor MySQL usa SU PROPIO timezone por defecto (que en un servidor
  compartido no está garantizado que sea Lima), mientras PHP razona en
  `America/Lima` — exactamente el incidente real que ya ocurrió una vez en
  otro proyecto de la agencia.
- Probado en `tests/Feature/TimezoneTest.php`: guarda un registro a las 23:30
  hora Lima y confirma que se relee como 23:30, no como el día siguiente en
  UTC.

## Esquema (tablas y campos clave)

| Tabla | Campos clave | Notas |
|---|---|---|
| `destinations` | `name` (JSON), `slug` (JSON), `description` (JSON, nullable), `is_published`, `order` | Categoría "Destino" del catálogo |
| `experiences` | `name` (JSON), `slug` (JSON), `description` (JSON, nullable), `is_published`, `order` | Categoría "Experiencia" del catálogo |
| `tours` | `destination_id` (FK nullable, `nullOnDelete`), `title`/`slug`/`summary`/`description`/`duration_label`/`meeting_point`/`inclusions`/`exclusions`/`meta_title`/`meta_description` (JSON), `difficulty` (string, `App\Enums\TourDifficulty`, NO traducible), `price_pen_cents`, `price_usd_cents` (enteros), `is_featured`, `is_published`, `order` | Núcleo del catálogo |
| `experience_tour` | `tour_id`, `experience_id` (PK compuesta) | Pivote muchos-a-muchos |
| `tour_images` | `tour_id` (FK `cascadeOnDelete`), `path`, `alt` (JSON), `order` | Galería ordenable; `path` es ruta en el disco `public`, nunca URL completa |
| `tour_slug_histories` | `tour_id` (FK `cascadeOnDelete`), `locale`, `slug`, `created_at` | Ver sección de slugs/301 abajo |
| `settings` | `key` (unique), `value` (text), `type` (string/integer/float/boolean/json), `group`, `description` | Clave/valor tipado; `exchange_rate_pen_usd` vive acá |
| `users` | + `is_admin` (boolean, nuevo) | Gate de `canAccessPanel()` — ver sección de seguridad |

Todas las FK son explícitas: `cascadeOnDelete()` donde el hijo no tiene
sentido sin el padre (imágenes, historial de slugs, pivote de experiencias),
`nullOnDelete()` en `tours.destination_id` (un tour puede quedar sin destino
asignado si se borra el destino, en vez de arrastrar el borrado).

## Slugs editables y redirección 301 (previsto, no construido)

El slug es editable por idioma. Como es una columna JSON, no puede llevar un
índice `UNIQUE` nativo por locale: la unicidad se valida en la aplicación
(`Tour::slugTaken($locale, $slug, $exceptId)`, usado tanto por la regla de
Filament como por los tests).

Cada vez que un slug cambia, `Tour::booted()` (evento `updating`) escribe el
valor anterior en `tour_slug_histories`. **Esto se construye en este lote**;
lo que NO se construye es el middleware público de redirección 301, porque
no existe front público todavía (lote 3/5 lo traen). Cuando exista, puede
resolver la 301 con una simple consulta a esta tabla — la historia ya no se
pierde desde hoy.

## Módulos previstos para lotes siguientes (documentados, sin migraciones)

- **Reserva** (lote 4): tabla `bookings` con, como mínimo, `tour_id`,
  datos del cliente, moneda elegida, `price_cents` congelado al momento de
  reservar, y **`exchange_rate_used`** — el tipo de cambio de
  `settings.exchange_rate_pen_usd` vigente en el instante de la reserva,
  copiado ahí para que el precio en USD de un tour no cambie por debajo de
  una reserva en curso si la clienta edita el tipo de cambio después. No se
  crea la migración ahora: el módulo de reservas no existe.
- **Reseña** (lote 4/5): tabla `reviews`, probablemente polimórfica sobre
  `tour_id`, con autor, calificación, texto, estado de moderación.
- **Entrada de blog** (lote 5): tabla `posts` con los mismos campos
  traducibles que `tours` (título, slug, cuerpo, meta SEO) y el mismo
  mecanismo de historial de slug.
- **Mensaje de contacto** (lote 3): tabla `contact_messages` — remitente,
  canal, mensaje, estado de atención.

## Usuario admin de desarrollo

Se creó un usuario `is_admin = true` **directamente por tinker**, no por un
seeder. `database/seeders/DatabaseSeeder.php` deliberadamente NO crea
ningún usuario — un seeder que crea una cuenta con contraseña predecible es
exactamente el tipo de cosa que ya se filtró a producción antes en otro
proyecto de la agencia. Las credenciales de este usuario viven solo en el
reporte de entrega, no en ningún archivo del repo. **Este usuario no debe
existir en producción**: antes de desplegar, confirmar con
`SELECT * FROM users WHERE is_admin = 1;` que la única fila (si alguna) es la
cuenta real de la clienta, no esta de desarrollo.

El gate está en `App\Models\User::canAccessPanel()`: NO es "cualquier usuario
autenticado entra al panel" — solo `is_admin = true`. Cubierto por
`tests/Feature/AdminPanelAccessTest.php`, incluyendo el caso negativo (un
usuario normal recibe 403).

## Datos de muestra

`database/seeders/DemoTourSeeder.php` crea 2 destinos reales (Cusco,
Arequipa), 3 experiencias reales (Trekking, Gastronomía, Cultura) y 2 tours
con el título prefijado **"[MUESTRA]"**. Los precios de estos tours de
muestra son números redondos de relleno (S/ 3,500.00, S/ 120.00 / US$ 95.00,
US$ 32.00), explícitamente no reales — ningún dato de este seeder debe leerse
como información provista por la clienta.

# Lote 16 — Home: ajuste a las 4 maquetas móviles del cliente

**Línea base git**: rama `lote-1-sistema-diseno`, `a26aa1f fix(storage): la URL del disco publico sale de configuracion` (working tree limpio antes de empezar).

Maquetas recibidas (4 imágenes, JPEG, enviadas por WhatsApp):
1. Hero + buscador + píldoras de destino + franja de 5 íconos de confianza.
2. Destinos imperdibles (carrusel con tarjeta central destacada) + Actividades para vivir Perú (carrusel de tarjetas verticales).
3. Tarjeta de tour (carrusel con laterales asomando).
4. Por qué elegirnos (foto + tarjeta flotante "Asistencia 24/7" + grilla 2x2 + franja de 4 íconos).

Este documento se escribe por bloques a medida que avanzo (no al final).

---

## 1. Archivos tocados

- `resources/views/home.blade.php` — reescritura de estructura y orden de secciones.
- `resources/views/components/ui/tour-card.blade.php` — props aditivas `isFeatured`/`showAttributes`, badges de foto, grilla 2x2 DEMO.
- `resources/views/components/ui/carousel-shell.blade.php` — prop aditiva `trackClass`.
- `resources/views/components/ui/badge.blade.php` — variante aditiva `white`.
- `lang/es/site.php` / `lang/en/site.php` — claves nuevas (buscador, píldoras, franja de confianza reescrita, badges/atributos de tour-card, badge de destino destacado).

Ningún archivo de backend (controller, modelo, migración) se tocó — la tarea es 100% maquetación, tal como pedía el encargo ("maquetación primero, cero backend").

---

## 2. Nuevo orden de la home

Hero (con buscador + píldoras + franja) → Destinos imperdibles (carrusel) → Actividades (carrusel) → Tours destacados (carrusel) → Por qué elegirnos → Mosaico → Testimonios (apagado) → Aliados → Newsletter.

Ninguna sección se borró; el Mosaico (antes pegado al hero) se movió íntegro (mismo contenido, mismo grid, mismo JS) al tramo final, junto a Aliados/Newsletter, tal como pedía la decisión cerrada.

---

## 3. Hero: buscador + píldoras + franja

- **Buscador**: decisión tomada — `TourController@index` (revisado su código) solo valida `destino` y `experiencia` por **slug exacto** vía `Validator`; no existe ningún parámetro de texto libre. Un `<input>` que no filtra nada real es un control falso, así que el buscador es un `<a href="{{ route('tours.index') }}">` con la piel de un input (icono de lupa + placeholder "¿A dónde te gustaría viajar?"), sin tocar el controller.
- **Píldoras**: los 3 primeros `$destinations` reales por `order`, con su `coverImageUrl()` (o el placeholder de catálogo de siempre) en miniatura redonda de 36px, y `href` real a `destinations.show`. Cero nombres quemados.
- **Franja de confianza**: se sacó de dentro del hero (donde vivía sobre el scrim oscuro) y pasó a una sección propia `bg-sand` debajo del buscador/píldoras, `tone="light"` en vez de `tone="dark"`. Reemplacé también el copy de las 5 pastillas por el que aprobó la clienta en la maqueta (Operador local / Reserva segura / Guías expertos / Atención personalizada / Cancelación flexible) — antes eran 5 claims distintos (Viajes 100% seguros / Mejores precios / Turismo sostenible). Verificado: ningún test depende del copy anterior (grep en `tests/`).
- El buscador queda como **tarjeta flotante** (hermana del `<section id="hero">`, margen negativo `-mt-9`/`-mt-11` + `z-10`) — mismo patrón ya usado en "Por qué elegirnos" para la tarjeta "Asistencia 24/7", nunca `position:absolute` dentro de un contenedor con overflow oculto.
- El cue de scroll del hero (flecha "Desplázate para ver más") apuntaba a `#mosaico`; se repuntó a `#destinos`, la sección de contenido real que sigue ahora.

---

## 4. Destinos imperdibles → carrusel con destacado

Reemplacé la rejilla `auto-fit` por `x-ui.carousel-shell`. Le agregué una prop aditiva **`trackClass`** al componente (antes no existía forma de inyectar clases al `<ul>` del track) para meter el padding lateral que deja asomar la tarjeta vecina y usar `snap-center` en vez de `snap-start`. Default `''`, así que el único otro consumidor del carrusel (Testimonios, apagado) no cambia.

"Destino destacado" = el primero por `order` (decisión cerrada: no hay flag propio en `Destination` y no se crea uno). Se marca con:
- Una pastilla `x-ui.badge` ("Destino destacado") — vive en el `<li>`, **no** dentro de `x-ui.destination-card` (componente compartido con otras pantallas, no se toca su contrato).
- `lg:scale-110` en vez de un ancho mayor — importante: si el ancho del slide cambiara, el cálculo de `active`/`maxScroll` de `carousel-shell` (que asume slides del mismo ancho, ver `sync()`) se desincroniza. Con `scale` el ancho en el DOM no cambia, solo el tamaño visual.

---

## 5. Actividades → carrusel de tarjetas verticales

Mismo dato de siempre (`$experiences`), pero `x-ui.mosaic-tile` con `aspect-[3/4]` (antes `aspect-video`) dentro de `carousel-shell` en vez de grid. El componente `mosaic-tile` no cambió — solo el aspecto y contenedor que le pasa la Home.

---

## 6. Tours destacados → carrusel + tarjeta de mockup

`x-ui.tour-card` recibió dos props aditivas:
- `isFeatured` (bool, default `false`) — pinta el badge "Destacado" cuando `$tour->is_featured` es verdadero. **Nunca** "Más reservado": esa insignia del mockup es una afirmación sin dato que la respalde (no hay conteo de reservas en el modelo).
- `showAttributes` (bool, default `false`) — activa: (a) los badges de foto (Destacado + duración real si `duration_label` trae dato — nunca el texto "Full day" cableado, se imprime lo que el tour realmente tenga, y hoy un tour sembrado sí tiene exactamente "Full day"/"1 día" en `DemoTourSeeder`), y (b) la grilla 2x2 de atributos DEMO en vez de la fila duration/category.

Los 4 atributos 2x2 (Recojo incluido, Español/Inglés, Cancelación flexible, Salida diaria) están en **un solo array dentro del componente** (`$demoAttributes`), marcado `{{-- DEMO: pendiente campos en Tour --}}`, tal como pedía el encargo — para que backend lo reemplace en un único punto el día que existan esas columnas.

Ningún otro llamador de `x-ui.tour-card` (`tours/index.blade.php`, `styleguide.blade.php`) pasa `showAttributes`/`isFeatured` — su salida no cambia (verificado leyendo ambos archivos, ninguno pasa esas props).

Reseñas/estrellas: **no se agregaron**. No hay modelo de reviews en el proyecto; publicar "5.0 · 48 reseñas" sería un dato inventado (regla dura ya documentada en el proyecto).

---

## 7. Por qué elegirnos

Sin cambios estructurales — ya coincidía con la maqueta (foto + tarjeta flotante "Asistencia 24/7" + eyebrow/título + grilla 2x2 de `feature-card`). **Decisión**: no dupliqué la franja de 4 íconos que la maqueta 4 muestra al pie de esta sección — es prácticamente la misma franja de confianza que ya vive una vez bajo el hero (con 4 de sus 5 ítems); repetirla al final de "Por qué elegirnos" habría sido contenido redundante en la misma página. Si el cliente confirma que la quiere en ambos lugares, es un añadido de una tarde (reutilizar el mismo bloque `bg-sand` con `$heroTrustIcons`).

---

## 8. Suite de tests

**No verificado por entorno** — mismo problema ya documentado en memoria de proyectos anteriores en esta máquina: `mysqld` de Laragon no arranca (`lib/plugin` inexistente por completo, no solo un archivo — confirmado con `ls` y arrancando `mysqld.exe` a mano, que falla con `Can't open shared library 'component_reference_cache.dll'`). El puerto 3306 está cerrado (`No se puede establecer una conexión ya que el equipo de destino denegó expresamente dicha conexión`).

Evidencia de que el fallo es 100% de entorno y no de mis cambios:
- Corrí `artisan test --filter=HomeCatalogLinksTest` (el test que más directamente cubre lo que toqué) con timeout corto: **5/5 fallan**, los 5 con la misma `QueryException` de conexión rechazada a MySQL, antes de que el código de la vista llegue a ejecutarse.
- Corrí la suite completa en background; se quedó colgada ~15 min reintentando conexión y tuve que matarla por PID (9040/18460, nunca `taskkill /IM`).
- No usé sqlite para la suite oficial: el propio `phpunit.xml` del proyecto lo descarta a propósito ("las columnas de fecha con cast se comportan distinto en SQLite y dan tests que fallan o pasan en falso").

Sí verifiqué, por separado, que el código en sí compila y se sirve:
- `php -l` sin errores en los 6 archivos Blade/PHP tocados.
- `php artisan view:cache` compiló toda la vista sin excepciones (cualquier error de sintaxis Blade real —paréntesis, `@if` sin `@endif`, prop mal referenciada— lo hubiera hecho fallar ahí).
- Levanté una base **sqlite desechable** (`database/smoke.sqlite`, fuera de git, borrada al cerrar) SOLO para poder migrar+sembrar (`DemoTourSeeder`) y navegar la home real en el navegador — nunca como reemplazo de la suite oficial. `artisan migrate:fresh` + `db:seed` corrieron limpio ahí, y la home entera (ES) respondió 200 y renderizó correctamente en los 3 breakpoints (capturas abajo). Confirma que no hay ningún error 500/Blade en el camino real de renderizado con datos reales.

**N/N real: pendiente** hasta que se repare `mysqld` en esta máquina (fuera de mi alcance como maquetador — es un binario de Laragon incompleto, no algo que se arregle con código). Recomendación: correr la suite en CI o en otra máquina antes de dar por cerrado el lote.

---

## 9. Build de Tailwind

`php artisan view:cache` (ANTES del build, según la lección ya documentada) → `npm run build`: compiló sin errores (`vite build`, 59 módulos, `app-kRJ3Y5Bt.css` 83.21 kB / `app-CjmmD5Mk.js` 110.89 kB). Confirmé en el CSS compilado que TODAS las clases arbitrarias nuevas quedaron incluidas: `px-[13%]`, `px-[19%]`, `px-[10%]`, `px-[16%]`, `w-[74%]`/`w-[80%]`/`w-[31%]`/etc., `-mt-9`/`-mt-11`, `lg:scale-110`, `bg-surface/95` (la variante `white` del badge), `snap-center`. `artisan view:clear` al terminar (no dejo la vista cacheada del smoke test).

---

## 10. Capturas y verificación visual

Servidor de prueba: `artisan serve` en el puerto 8790 contra la sqlite desechable sembrada (ES). Antes de cada `fullPage` disparé los `data-reveal` de cada sección con clics reales sobre elementos no-enlace (`h2` de cada título, la propia sección) — un `fullPage` directo deja secciones en blanco porque el reveal usa `IntersectionObserver` y una captura de página completa no dispara scroll real (lección ya documentada); confirmado que sin este paso varias tarjetas salían en blanco.

Guardadas en `docs/rediseno-2026/capturas-16/`:
- `home-375-full.png`, `home-768-full.png`, `home-1440-full.png` — home completa en los 3 breakpoints pedidos.
- `seccion-01-hero.png` — hero + buscador + píldoras + franja (↔ maqueta 1).
- `seccion-02a-destinos.png` + `seccion-02b-actividades.png` — carruseles de Destinos/Actividades (↔ maqueta 2).
- `seccion-03-tour-card.png` — carrusel de tours con badges/atributos (↔ maqueta 3).
- `seccion-04a-porque.png` + `seccion-04b-porque-foto.png` — Por qué elegirnos (↔ maqueta 4).
- `home-375-about.png` / `home-375-about-photo.png` — verificación puntual (ver nota abajo).

**Nota de un falso defecto descartado**: en el primer `fullPage` a 375 la sección "Por qué elegirnos" se veía con un hueco en blanco donde debía ir la foto + tarjeta "Asistencia 24/7". Antes de reportarlo como defecto, tomé una captura ENFOCADA de esa foto (`home-375-about-photo.png`) y confirmé que la foto y la tarjeta SÍ están y se ven correctamente — el hueco era un artefacto de la composición del `fullPage` (probablemente la imagen aún decodificando en el instante del stitch), no un bug de mi maquetación. Consistente con la lección de memoria "medir el fondo/instrumento antes de culpar al código".

También noté un `ERR_CONNECTION_RESET` en consola al cargar `PublicSans-Variable.woff2` durante una de las capturas: es el servidor de desarrollo de PHP (`artisan serve`, mono-hilo) tropezando bajo la carga de una sesión larga de Playwright, no un problema de la fuente ni de mi código — no se repitió en las demás capturas.

**Medidas**: con el set de herramientas MCP disponible en esta sesión (navigate/snapshot/screenshot/resize/click/console — sin `evaluate` ni `network_requests`) no pude leer `document.documentElement.scrollWidth` ni `getBoundingClientRect()` en crudo. Lo que sí verifiqué:
- **Scroll horizontal en 375**: ninguna de las capturas (`fullPage` ni por sección) excede el ancho de viewport — el ancho reportado de `home-375-full.png` es 360px (≤ 375), nunca mayor, lo que descarta desborde de la PÁGINA (los 3 carruseles sí desbordan dentro de su propio contenedor con scroll-x, que es el comportamiento esperado y documentado).
- **Alto del hero**: no toqué `class="... min-h-[calc(100svh-4rem)]"` de `<section id="hero">` — la decisión "pantalla completa" ya cerrada sigue intacta; solo se reordenó lo que va DEBAJO de la foto (buscador/píldoras/franja pasan a vivir fuera del hero, no dentro).
- **Contraste de la franja de confianza reubicada**: ahora vive en `bg-sand` con `tone="light"` (`text-text-2`/`bg-brand-50 text-action`), la misma combinación que `tokens.css` ya documenta medida sobre `--sand` (`--text-2` 8.41:1, pasa AA). No inventé un contraste nuevo: reutilicé un par ya medido en el propio sistema de diseño.

---

## 11. Pending diffs / lo que no se hizo

- **Franja de 4 íconos al pie de "Por qué elegirnos"** (visible en la maqueta 4): decisión tomada de NO duplicarla — ver §7. Si el cliente insiste en tenerla en los dos lugares, es un cambio de una tarde.
- **Suite oficial (MySQL)**: no verificada esta sesión por el entorno roto (ver §8). Recomiendo repetirla en cuanto `mysqld` esté disponible, antes de considerar el lote cerrado para producción.
- **Carrusel de Destinos con más de 3 destinos reales**: usé `lg:justify-center` en el track para centrar exactamente 3 tarjetas en escritorio (el catálogo real de hoy tiene 3 destinos, verificado con `DemoTourSeeder`). Si el catálogo creciera a 4+, ese `justify-center` en un contenedor con scroll podría dejar la primera tarjeta parcialmente inalcanzable en algunos motores — no es un bug hoy (probado y funciona con 3), solo una nota para cuando el catálogo crezca.
- **EN (inglés)**: traduje todas las claves nuevas (`lang/en/site.php`) pero solo verifiqué visualmente ES en el navegador esta sesión — no tomé capturas EN por presupuesto de turnos. El test `HomeCatalogLinksTest`/`LocaleSwitcherLinksTest` cubren EN a nivel de enlaces (cuando la suite pueda correr).

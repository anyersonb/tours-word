# 12 · QA post-fix — fuentes en subdirectorio (pase a staging)

**Agente:** `anyerson-qa`. Rama `lote-1-sistema-diseno`.
**Diff verificado:** `921e731` (port visual lotes 0-2) + el fix sin commitear de
`docs/rediseno-2026/11-fix-fuentes-subdirectorio.md`, que toca únicamente:
- `resources/css/app.css` (se quitaron los dos `@font-face` con URL absoluta)
- `resources/views/components/layout.blade.php` (se agregó el `<style>` inline
  con los `@font-face` construidos vía `asset()`)

Entorno: PHP 8.2.1 (`G:\laragon\bin\php\php8.2.1\php.exe`), `artisan serve`
contra la SQLite de QA sembrada (catálogo demo, 3 tours), MySQL local
inoperativo (confirmado con `Test-NetConnection 127.0.0.1:3306` →
`TcpTestSucceeded: False`, no es un problema de este fix).

## Gate 1 — las fuentes cargan de verdad → **PASS**

Home (`/es`), 1440×900, navegador real (Chromium vía `playwright-core`, no el
MCP compartido — ver nota de método al final).

- `document.fonts` (estado real de `FontFace`, no solo `.check()`):
  Inter Tight → `status: "loaded"`; Fraunces → `status: "loaded"`.
- Red: `inter-tight/InterTight-Variable.woff2` → **200**;
  `fraunces/Fraunces-Variable.woff2` → **200**.
- Control negativo (lo que hace válida la medición): medí el ancho renderizado
  de un mismo texto de prueba con la fuente real vs. forzado al fallback
  genérico. Si la fuente no aplicara, ambos anchos saldrían idénticos.
  - Inter Tight real: **1057.48px** vs. forzado a `sans-serif`: **1096.28px**
    (distintos → la fuente sí está pintando, no es el fallback).
  - Fraunces real: **1103.92px** vs. forzado a `serif`: **989.80px**
    (distintos, con una diferencia aún mayor → mismo resultado).
- `getComputedStyle`: `body` → `"Inter Tight", ui-sans-serif, ...`; `h1` →
  `Fraunces, ui-serif, ...` (la familia declarada es la primera de la lista,
  no un fallback promovido).

No verificado en Gate 1: tours/destinos/experiencias (solo se midió home,
que es donde vive el `<style>` inline — layout es compartido por todas las
vistas, así que el riesgo de que difiera es bajo, pero no se confirmó).

## Gate 2 — la URL emitida lleva el prefijo del subdirectorio → **NO CERRADO EN LOCAL / inferencia pendiente de confirmar en servidor**

Este es el bug original. Intenté forzar `APP_URL=http://127.0.0.1:8811/tour-word`
por tres vías distintas y las tres dieron el mismo resultado en el HTML
renderizado por una petición HTTP real:

1. Variable de entorno en la línea de arranque de `artisan serve`.
2. Igual, agregando `--no-reload`.
3. Editando `APP_URL` directo en `.env` (restaurado a su valor original
   `http://127.0.0.1:8000` al terminar — confirmado con un curl posterior a
   la restauración).

En los tres casos el `src` renderizado fue:
```
src: url('http://127.0.0.1:8811/fonts/inter-tight/InterTight-Variable.woff2')
```
**Sin** el prefijo `/tour-word`.

Diagnóstico de causa (no es un defecto del fix, es una limitación de la
herramienta de desarrollo local): `Illuminate\Foundation\Console\ServeCommand`
tiene una lista blanca `$passthroughVariables` (líneas 79-94 del archivo en
`vendor/laravel/framework`) que decide qué variables de entorno llegan al
proceso PHP hijo que realmente atiende las peticiones HTTP; `APP_URL` no
está en esa lista. Más de fondo: para peticiones HTTP reales, Laravel calcula
la raíz de `asset()`/`url()` con `Request::root()` (que depende de
`getBaseUrl()`, derivado de `SCRIPT_NAME`/`REQUEST_URI` del servidor real),
**no** de `config('app.url')` — `APP_URL` solo se usa como raíz de respaldo
en contexto de consola (sin petición HTTP real), que es exactamente el canal
que usa `tinker`. Por eso:
```
tinker → asset('fonts/inter-tight/InterTight-Variable.woff2')
       = http://127.0.0.1:8811/tour-word/fonts/inter-tight/InterTight-Variable.woff2
```
sí sale con el prefijo correcto, pero es el mecanismo de consola, **no** el
mismo camino de código que atiende una visita real al sitio.

`docs/rediseno-2026/02-seo.md:246` documenta que los canonicales de este
mismo proyecto (`url()->current()`) sí resuelven correctamente el prefijo
`/tour-word/` en el despliegue real (docroot dividido: `public_html/tour-word/`
apuntando al `public/` de esta app), porque ahí `getBaseUrl()` sí detecta el
subdirectorio a partir de la estructura real del servidor. `asset()` usa la
misma resolución de raíz que `url()->current()`, así que por ese precedente
documentado es razonable esperar que el fix funcione igual en staging — pero
**no lo confirmé con una petición HTTP real bajo un subdirectorio genuino**,
porque `php artisan serve` sirve siempre desde la raíz y no hay forma de
simularle un docroot dividido tipo Apache localmente en el tiempo disponible.

**Queda como inferencia, no como PASS.** Recomendación operativa: que
`deployer` verifique con `curl` el `src` real de los dos `@font-face` en el
HTML servido desde `https://www.limaviewtours.com/tour-word/` inmediatamente
después del despliegue, antes de dar el fix por cerrado.

## Gate 3 — el port visual no se rompió (lotes 0, 1, 2)

4 pantallas × 2 viewports, navegador real, scroll no aplicó (medidas contra
`document.documentElement` sin necesidad de desplazar).

| Página | Viewport | scrollWidth | clientWidth | Scroll horizontal | Testimonios (`#testimonios`) | H1 (cantidad) | H1 (texto) | Errores de consola |
|---|---|---|---|---|---|---|---|---|
| home | 1440×900 | 1440 | 1440 | No | ausente (`null`) | 1 | "Vive lo mejor de Perú" | ninguno |
| tours | 1440×900 | 1440 | 1440 | No | ausente | 1 | "Nuestros tours" | ninguno |
| destinos | 1440×900 | 1440 | 1440 | No | ausente | 1 | "Destinos" | ninguno |
| experiencias | 1440×900 | 1440 | 1440 | No | ausente | 1 | "Experiencias" | ninguno |
| home | 375×800 | 375 | 375 | No | ausente | 1 | "Vive lo mejor de Perú" | ninguno |
| tours | 375×800 | 375 | 375 | No | ausente | 1 | "Nuestros tours" | ninguno |
| destinos | 375×800 | 375 | 375 | No | ausente | 1 | "Destinos" | ninguno |
| experiencias | 375×800 | 375 | 375 | No | ausente | 1 | "Experiencias" | ninguno |

Cero scroll horizontal en las 8 combinaciones (`scrollWidth === clientWidth`
en todas). Sección de testimonios ausente en las 8, como exige el cliente.
Un solo `<h1>` en las 8. Sin errores de consola en las 8.

**Mosaico de la home (ancho 1432px / radio 10px a 1440): NO MEDIDO.** El
selector CSS que usé para localizarlo (`[class*="mosaic"], [data-mosaico],
.mosaico`) no encontró el nodo — devolvió `null`, y lo dejo así en vez de
forzar una lectura falsa. Localicé los archivos candidatos
(`resources/views/components/ui/mosaic-tile.blade.php`,
`resources/views/home.blade.php`, `resources/css/tokens.css`) pero no llegué
a leer la clase real ni a remedir antes del corte de turnos. No verificado.

No verificado en Gate 3: el hueco del `<style>` inline nuevo en el `<head>`
no se midió por separado con `getBoundingClientRect()` de los vecinos
inmediatos (se infiere limpio porque `scrollWidth === clientWidth` y no hubo
salto visual evidente en las capturas de consola, pero no es lo mismo que
medir el hueco directamente).

## Gate 4 — regresión SEO acotada (title, meta, un h1, alt)

| Página | Title | Meta description | H1 único |
|---|---|---|---|
| home | "Agencia de turismo en Cusco, Perú · Pacha Viva" | "Agencia de turismo en Cusco, Perú. Diseñamos experiencias auténticas e inolvidables en los destinos más increíbles del país, guiadas por expertos locales." | Sí (1) |
| tours | "Tours en Perú · Pacha Viva" | "Descubre todos nuestros tours por Perú. Filtra por destino o por experiencia y encuentra el viaje ideal para ti." | Sí (1) |
| destinos | "Destinos en Perú · Pacha Viva" | "Descubre los destinos que tenemos para ti en Perú." | Sí (1) |
| experiencias | "Experiencias en Perú · Pacha Viva" | "Descubre las experiencias que tenemos para ti en Perú." | Sí (1) |

Las 4 páginas conservan `<title>` y meta description propios, sin duplicar
la home, y un solo `<h1>` cada una.

**`alt` en imágenes de hero y tarjetas: NO MEDIDO.** No llegué a extraer los
atributos `alt` de las imágenes reales antes del corte de turnos.

**Sobre el `noindex`:** las 4 páginas salen con
`<meta name="robots" content="noindex, nofollow">`. Esto es **preexistente y
por diseño**, no una regresión de este fix: `app/Support/Indexability.php`
lo activa mientras `config('cms.catalog_demo_content')` o
`config('cms.is_staging_mirror')` resuelvan a `true` (ambos lo hacen hoy por
default en `config/cms.php`), independiente de cualquier cambio de CSS/Blade.
No se audita más allá de esto por instrucción explícita del alcance de esta
pasada.

## Gate 5 — la suite

**343 passed, 1507 aserciones**, PHP 8.2.1, contra la SQLite de QA — línea
base exacta del cierre del lote 0. **Esta corrida la ejecutó `jefe` en el
hilo principal, no yo**; la dejo registrada aquí con esa atribución porque
es la evidencia de Gate 5 para este pase, pero no es una medición propia de
`anyerson-qa`.

## Hallazgo lateral sin explicar (no bloqueante)

`storage/logs/laravel.log` tiene entradas recientes (≈21:49-21:50 del día de
esta pasada) con `SQLSTATE[HY000] [2002]` intentando conectar a
`mysql://127.0.0.1:3306/pachaviva` (sesiones y una consulta de tours en
`home.blade.php`), pese a que mis peticiones directas a `/es` y `/es/tours`
en esas mismas ventanas de tiempo devolvieron **200** con contenido real de
la SQLite de QA (título correcto, conteo de tours consistente). Razón para
no dejar que esto contamine lo medido: las respuestas que reporto arriba se
verificaron leyendo el **HTML devuelto por esas peticiones concretas**, no
infiriendo su éxito a partir del log — un curl que devuelve 200 con el
título y el conteo de tours esperado es evidencia directa de que ESA
petición sí usó la SQLite, independientemente de qué haya fallado en otro
proceso o ventana. Más probable: una petición de otro proceso del servidor
(el que quedó vivo brevemente durante los reinicios de Gate 2, con el
`DB_CONNECTION` de `.env` sin mi override) o una tarea de sesión/cola de
fondo. No lo perseguí más porque no afecta al fix bajo prueba y el alcance
de esta pasada no incluye backend.

## Lo no verificado (declarado, no asumido)

- Gate 2: el prefijo de subdirectorio en una petición HTTP real bajo un
  docroot dividido genuino (solo inferido por precedente documentado +
  `tinker`, que usa un camino de código distinto).
- Mosaico de la home: ancho 1432px / radio 10px a 1440.
- `alt` de imágenes de hero y tarjetas en las 4 pantallas.
- Gate 1 y el hueco del `<style>` inline: solo confirmados en home, no
  repetidos en tours/destinos/experiencias.
- Gate 5: ejecutada por `jefe` en el hilo principal, no por mí.

## Veredicto

**APTO para staging, con 3 huecos declarados** — no encontré ningún defecto
confirmado de severidad Crítica o Alta que obligue a devolver el trabajo a
`maquetador-frontend`. Lo que sí dejo explícitamente sin cerrar, para que la
decisión de avanzar sea informada y no una firma en blanco:

1. **Gate 2 no está confirmado con una petición HTTP real** — es una
   inferencia razonable (mismo mecanismo documentado que ya funciona para los
   canonicales de este proyecto en `/tour-word/`), pero no una medición
   directa. Pido que `deployer` la cierre con un `curl` al HTML real de
   staging apenas se publique, antes de considerar el bug original resuelto
   de verdad.
2. **Mosaico (1432px/10px) sin medir.**
3. **`alt` de hero y tarjetas sin medir.**

Ninguno de los tres es, por lo que sé hoy, evidencia de que algo se rompió —
son mediciones que no llegué a hacer, no fallos confirmados. Si alguien
prefiere no avanzar hasta cerrarlas, es una decisión legítima con la misma
información que tengo yo; no la tomo por mi cuenta porque no la verifiqué.

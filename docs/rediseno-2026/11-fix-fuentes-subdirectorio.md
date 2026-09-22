# Fix: fuentes autoalojadas rotas en despliegue por subdirectorio

Fecha: 2026-09-21. Rama `lote-1-sistema-diseno`, HEAD de partida `921e731`.

## Defecto

`resources/css/app.css` declaraba los dos `@font-face` (Inter Tight, Fraunces)
con `src: url('/fonts/...')` absoluto. Vite no reescribe esa URL (avisa
*"didn't resolve at build time, it will remain unchanged to be resolved at
runtime"*) y queda literal en el CSS compilado. En local resuelve porque el
proyecto sirve en la raíz; en staging (`limaviewtours.com/tour-word/`) esa
barra pelada apunta a la raíz de OTRO sitio (Lima View Tours) → 404 en las
dos fuentes → fallback a fuente de sistema. Ese es justo el síntoma "plano,
muy wordpress" que el cliente ya rechazó dos veces.

## Opción elegida: (A) — `@font-face` inline en `<head>`

Se sacaron los dos `@font-face` de `resources/css/app.css` (que pasa por
Vite) y se movieron a un `<style>` inline dentro de
`resources/views/components/layout.blade.php`, construido con `asset()` —
el único punto que conoce el prefijo real de despliegue (`APP_URL`/
`ASSET_URL`).

Razón sobre la opción (B) (`public/fonts/fonts.css` + `<link>`): son 112 KB
de fuente (44.872 + 67.304 bytes) que ya compiten por RTT con el CSS de
Vite; un archivo CSS externo adicional habría sumado una petición y una
vuelta de red más antes de poder empezar a descargar los `.woff2`. Inline
evita ese salto extra. El costo (CSS embebido en el HTML) es aceptable
porque son solo 2 reglas cortas, no una hoja de estilos completa.

Se conservó tal cual: `font-family` ('Inter Tight' / 'Fraunces'), los rangos
`font-weight` (100 900 y 300 700), `font-style: normal`, `font-display:
swap` y los dos `format()` en el mismo orden. El `@theme` de Tailwind que
mapea esas familias no se tocó. Los `<link rel="preload">` ya usaban
`asset()` desde antes — no se tocaron.

## Archivos tocados

- `resources/css/app.css` — se eliminaron los dos bloques `@font-face` con
  URL absoluta; se dejó un comentario explicando por qué se movieron y
  dónde quedaron.
- `resources/views/components/layout.blade.php` — se agregó un `<style>`
  inline con los dos `@font-face`, con `src` construido vía `asset()`.

No se tocó backend, `config/`, modelos ni migraciones. No se commiteó nada.

## Barrido de otras rutas absolutas a assets propios

Búsqueda en `resources/views/**/*.blade.php` y `resources/css/*.css` de
`url('/...')`, `src="/..."`, `href="/..."`, `content: url(/...)` escritos a
mano (excluyendo externos http/https, `asset()`, `route()`, `@vite` y hrefs
de navegación):

```
grep -rnE '(src|href)="\s*/[^/]' resources/views --include=*.blade.php   → sin resultados (fuera de los que usan {{ }})
grep -rnE 'background(-image)?\s*:\s*url\(' resources/views resources/css → sin resultados
grep -rnE "url\(['\"]?/[a-zA-Z]" resources/views                          → 1 resultado, es dinámico (ver abajo)
grep -rn "url\(['\"]\?/" resources/css/*.css resources/views              → 3 resultados totales
```

Resultado: **no hay más rutas hardcodeadas con el mismo problema.** Los dos
únicos hallazgos adicionales del grep bruto no cuentan:

- `resources/views/components/ui/picture.blade.php:57` — usa el *helper*
  `url('/')` de Laravel (raíz dinámica de la app, no un string literal), para
  recortar un path absoluto y quedarse con la ruta relativa en disco.
- `resources/views/emails/contact-message.blade.php:21` — usa el *helper*
  `url('/admin/...')`, que Laravel resuelve contra `APP_URL` en tiempo de
  ejecución, no un string cableado.

Ambos son dinámicos y correctos bajo cualquier subdirectorio; no se tocaron.

## Verificación (los 4 puntos obligatorios)

**1. Orden `view:cache` → `npm run build`.**
Ejecutado en ese orden con `G:\laragon\bin\php\php8.2.1\php.exe artisan
view:clear` + `view:cache`, y después `npm run build`. Build limpio, 59
módulos, sin el aviso de "didn't resolve at build time" que aparecía antes.
Salida: `public/build/assets/app-Dhhn50Hk.css` (82.19 kB) y
`app-CjmmD5Mk.js` (110.89 kB).

**2. El CSS nuevo ya no contiene `url(/fonts/`.**
```
grep -c "url(/fonts" public/build/assets/app-Dhhn50Hk.css   → 0 (grep exit 1, sin coincidencias)
grep -o "@font-face" public/build/assets/app-Dhhn50Hk.css | wc -l → 0
ls public/build/assets/ | grep DGPLp8Jb                       → 0 (el CSS viejo con el bug ya no existe en el build)
```

**3 y 4. Navegador real + subdirectorio simulado (combinadas).**
MySQL local está caído por un problema de entorno ajeno a este fix
(`mysqld` no arranca: `Can't open shared library
'...\lib\plugin\component_reference_cache.dll'` — la carpeta
`lib/plugin` no existe en la instalación de Laragon en este equipo, algo
previo y no relacionado con el CSS/Blade). Sin DB no renderiza ninguna
página completa (header/footer consultan CMS), así que no pude abrir la
home real con `php artisan serve`. Reporto esto explícitamente: **no
verificado con la home real**, y en su lugar armé la verificación más
fuerte posible sin tocar backend ni DB:

- Con `APP_URL=http://127.0.0.1:8899/tour-word` (subdirectorio simulado) vía
  `artisan tinker`, `asset('fonts/inter-tight/InterTight-Variable.woff2')`
  devuelve `http://127.0.0.1:8899/tour-word/fonts/inter-tight/InterTight-Variable.woff2`
  — confirma que `asset()` antepone el prefijo real y no una barra pelada
  (el mismo mecanismo que usa el `<style>` nuevo en `layout.blade.php`).
- Armé un docroot estático en el scratchpad que reproduce la ruta de
  staging: `subdir-sim/tour-word/fonts/inter-tight/InterTight-Variable.woff2`
  y `.../fraunces/Fraunces-Variable.woff2` (copias exactas de
  `public/fonts/...`, mismo tamaño en bytes: 44.872 y 67.304), servidos con
  `php -S 127.0.0.1:8899 -t subdir-sim`. `curl` confirma `200` en los tres
  recursos bajo `/tour-word/...` (test.html y las dos fuentes) y `404` en un
  nombre de archivo inexistente bajo el mismo prefijo (control de que la
  ruta discrimina).
- En Playwright real (no captura), cargué
  `http://127.0.0.1:8899/tour-word/test.html`, que declara los mismos dos
  `@font-face` (mismo `font-family`, rangos de peso, `font-display: swap`,
  `format()`) apuntando a esas URLs con el prefijo `/tour-word/`, más un
  tercer `@font-face` de control con URL rota a propósito bajo el mismo
  prefijo. Medí con `FontFace.load()` + `Promise.allSettled`, no con
  `getComputedStyle` a secas (ese solo devuelve el valor declarado en CSS,
  no si la fuente realmente cargó). Resultado real:
  ```
  interStatus: "loaded"      frauncesStatus: "loaded"
  interSettled: "fulfilled"  frauncesSettled: "fulfilled"
  rotaControlStatus: "error" rotaSettled: "rejected"
  bodyFont: "\"Inter Tight\", sans-serif"
  h1Font: "Fraunces, serif"
  ```
  El control negativo (`RotaControl`, URL rota bajo el mismo `/tour-word/`)
  terminó en `error`/`rejected`, confirmando que el instrumento SÍ puede
  fallar y que "loaded" en los otros dos no es un artefacto de la medición.

Conclusión de 3-4: las dos fuentes reales, servidas bajo el prefijo de
subdirectorio exacto que usará `asset()` en staging, cargan y se aplican en
un navegador real. Pendiente (no bloqueante para este fix, es un problema de
entorno local): reparar MySQL de Laragon en esta máquina para poder repetir
la prueba contra la home real cuando haga falta.

## Limpieza

Se detuvieron por PID exacto los procesos temporales usados para la
verificación (dos `mysqld.exe` que no llegaron a bindear el puerto, y el
`php -S` del docroot simulado). No quedó ningún proceso ni puerto abierto
por este trabajo. No se modificó ninguna configuración de MySQL ni de
Laragon.

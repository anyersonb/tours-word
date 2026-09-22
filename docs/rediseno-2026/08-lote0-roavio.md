# 08 · Lote 0 — port fiel de Roavio (home: tipografía, tokens, header/footer, hero, mosaico)

**Agente:** `maquetador-frontend` · **Fecha:** 2026-09-20 · **Rama:** `lote-1-sistema-diseno`
**Estado del árbol:** sin commit. Todo lo de abajo vive en el working tree de
`G:\laragon\www\tours-word` — decide Anyerson si se commitea.
**Alcance:** `resources/views/home.blade.php` + layout compartido (fuentes, tokens,
header, footer). **No se tocó** `listados`, `fichas`, `Nosotros` ni `Contacto`, salvo el
efecto inevitable y esperado de heredar la fuente global (ver §1).

---

## 0. Corrección en caliente del cliente (léase primero)

A mitad de esta sesión, con el hero ya boxeado a 1392px con radio (el port fiel del dato
de Roavio), Anyerson pidió textualmente: **"que el `id=\"hero\"` sea full width fullhero
apaisado"**. Eso **anula** el punto "hero boxed a 1392 con radio" de la sección 4. El
hero volvió a ser a sangre completa.

"Fullhero apaisado" es ambiguo y este estudio ya perdió dos rondas por resolver una
ambigüedad a ojo, así que **no se eligió una interpretación**: se construyeron las dos,
ambas en código real (no maquetas sueltas), intercambiables con **una sola línea**
(`$heroVariant` al inicio de `home.blade.php`). El detalle completo, con las medidas
reales, está en la §4. **Decide Anyerson mirando las capturas.**

---

## 1. Tipografía — Inter Tight + Fraunces, autoalojadas

- **Inter Tight** (sans/cuerpo, reemplaza a Figtree) y **Fraunces** (display, ya estaba
  en el proyecto) son SIL OFL 1.1. Las dos se descargaron UNA vez desde Google Fonts
  (variable, subset `latin`, cubre acentos y ñ) y se sirven desde disco:
  `public/fonts/inter-tight/InterTight-Variable.woff2` (44,9 KB) y
  `public/fonts/fraunces/Fraunces-Variable.woff2` (67,3 KB). **Nunca** desde
  `fonts.googleapis.com` — verificado: el único `<link>` de Google Fonts que queda en
  `layout.blade.php` sirve **solo Caveat** (script, uso puntual en Nosotros, fuera de
  este lote).
- Precargadas con `<link rel="preload" as="font" crossorigin>` en `layout.blade.php`.
- **Este proyecto es Tailwind 4 CSS-first: no hay `tailwind.config.js`** (se eliminó en
  el lote 1, ver comentario en `app.css`). La fuente global sale de `@theme` en
  `resources/css/app.css` (`--font-sans`, `--font-display`), que es el equivalente exacto
  de "declararlo en el config" en este proyecto — `body`/`h1..h6` se resuelven desde ahí
  (`@layer base` en `app.css`), no desde una vista suelta. Decisión documentada en el
  propio `app.css`.
- **Verificado en el navegador** (no solo compilado): `getComputedStyle(document.body).
  fontFamily` → `"Inter Tight", ui-sans-serif...`; `getComputedStyle(h1).fontFamily` →
  `Fraunces, ui-serif...`.
- Efecto colateral esperado y aceptado: como el token es global, **toda página hereda la
  fuente nueva** (probado en `/es/tours`, sin errores de consola, sin layout roto — ver
  `pv-tours-index-1440.png`). Es justo lo que pide la regla del proyecto ("body/h1..h6 se
  resuelven desde el config, no del SCSS").

## 2. Tokens — anchos Roavio + radio 10px (extendidos, nada roto)

Añadido en `resources/css/tokens.css`, capa nueva, **sin tocar ni renombrar** ningún
token existente:

```css
--container-narrow: 80.625rem; /* 1290px */
--container-boxed:  87rem;     /* 1392px */
--container-bleed:  89.5rem;   /* 1432px */
--r-tile: 0.625rem;            /* 10px */
```

Mapeados en `@theme` de `app.css` → `--radius-tile` (utilidad `rounded-tile`). Dos
utilidades nuevas en `@layer components` (mismo patrón que `.shell`/`.shell-prose`):
`.shell-boxed` (1392, hoy sin uso tras la corrección de §0 — ver "Pendiente") y
`.shell-bleed` (1432, en uso por el mosaico).

**`--container-narrow` (1290px) queda declarado pero sin consumir** en este lote —
ninguna pantalla del alcance lo necesitaba. Es deuda cero (es un token, no código
muerto), disponible para el resto del sistema.

## 3. Header y footer — lenguaje Roavio

- **Topbar** nueva, franja delgada oscura sobre el header blanco (oculta desde `lg`
  hacia abajo, igual que Roavio): tagline + teléfono/correo/redes. **Mismos `Setting`**
  que ya lee `contact.blade.php`/`footer.blade.php` (`contact_phone`, `contact_email`,
  `social_instagram_url`, `social_facebook_url`) — cero dato nuevo, cero cifra inventada.
  Se oculta ENTERA si no hay ningún dato (hoy, sin seed, así está — ver §5, probado en
  los dos sentidos).
- **Footer**: fila de redes bajo la descripción (mismos `Setting`, mismo criterio de
  ocultar por ítem) y **wordmark gigante** al cierre — el mismo SVG de marca
  (`x-brand.mark variant="mono"`, inline, reacciona a `--brand-h` en vivo) a `w-[88%]
  max-w-5xl`, ancho relativo (no alto fijo) para que escale con su propio `viewBox` sin
  desbordar ni recortarse a los lados. Ver `pv-footer-1440.png` / `pv-footer-375.png`.

## 4. Hero — full-bleed, dos variantes (sin elegir)

Estructura común a ambas variantes: `<section id="hero">` full-bleed real (`w-full`,
hijo directo de `<main>`, sin `100vw` literal — ese patrón sí puede desbordar con
scrollbar vertical). El slider de 3 fotos, el scrim, los botones, la franja de confianza
y el cue de scroll **no se tocaron**: es exactamente el mismo marcado/JS del pase
cinematográfico anterior, solo cambia el contenedor que lo envuelve.

```php
$heroVariant = 'v2'; // cambiar a 'v1' intercambia el hero completo
```

| | V1 — banda cinematográfica | V2 — pantalla completa |
|---|---|---|
| Lectura de | "apaisado" | "fullhero" |
| CSS | `aspect-[12/5]` (2,40:1), `min-h-0` | `min-h-[calc(100svh-4rem)]` |
| Alto medido @1440 | **600px** (ratio 2,400 exacto) | **836px** (ratio 1,722) |
| Alto medido @375 | **156px** (ratio 2,400 exacto) | **836px**¹ (ratio 0,449) |
| `scrollWidth` vs `innerWidth` | igual en ambas, en ambos viewports → **sin scroll-x** | igual |
| Captura 1440 | `hero-v1-1440.png` | `hero-v2-1440.png` |
| Captura 375 | `hero-v1-375.png` | `hero-v2-375.png` |

¹ prueba con la misma altura de viewport (900px) en ambas columnas para aislar el ancho;
en un móvil real (`svh` ≈ 812px) V2 da ≈748px, no 836 — el punto que importa (ratio
0,449, retrato) no cambia.

### El hallazgo que no estaba en el dato de partida

Se midieron las **3 fotos reales** del slider (nunca el 2,80:1 de referencia, que es de
`destino-valle-sagrado.jpg`, un archivo *distinto* usado en Destinos, no en el hero):

| Foto | AR nativo |
|---|---|
| `hero-machupicchu-amanecer.jpg` | **1,478** |
| `hero-valle-sagrado-panoramica.jpg` | **2,391** |
| `hero-cordillera-rio.jpg` | **1,692** |

Recorte real (`1 − min(nativo,objetivo)/max(nativo,objetivo)`), por foto y variante:

| | V1 (2,40:1) | V2 @1440 (1,722:1, medido) | V2 @375 (0,449:1, medido) |
|---|---|---|---|
| Machu Picchu | 38,4 % | 14,2 % | 69,6 % |
| Valle Sagrado | **0,4 %** | 28,0 % | 81,2 % |
| Cordillera-río | 29,5 % | 1,7 % | 73,5 % |
| **Promedio** | **22,8 %** | **14,6 %** | **74,8 %** |

(La cifra "~14 % / ~43 %" del encargo asumía el 2,80:1 de la foto de Destinos y un
1440×900 exacto; con las 3 fotos reales del hero y el alto real medido —836px, no
900— el número correcto es el de esta tabla. Se deja escrito para que quede claro que
no se ignoró el dato, se verificó y corrigió contra el archivo real.)

### Defecto real encontrado en V1, no resuelto a propósito

**A 375px, V1 pura (`aspect-[12/5]` sin piso de alto) recorta los CTA**: el subtítulo se
corta a media frase y los dos botones + la franja de confianza quedan **fuera de la caja
del hero** (se ven "flotando" contra el mosaico que sigue, ver `hero-v1-375.png`). Es la
consecuencia literal de forzar 2,40:1 en 375px de ancho (156px de alto no alcanza para
H1 + subtítulo + CTAs). **No se le puso un piso de alto mínimo en móvil** porque eso ya
es resolver la ambigüedad por mi cuenta — lo que hace falta decidir, si se elige V1, es
justamente cuánto piso ponerle debajo de qué breakpoint. Documentado, no arreglado.

### Default dejado en el repo mientras se decide

`$heroVariant = 'v2'` — es el de menor riesgo: no tiene el defecto de V1 en móvil, y es
el mismo alto (`100svh` menos header) que tenía el hero **antes** de este lote. Cambiar
a `'v1'` es una sola línea, sin tocar nada más.

## 5. El mosaico de 6 tiles — la pieza central del encargo

Sin cambios respecto al primer pase de esta sesión (la corrección del cliente fue solo
sobre el hero). Sección nueva `id="mosaico"`, directamente bajo el hero, **a sangre
completa** (`.shell-bleed`, 1432px). Contenido: **no son fotos nuevas** — son las mismas
3 `$destinations` + 3 `$experiences` que ya bajan a sus propias secciones más abajo,
solo reordenadas por slot (`$mosaicTiles` en el `@php` de `home.blade.php`). Cero query
nueva, cero contenido inventado.

Geometría: un solo CSS Grid, 4 columnas por **fr literal** (`296fr 556fr 296fr 276fr` —
el valor medido en Roavio, no una fracción redondeada a `grid-cols-12`) con `row-span-2`
en las dos columnas altas, activo solo en `lg:` (en `<1024px` cae a `grid-cols-2`/
`grid-cols-3` parejo, sin `row-span`, cada tile `aspect-[3/4]`). Radio `rounded-tile`
(10px, el token nuevo de §2), scrim reutilizado de `.scrim-card` (el mismo que
`destination-card`, no una opacidad nueva).

**Medido con `getBoundingClientRect()` (no asumido), a 1440×900:**

| | Medido | Objetivo Roavio | Nota |
|---|---|---|---|
| `.shell-bleed` (wrapper) | **1432px**, 4px de margen a cada lado | 1432px | exacto |
| Radio de cada tile | **10px** | 10px | exacto |
| Tile "ancha" (Valle Sagrado) | 542×642 (0,844) | 556×634 (0,877) | el `gap` (12px×3) resta ancho al reparto `fr`; ver nota abajo |
| Tile "angosta" (Cultura) | 289×642 (0,449) | 296×634 (0,467) | ídem |
| Par apilado A (Cusco/Trekking) | 289×315 (0,915) | 296×315 (0,940) | ídem |
| Par apilado B (Arequipa/Gastronomía) | 269×315 (0,854) | 276×315 (0,876) | ídem |

Nota sobre la diferencia: `grid-template-columns` reparte los `fr` **después** de
descontar los `gap` (12px × 3 huecos = 36px sobre 1432px), así que cada columna sale
unos puntos más angosta que el valor bruto — es aritmética de CSS Grid, no un error de
tipeo del `fr`. Los **ratios** quedan a menos de 0,03 del objetivo en las 4 columnas.

A 375px: `grid-template-columns` cae a `171.25px 171.25px` (2 columnas), cada tile
171×228 (exactamente `aspect-[3/4]`, sin desviación). Ver `pv-mosaic-1440-b.png` /
`pv-mosaic-375.png`.

### `object-position`, foto por foto (no "center" a ciegas)

Las 6 fotos se **miraron una por una** (no se asumió el recorte):

| Tile | Foto | `object-position` | Por qué |
|---|---|---|---|
| Ancha | Valle Sagrado (2,80:1 nativo) | `center 72%` | empuja hacia las terrazas, menos cielo |
| Angosta | Cultura (telar) | `68% 45%` | composición diagonal, lee bien en hueco muy estrecho |
| Par A · arriba | Cusco | `center 58%` | mantiene la plaza/catedral en cuadro |
| Par A · abajo | Trekking | `76% 58%` | el grupo de caminantes está a la derecha del encuadre |
| Par B · arriba | Arequipa | `center 68%` | volcán + plaza, sesgado a la plaza |
| Par B · abajo | Gastronomía | `center 48%` | textura sin sujeto único, centrado |

## 6. Gate de regresión

- `HomeCatalogLinksTest` + `LocaleSwitcherLinksTest`: **10/10 OK** (corridos dos veces,
  antes y después de la corrección del hero).
- **Suite completa**: **343/343 OK, 1507 assertions**, corrida con PHP 8.2.1 (el binario
  ya existente en `G:\laragon\bin\php\php8.2.1\php.exe`, documentado en el propio repo
  para el mismo fin — no se "arregló" el PHP del PATH, se usó el binario correcto que ya
  estaba instalado).
- `php artisan view:cache` corrido **antes** de cada `npm run build` (dos veces, una por
  cada pase de esta sesión) — Tailwind lee vistas compiladas en este proyecto.
- Sin errores de consola en home, mosaico, hero (las 2 variantes) ni `/es/tours`.
- Topbar probado en **los dos sentidos**: con `Setting::set()` (Tinker) se ve completa
  (`pv-topbar-with-data.png`), revertido con `Setting::where(...)->delete()` +
  `Cache::flush()` vuelve a ocultarse (`pv-topbar-reverted.png`) — la base quedó limpia,
  verificado con `Setting::count()` = 0 sobre esas 4 claves.

## 7. Lo que quedó con contenido estático / pendiente de modelo

**Nada se quemó como estático.** El mosaico usa datos reales de `Destination`/
`Experience` ya en BD. El único contenido "nuevo" es copy de UI (tagline de topbar,
`aria-label` del mosaico) en `lang/es/site.php` + `lang/en/site.php`, con paridad de
claves en los dos idiomas.

## 8. Lo que NO se hizo

- **No se eligió entre V1 y V2 del hero** — decide Anyerson con las 4 capturas.
- **No se le puso piso de alto móvil a V1** (ver defecto documentado en §4) — depende de
  qué variante se elija.
- **`.shell-boxed` quedó sin consumidor** tras la corrección del hero (existía para el
  hero boxed, anulado en §0). Se deja declarado por si alguna otra pantalla del sistema
  lo necesita — no se borra un token recién creado por una corrección de último momento.
- No se tocó listados, fichas, Nosotros ni Contacto (fuera del efecto esperado de la
  fuente global).
- No se corrió `security-engineer` — cambio puramente visual (CSS/Blade/Setting ya
  existentes), no toca backend/auth/datos/archivos.
- No se desplegó nada. Todo vive en el working tree local.

---

## Archivos tocados

- `resources/css/tokens.css` — tokens Roavio (contenedores + radio), extendidos.
- `resources/css/app.css` — `@font-face` self-hosted, `@theme` (fuente/radio), `.shell-boxed`/`.shell-bleed`.
- `resources/views/components/layout.blade.php` — preload de fuentes, Google Fonts reducido a Caveat.
- `resources/views/components/site/header.blade.php` — topbar.
- `resources/views/components/site/footer.blade.php` — redes + wordmark.
- `resources/views/home.blade.php` — hero full-bleed (2 variantes) + mosaico de 6 tiles.
- `resources/views/components/ui/mosaic-tile.blade.php` — **nuevo**, tile del mosaico.
- `lang/es/site.php`, `lang/en/site.php` — claves nuevas (topbar, mosaico), paridad ES/EN.
- `public/fonts/inter-tight/InterTight-Variable.woff2`, `public/fonts/fraunces/Fraunces-Variable.woff2` — **nuevos**.

## Capturas

Todas en el scratchpad de la sesión (no se commitean):

- `hero-v1-1440.png`, `hero-v1-375.png` — hero V1 (banda cinematográfica).
- `hero-v2-1440.png`, `hero-v2-375.png` — hero V2 (pantalla completa), default actual.
- `pv-mosaic-1440-b.png`, `pv-mosaic-375.png` — mosaico de 6 tiles.
- `pv-footer-1440.png`, `pv-footer-375.png` — footer con wordmark.
- `pv-topbar-with-data.png` / `pv-topbar-reverted.png` — topbar con y sin datos.
- `pv-tours-index-1440.png` — sanity de que el cambio global de fuente no rompió otra pantalla.

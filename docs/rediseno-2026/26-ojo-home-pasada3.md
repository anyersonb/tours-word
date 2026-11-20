# 26 · Ojo — Home vs PDF, pasada 3 (2026-11-20)

**Referencia:** `Inka-Palace-1-Home (4).pdf` (JPEG 2880×18142), reducido a 1440×9071 (escala exacta 0.5).
**Render:** http://127.0.0.1:8000/es, Playwright con Chrome headless, cookie `cookie_consent=all`, 1440×900 DPR 1, página completa 1440×9065 (+ hero a 1920).
**Capturas propias:** `docs/rediseno-2026/capturas-26/` → abre `index.html`. Por bloque hay `cmp-<bloque>-1440.png` (PDF izq | render der, 1:1), `cmp-<bloque>-figma|render-1440.png`, `ovl-<bloque>-1440.png` (superposición 50/50) y `zoom-*.png` (arriba PDF, abajo render).

## Veredicto

**Casi igual al PDF, pero todavía no está tal cual.** La estructura, el orden, los textos (palabra por palabra fuera de los corchetes), las fotos y la geometría coinciden: la altura total difiere 6 px en 9.071 y ningún bloque se desfasa más de 19 px. Encontré **1 desviación que rompe la fidelidad**, **5 de prioridad alta**, 7 medias y varias bajas. Casi todas se arreglan sin fotos nuevas.

| Bloque | Estado |
|---|---|
| 01 Topbar + header | casi (bajo) |
| 02 Hero | casi (medio: tarjetas de vidrio) |
| 03 Buscador + garantías | tal cual |
| 04 Destinos (bento) | tal cual |
| 05 Tours destacados | **NO igual: badges con color cruzado** + estrellas más chicas |
| 06 Camino Inca | casi (bajo/medio) |
| 07 Tours por ciudad | **NO igual: el subtítulo de Cusco choca con el botón** |
| 08 Experiencias | **NO igual: icono de Gastronomía oscuro (PDF: blanco)** + glifos distintos |
| 09 Banda paisajes | casi (bajo) |
| 10 Planes | casi (medio: corte de línea del 4.º título, tipos más chicos) |
| 11 Razones | casi (medio: títulos más chicos) |
| 12 Testimonios | casi (alto: texto más claro que el PDF) |
| 13 FAQ | **NO igual: la respuesta abierta queda pegada al divisor** + botón WhatsApp |
| 14 Instagram + newsletter | casi (medio: "vivo" queda solo en la última línea) |
| 15 Footer | casi (alto: botón WhatsApp) |

## Hallazgos priorizados

### Arreglables ya (código y CSS)

| # | Prioridad | Ubicación | Qué se ve (PDF vs render) | Archivo:línea probable | Fix sugerido |
|---|---|---|---|---|---|
| 1 | **Rompe-fidelidad** | Tours destacados › badges de las tarjetas 1 y 2 | En el PDF "Recomendado" es **turquesa** y "Tendencia" **verde bosque**. En el render están al revés (muestra de color: PDF c1 rgb(28,109,130) / c2 rgb(35,73,58); render c1 rgb(37,72,59) / c2 rgb(28,109,131)). | `resources/views/components/home/tour-card.blade.php:19` | `'Recomendado' => 'teal', 'Tendencia' => 'forest'` |
| 2 | Alto | Tours por ciudad › Cusco | En el PDF el subtítulo corta en 2 líneas ("Machu Picchu y Valle / Sagrado") y el título sube. En el render va en 1 línea y la palabra "Sagrado" toca el botón de flecha (colisión). | `resources/css/inka.css:420-421` (`.city__body` padding-right 64 px y `.city .dest__tag { max-width:none }`) | Reservar el espacio del botón: `.city__body{padding-right:84px}` o `.city .dest__tag{max-width:150px}` en ≥1024 px |
| 3 | Alto | Experiencias › icono de Gastronomía | En el PDF el glifo es **blanco** sobre un dorado más profundo (~#C8912A). En el render es **café oscuro** sobre el dorado claro #E0A63A. Es el único icono oscuro de los 3, así que rompe la consistencia del trío. | `resources/css/inka.css:252` (`.icon-tile--solid-accent` usa `color: var(--on-action)`) | En la variante de experiencias: `color:#fff; background: var(--c-gold-600)` |
| 4 | Alto | FAQ › respuesta abierta | En el PDF hay unos 33 px de aire entre la última línea de la respuesta y el divisor. En el render la respuesta queda pegada al divisor (unos 12 px). Por eso toda la lista sube unos 20 px. | `resources/css/inka.css:1016` (`.acc__panel{padding-bottom:0}`, pase 25) | `padding-bottom: 22px` (lo que había en la línea 535) |
| 5 | Alto | Testimonios › texto de la cita | En el PDF el texto en cursiva es casi negro (espresso). En el render es café medio #5C4B3D, se ve lavado. | `resources/css/inka.css:496` (`.testi__text` usa `color: var(--text-2)`) | `color: var(--text)` |
| 6 | Alto (🔵 por confirmar, accesibilidad) | FAQ › tarjeta de contacto "Hablar por WhatsApp" + footer "Escríbenos por WhatsApp" | En el PDF el texto y el icono son **blancos** sobre verde brillante #1FAF5A. En el render son **café oscuro** sobre verde #1A9D50. Salta a la vista en los dos botones. | `resources/css/inka.css:63` (`.btn--wa`) + tokens `--whatsapp-action`/`--on-whatsapp-text` (`tokens.css:71-72`) | Para igualar el PDF: `background: var(--c-wa-icon); color:#fff`. El token documenta que el blanco da 3.51:1 (pasa como texto grande en negrita ≥14 px bold = 18.66 px; a 15 px no pasa AA). Anyerson decide: fidelidad o AA. |
| 7 | Medio | Planes › título del 4.º plan | PDF: "Camino Inca a Machu / Picchu, 4 días". Render: "Camino Inca a / Machu Picchu, 4 días". | `resources/css/inka.css:778` (`.plan__title{text-wrap:balance}`) | Quitar `balance` en ≥1024 px (y `font-size` del #8) |
| 8 | Medio | Planes › título y precio de las tarjetas | En el render el tipo es unos 8-10 % más chico. "City Tour Lima" mide 112 px contra 121 en el PDF y "S/ [PRECIO]" 95 px contra 106. | `inka.css:1014` (`.plan__title 15px`), `inka.css:625` (`.plan__price strong 16px`) | Título 16 px, precio 17.5-18 px (comprobar que la fila Desde/Ver planes no se parta) |
| 9 | Medio | Razones › títulos de tarjeta | En el render son un 8 % más chicos: "Atención personalizada" mide 190 px contra 207 en el PDF. | `inka.css:1015` (`.reason__title 15.3px`, pase 25) | ≈16.6 px |
| 10 | Medio | Hero › tarjetas inactivas del panel (Ica, Machu Picchu) | En el PDF son de vidrio esmerilado: translúcidas, se ven las palmeras difuminadas detrás y el subtítulo es más fino. En el render son un bloque gris azulado casi opaco, con el texto más pesado. | `inka.css:155` (`.hero__item` `background: var(--glass)`, `blur(8px)`) | Bajar la opacidad del fondo (~.18-.22 blanco/negro), `backdrop-filter: blur(14-16px)` y subtítulo con peso 400 |
| 11 | Medio | Experiencias › glifos Trekking/Gastronomía/Cultura | El PDF usa otro set de iconos: montaña de 2 picos ancha y baja (tipo Material "landscape"), cubiertos tenedor+cuchillo curvo (Material "restaurant") y edificio clásico más grueso (Material "account_balance"). El render usa los de Lucide: pico alto, "ψ" y un landmark fino. | `resources/views/home.blade.php:114` + `resources/views/components/glyph.blade.php:21-23` | Agregar 3 glifos con el trazo del PDF |
| 12 | Medio | Instagram › texto central | PDF: "Síguenos y mira cada salida / en vivo". Render: "…cada salida en / vivo" (la palabra "vivo" queda sola). | `inka.css:1026` (`.ig__text{max-width:16em}`) | `max-width: 14.5em` o `text-wrap: balance` |
| 13 | Medio | Botones fantasma (hero "Ver destinos", Inca "Consultar fechas", banda "Planear mi viaje") | En el PDF el borde es blanco semitransparente y el radio ~10 px. En el render el borde es blanco sólido (más duro) y el radio 8 px. Los botones del Camino Inca y la banda salen 4-10 px más anchos. | `inka.css:61` (`.btn--ghost`) | `border-color: rgb(255 255 255 / .55)`, radio 10 px (revisar también el radio de `.btn` primario: el PDF también se ve ~10) |
| 14 | Bajo | Tours destacados › estrellas | En el render las estrellas son más chicas (5 estrellas ocupan 60 px contra 71 en el PDF) y "[4,9]" queda 8 px más a la izquierda. | estilos de `.rating__stars` dentro de `tour-card` | Estrella de ~12 px |
| 15 | Bajo | Tours destacados › cuerpo de la tarjeta | La tarjeta empieza 9 px más arriba y crece 6 px. Título, chips y "Cancelación" van 4-6 px desplazados. | `tour-card` / `inka.css` | Ajuste de padding superior del cuerpo |
| 16 | Bajo | Camino Inca › caja de precio | La caja mide 74 px de alto contra 85 en el PDF y queda 12 px más baja. En el PDF hay más aire entre la etiqueta y el precio. | `inka.css:398-400` (`.inca__price` gap 2 px, padding 14/18) | `gap:6px; padding:18px 18px` |
| 17 | Bajo | Camino Inca › título | El título sale un ~2 % más ancho que en el PDF ("…SE AGOTA" termina 10 px más a la derecha). | `inka.css:406` | 37.2 px |
| 18 | Bajo | Planes › filtros | Los chips salen más anchos (Todos 87 px contra 81 en el PDF): el grupo arranca 27 px más a la izquierda y va 8 px más abajo. | `inka.css:468,471` (`.filter` 14.5 px, padding 0 20 px) | 14 px, padding 0 18 px |
| 19 | Bajo | Testimonios › comillas, avatar B y pills de rating | La comilla es más grande y pesada que en el PDF (Cinzel 56 px). El avatar "B" es más oscuro: #8C5F10 contra #B5851F en el PDF. Los pills de rating tienen un gap de 13 px contra 18. | `inka.css:494`, `inka.css:502` (`.avatar--gold` usa `--accent-text`) | Comilla ~40 px, avatar `var(--brand-600)`, gap 18 px |
| 20 | Bajo | Experiencias › espaciado del texto | Del título a la descripción hay 34 px contra 40 en el PDF, y el link "Ver …" queda 4 px más arriba. | `inka.css:1012` y siguientes | `.exp__body .dest__tag{margin-top:10px}` |
| 21 | Bajo | Header › nav y PEN/ES | "Inicio" arranca en x=286 contra 299 en el PDF (el comentario de `inka.css:953` dice 299, pero no se cumple). El toggle PEN/ES mide 98 px de ancho contra 86. | `inka.css:953` y estilos del toggle | margin-left +13 px, toggle más compacto |
| 22 | Bajo | FAQ › tarjeta de contacto · footer › chips sociales · newsletter "Suscribirme" | En el render el tipo es un 4-7 % más grande y por eso la tarjeta de contacto sale 8 px más alta y los chips más anchos. | estilos `.contact-card`, `.footer` chips, `.news__form button` | -1 px |
| 23 | Bajo | Eyebrows | En el render el dorado es más café: #8C5F10 contra un tono más amarillo en el PDF (~#9A7012). | `inka.css:286` (`--accent-text`) | Opcional: se eligió por contraste AA |

### Requieren fotos originales (no es maquetación)

| # | Prioridad | Ubicación | Observación |
|---|---|---|---|
| F1 | Bajo | Tours por ciudad › Ica y Lima | El encuadre es casi igual. En el PDF la foto de Ica sale con un poco más de zoom en la laguna (residual ya declarado). |
| F2 | Bajo | Planes › "4+ días" (Camino Inca) | La foto y el encuadre difieren levemente: en el PDF se ve más sendero y los caminantes están más arriba (residual declarado). |
| F3 | Bajo | Hero a 1920 y fotos provisionales en general | Las fotos se ven blandas por la baja resolución de los provisionales. La composición está bien. Hace falta el original en alta. |

## Lo que está tal cual (verificado en par)

- Buscador y garantías (posición ±2 px, iconos, textos).
- Bento de destinos (fotos, encuadre, badge "★ El más buscado", flechas).
- Banda (foto, textos, stats, franja textil).
- Grilla de Instagram, newsletter y footer (columnas, textos, alturas).
- Todos los textos que no van entre corchetes coinciden palabra por palabra. No sobra ni falta ningún elemento en ningún bloque.
- Fuentes: cargaron Cinzel 400/700 y Poppins 400/500/600/700, sin fallback.

## Notas de método

- Alineé cada bloque con correlación vertical contra el PDF. El desfase es de 0 a +19 px, y el +19 viene del hallazgo #4 de la FAQ.
- No hay referencia móvil en el PDF de Home, así que no se compara un viewport móvil en esta pasada.
- No toqué código. Un solo Chrome headless, cerrado al terminar.

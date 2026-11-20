# 21 — Ojo: Home vs PDF aprobado (fidelidad visual)

- **Fecha:** 2026-11-19
- **Fuente de verdad:** `Inka-Palace-1-Home (4).pdf` (imagen desktop, 15 bloques)
- **Render:** `http://127.0.0.1:8000/es` (local), 1440 px + pasada a 1920 px. Mobile fuera de alcance (el PDF no tiene mobile).
- **Herramienta:** MCP chrome-devtools **no disponible** (el perfil `~/.chrome-mcp-profile` está tomado por otra instancia: "The browser is already running"). No se mató el Chrome del usuario. Como respaldo usé Playwright (Python) sobre el Chrome del sistema en headless, con la cookie `cookie_consent=all`.
- **Side-by-side:** `docs/rediseno-2026/capturas-21/index.html`, que abre los 11 pares `cmp-XX-*.png` (PDF a la izquierda, render 1440 a la derecha).
- Las capturas completas del render están en `render-full-1440.png` y `render-full-1920.png`. Hero en slide 1: `r01-hero-slide1-1440.png` y `r01-hero-slide1-1920.png`. Zoom del artefacto: `zoom-hero-artefacto.png`.

## Veredicto

**NO: el Home no está "tal cual PDF".** La estructura sí está: los 15 bloques, el orden, las grillas y los solapes coinciden. Lo que falla es que **no se ve igual**, por tres causas:

1. **Fotos:** son recortes del PDF con manchas de interpolación y de baja resolución. El hero, la banda y el pie de las tarjetas se ven borrosos y "embarrados".
2. **Datos demo del CMS:** desplazan las fotos del diseño y dejan tarjetas sin imagen. Además, el ocultamiento de marcadores abre huecos: planes con 1 de 4 tarjetas, FAQ con 1 de 6 preguntas, testimonios y sellos ausentes, y la banda con 1 de 3 cifras.
3. **Paleta:** es la hoja de marca oficial (Champagne #C9A96A, Sage #556B52, Espresso #3E2F24, Ivory) y no la del PDF (ámbar #E2A73A, verde bosque #24473A, café #2A1C15, blanco). Por eso el sitio se ve **apagado, no "full llamativo"**. Es una decisión documentada en `resources/css/tokens.css:1-27`, no un error de maquetación, pero choca de frente con el pedido actual.

**Hallazgos que bloquean el cierre: 7 Rompe-fidelidad** (más 1 decisión de paleta que Anyerson debe tomar). Además hay 6 Alto, 7 Medio y 5 Bajo.

| Bloque | Juicio |
|---|---|
| 1 Topbar + franja | Casi igual. La franja textil no tiene turquesa y sus colores están apagados. |
| 2 Header | Casi igual: fondo ivory en vez de blanco, WhatsApp en lugar del carrito (decisión CRO A-3), switch con chevrons. |
| 3 Hero | **NO igual**: foto con manchas, borrosa y con el encuadre de LIMA roto. |
| 4 Buscador | Igual: solape sobre el hero y 3 campos + CTA. |
| 5 Garantías | Igual, salvo los tintes de los iconos. |
| 6 Bento | **NO igual**: la tarjeta Arequipa no tiene foto, falta Humantay y el orden cambia. |
| 7 Tours destacados | **NO igual**: 1 tarjeta sin foto, falta Machu Picchu / "Últimos cupos" y las variantes de tarjeta son inconsistentes. |
| 8 Banner Camino Inca | Casi igual: verde sage apagado, la foto pierde al caminante y está borrosa. |
| 9 Tours por ciudad | Casi igual: Ica con encuadre distinto, mancha al pie de las tarjetas, pill solo en Cusco. |
| 10 Experiencias | Casi igual: el icono de Cultura es blanco (en el PDF, turquesa) y hay mancha al pie de las tarjetas. |
| 11 Banda paisajes | **NO igual**: la foto casi negra (oscurecida dos veces) y queda 1 sola cifra. |
| 12 Planes | **NO igual**: 1 de 4 tarjetas (bug), café más claro. |
| 13 Razones + testimonios | **NO igual**: faltan por completo "Lo que dicen nuestros viajeros" y los 3 sellos. |
| 14 FAQ | **NO igual**: 1 de 6 preguntas y la columna derecha vacía. |
| 15 Instagram + newsletter + footer | Casi igual: las fotos IG son las correctas. Newsletter con dorado apagado. El footer de Destinos solo lista Cusco y Arequipa. |

## Hallazgos priorizados

| # | Prioridad | Bloque | PDF | Render | Archivo:línea probable | Fix sugerido (tipo) |
|---|---|---|---|---|---|---|
| 1 | **Rompe** | 3 Hero (1440/1920) | Plaza de Armas nítida. Letras LIMA completas al centro y abajo, Catedral visible tras un degradado suave. | Foto con **manchas horizontales de interpolación** (zona izquierda bajo el título y bloque arriba a la derecha, sobre las palmeras). Se ven a simple vista como "barrido". La foto está **borrosa**: se sirve `hero-lima-1200.webp` estirada a 1440 y 1920. La "L" y la "I" de LIMA quedan bajo el degradado y la mancha; a 1920 LIMA queda además cortada por el buscador. Las slides Ica y Machu Picchu salen de fuentes de 664 y 998 px, más borrosas aún. | `public/img/provisional/hero-*.{jpg,webp}` (lo dice su propio `LEEME.md`); `resources/views/components/home/hero.blade.php`; `.hero__bg` en `resources/css/inka.css:141` | **Imagen/dato:** fotos originales de la clienta, sin recortar del PDF. **Maquetación:** srcset con variantes 1920/2880 y `object-position` que deje LIMA entera (≈ `60% 70%`). |
| 2 | **Rompe** | 6 Bento | 1 grande (Machu Picchu) + Cusco y Valle Sagrado arriba, Ica y Humantay abajo. | La 2.ª tarjeta pequeña es **"Arequipa" sin foto**: bloque verde plano. **Humantay no aparece.** El orden queda Cusco, Arequipa / Valle, Ica. | `resources/views/home.blade.php:49-63`: el pool mete primero los destinos reales del CMS (Cusco y Arequipa, ambos con `image=null`) y las muestras solo rellenan. | **Dato:** subir foto a Arequipa o retirarla. **Programación:** excluir del mosaico los destinos sin imagen y respetar el orden del PDF. |
| 3 | **Rompe** | 7 Tours destacados | 4 tarjetas con foto: City Tour Lima (Recomendado), Humantay (Tendencia), 7 Colores (Más vendido), Machu Picchu (Últimos cupos). | 1.ª tarjeta "Camino Inca 4 días" (tour CMS `[MUESTRA]`) **sin foto**: verde plano con icono de montaña. **Falta Machu Picchu y el badge rojo "Últimos cupos".** El orden queda corrido. | `home.blade.php:84-95`; `resources/views/components/home/tour-card.blade.php` | **Dato:** foto al tour real. **Programación:** un tour destacado sin imagen no debe entrar en la grilla del Home. |
| 4 | **Rompe** | 12 Planes | 4 tarjetas: 4 h, 1 día, 2-3 días, 4+ días. | **Solo 1 tarjeta** (7 Colores) y 3/4 del ancho vacío. Bug: los tours CMS "[MUESTRA] Camino Inca…" y "[MUESTRA] Tour gastronómico…" ocupan su bucket, lo que impide la muestra. Luego `@continue (Placeholder::is($p['title']))` los descarta porque el título empieza por "[". El de 2-3 días es un marcador real `[Tour sugerido…]`. | `home.blade.php:99-110` y `:326` | **Programación:** evaluar `Placeholder::is(Placeholder::title($title))`, o no reservar el bucket con un ítem que luego se descarta. |
| 5 | **Rompe** | 13 Testimonios + sellos | Bloque "Lo que dicen nuestros viajeros": 3 testimonios + 3 sellos (Tripadvisor/Viator/Google). | **Bloque entero ausente.** La sección termina en las 4 razones. | `home.blade.php:382-416` (`Placeholder::items` descarta todo) | **Cliente/contenido:** 3 reseñas reales + ratings. Mientras falten, es **decisión** de Anyerson: aceptar el hueco o mostrar los sellos con un enlace "ver reseñas" sin cifra. |
| 6 | **Rompe** | 14 FAQ | 6 preguntas en acordeón que equilibran la tarjeta verde. | **1 sola pregunta** y la columna derecha vacía unos 400 px bajo ella. Composición coja. | `home.blade.php:435`; `lang/es/home.php:253-259` (5 respuestas `[Respuesta por completar]`) | **Contenido:** redactar las 5 respuestas (cancelación, recojo, idiomas, pagos, anticipación). Es texto, no se inventa ningún dato duro. |
| 7 | **Rompe** | 11 Banda "Paisajes…" | Foto de nevados **visible** con un velo marrón medio. 3 cifras doradas ([N]+, [4,9], 5). | Foto casi negra, **oscurecida dos veces**: `paisajes-1200.webp` ya trae el velo y la mancha horneados, y encima va `--shade-band` al 0.9/0.72/0.42. Queda **1 sola cifra** ("5 destinos en Perú") colgando. Además es un webp de 10 KB, muy comprimido. | `public/img/provisional/paisajes-*.webp`; `tokens.css:240` `--shade-band`; `home.blade.php:295-303` | **Imagen:** foto limpia. **Maquetación:** aligerar `--shade-band` (≈ .75/.5/.2). **Decisión:** con una sola cifra, ocultar las stats o diseñar la variante de 1 ítem. |
| D | **Decisión (bloquea "llamativo")** | Todo el sitio | Dorado ámbar saturado #E2A73A, verde bosque #24473A, café #2A1C15, fondos blancos, acento turquesa (icono Cultura, sello "Viajes seguros"). | Champagne #C9A96A (botones caqui y apagados), Sage #556B52 (banner Inca y tarjeta FAQ grisáceos), Espresso #3E2F24 (planes y footer más claros), fondo Ivory #FAF7EE. El turquesa se eliminó. | `resources/css/tokens.css:31-61` ("La hoja de marca MANDA sobre los colores del PDF") | **Anyerson decide:** si el objetivo es "tal cual PDF y full llamativo", hay que volver a los colores del PDF (o subir el chroma del gold y del sage) cambiando solo los 4 tokens. Si manda la hoja de marca, el Home nunca se verá como el PDF. |
| 8 | Alto | 9 Ciudad, 10 Experiencias, 6 Bento | Degradado inferior suave y limpio. | Al pie de las tarjetas se ve una **franja rectangular con rayas verticales**, muy evidente en Ica, Cusco y Lima (ciudad) y en Trekking y Cultura. Son las zonas donde el PDF tenía texto y se rellenaron por interpolación. El overlay no las tapa. | `public/img/provisional/{ica,cusco,lima,trekking,cultura,...}` (`LEEME.md`: "manchas oscuras bajo el overlay") | **Imagen:** originales sin texto horneado. Paliativo de maquetación: overlay más alto y opaco en el 40 % inferior. |
| 9 | Alto | 3 Hero / 2 Header | Panel derecho: tarjetas inactivas de cristal oscuro; header blanco con carrito y badge "1". | Panel bien. El carrito se cambió por un círculo verde de WhatsApp. Hay eyebrow extra "AGENCIA DE TURISMO CON EXPERTOS LOCALES" y botón de pausa. | `components/site/header.blade.php:113-116` (CRO A-3); `lang/es/site.php:30` (CRO A-5) | **Decisión** documentada por CRO. Si se quiere tal cual PDF, recuperar el carrito cuando exista (lote 4). No toca a maquetación. |
| 10 | Alto | 7 Tours destacados | Todas las tarjetas son la misma variante: estrellas + rating, chips de duración e idioma, precio "Desde S/". | Variantes mezcladas. City Tour Lima **sin chip de duración** (su `[N] horas` se oculta), así que su pie sube y no se alinea con las otras 3. Las 3 de muestra dicen "Consultar precio" y la 1.ª trae precio: alturas internas distintas. Sin fila de estrellas ni chip ES/EN. Títulos en peso 500-600 (en el PDF, 700). | `components/home/tour-card.blade.php`; `home.blade.php:87` | **Maquetación:** fijar el pie con `margin-top:auto` para alinear "Ver tour" y subir el título a 700. **Contenido:** duración real de City Tour. |
| 11 | Alto | 9 Ciudad: Ica | Tarjeta Ica = paisaje del oasis (laguna y dunas), sin persona. | Muestra **a la mujer** del recorte (la misma foto del bento y el hero, con `object-position: 12% 40%`). Repite el mismo encuadre que el bento: la foto se ve 3 veces. | `resources/css/inka.css` (regla `.city` de Ica, `object-position 12% 40%`); `ica-480.webp` | **Maquetación:** `object-position` ≈ `85% 60%` para enfocar el oasis, o asset propio `city-ica`. |
| 12 | Alto | 8 Banner Camino Inca | Foto con un **caminante de rojo** abajo a la derecha, junto a la tarjeta de precio (punto focal humano). | El recorte `camino-inca-*.webp` termina antes del caminante: solo paisaje, y además borroso. La fuente trae una esquina redondeada blanca horneada arriba a la derecha. | `public/img/provisional/camino-inca*`; `home.blade.php:206` | **Imagen:** original completo. **Maquetación:** `object-position: 50% 100%`. |
| 13 | Alto | 15 Footer | Columna Destinos: Lima, Ica, Cusco y Machu Picchu, Nazca, Arequipa. | Solo Cusco y Arequipa (CMS); la columna se ve vacía. | footer (lista desde CMS de destinos) | **Dato:** cargar los destinos en el CMS. |
| 14 | Medio | 10 Experiencias | Iconos en cuadro: Trekking verde oscuro, Gastronomía dorado, Cultura **turquesa**. | Cultura con cuadro **blanco** e icono oscuro (`--c-teal-*` = espresso/clay en tokens). Se ve como la "impar" de las tres. | `tokens.css:47-48`; `home.blade.php:81` | **Decisión de paleta (D)** o un token `--teal` propio. |
| 15 | Medio | 13 Razones | Cuadros de icono: crema, verde, **azul claro**, crema. | Crema, verde-gris, crema, crema: se pierde el azul de "Viajes seguros". | `.reason` en `inka.css` / tokens | Igual que #14. |
| 16 | Medio | 9 Ciudad | Pill "[N] tours" en las 4. | Pill "1 tour" solo en Cusco; las otras 3 sin pill (marcador oculto). | `home.blade.php:45` | Consistencia: ocultar el pill en todas mientras falten conteos, o completar los conteos. |
| 17 | Medio | 12 Planes | Fondo café muy oscuro #2A1C15; badges de duración turquesa / dorado / verde con buen contraste. | Fondo #3E2F24, más claro y menos premium. Badge "1 día" caqui con poco contraste. | `tokens.css` (espresso) | Decisión D. |
| 18 | Medio | 14 FAQ (tarjeta) | Botón WhatsApp verde sólido brillante (#22B35B), sin borde. | Verde #158045 con **anillo ivory** de 2 px: parece outline. Teléfono y email muy separados (≈ 60 px entre líneas, unos 40 en el PDF). | `inka.css:762` (`.contact-card .btn--wa`) | **Maquetación:** quitar el box-shadow ring y compactar el gap de los datos. |
| 19 | Medio | 1 / 11 / 15 Franjas textiles | Rojo, dorado, verde, **turquesa** y café, saturados. | Sin turquesa y con tonos sage/caqui apagados. | `inka.css:69` (`repeating-linear-gradient` de `.ribbon`) | Decisión D (colores de franja). |
| 20 | Medio | 2 Header | Header **blanco**. Switch segmentado PEN \| ES con la opción activa en pill. | Header ivory. Switch con chevrons (desplegables). | `components/site/header.blade.php` | **Maquetación:** fondo `--surface`; switch segmentado como el PDF si no hay razón funcional. |
| 21 | Bajo | 5 Garantías | Cuadros de icono en 4 tintes (crema, verde, azul, café). | Tintes casi iguales, todos crema. | `components/home/guarantees.blade.php` | Decisión D. |
| 22 | Bajo | 6 Bento | Badge "★ El más buscado" dorado ámbar con texto oscuro. | Champagne: se lee, pero llama menos la atención. | — | Decisión D. |
| 23 | Bajo | 9 Ciudad | Tarjetas de ≈ 295×480 (más esbeltas). | 324×460 (más achatadas). | `.city` en `inka.css` | `aspect-ratio` ≈ 0.62. |
| 24 | Bajo | Botones dorados | Ámbar con texto café oscuro, alto contraste y "grita CTA". | Caqui con texto espresso: se ve deslavado (ver hero, Camino Inca, banda, buscador). | `tokens.css:33` | Decisión D. |
| 25 | Bajo | Burbuja WhatsApp | Botón flotante + burbuja "¿Te ayudamos? Escríbenos por WhatsApp". | Solo el botón; la burbuja no se vio en carga ni al hacer scroll. | layout / `wa-float` | Confirmar si la burbuja aparece con retardo. Si se quitó, es decisión. |

**Sin desviación relevante:** el orden de los 15 bloques, la topbar (textos y pill roja), el nav, el buscador montado sobre el borde del hero, la proporción del bento (1 grande + 2×2), la grilla de tours (4), el banner Camino Inca (copy / foto 54/46 / tarjeta de precio montada), experiencias (1 ancha + 2), razones centradas, la FAQ en 2 columnas, la tira IG de 6 columnas con el bloque central oscuro, el newsletter y el footer de 4 columnas. La tipografía es correcta: **Cinzel** cargada (400/700) en títulos versalitas y **Poppins** 400-700 en el cuerpo, sin fallback. Los wraps de los títulos coinciden con el PDF; "Encuentra un plan para tu tiempo" corta en 2 líneas igual que el diseño. A 1920 no hay roturas nuevas: el contenedor queda centrado igual que a 1440. Solo se agrava el encuadre del hero (#1).

## Imágenes faltantes o distintas

Todas las fotos servidas viven en `/img/provisional/` y son **recortes del JPEG del PDF** (ver `public/img/provisional/LEEME.md`), así que ninguna es apta para producción.

| Slot | Foto esperada (PDF) | Foto actual (servida) | Estado |
|---|---|---|---|
| Hero slide 1 | Plaza de Armas + letras LIMA, nítida | `/img/provisional/hero-lima-1200.webp` (1200 px estirada a 1440/1920, con manchas de interpolación) | Misma foto, **defectuosa y borrosa**, encuadre corrido |
| Hero slide 2 | Ica / Huacachina | `/img/provisional/hero-ica-664.webp` (664 px a 1440) | Misma foto, **muy borrosa** |
| Hero slide 3 | Machu Picchu | `/img/provisional/hero-machu-picchu-998.webp` | Misma foto, borrosa |
| Miniaturas 01-03 | Lima / Ica / Machu Picchu | `thumb-{lima,ica,machu-picchu}.jpg` (128×104) | OK |
| Bento grande | Machu Picchu con pareja | `/img/provisional/machu-picchu-828.webp` | OK |
| Bento: Cusco | Plaza de Cusco con bandera | `cusco-480.webp` | OK |
| Bento: Valle Sagrado | Moray | `valle-sagrado-480.webp` | OK (posición cambiada: va abajo en lugar de arriba) |
| Bento: Ica | Mujer en duna + oasis | `ica-480.webp` | OK (posición cambiada) |
| **Bento: Humantay** | Laguna Humantay | **(ninguna)**: el slot lo ocupa "Arequipa" sin imagen | **FALTA** (`humantay-480/666.webp` existe y no se usa) |
| **Tour destacado 1** | City Tour Lima (palacio + carruaje) | **Camino Inca 4 días sin imagen** (placeholder verde) | **FALTA** |
| Tour: City Tour Lima | Palacio de Gobierno **con carruaje de caballos** | `tour-city-tour-lima-480.webp`: el carruaje queda casi fuera del encuadre (cover centrado de una fuente vertical 345×383) | Misma foto, **foco perdido**: `object-position: 50% 85%` |
| Tour: Humantay | Laguna Humantay | `tour-laguna-humantay-480.webp` | OK (en el PDF la persona sobre la roca se ve más; aquí la corta el borde inferior) |
| Tour: 7 Colores | Hombre + llama | `tour-montana-7-colores-480.webp` | OK |
| **Tour: Machu Picchu** | Pareja en Machu Picchu, badge "Últimos cupos" | **(no se muestra)** | **FALTA** (`tour-machu-picchu.jpg` existe y no se usa) |
| Banner Camino Inca | Valle + caminante de rojo | `camino-inca-828.webp`: sin caminante, esquina blanca horneada, borrosa | Distinta (recorte incompleto) |
| Ciudad: Ica | Oasis (sin persona) | `ica-480.webp` con `object-position 12% 40%`: se ve la mujer | **Encuadre distinto** |
| Ciudad: Cusco / Lima / Nazca | Plaza Cusco / Palacio + carruaje / Colibrí con avioneta | `cusco-480`, `lima-480`, `nazca-480` | OK, con mancha al pie |
| Exp: Trekking / Gastronomía / Cultura | Humantay nevado / maíz morado / Machu Picchu templo | `trekking-828`, `gastronomia-710`, `cultura-712` | OK, con mancha al pie |
| Banda paisajes | Nevados visibles, velo medio | `paisajes-1200.webp` (10 KB, velo y mancha horneados + overlay CSS) | Misma foto, **casi negra** |
| **Planes 4h / 2-3 días / 4+ días** | City Tour Lima / Nazca avioneta / Camino Inca | **(no se muestran)**; solo se ve el plan 1 día (`tour-montana-7-colores-480.webp`) | **FALTAN 3** (`plan-nazca.jpg` existe y no se usa) |
| Instagram 1-5 | Catedral de noche, pareja MP, 7 Colores, Humantay, Huacachina | `ig-1..5-480.webp` | OK |
| Logo header / footer | Logo INKA PALACE | `/images/brand/inka-palace-logo-640.webp` | OK |

## Siguiente paso sugerido

1. **Anyerson decide la paleta (D):** colores del PDF o hoja de marca. Sin esa decisión el Home no será "llamativo".
2. **webtilia-programacion:** arreglar el bug de planes (#4) y el filtro de destinos/tours sin imagen en el Home (#2, #3).
3. **Cliente:** fotos originales en alta (el hero, sobre todo), 5 respuestas de FAQ, reseñas y ratings.
4. **webtilia-maquetacion:** encuadres (#1, #11, #12, City Tour), overlay de la banda (#7), pie de tarjetas de tour (#10), botón WhatsApp de la FAQ (#18) y header (#20).
5. Re-pasar el ojo tras esos cambios.

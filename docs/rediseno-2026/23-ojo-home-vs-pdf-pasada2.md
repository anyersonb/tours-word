# 23 — Ojo: Home vs PDF, segunda pasada (después de los fixes)

- **Fecha:** 2026-11-19
- **Fuente de verdad:** `Inka-Palace-1-Home (4).pdf`, un JPEG de 2880×18142 cortado en `sec_00..08`.
- **Render:** `http://127.0.0.1:8000/es` (local). Capturas propias con Playwright headless sobre el Chrome del sistema, cookie `cookie_consent=all`, a **1440** y **1920** (página completa y cada slide del hero con `reduced_motion`), más una pasada a **390** (DPR 1 y DPR 2). No usé las capturas del maquetador.
- **Side-by-side:** `docs/rediseno-2026/capturas-23/index.html`. Para cada bloque muestra el PDF a la izquierda y el render a la derecha, en 1440 y 1920. Incluye los slides 2 y 3 del hero y los zooms a tamaño real que sirven de evidencia.

## Veredicto

**Casi tal cual el PDF. Está listo para staging como demo para la clienta, con dos arreglos cortos antes de mostrarlo** (R1 y A1, ambos son CSS o recorte y se hacen en minutos).

El salto respecto de la pasada 1 es grande. Los 7 rompe-fidelidad de aquella pasada quedaron cerrados o resueltos por decisión. La paleta del PDF ya está aplicada y el sitio ahora sí se ve "llamativo": el ámbar grita, el verde bosque y el café oscuro se ven premium y el turquesa volvió. Bento, tours destacados, planes y FAQ tienen la composición del PDF. Las fotos están limpias a tamaño real: no vi texto fantasma en ninguna tarjeta, ni siquiera el "empo" bajo Cusco. Solo aparece con un zoom de 3x.

Lo que todavía haría torcer el gesto a la clienta en los primeros 2 segundos:

1. **(R1) Hero slide 3, Machu Picchu: a 1920 la pareja sale con la cara cortada a la altura de la boca**, y a 1440 sin la frente. Además se ve ampliada y blanda. Es el slide que más se notaría.
2. **(A1) Hero slide 1, Lima: detrás del panel de miniaturas quedan restos del PDF.** Se ve una mancha verde-gris borrosa a la izquierda del panel y una "segunda barra de progreso" dorada fantasma bajo la flecha ←. Eso se lee como un control duplicado. Lo ve cualquiera que mire el panel.

Lo demás es pulido o depende de contenido de la clienta (precios, estrellas, testimonios y fotos originales).

| Pantalla / bloque | Juicio pasada 2 |
|---|---|
| 1 Topbar + franja | Tal cual. La franja textil ya trae turquesa y saturación. |
| 2 Header | Tal cual: blanco y PEN\|ES segmentado. WhatsApp en lugar del carrito es decisión CRO. |
| 3 Hero | Casi. Slide 1 bien (LIMA entera, nítida a 1440 y 1920), pero con el fantasma de controles (A1). Slide 2 (Ica) blando pero digno. Slide 3 (Machu Picchu) con la cara cortada (R1). |
| 4 Buscador | Tal cual. |
| 5 Garantías | Tal cual, con los 4 tintes recuperados (crema, verde, azul, café). |
| 6 Bento | Tal cual: Machu Picchu + Cusco/Valle + Ica/Humantay, en el orden del PDF. |
| 7 Tours destacados | Casi: 4 fotos correctas y badges correctos (Últimos cupos rojo). Faltan estrellas, idioma y precio (datos), y la fila central queda desalineada (M1). |
| 8 Banner Camino Inca | Tal cual. El caminante de rojo volvió. |
| 9 Tours por ciudad | Tal cual: Ica muestra el oasis. Los pills `[N] tours` están ocultos en las 4 (consistente). |
| 10 Experiencias | Tal cual, con cuadros de icono verde, ámbar y turquesa. |
| 11 Banda paisajes | Casi: la foto se ve y el velo es correcto. Sin cifras queda un aire vacío abajo (B1). A 1920 se cortan las cumbres (M3). |
| 12 Planes | Casi: 4 tarjetas, como el PDF. Hay un parche de relleno en la foto de City Tour (A2). |
| 13 Razones | Tal cual, con el azul de "Viajes seguros". Testimonios ocultos por decisión. La sección cierra limpia, sin hueco. |
| 14 FAQ | Tal cual: 6 preguntas, tarjeta verde y botón WhatsApp sólido. |
| 15 IG + newsletter + footer | Tal cual: footer con 6 destinos. Falta "Pagos seguros [medios de pago]" (marcador oculto, OK). |
| 390 (mobile) | Sin roturas. Solo el pie de la tarjeta de tour queda apretado (M2). |

## Estado de los pendientes de la pasada 1

| # P1 | Hallazgo | Estado | Nota |
|---|---|---|---|
| 1 | Hero: manchas, borroso, LIMA cortada | **Parcial** | Slide 1 nítido y con LIMA entera a 1440 y 1920; se quitaron las manchas grandes. Quedan restos detrás del panel (A1). Slides 2 y 3 ampliados desde 664 y 998 px y blandos; el slide 3 corta caras (R1). |
| 2 | Bento: Arequipa sin foto, falta Humantay | **Cerrado** | |
| 3 | Tours destacados: sin foto, falta Machu Picchu | **Cerrado** | |
| 4 | Planes 1 de 4 | **Cerrado** | |
| 5 | Testimonios y sellos ausentes | **Cerrado por decisión** | Se ocultan hasta tener los reales y la sección termina limpia. |
| 6 | FAQ con 1 pregunta | **Cerrado** | |
| 7 | Banda casi negra, 1 cifra | **Cerrado** (con B1 y M3) | |
| D | Paleta | **Cerrado** | Colores del PDF aplicados. |
| 8 | Mancha al pie de las tarjetas | **Cerrado** | Invisible a tamaño real. Hay un parche nuevo en la foto de City Tour en planes (A2). |
| 9 | Carrito → WhatsApp, eyebrow extra | Decisión CRO (se mantiene) | |
| 10 | Pie de tour desalineado, títulos 500-600 | **Parcial** | Pie y "Ver tour" alineados y títulos a 700. La fila de chips sigue variando: "Cancelación flexible" sube en City Tour y Machu Picchu (M1). |
| 11 | Ica en ciudad muestra a la mujer | **Cerrado** | |
| 12 | Camino Inca sin caminante | **Cerrado** | |
| 13 | Footer con 2 destinos | **Cerrado** | |
| 14 | Icono Cultura blanco | **Cerrado** (turquesa) | |
| 15 | Azul de "Viajes seguros" | **Cerrado** | |
| 16 | Pill solo en Cusco | **Cerrado** (oculto en las 4) | |
| 17 | Café de planes claro | **Cerrado** | |
| 18 | WhatsApp FAQ outline | **Cerrado** | Verde sólido, sin anillo. Texto en AA por decisión. |
| 19 | Franja sin turquesa | **Cerrado** | |
| 20 | Header ivory y chevrons | **Cerrado** | |
| 21 | Tintes de garantías | **Cerrado** | |
| 22 | Badge "El más buscado" apagado | **Cerrado** | |
| 23 | Ciudad más achatada | **Abierto (Bajo)** | 324×460 frente a un ≈ 0.62 del PDF. Casi imperceptible. |
| 24 | Botones dorados deslavados | **Cerrado** | |
| 25 | Burbuja "¿Te ayudamos?" | **Cerrado** | |

## Hallazgos priorizados (pasada 2)

### Arreglable ya en código o en el recorte (maquetación)

| # | Prioridad | Bloque @vp | Qué se ve (en humano) | Archivo:línea probable | Fix sugerido |
|---|---|---|---|---|---|
| R1 | **Rompe** | 3 Hero slide 3, @1920 (y @1440 en menor grado) | La pareja de Machu Picchu ocupa media pantalla con la **cara cortada**: a 1920 se ven solo la boca y el mentón de ella; a 1440 desaparecen la frente y el gorro. Se nota ampliada y blanda. Parece un error de encuadre. Evidencia: `cmp-01b-hero-slide3-1920.png`. | `resources/css/inka.css:859` (`.hero__bg { object-position: 50% 100% }` a ≥1024 para todos los slides); `config/brand.php:117` | Regla propia para el slide: `[data-hero-photo="2"] .hero__bg { object-position: 50% 25%; }` a ≥1024, que muestre caras y ciudadela. Alternativa: re-recortar `hero-machu-picchu` con más cielo arriba. |
| A1 | Alto | 3 Hero slide 1, @1440 y @1920 | A la izquierda del panel de miniaturas hay una **mancha verde-gris borrosa** (un rectángulo de relleno) y, bajo la flecha ←, una **barra dorada y otra flecha fantasma** horneadas del PDF: parecen dos barras de progreso. Evidencia: `z-hero-ctrl-1440.png`. | `public/img/provisional/hero-lima-{1440,1920}.webp` (recorte); `resources/views/components/home/hero.blade.php:70-104` | **Recorte:** clonar el cielo y las palmeras sobre la zona de controles del JPEG fuente, o rellenar con desenfoque *y* oscurecer. **CSS paliativo:** fondo de cristal oscuro (`backdrop-filter: blur(8px)` + rgba oscuro) bajo `.hero__controls` y un ancho del panel que cubra el resto. |
| A2 | Alto | 12 Planes, tarjeta "4 h City Tour Lima", @1440 y @1920 | En la esquina inferior izquierda de la foto hay un **parche verde claro rectangular** (pasto "barrido"). En la tarjeta de tour destacado lo tapa el chip "Lima"; en planes queda a la vista. Evidencia: `z-planes-pies.png` (arriba). | `resources/css/inka.css:772` (comparte `object-position: 50% 85%` con la tarjeta de tour) | Separar la regla de `.plan__media` y usar `object-position: 60% 60%`, que deja el carruaje y saca el parche. O limpiar el parche en `tour-city-tour-lima-480.webp`. |
| M1 | Medio | 7 Tours destacados, @todos | Las 4 tarjetas no tienen la misma fila central. Humantay y 7 Colores muestran el chip "1 día"; City Tour y Machu Picchu no, así que su "Cancelación flexible" sube y deja un hueco en blanco antes del pie. Se ve como dos variantes de tarjeta. | `resources/views/components/home/tour-card.blade.php:45-48`; `resources/css/inka.css:372` | Reservar la altura de la fila de chips (`.tour-card__meta { min-height: 28px }`, renderizada vacía). Mejor aún: **dato**, cargar la duración en City Tour y Machu Picchu. |
| M2 | Medio | 7 Tours destacados, @390 | El pie de la tarjeta queda apretado: "Consultar precio" rompe en 2 líneas y el botón dice "Ver / tour" en 2 líneas. | `inka.css` (`.tour-card__foot` en mobile) | En <480 px: `white-space: nowrap` en el botón y en el enlace, o apilar el pie (enlace arriba, botón al 100 %). |
| M3 | Medio | 11 Banda paisajes, @1920 | La foto pierde las cumbres nevadas y queda mucha ladera café. "Paisajes que no caben en una foto" con poca montaña. Evidencia: `z-band-1920.png`. | `resources/css/inka.css:443` (`.band__bg`, sin `object-position`) | `object-position: 50% 30%` a ≥1600 px. |
| B1 | Bajo | 11 Banda, @1440 y @1920 | Con las cifras ocultas quedan ≈ 90 px de aire bajo los botones: la composición queda arriba. | `resources/css/inka.css:455` (`.band { min-height: 540px }`); `home.blade.php:314-317` | Cuando no hay `band__stats`, centrar el contenido en vertical o bajar el `min-height` a ≈ 460. |
| B2 | Bajo | 7 Tours: Laguna Humantay | La persona sobre la roca, punto focal del PDF, queda cortada por el borde inferior. | `public/img/provisional/tour-laguna-humantay-480.webp` | `object-position: 50% 80%` o re-recorte. |
| B3 | Bajo | 9 Ciudad | Tarjetas algo más achatadas que en el PDF (#23 de P1). | `.city` en `inka.css` | `aspect-ratio: .62`. |
| B4 | Bajo | 12 Planes 4+ días | Una zona clara y lavada en la ladera inferior izquierda. Apenas se nota y la clienta probablemente no la ve. | `camino-inca-*.webp` | Opcional: `object-position: 50% 40%`. |

### Requiere fotos originales o contenido de la clienta (no es defecto de maquetación)

| # | Tipo | Qué falta | Efecto visual hoy |
|---|---|---|---|
| C1 | Fotos | Hero Ica y Machu Picchu en alta (el PDF solo las trae a 664 y 998 px) | Slides 2 y 3 blandos a 1440 y 1920. Con originales también se resuelve R1 de raíz. |
| C2 | Fotos | Hero Lima original (sin controles horneados) | A1 desaparece de raíz. |
| C3 | Datos | Precio, duración, idiomas y rating de los tours destacados | Tarjetas sin estrellas ni chip ES/EN, con "Consultar precio" en vez de "Desde S/". Se ven más pobres que en el PDF, pero no rotas. |
| C4 | Contenido | 3 testimonios reales y ratings Tripadvisor/Viator/Google | El bloque "Lo que dicen nuestros viajeros" no está. La sección cierra limpia: decisión aceptada. |
| C5 | Contenido | Cifras de la banda ([N]+ viajeros, [4,9] Tripadvisor) | Ocultas. Ver B1. |
| C6 | Contenido | Conteo "[N] tours" por ciudad y "Pagos seguros" del footer | Ocultos de forma consistente. |

## Sin desviación relevante

- **Tipografía:** Cinzel 400/700 y Poppins 400-700 cargadas, sin fallback. Los wraps de títulos coinciden con el PDF ("Encuentra un plan para tu / tiempo").
- **Composición:** orden de los 15 bloques, buscador montado sobre el hero, grillas de 4, banner Camino Inca 54/46 con la tarjeta de precio montada, FAQ en 2 columnas y tira IG con el bloque oscuro central.
- **1920:** el contenedor queda centrado en 1360 igual que a 1440, sin roturas nuevas.
- **390:** sin overflow ni solapes. Hero legible y panel de destinos debajo.
- **WhatsApp:** verde #1a9d50 con texto café, decisión AA. Se ve bien, no es defecto.

## Siguiente paso sugerido

`webtilia-maquetacion`: R1 + A1 + A2 (3 reglas CSS y 1 retoque de recorte) y, si hay tiempo, M1-M3. Después, cierre visual de Anyerson sobre `capturas-23/index.html` y subida a staging como demo. C1-C6 van a la lista de insumos de la clienta.

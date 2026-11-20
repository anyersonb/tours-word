# 29 · Ojo — Home vs PDF, pasada 4 (2026-11-20)

**Referencia:** `Inka-Palace-1-Home (4).pdf` (JPEG 2880×18142) reducido a 1440×9071 (escala 0.5).
**Render:** http://127.0.0.1:8000/es, build `public/build/assets/app-bKPAWrtl.css`. Playwright con Chrome headless (un solo navegador, cerrado al terminar), cookie `cookie_consent=all`, 1440 px, DPR 1.
**Capturas:** `docs/rediseno-2026/capturas-29/index.html`. Cada bloque tiene `cmp-*` (PDF a la izquierda, render a la derecha, alineados por correlación), `ovl-*` (superposición 50/50) y `zoom-*` (PDF arriba, render abajo).
Nota de método: en la página completa el header sticky se mostraba a mitad del hero. Por eso el hero se capturó en el viewport con scroll 0 y la página completa se tomó con el header en `position:relative`, que no cambia el layout. La grilla de Instagram sale vacía en la página completa porque sus imágenes son lazy. Su comparación válida es `cmp-08b` (viewport con las imágenes cargadas). No es un bug.

## Veredicto

**Casi tal cual, pero todavía no.** De los 23 hallazgos: **14 cerrados, 7 parciales, 1 abierto sin tocar (#15) y 1 que pasó a regresión (#23)**. El fix de los eyebrows (#23) dejó los eyebrows sobre fondo oscuro en café apagado (banda y planes). Eso rompe la fidelidad y además baja el contraste. También hay un cambio colateral leve en testimonios: la comilla quedó más chica, la tarjeta perdió unos 10 px y la FAQ sube 12-14 px respecto al PDF.

| Bloque | Estado |
|---|---|
| Header + hero | casi: vidrio de las tarjetas parcial, botones fantasma parcial, PEN/ES parcial |
| Tours destacados | casi: badges y estrellas OK; el cuerpo de la tarjeta sigue unos 5 px más bajo (#15) |
| Camino Inca | tal cual (#16 y #17 cerrados) |
| Tours por ciudad | tal cual (Cusco en 2 líneas) |
| Experiencias | casi: el glifo de Trekking es relleno y en el PDF es de contorno |
| Banda | **NO igual: eyebrow en café oscuro (regresión)** |
| Planes | **NO igual: eyebrow en café oscuro (regresión)**; títulos, precio y filtros OK |
| Razones + testimonios | casi: comilla más chica y redondeada, texto de cita ~5 % más ancho |
| FAQ | casi: respuesta OK; la tarjeta de contacto sale ~17 px más alta y con texto más pesado |
| Instagram + footer | casi: "en vivo" OK; el botón WhatsApp del footer sale 32 px más ancho |

## Estado #1–#23

| # | Estado | Evidencia y comentario |
|---|---|---|
| 1 Badges | **Cerrado** | Recomendado rgb(28,109,130) turquesa y Tendencia rgb(36,72,58) verde, como el PDF (`cmp-02`) |
| 2 Cusco | **Cerrado** | Va en 2 líneas ("Machu Picchu y Valle / Sagrado", h=40) y ya no toca la flecha (`cmp-03`) |
| 3 Gastronomía | **Cerrado** | Glifo blanco sobre dorado profundo, consistente con el trío (`zoom-04-glifos-tiles`) |
| 4 Respuesta FAQ | **Cerrado** | La lista de preguntas calza con el PDF línea por línea (`cmp-07`) |
| 5 Texto de la cita | **Cerrado** | rgb(41,27,18), casi negro como el PDF |
| 6 WhatsApp | **Cerrado por decisión** | Blanco sobre #1faf5a en los dos botones |
| 7 4.º plan | **Cerrado** | "Camino Inca a Machu / Picchu, 4 días" |
| 8 Planes, tipos | **Cerrado** | Título y precio con el mismo ancho que el PDF |
| 9 Razones | **Cerrado** | Títulos del mismo ancho que el PDF (`cmp-06`) |
| 10 Vidrio del hero | **Parcial** | Ahora es translúcido, pero quedó **demasiado claro y azulado**. Muestra de color en la zona sin texto: PDF rgb(116,141,162), render rgb(139,178,222). En la tarjeta Machu Picchu: PDF rgb(90,115,125), render rgb(129,167,207). Además el `blur(15px)` **no llega al navegador**: el build (lightningcss) solo dejó `-webkit-backdrop-filter:blur(15px)` en `.hero__item:not(.is-active)` y quitó el `backdrop-filter` estándar, así que Chrome sigue usando el `blur(8px)` de la línea 155. (`zoom-01-hero-tarjetas`) |
| 11 Glifos | **Parcial** | Restaurant y account_balance calzan. El de **Trekking es relleno (montaña sólida)** y en el PDF es de contorno (dos picos solo con trazo) (`zoom-04-textos-trekking`) |
| 12 Instagram | **Cerrado** | "Síguenos y mira cada salida / en vivo" (`cmp-08b`) |
| 13 Botones fantasma | **Parcial** | El radio de 10 px está OK. El borde quedó **demasiado tenue**: en el PDF es un blanco casi pleno (~.85-.9, ~1.5 px) y en el render es .55 a 1 px. Se nota en "Ver destinos", "Consultar fechas" y "Planear mi viaje" (`zoom-01-botones-hero`, `zoom-04c-ghost-banda`, `cmp-02b`) |
| 14 Estrellas | **Cerrado** | 5 estrellas ocupan 71 px, igual que el PDF |
| 15 Cuerpo de la tarjeta de tour | **Abierto (no se tocó)** | El título va ~4 px más abajo y el divisor y "Desde" ~5 px más abajo. La tarjeta es más alta y empuja el Camino Inca ~5 px (`zoom-02-card1-cuerpo`) |
| 16 Precio del Inca | **Cerrado** | La caja mide ~85 px, igual que el PDF (`cmp-02b`) |
| 17 Título del Inca | **Cerrado** | Termina donde termina en el PDF |
| 18 Filtros | **Cerrado** | "Todos" mide 80 px, igual que el PDF, y el grupo va a ±4 px |
| 19 Testimonios | **Parcial (colateral)** | El avatar B y el gap de los pills están OK. La comilla a 40 px quedó **más chica y redondeada** que la del PDF (en el PDF es un "66" más cuadrado y alto), y la tarjeta perdió ~10 px de alto (256 px). Todo el bloque FAQ sube 12-14 px respecto al PDF (correlación dy=14) (`zoom-06-testimonio-1`) |
| 20 Experiencias, aire | **Cerrado** | Título, descripción y link a ±3 px |
| 21 Nav y PEN/ES | **Parcial** | "Inicio" está en x=299, OK. El toggle **sigue en 99 px contra 86** (opciones de 47 y 44 px). Como la nav se corrió +13 px, "Contacto" ahora termina ~12 px más a la derecha que en el PDF (`zoom-01-nav-switch`) |
| 22 Tipos -1 px | **Parcial** | La tarjeta de contacto de la FAQ mide 287 px, ~17 px más que el PDF. Las filas de teléfono y correo se ven más grandes y pesadas y van más separadas (`zoom-07-contacto-wa`). El botón WhatsApp del footer mide 273 px de ancho a 15 px de letra; en el PDF mide ~241 px |
| 23 Eyebrows | **REGRESIÓN** | En fondo claro está bien (#96690f). Pero la regla también pisa `.ip-eyebrow--dark`, y "Perú se vive caminando" (banda) y "Según tus días" (planes) salen en café apagado rgb(150,105,15) sobre el fondo oscuro. En el PDF son dorado brillante #E0A63A (`zoom-05-eyebrow-oscuro`, `cmp-04b`) |

## Hallazgos abiertos (🔴 → 🟡)

- [🔴] Banda y Planes › eyebrow sobre fondo oscuro @1440: se ve café apagado y casi no se lee. En el PDF es dorado brillante. Causa: `resources/css/inka.css:1073` (`.ip-eyebrow { color:#96690f }`, más abajo en el archivo) pisa `inka.css:288` (`.ip-eyebrow--dark`). Fix: `.ip-eyebrow:not(.ip-eyebrow--dark)` o volver a declarar `.ip-eyebrow--dark{color:var(--accent-on-dark)}` en el mismo bloque. → maquetación
- [🟡] Hero › tarjetas inactivas @1440: el vidrio quedó claro y azulado. `inka.css:1046`: subir el alfa a ~.42 y declarar el blur de modo que sobreviva al build (cambiar el `blur(8px)` de la línea 155 o usar solo `backdrop-filter`). → maquetación
- [🟡] Botones fantasma @1440: el borde quedó tenue. `inka.css:1043`: usar `rgb(255 255 255 / .85)`, y probar un borde de 1.5 px. → maquetación
- [🟡] Testimonios › comilla @1440: más chica y redondeada; la tarjeta perdió ~10 px y la FAQ sube 12-14 px. `inka.css:1038` (`.testi__quote 40px`; ver también `height:20px` en `inka.css:1018`): probar ~48 px o compensar con margen, y revisar el glifo/fuente de la comilla. El texto de la cita (16 px) además sale ~5 % más ancho que el PDF: probar ~15.2 px (`inka.css:496`). → maquetación
- [🟡] Header › PEN/ES @1440: 99 px contra 86. `inka.css:1082` no basta porque las opciones siguen en 47/44 px (revisar el contenido interno y `inka.css:669` gap/icono). La nav quedó ~12 px más ancha: probar `.nav__list{gap:28px}` (`inka.css:128`). → maquetación
- [🟡] Experiencias › glifo Trekking @1440: el render lo dibuja relleno y en el PDF es de contorno. `resources/views/components/glyph.blade.php:22` (`landscape`): usar la variante outlined. → maquetación
- [🟡] FAQ › tarjeta de contacto @1440: sale ~17 px más alta y con el texto pesado; footer › botón WhatsApp 32 px más ancho. `inka.css:1075` + `inka.css:657` (`.contact-card p a{min-height:44px}`: es lo que estira las filas de teléfono y correo; en desktop bajar a ~32 px y revisar el peso) y `.footer .btn--wa` (~13.5 px). → maquetación
- [🟡] Tours destacados › cuerpo de la tarjeta @1440: título y precio ~4-5 px más abajo (#15, sin tocar). `tour-card` / `inka.css`: reducir el gap entre la valoración y el título. → maquetación

## Residuales por fotos provisionales (no son de maquetación)
F1 (encuadre de Ica), F2 (foto 4+ días) y F3 (resolución del hero) siguen igual que en la pasada 3.

## Siguiente paso sugerido
Loop back a maquetación: el 🔴 de los eyebrows oscuros (1 línea) más los 7 🟡. Después, una pasada corta de ojo solo sobre hero, banda, planes, testimonios y FAQ.

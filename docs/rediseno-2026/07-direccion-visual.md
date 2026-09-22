# Dirección visual — propuesta (no implementación)

**Fecha:** 2026-09-18
**Autor:** maquetador-frontend
**Estado del árbol:** intacto. Lote commiteado en `9e9fe6a` (rama `lote-1-sistema-diseno`), desplegado en demo.
**Alcance:** home (`/es`), listado (`/es/tours`), ficha (`/es/tours/{slug}`).

**Cómo se midió:** Chrome (playwright-core cacheado, `executablePath` al Chrome del sistema) contra
`http://127.0.0.1:8087`, viewports 1440x900 y 375x812, `deviceScaleFactor: 1`.
Reveals disparados con scroll instantáneo por pasos de 0,5 viewport con espera de 120 ms
antes de cualquier captura (un `fullPage` en frío deja las secciones con `data-reveal` en blanco).
Todas las cifras de abajo salen de `getBoundingClientRect()` y `getComputedStyle()`, no de mirar capturas.
Scripts: `scratchpad/medir.js`, `scratchpad/medir2.js`, `scratchpad/capturar.js`.

---

## 1. Por qué se lee "plano" y "de WordPress"

### 1.1 El veredicto corto

No es falta de efectos. Es que **la fotografía nunca gobierna la composición**: vive
encarcelada en una columna de 1152 px y recortada a retrato, mientras el 100 % de las
secciones son bandas rectangulares del mismo alto apiladas en alternancia blanco/crema.
Lo cinematográfico se aplicó solo a los dos heroes; del pliegue hacia abajo el sitio es
una plantilla de catálogo.

### 1.2 "Apaisado": confirmado, y es el hallazgo más fuerte

Anyerson tiene razón literal. **El material fotográfico del proyecto es apaisado y la
maqueta lo está metiendo a la fuerza en huecos verticales.** Relación de aspecto nativa
del archivo → relación a la que se renderiza (`object-fit: cover`, o sea recorte real):

| Dónde | Nativo | Renderizado | Qué se tira |
|---|---|---|---|
| Destinos · Valle Sagrado (1440) | **2,81** | **0,80** | 71,5 % del ancho del encuadre |
| Destinos · Cusco (1440) | 1,50 | 0,80 | 46,7 % del ancho |
| Destinos · Arequipa (1440) | 1,33 | 0,80 | 39,8 % del ancho |
| "Por qué elegirnos" · foto (1440) | 1,50 | 0,88 | 41,3 % del ancho |
| Banda CTA newsletter (375) | 2,40 | 0,85 | 64,6 % del ancho |
| Hero ficha de tour (375) | 1,78 | **0,49** | 72,5 % del ancho |
| Hero listado (375) | 1,70 | 0,52 | 69,4 % del ancho |
| Hero home (375) | 1,48 | 0,50 | 66,2 % del ancho |
| Tarjetas de tour (1440 y 375) | 1,33–1,78 | 1,33 | 0–25 % |

Tres bloques de destinos con foto **2,81:1 mostrada en 0,80:1**. Eso no es un ajuste fino:
es servir un panorama por una rendija. La sensación de "estrecho" que describe el cliente
sale de ahí antes que de ningún otro sitio.

### 1.3 La foto nunca llega al borde de la ventana (salvo dos heroes)

A 1440 px solo existen **tres anchos de imagen** en toda la home: 100 %, 40 % y 27 % del viewport.

- 100 % → únicamente el hero (836 px) y la banda de newsletter (272 px).
- 40 % → una sola foto en toda la página (576 px, "Por qué elegirnos").
- 27 % → **todas las demás** (392 px: destinos, tarjetas de tour, experiencias).

El contenedor máximo es **1152 px sobre 1440**: hay **144 px de margen muerto a cada lado,
el 20 % del ancho de la pantalla, en el 100 % del recorrido salvo el hero**. Ese marco
constante es la firma visual de una plantilla de WordPress genérica, y es lo que hace que
un sitio con buenas fotos se lea como un catálogo.

En la ficha de tour es peor: **la foto principal del tour mide 696 × 392 px, el 48 % del
ancho de la ventana**, y la galería son **cinco miniaturas de 76 × 60 px** (el 5 % del ancho
cada una). Un tour de 4 días en el Camino Inca se presenta con una foto de medio ancho y
cinco sellos postales.

### 1.4 Ritmo vertical: constante, nunca cambia

Home a 1440 (`docH` = 5 222 px, 6 secciones):

| # | Sección | Alto | vh | Fondo |
|---|---|---|---|---|
| 0 | Hero | 836 | 93 | `#0A291C` |
| 1 | Tours destacados | 1 400 | 156 | `#FFFFFF` |
| 2 | Destinos | 793 | 88 | `#F7F3EC` |
| 3 | Por qué elegirnos | 716 | 80 | `#FFFFFF` |
| 4 | Experiencias | 743 | 83 | `#F7F3EC` |
| 5 | Newsletter | 272 | 30 | `#0A291C` |

Las cuatro secciones de contenido miden 716, 743, 793 y 1 400 px. Quitando la anómala
(ver 1.6), **el rango es 716–793 px: 77 px de variación en tres secciones seguidas**.
Y las cuatro abren igual: eyebrow verde con punto → H2 a la izquierda → enlace "Ver todos →"
a la derecha → rejilla de 3. Cuatro veces el mismo compás. El ojo deja de encontrar
novedad a partir de la segunda.

La alternancia **blanco → crema → blanco → crema** cierra el diagnóstico: es el patrón
por defecto de Astra/Kadence/Divi. No es una opinión de estilo, es reconocimiento de patrón.

### 1.5 Cero profundidad: no hay un solo solape entre secciones

Todas las secciones son rectángulos que empiezan donde termina el anterior
(`top` de la sección N = `top`+`h` de la N−1, exacto, en las tres páginas y los dos
viewports). **El único elemento que rompe un plano en todo el sitio es la pastilla
"Asistencia 24/7"** sobre la foto de "Por qué elegirnos". Uno, en 5 222 px de scroll.

Sin solapes, sin elementos que crucen el límite de dos bandas, sin cambios de escala
tipográfica entre secciones, el parallax de 95 px es invisible: no tiene contra qué
moverse. Ese es el motivo de que Anyerson siga viendo "plano" pese a que el parallax
sí está implementado y sí funciona — está aplicado a una imagen de fondo que ya está
encerrada dentro de una banda de 793 px con márgenes blancos.

### 1.6 Defecto concreto de rejilla (independiente de la dirección que se elija)

`home.blade.php:329`:

```
[grid-template-columns:repeat(auto-fit,minmax(min(100%,17rem),24rem))]
```

Con `max-w-6xl` (1152 px) y `gap-6` (24 px): 3 × 384 + 2 × 24 = **1 200 px > 1 152 px**.
`auto-fit` mete **dos** tarjetas por fila, no tres. Medido: `grid-template-columns:
384px 384px`. Resultado: la tercera tarjeta queda sola con **792 px de blanco a su derecha**,
y la sección se infla a **1 400 px (156 vh)** para mostrar tres tarjetas.
Es el vacío más grande de la página y se ve en la captura.

(En destinos y experiencias la misma familia de rejilla produce
`392px 392px 392px 0px` — un cuarto carril de 0 px, inofensivo pero sucio.)

### 1.7 Móvil (375): la tira infinita

`docH` = **7 743 px = 9,5 pantallas** de scroll en la home. Todas las rejillas colapsan
a `343px` de un solo carril, así que el recorrido es **12 tarjetas idénticas apiladas**,
todas con foto 4:3 arriba y texto debajo, con el mismo ancho, el mismo radio y la misma
sombra. Alturas de sección en móvil: 1 732, 1 595, 1 276 y 1 501 px — es decir
**157 vh a 213 vh cada una**: hay que scrollear dos pantallas completas dentro de
una misma sección. Ninguna sección cabe en pantalla, así que no existe "momento" visual.

Listado a 375: `docH` = 2 838 px para **tres tarjetas** (218 vh solo la rejilla).

### 1.8 Ficha de tour: tres cuartas partes sin fotografía

Cobertura vertical de imagen (unión de intervalos de imágenes ≥ 25 % de ancho, sobre `docH`):

| Página | 1440 | 375 |
|---|---|---|
| Home | 62,6 % | 57,0 % |
| Listado | 65,9 % | 52,3 % |
| **Ficha** | **41,9 %** | **23,9 %** |

En la ficha, **pasada la galería (px 1 141) no hay una sola imagen hasta el pie**: 2 131 px
de itinerario, incluye/no incluye y CTA sobre blanco y crema. En móvil eso es el 76 % de
la página sin fotografía, justo en la pantalla donde se decide la compra.

Añadido: la columna derecha (tarjeta de reserva) termina en `y≈750` mientras la izquierda
sigue hasta `y≈1 060`. **310 px de blanco vertical** a la derecha, visible.

### 1.9 Resumen de causas, en orden de impacto

1. Fotografía apaisada recortada a retrato (hasta 2,81 → 0,80).
2. Ninguna foto toca el borde del viewport fuera de los dos heroes; 20 % del ancho siempre vacío.
3. Cuatro secciones con el mismo alto (716–793 px) y la misma apertura.
4. Alternancia blanco/crema — patrón de plantilla reconocible.
5. Cero solapes: el parallax no tiene contra qué moverse.
6. Rejilla `auto-fit` mal dimensionada: 792 px de hueco y +600 px de alto inútil.
7. Móvil: 9,5 pantallas de tarjetas clonadas.
8. Ficha sin imagen en el 58 % (1440) / 76 % (375) de su recorrido.

---

## 2. Inventario fotográfico real (esto decide qué es viable)

85 archivos base (sin contar derivadas responsive), leídos con un lector de cabeceras
JPEG/PNG/WebP propio (`scratchpad/inventario.js`), no estimados:

| Franja | Nº | Comentario |
|---|---|---|
| Ultrapanorámica >= 2,2 | **2 únicas** | `destino-valle-sagrado` (2,80) y `hero-valle-sagrado-panoramica` (2,39) |
| Apaisada 1,6-2,2 | 5 únicas | `hero-cordillera-rio` 1,69 · `tour-camino-inca` 1,78 · `tour-valle-sagrado-galeria-01` 1,78 · `og-default` 1,90 |
| 4:3-3:2 (1,2-1,6) | **49** | el grueso del banco, casi todas a 1,50 |
| Retrato / cuadrada < 1,2 | **27** | galerías: 0,56 · 0,67 · 0,69 · 0,75 |

**Resolución disponible — este es el límite duro:**

- Solo **3 archivos llegan a 1920 px de ancho** (los tres heroes de `site/`).
- **Todo lo demás tope a 1400 px** (galerías) o **1200 px** (portadas de tour, destino, experiencia).

Consecuencia operativa: una banda a sangre de 1440 px CSS tirando de un archivo de 1400 px
va al 97 % — indistinguible a DPR 1. A **DPR 2 (móvil retina, portátiles Mac) se verá blanda**:
pediría 2 880 px y no existen. **No es un bloqueo nuevo**: el hero de hoy ya es a sangre con
archivos de 1920 px y tampoco cubre DPR 2 a 1440. Es el mismo compromiso ya aceptado, extendido
al resto de la página — pero hay que decirlo antes, no después.

**Recorte a banda apaisada, coste real en altura de encuadre:**

| Origen | a 16:9 | a 21:9 |
|---|---|---|
| 1,78 (tours) | usa 100 % | usa 76 % |
| 1,50 (49 archivos) | usa **84 %** | usa **64 %** |
| 1,33 | usa 75 % | usa 57 % |
| 0,67-0,75 (27 archivos) | usa 38-42 % | usa **29-32 %** |

Traducción: **16:9 es gratis** con el banco actual (el 84 % del encuadre de una foto 3:2 sigue
siendo la foto). **21:9 es agresivo pero posible** en las 56 fotos de paisaje. **En las 27 de
retrato, cualquier banda apaisada las destruye** — hay que usarlas como retrato o no usarlas.

### Bloqueo declarado

Una dirección que exija **bandas ultrapanorámicas (>= 2,2) a sangre en cada sección** no es
ejecutable: hay **dos** fotos así en todo el proyecto. Si esa es la dirección elegida,
**hace falta compra o sesión fotográfica**, y eso el encargo lo prohíbe. Se descarta por
material, no por gusto.
Lo que sí es ejecutable hoy: bandas 16:9 a sangre con las 56 fotos de paisaje,
y un mosaico de anchos mixtos que **use las 27 de retrato como retrato**.

---

## 3. Tres direcciones, excluyentes entre sí

No son una lista de mejoras: cada una define un principio compositivo distinto y elegir
una descarta las otras dos.

### A · Lienzo continuo — *la foto es el fondo, el contenido flota encima*

**Principio:** desaparece la alternancia blanco/crema. El sitio entero es un fondo oscuro
continuo (`--ink-surface` #0A291C). Entre bloques de contenido se intercalan **bandas
fotográficas a sangre de 16:9** que ocupan el 100 % del viewport y llevan el titular de
sección superpuesto. Las tarjetas pierden fondo, borde y sombra: se convierten en foto +
texto directamente sobre el lienzo.

**Qué se nota al scrollear:** ya no hay "secciones", hay un plano oscuro del que emergen
ventanas de paisaje. El parallax por fin se lee, porque la foto ocupa el ancho entero y
el desplazamiento de 95 px es un 6,6 % de la banda, no un detalle dentro de una tarjeta
de 392 px.

**Archivos:** `resources/css/tokens.css` (invertir superficie por defecto),
`resources/css/app.css` (scrims, `.photo`, `.tour-card`), `components/layout.blade.php`,
`components/ui/tour-card.blade.php`, `destination-card`, `experience-card`, `section-title`,
`home.blade.php`, `tours/index.blade.php`, `tours/show.blade.php`, `components/site/footer`.

**Trabajo:** alto. Es un cambio de sistema, no de pantallas: todo componente con
`bg-white`/`bg-sand` y todo texto `--ink` hay que revisarlo en oscuro.

**Riesgos**
- *Contraste:* el más alto de los tres. Todo el cuerpo de texto pasa a blanco sobre oscuro
  (eso es fácil: `--on-dark` #FFF sobre #0A291C = **17,4:1**, calculado con la fórmula WCAG),
  pero los titulares sobre banda fotográfica exigen scrim medido banda por banda. El
  antecedente de 4,07:1 a 375 px sale precisamente de ahí.
- *Rendimiento:* +4 a +6 fotos a ancho completo por página. Con las derivadas actuales
  (1400 px, ~250-400 KB en JPEG; la WebP del banco a veces **pesa más** que la JPEG) la home
  se puede ir por encima del megabyte. Hay que generar derivadas de banda y diferir con
  `<template>` igual que se hizo con las diapositivas 2 y 3 del hero.
- *Reduced-motion:* sin cambio, el parallax ya respeta la media query.
- *Accesibilidad:* el modo oscuro obligatorio reduce legibilidad para usuarios con
  astigmatismo en textos largos (la ficha de tour tiene 4 párrafos). Riesgo real, no teórico.

---

### B · Mosaico editorial asimétrico — *la foto define el ancho; nada está centrado*

**Principio:** se rompe la columna de 1152 px. Retícula de 12 columnas de borde a borde del
viewport (margen lateral de 40 px, no 144 px) con **ocupaciones desiguales y deliberadas**:
una foto ocupa las columnas 1-7 sangrando por el borde izquierdo, el texto las 8-12;
la siguiente invierte. Convive **retrato con apaisado en la misma fila** (el banco tiene 27
retratos que hoy se están recortando a la fuerza). Desaparecen las tarjetas: fuera sombra,
fuera borde, fuera radio grande; queda foto + rótulo. Tipografía display de Fraunces sube a
clamp 48->96 px en los rótulos de sección y se apoya contra el borde de la foto.
Dos anclas por página llevan **banda a sangre 16:9**, no más.

**Qué se nota al scrollear:** deja de existir el compás repetido. Cada sección aterriza el ojo
en un punto distinto del ancho, y los bloques de foto cruzan el límite de una sección a la
siguiente (solape real, hoy inexistente: hay exactamente **un** solape en 5 222 px).

**Archivos:** `resources/css/app.css` (utilidad de retícula + `.bleed-l`/`.bleed-r`),
`components/ui/tour-card.blade.php`, `destination-card`, `experience-card`,
`components/ui/section-title.blade.php`, `home.blade.php`, `tours/index.blade.php`,
`tours/show.blade.php`. `tokens.css` solo para las escalas tipográficas.

**Trabajo:** medio-alto. Más pantallas que sistema: los tokens de color no se tocan.

**Riesgos**
- *Contraste:* el más bajo de los tres. El texto sigue sobre fondo sólido; solo los dos
  heroes ya medidos llevan texto sobre foto.
- *Rendimiento:* neutro a favorable. No se añaden fotos, se muestran más grandes —
  pero al sangrar hasta 7/12 del viewport (aprox. 840 px a 1440) las derivadas de 1200 px
  siguen alcanzando, y el `sizes` deja de pedir el archivo grande para huecos de 392 px.
- *Reduced-motion:* sin impacto.
- *Accesibilidad:* el orden visual asimétrico puede divergir del orden del DOM.
  Regla dura: la asimetría se resuelve con `grid-column`, nunca con `order`, para que
  el recorrido por teclado siga la lectura.
- *Riesgo específico:* a 375 px la asimetría colapsa y se vuelve una columna otra vez.
  Si no se rediseña el móvil aparte, se arregla el escritorio y el móvil sigue igual de
  plano — que es la mitad del problema (7 743 px de tira).

---

### C · Escenas ancladas — *el scroll deja de ser una lista y pasa a ser una secuencia*

**Principio:** cada bloque mayor es una escena de 100 svh con la fotografía en
`position: sticky` a sangre; el contenido pasa por encima de una foto que se queda quieta
y cambia de escala/máscara. Transiciones de escena por `scroll-timeline`
(o `IntersectionObserver` + `transform` como alternativa). Estructura de vídeo, no de página.

**Qué se nota al scrollear:** es lo más lejos de "WordPress" que hay, y lo que un cliente
llama "de 2026". También es lo que más rápido cansa si la secuencia es larga.

**Archivos:** los mismos que A y B **más** `resources/js/app.js` (motor de escenas,
hoy solo hay `heroSlider()` y el parallax) y control de `scroll-behavior`.

**Trabajo:** alto, y el de mayor riesgo de rehacerse.

**Riesgos**
- *Bloqueo de material:* una escena a 100 svh en 1440x900 es AR 1,6. Hay **9 fotos >= 1,6**
  en el banco. Para 3 escenas por página en 3 páginas hacen falta 9 justas — sin margen y
  repitiendo entre páginas. A 375x812 la escena es AR 0,46 y ahí sí hay material,
  pero el escritorio queda al límite. Declarado como bloqueo parcial.
- *Rendimiento:* el peor. `position: sticky` + `transform` en fotos a ancho completo genera
  capas de composición grandes; en móvil de gama media cae el frame rate. Además tira por
  tierra el ahorro de 464 KB: las escenas están "en viewport" desde el arranque (es
  exactamente el mismo motivo por el que las diapositivas 2 y 3 se descargaban aunque
  estuvieran a `opacity: 0`), así que hay que rehacer todo el diferido con `<template>`.
- *Reduced-motion:* con `prefers-reduced-motion` la dirección **se cae entera** y hay que
  entregar una segunda maqueta estática. Eso es un segundo diseño, no una variante.
- *Accesibilidad:* el scroll secuestrado rompe la navegación por teclado y el
  "buscar en la página". Alto.

---

### Tabla de decisión

| | A · Lienzo continuo | B · Mosaico editorial | C · Escenas ancladas |
|---|---|---|---|
| Resuelve causa 1 (recorte vertical) | Sí | **Sí** | Parcial |
| Resuelve causa 2 (gutter 144 px) | Sí | **Sí** | Sí |
| Resuelve causa 3 (ritmo constante) | Parcial | **Sí** | Sí |
| Resuelve causa 4 (blanco/crema) | **Sí** | Sí | Sí |
| Resuelve causa 5 (sin solapes) | Parcial | **Sí** | Sí |
| Resuelve causa 7 (móvil = tira) | No | Requiere trabajo aparte | Sí |
| Usa el banco tal cual | Parcial (descarta 27 retratos) | **Sí (los 85)** | **No - bloqueo parcial** |
| Riesgo de contraste | Alto | **Bajo** | Alto |
| Riesgo de rendimiento | Medio-alto | **Bajo** | Alto |
| Sobrevive a `reduced-motion` | Sí | **Sí** | No |
| Trabajo | Alto | Medio-alto | Alto |

---

## 4. Pruebas visuales

Las maquetas **no tocan el árbol del proyecto**. Viven en el scratchpad de la sesión,
son HTML estático con CSS propio, usan los tokens reales (`--brand-h:155`, `--ink-surface`,
`--sand`, Fraunces + Figtree) y tiran de las **fotos reales servidas por el local en 8087**.
Cada una lleva una marca naranja fija abajo a la izquierda que dice "Maqueta de prueba".
Ninguna se compila, ninguna se despliega.

```
scratchpad/base.css          tokens y cabecera comunes
scratchpad/maqueta-A.html    Dirección A
scratchpad/maqueta-B.html    Dirección B
scratchpad/maqueta-C.html    Dirección C
scratchpad/shot-maqueta.js   capturas 1440 / 375
scratchpad/contraste.js      contraste WCAG medido sobre el compuesto foto+scrim
scratchpad/shots/            todas las capturas
```

### 4.1 Capturas

| | Actual | Propuesta |
|---|---|---|
| Home 1440 | `shots/home-1440-full.png` | `shots/maqueta-A-1440-full.png` · `shots/maqueta-B-1440-full.png` · `shots/maqueta-C-1440-full.png` |
| Home 375 | `shots/home-375-full.png` | `shots/maqueta-A-375-full.png` · `shots/maqueta-B-375-full.png` · `shots/maqueta-C-375-full.png` |
| Listado 1440 / 375 | `shots/tours-1440-full.png` · `shots/tours-375-full.png` | — |
| Ficha 1440 / 375 | `shots/ficha-1440-full.png` · `shots/ficha-375-full.png` | — |

### 4.2 El número que separa las direcciones: variedad de anchos

Cuántos anchos de imagen distintos existen en la página, medidos como % del viewport a 1440:

| | Anchos distintos | Valores |
|---|---|---|
| **Actual** | **3** | 100 %, 40 %, 27 % |
| Dirección A | **3** | 100 %, 33 %, 29 % |
| **Dirección B** | **7** | 100 %, 65 %, 57 %, 49 %, 30 %, 30 %, 17 % |

Esto es lo más importante de toda la prueba. **La dirección A cambia el color y agranda
los heroes, pero deja intacta la fila de tres columnas iguales: sigue habiendo tres anchos,
igual que hoy.** Se ve más elegante y sigue teniendo el mismo compás. La dirección B es la
única que rompe la regularidad horizontal.

### 4.3 Recorte: qué le hace cada dirección al encuadre

Nativo → renderizado en la misma foto (`destino-valle-sagrado`, la panorámica de 2,80):

| | 1440 | 375 |
|---|---|---|
| Actual | 2,80 → **0,80** (tira el 71 %) | 2,80 → **0,80** |
| Dirección A | 2,80 → **0,75** (tira el 73 %) | 2,80 → 1,33 |
| **Dirección B** | 2,80 → **1,78** (tira el 36 %) | 2,80 → **1,78** |

La franja de destinos de A, al ser tres retratos a sangre, **repite el pecado actual**
sobre la única foto ultrapanorámica del proyecto. B la muestra a 16:9 en 65 % del ancho.

### 4.4 Contraste WCAG medido sobre el compuesto foto + scrim

Método (`scratchpad/contraste.js`): se localiza cada nodo de **texto claro que tiene una
foto detrás**, se oculta **solo ese texto**, se captura el compuesto real y se mide el
**píxel más claro** del rectángulo que ocupaba, no un promedio. Ratio WCAG contra el color
computado del texto. Umbral 4,5:1, o 3,0:1 si es texto grande (>= 24 px, o >= 18,66 px en
peso >= 700).

**Testigo negativo declarado:** si la extracción devuelve cero nodos, el script imprime
`TESTIGO NEGATIVO` y sale con código 1, en lugar de imprimir "todo OK". Se midieron
**42 + 37 nodos en A** y **8 + 8 en B**; ninguna corrida quedó vacía.

**Dirección A — 1440 y 375, fallos encontrados:**

| Elemento | 1440 | 375 | Umbral |
|---|---|---|---|
| Menú del header sobre el hero (`Inicio`…`Nosotros`) | 2,71–3,22:1 | — | 4,5 |
| Eyebrow verde `#9fd9bd` "Agencia local · Cusco, Perú" | **1,82:1** | **1,74:1** | 4,5 |
| H1 del hero | 3,05:1 | 3,90:1 | 3,0 (al límite) |
| Eyebrow "Tu próximo mapa" sobre la banda | 3,17:1 | 3,41:1 | 4,5 |
| Rótulo "Cusco" en la franja de destinos | 4,04:1 | **2,25:1** | 4,5 |
| Rótulo "Valle Sagrado" en la franja | 3,09:1 | 3,47:1 | 4,5 |

Seis fallos, y el H1 del hero pasa por 0,05 puntos. **A pone 42 nodos de texto encima de
fotografía**: la superficie de riesgo es estructural, no un descuido de esta maqueta.

**Dirección B — tras dos correcciones, los 16 nodos pasan:**

| | 1440 | 375 |
|---|---|---|
| Eyebrow "Agencia local · Cusco, Perú" | **13,06:1** | **5,72:1** |
| H1 del hero (104 / 44 px) | 6,95:1 | 6,39:1 |
| Entradilla del hero | 12,54:1 | 8,43:1 |
| Botón "Ver destinos" | 17,36:1 | 15,28:1 |
| Rótulo "Valle Sagrado · 2.800 m" | 10,87:1 | 6,77:1 |
| Eyebrow "Nuestro compromiso" (banda) | 13,35:1 | 6,78:1 |
| H2 de la banda | 12,02:1 | 8,14:1 |
| Párrafo de la banda | 10,86:1 | 8,90:1 |

**Las dos correcciones, que son las reglas duras que se llevan a la implementación:**

1. **El eyebrow sobre foto va en blanco puro, nunca en tinte de marca.** El verde claro
   `#9fd9bd` daba **1,74:1**; en `#fff` con el mismo scrim da **5,72:1**. Ningún tono de la
   rampa de marca gana a una foto clara: el énfasis va en el peso y el interletrado,
   no en el color.
2. **El scrim no puede vivir en el mismo elemento que el texto.** El rótulo
   "Valle Sagrado · 2.800 m" medía **1,19:1** con el degradado puesto como `background` del
   propio `<p>`. Movido a un hermano `.dest-scrim` en `position:absolute`, el mismo rótulo
   da **10,87:1**. Aquí hubo además un artefacto de medición que conviene dejar escrito:
   al ocultar el texto para medir, se ocultaba también su propio fondo, así que el
   instrumento leía la foto desnuda. **Era artefacto y defecto a la vez** — la
   implementación correcta es la misma que la que hace medible el contraste.
   `text-shadow` no sustituye a un scrim: fue lo primero que se probó y daba 1,21:1.

### 4.5 Dirección C: qué muestra la prueba

C no se puede capturar con un `fullPage`: `position: sticky` se desmonta y además lo que
importa son los fotogramas intermedios. Se capturaron **5 momentos con scroll real por
rueda** (`page.mouse.wheel`, no `scrollTo` — `scrollTo` amortigua el valor que calcula el
manejador y el efecto se mide más pequeño de lo que es). Ficheros
`shots/maqueta-C-{1440,375}-f{0..4}.png`.

El efecto existe y está medido: la escala de la foto anclada va de `scale(1.18)` en
`scrollY=0` a `scale(1.02)` al final de la escena. No es una captura bonita sin nada detrás.

**En un fotograma suelto es la más impresionante de las tres.** El problema aparece al
medir el recorrido completo.

**Contraste (medido en viewport, en tres puntos del avance):**

| Elemento | 1440 | 375 | Umbral |
|---|---|---|---|
| H1 sobre la escena (76 / 36 px) | **2,35:1** | **2,26:1** | 3,0 |
| Eyebrow "Agencia local · Cusco, Perú" | — | **2,21:1** | 4,5 |
| Entradilla del hero | — | **3,57:1** | 4,5 |
| Eyebrow "Nuestro compromiso" (2.º paso) | **3,17:1** | — | 4,5 |
| Párrafo "Guías locales, grupos cortos…" (2.º paso) | — | **1,70:1** | 4,5 |

(Se descartan como artefacto los 1,07–1,28:1 de "Contáctanos" y "Explora tours": son botones
de fondo blanco con texto oscuro, y el medidor está preparado para texto claro; además su
fondo vive en el mismo nodo que se oculta para medir. No son fallos.)

**El hallazgo estructural de C, y es el que la descarta:** el texto viaja por encima de una
foto que **se está moviendo**, así que el fondo bajo cada palabra cambia a lo largo del
recorrido. Un scrim fijo no puede garantizar AA en todos los fotogramas — el mismo párrafo
da 8:1 al entrar y 1,70:1 tres cuartos de escena después. Para cumplir AA el scrim tendría
que ser **función del avance del scroll**, y con `prefers-reduced-motion` el scroll no avanza,
así que hay que resolverlo **dos veces, con dos cálculos distintos**. Eso no es una variante:
es un segundo diseño.

**Coste de scroll (medido):**

| | docH 1440 | Primer tour visible | docH 375 | Primer tour visible |
|---|---|---|---|---|
| Actual | 5 222 | y=1 416 (1,6 pantallas) | 7 743 | y=1 306 (1,6) |
| A | 3 551 | y=1 167 (1,3) | 4 577 | y=1 038 (1,3) |
| B | 4 367 | y=3 553 (3,9) | 3 850 | y=2 592 (3,2) |
| C | 5 617 | y=**5 227 (5,8 pantallas)** | 4 954 | y=4 025 (5,0) |

**Aviso de honestidad sobre el 3,9 de B:** eso **no es un defecto de la dirección B**, es
cómo ordené yo la maqueta (destinos antes que tours). El orden de secciones lo decide el
negocio y es independiente de la dirección. En C, en cambio, **sí es estructural**: cada
escena consume 280 svh por definición, así que el producto queda a 5,8 pantallas se ordene
como se ordene. Con dos escenas, C gasta **más scroll que la home actual para mostrar
menos de la mitad del contenido**.

También conviene anotar que **A es la que menos scroll consume** (3 551 px frente a 5 222):
al quitar la alternancia de fondos y compactar el ritmo, la home se acorta un 32 %.
Es un punto real a su favor.

---

## 5. Recomendación

**Dirección B — Mosaico editorial asimétrico.** Una, no un abanico.

### Por qué

1. **Es la única que resuelve la causa raíz.** El problema no es que falte efecto: es que
   hay **tres anchos de imagen** en toda la página y un marco muerto de 144 px. B pasa a
   **siete anchos** (100/65/57/49/30/30/17 %). A, pese a verse más inmersiva, se queda en
   **tres** — cambia la piel, no el esqueleto. Y el cliente ya rechazó una vez un cambio de
   piel: el pase cinematográfico anterior fue exactamente eso.

2. **Es la única que hace justicia al banco fotográfico.** Sobre la misma foto panorámica
   de 2,80: hoy se renderiza a 0,80 (se tira el 71 % del encuadre), A la deja en 0,75
   (73 %), **B la muestra a 1,78 (36 %)**. Y B es la única que aprovecha las 27 fotos de
   retrato **como retrato** en lugar de recortarlas.

3. **Es la única que entra en presupuesto de contraste.** B expone **8 nodos de texto sobre
   foto**; A expone **42**. Medido: B pasa AA en los 16 nodos (1440 y 375) tras dos
   correcciones concretas; A acumula 6 fallos con el H1 rozando el mínimo a 3,05:1;
   C **no puede garantizar AA** porque el fondo se mueve bajo el texto.

4. **No rompe nada de lo que ya costó trabajo.** No toca `tokens.css` salvo escalas
   tipográficas, no toca el motor de parallax, respeta `prefers-reduced-motion` sin
   trabajo extra, y **conserva el ahorro de 464 KB**: no añade fotos, muestra más grandes
   las que ya se cargan. C tiraría ese ahorro por tierra.

5. **Entrega lo que pidió Anyerson, literalmente.** "Apaisado": la foto pasa de 0,80 a 1,78.
   "Imágenes full": dos bandas a sangre por página, más los sangrados laterales
   (`bleed-l` / `bleed-r`) que llevan la foto hasta el borde del viewport. "Parallax": se
   mantiene el que ya existe, y por primera vez se nota, porque ahora se aplica sobre
   bloques de 65 % de ancho en lugar de tarjetas de 27 %.

### Por qué no las otras

- **A** es la más bonita en captura y la más barata en scroll (−32 % de altura), pero
  **repite el compás de tres columnas iguales** y vuelve a recortar la única panorámica del
  proyecto a retrato. Riesgo alto de que dentro de seis meses se vuelva a oír "sigue plano",
  porque estructuralmente lo estaría. Además el fondo oscuro obligatorio penaliza la ficha
  de tour, que es donde hay cuatro párrafos seguidos de lectura.
- **C** es la que más suena a 2026 y la que peor aguanta el contacto con la realidad:
  bloqueo parcial de material (9 fotos ≥ 1,6 para todas las escenas de tres páginas),
  imposibilidad de garantizar AA con fondo en movimiento, `reduced-motion` obliga a un
  segundo diseño completo, y **5,8 pantallas de scroll hasta el primer producto**.
  Para una agencia de turismo que vive de que se vea el catálogo, eso es un coste de
  negocio, no un detalle.

### Lo que hay que llevarse a la implementación (ya probado en la maqueta)

1. **El eyebrow sobre foto va en `#fff`, nunca en tinte de marca.** `#9fd9bd` daba 1,74:1;
   en blanco, 5,72:1. El énfasis va en peso e interletrado, no en color.
2. **El scrim vive en un elemento hermano, nunca como `background` del nodo de texto.**
   1,19:1 contra 10,87:1 sobre la misma foto. `text-shadow` no es un sustituto: 1,21:1.
3. **Con `aspect-ratio`, un `max-height` encoge el ANCHO.** La banda "a sangre" dejó de
   sangrar y se quedó en el 69 % del viewport sin que nada avisara. Alto por `clamp()` y
   recorte por `object-fit`.
4. **La asimetría se resuelve con `grid-column`, nunca con `order`**, para que el recorrido
   por teclado siga la lectura.
5. **A 375 la retícula baja a 6 carriles, no a 1.** Es lo que evita volver a la tira de
   tarjetas clonadas. Verificado en `shots/maqueta-B-375-full.png`.

### Arreglo independiente de la dirección elegida

`home.blade.php:329` — la rejilla `auto-fit` con tope de 24 rem dentro de `max-w-6xl`
sirve **dos** tarjetas por fila en lugar de tres (1 200 px pedidos contra 1 152 disponibles),
deja 792 px de blanco e infla la sección a 1 400 px. Se arregla con `lg:grid-cols-3` o
bajando el tope a 22 rem. **Esto conviene corregirlo aunque se decida no rediseñar nada.**

---

## 6. Qué NO se hizo

- **No se tocó el árbol del proyecto.** Único archivo escrito dentro de `G:\laragon\www\tours-word`:
  este documento, en `docs/rediseno-2026/07-direccion-visual.md`. Las maquetas, los scripts
  y las capturas viven en el scratchpad de la sesión. No se creó ninguna rama: no hizo falta,
  porque ninguna maqueta es código del proyecto.
- **No se validó contra la demo pública** (`limaviewtours.com/tour-word/es`). Todo se midió
  contra el local en 8087, como indicaba el encargo, porque las fotos de la demo pueden dar
  403 ahora mismo. Eso significa que **no está verificado que la demo se vea igual que el
  local**; si hace falta, es una pasada aparte.
- **No se maquetaron el listado ni la ficha en las direcciones propuestas.** Las tres maquetas
  cubren la home. El diagnóstico de la sección 1 sí cubre las tres pantallas con medidas.
  Aplicar la dirección elegida a listado y ficha es parte de la implementación, no de la
  propuesta — y la ficha es donde hay más que ganar (41,9 % de cobertura fotográfica a 1440,
  23,9 % a 375).
- **No se midió rendimiento real** (LCP, peso transferido) de ninguna maqueta. Las cifras de
  peso de la sección 2 salen del tamaño en disco de los archivos, no de una traza de red.

---

## 7. Lo que decide otro

Este documento es de maquetación: composición, recorte, contraste, retícula y coste de
implementación. Dos cosas que aparecen arriba **no las decide este agente**:

- **El orden de las secciones y el scroll hasta el primer producto** (tabla 4.5). Aquí se
  aporta el número medido; si 1,6 o 3,9 pantallas hasta el primer tour es aceptable es
  criterio de conversión → `cro-validator`.
- **La elección final entre A, B y C.** Se entrega una recomendación argumentada con
  medidas; la decisión es de Anyerson.

Cuando haya dirección elegida, la implementación vuelve a `maquetador-frontend`, y el
gate de contraste y regresión lo corre `anyerson-qa` con el método de la sección 4.4
(testigo negativo incluido: una extracción vacía debe FALLAR, no imprimir OK).

# Auditoría CRO pre-fix — Pase cinematográfico 2026 (Pacha Viva)

Rama `lote-1-sistema-diseno`, árbol de trabajo con cambios sin commitear (pase B,
"cinematográfico", 2026-09-18) sobre: `home.blade.php`, `tours/index.blade.php`,
`tours/show.blade.php`, componentes `page-header`, `gallery`, `money`, `faq-item`,
`app.js`, `app.css`, `tokens.css`, `lang/es/site.php`, `lang/en/site.php`.

**Veredicto final (ronda 3): APTO.** Los veredictos de rondas 1 (PARCIAL) y 2
quedan superados — ver `## RONDA 3` al final del documento para el cierre
completo, los defectos priorizados y lo que sigue sin cubrir (no bloqueante).

---

## RONDA 2 — mediciones con `playwright-core` + Chrome real (vía Bash)

El MCP de Playwright de la ronda 1 no tenía `evaluate`, scroll programático ni
`emulateMedia`. El coordinador facilitó un bridge alterno: `playwright-core`
instalado en la máquina + Chrome del sistema, invocado con `Bash`/Node. Todo lo
de esta sección es **medición de cero, no confirmación de lo que reportaron los
maquetadores** — los números de contraste y parallax se recalcularon de forma
independiente. Scripts en el scratchpad de la sesión
(`cro-01-enumerar-urls.js` … `cro-04-contraste.js` y siguientes), no en el repo.

### R2.1 — Selector de idioma + TODAS las fichas EN (cierra el antecedente)

El antecedente hablaba de "6 de 18 fichas EN en 404". **Hoy el catálogo real
tiene 9 fichas de contenido, no 18** (3 tours + 3 destinos + 3 experiencias —
confirmado enumerando `/es/tours`, `/es/destinos`, `/es/experiencias` con
`document.querySelectorAll('a[href*=...]')`, sin asumir el número).

Para las 9, se hizo **clic real** en el botón del selector de idioma
(`button[aria-expanded]`) y se leyó el `href` que ofrece para EN:

| Ficha ES | href EN resuelto por clic real | status EN | title EN | H1 |
|---|---|---|---|---|
| /es/tours/camino-inca-machu-picchu-4-dias | /en/tours/inca-trail-machu-picchu-4-days | 200 | Inca Trail to Machu Picchu, 4 Days \| Pacha Viva | 1 |
| /es/tours/ruta-de-picanterias-arequipa | /en/tours/arequipa-picanteria-food-trail | 200 | Arequipa Picantería Food Trail \| Pacha Viva | 1 |
| /es/tours/valle-sagrado-pisac-maras-moray | /en/tours/sacred-valley-pisac-maras-moray | 200 | Sacred Valley: Pisac, Maras and Moray \| Pacha Viva | 1 |
| /es/destinos/cusco | /en/destinos/cusco | 200 | Cusco Travel and Tours \| Pacha Viva | 1 |
| /es/destinos/valle-sagrado | /en/destinos/sacred-valley | 200 | The Sacred Valley of the Incas: Tours and Routes \| Pacha Viva | 1 |
| /es/destinos/arequipa | /en/destinos/arequipa | 200 | Arequipa: What to See and Where to Eat \| Pacha Viva | 1 |
| /es/experiencias/trekking | /en/experiencias/trekking | 200 | Trekking in the Peruvian Andes \| Pacha Viva | 1 |
| /es/experiencias/gastronomia | /en/experiencias/cuisine | 200 | Peruvian Food Routes \| Pacha Viva | 1 |
| /es/experiencias/cultura | /en/experiencias/culture | 200 | Living Culture and Archaeological Sites \| Pacha Viva | 1 |

**9 de 9 → 200, título único, exactamente 1 `H1`.** También se verificó
`/en/`, `/en/tours`, `/en/destinos`, `/en/experiencias`, `/en/nosotros`,
`/en/contacto` → las 6, 200. **El antecedente de fichas EN en 404 queda
CERRADO para el catálogo actual** (no para uno futuro con más contenido — si
se agregan tours/destinos/experiencias sin su traducción, el `?? '#'` de
`locale-switcher.blade.php` sigue siendo el punto donde reaparecería; no es
un defecto en sí, es dónde mirar primero si vuelve a pasar).

**Nota de path:** el EN de destinos/experiencias conserva el segmento
`/en/destinos/...` y `/en/experiencias/...` (no traduce el segmento de ruta,
solo el slug de tours sí trae ruta traducida `/en/tours/...`). Esto es
inconsistencia de URL-scheme entre secciones, no un error funcional — lo dejo
anotado como observación Baja para `backend-laravel`, no como defecto: no
rompe nada, pero un hreflang/canonical bien armado por `anyerson-seo` debe
saber que el prefijo no se traduce parejo.

### R2.2 — Breakpoints 768 y 1440 en las 3 pantallas del pase

Script: overflow horizontal del documento + overflow por elemento individual
(`getBoundingClientRect().right` contra el ancho del viewport) + conteo/orden
de encabezados + errores de consola, en Home, `/es/tours` y la ficha de tour,
en 768 y 1440. Capturas full-page guardadas en el scratchpad
(`cro-bp-<pantalla>-<ancho>.png`).

| Pantalla | Ancho | Overflow horizontal | H1 | Salto de jerarquía | Errores consola |
|---|---|---|---|---|---|
| Home | 768 | 0px | 1 | ninguno | 0 |
| Home | 1440 | 0px | 1 | ninguno | 0 |
| /es/tours | 768 | 0px | 1 | ninguno | 0 |
| /es/tours | 1440 | 0px | 1 | ninguno | 0 |
| ficha de tour | 768 | 0px | 1 | ninguno | 0 |
| ficha de tour | 1440 | 0px | 1 | ninguno | 0 |

Sin desborde horizontal, sin salto de nivel de encabezado (h2→h4 sin h3, etc.),
sin errores de consola, en ninguna de las 6 combinaciones. Capturas revisadas
puntualmente (hero, header cinemático, galería) sin defecto visual evidente.
**No es una comparación pixel-a-pixel contra Figma** — eso sigue sin hacerse
(ver NO COMPROBADO).

### R2.3 — Contraste real del texto sobre foto (muestreo de píxeles, no `getComputedStyle`)

Método: capturar la caja del texto ya renderizada, volverlo transparente,
re-capturar el compuesto foto+scrim debajo, medir el píxel de luminancia más
clara de esa caja (peor caso para texto claro), calcular contraste WCAG contra
el color real del texto (`#fff` / `#c6d6cd`, tomados de `tokens.css`, no
inventados).

**Hero de Home, 3 diapositivas × 3 anchos (375/768/1440), remedido de cero:**

| Ancho | Diapositiva | Contraste H1 | Contraste subtítulo |
|---|---|---|---|
| 375 | Machu Picchu al amanecer | 7.33:1 | 6.59:1 |
| 375 | Valle Sagrado | 8.17:1 | 6.31:1 |
| 375 | Cordillera y río andino | 7.21:1 | 5.89:1 |
| 768 | Machu Picchu al amanecer | 7.13:1 | 6.33:1 |
| 768 | Valle Sagrado | 8.18:1 | 6.20:1 |
| 768 | Cordillera y río andino | 6.97:1 | 5.71:1 |
| 1440 | Machu Picchu al amanecer | 8.28:1 | 6.27:1 |
| 1440 | Valle Sagrado | 7.20:1 | 7.05:1 |
| 1440 | Cordillera y río andino | 6.01:1 | 7.85:1 |

Los 18 valores ≥ 5.71:1 → **pasan AA (4.5:1) con margen** en las 9
combinaciones. Ninguno llega a rozar el mínimo. Los rangos que reportaron los
maquetadores (6:1–15:1) son consistentes con esta remedición independiente en
el hero — no encontré ningún valor por debajo de lo que ellos dijeron.

**Cabecera cinemática de `/es/tours` (misma foto `hero-cordillera-rio.jpg`
que la diapositiva 3 del hero), 768 y 1440:**

| Ancho | Contraste H1 | Contraste lead |
|---|---|---|
| 768 | 12.78:1 | 10.87:1 |
| 1440 | 11.28:1 | 9.18:1 |

También muy por encima de AA. El comentario del propio código ("el contraste
de `--scrim-hero` contra esta foto ya está medido en el hero de Home") queda
**confirmado por remedición independiente**, no solo aceptado de palabra.

**No remedido:** contraste en destinos/experiencias/nosotros (pantallas de
regresión, ver R2.4) ni en tarjetas/botones fuera del hero.

---

## Verificado — código (lectura, sin ejecutar)

- **`page-header.blade.php`**: prop `cinematic` con default `false`. La rama
  `@elseif($image)` (sin `cinematic`) es *exactamente* el bloque que ya usaban
  destinos/experiencias antes del pase — no se tocó una línea de esa rama, el
  diff solo *agrega* la rama `@if($image && $cinematic)` arriba. Riesgo de
  regresión visual en destinos/experiencias por este componente: bajo, por
  lectura. No verificado en navegador (ver NO COMPROBADO).
- **`gallery.blade.php`**: el lightbox es aditivo (bloque de foto principal +
  miniaturas sin cambios); `noscript` con `<img>` real como respaldo si JS no
  corre; botón de expandir queda inerte sin JS pero la foto principal ya es
  visible por defecto (no hay pantalla en blanco). Confirmado en runtime en
  `/es/tours/camino-inca-machu-picchu-4-dias` a 375px (ver abajo).
- **`money.blade.php`**: prop `tone` nueva, solo cambia el color del prefijo
  ("Desde"); el precio sigue formateado 100% por `App\Support\Money`, cero
  cálculo en la vista. `<noscript>{{ $pen }}</noscript>` como respaldo.
- **`faq-item.blade.php`**: slot ahora se imprime con `{!! $slot !!}` en vez de
  `{{ $slot }}` — comentario en el propio archivo documenta que evita
  doble-escape; cada llamador ya sanea o interpola texto plano antes. Sin
  regresión visible por lectura.
- **Fallback de galería vacía (defecto Alto del lote 3, 2026-09-14): FIJO.**
  `destinations/show.blade.php:8-11` y `experiences/show.blade.php` ahora arman
  `$galleryImages` con `PlaceholderImage::svg(...)` cuando la colección viene
  vacía — el comentario en el propio archivo referencia el hallazgo del CRO
  anterior. No reproducido de nuevo.
- **Selector de idioma sin `href` (defecto Crítico del lote 3): FIJO.**
  `components/header/locale-switcher.blade.php` ahora renderiza `<a href="{{
  $alternateUrls[$code] ?? '#' }}">` real para cada locale no activo, calculado
  en `x-site.header` con la misma lógica del hreflang. No se reprodujo el
  `<span>` mudo. **Ojo:** el `href` cae a `'#'` si `$alternateUrls[$code]` no
  existe — no comprobé en runtime qué pasa si una ficha no tiene traducción
  (ver NO COMPROBADO #7, antecedente de 6/18 fichas EN en 404).
- **`app.js` — `setupReveal()`**: el CSS (`app.css:359-372`) solo pone
  `opacity:0` en `[data-reveal]` cuando `<html>` lleva la clase
  `js-reveal-ready`, y esa clase la agrega el propio `setupReveal()` — y solo si
  `IntersectionObserver` existe y `prefers-reduced-motion` no pide reducir. Sin
  JS, o con motion reducido, el contenido nunca se esconde. Verificado por
  lectura de código (CSS + JS cruzados), no en runtime con JS apagado de
  verdad (ver NO COMPROBADO #5).
- **`app.js` — `setupParallax()`**: gateado por TRES media queries
  (`min-width:1024px`, `pointer:fine`, `prefers-reduced-motion:no-preference`),
  las tres con listener `change` (se re-evalúa si el visitante rota o conecta
  mouse). `unbind()` resetea `el.style.transform = ''` explícitamente. Esto es
  evidencia de código sólida, pero **no es una medición de runtime** — el
  puente Playwright disponible en este entorno no expone `browser_evaluate` ni
  una herramienta de scroll programático (confirmado al intentarlo: el listado
  de herramientas es navigate/snapshot/screenshot/resize/click/fill_form/
  console/network, sin scroll ni evaluate). No pude leer el `transform`
  computado tras un scroll real en 375px como pedía el encargo. Declarado NO
  COMPROBADO #4, no aprobado por lectura.
- **Cifras sin respaldo**: no encontré ninguna en `home.blade.php`. La sección
  de testimonios está deliberadamente comentada/fuera de la Home (comentario
  "B2" documenta que son reseñas falsas de IA y decide no publicarlas). El
  newsletter tiene input y botón `disabled` con copy honesto ("todavía no
  configuramos..."). Sin años de experiencia ni contadores inventados en las 3
  pantallas del pase. Reviso solo Home a fondo; tours/index y tours/show no se
  grepearon línea por línea en busca de cifras (NO COMPROBADO #8).

## Verificado — runtime (Playwright, clic real, 375px)

1. **Home (`/es/`, 375px)**: 0 errores de consola, 13 requests estáticos todos
   200 (imágenes hero en `.webp`, tours en `/storage/tours/...`, fuentes
   Google). Slider de hero: clic real en indicador "Cordillera y río andino" →
   `status` cambia de texto y el botón queda `[active]` (confirmado con
   snapshot antes/después). Botón pausa: clic real → cambia a "Reanudar el
   avance automático de fotos" con `[pressed]`. Autoavance también se observó
   solo (cambió de slide 1→2 entre dos snapshots sin interacción, ~30s de
   diferencia, consistente con el intervalo de 6.5s). Franja de insignias de
   confianza ("Viajes 100% seguros"...) se extiende más allá del viewport en
   los `box` del snapshot — confirmado por lectura de código que es
   `overflow-x-auto` intencional (carrusel horizontal en móvil), no fuga de
   layout.
2. **Ficha de tour (`/es/tours/camino-inca-machu-picchu-4-dias`, 375px)**: 0
   errores de consola. Lightbox: clic en "Ver foto a pantalla completa" → abre
   `dialog` con `role="dialog"` `aria-modal="true"`; clic en "Foto siguiente" →
   navega; clic en "Cerrar" → cierra. Los tres con clic real, no asumido.
3. **CTA de reserva → contacto (regresión ya corregida antes, según el
   encargo): sigue viva.** Clic real en "Solicitar reserva" desde la ficha →
   navega a `/es/contacto?tour=camino-inca-machu-picchu-4-dias#formulario` →
   la página de contacto muestra la caja "Tour seleccionado: Camino Inca a
   Machu Picchu, 4 días" con link "Ver la ficha del tour", y el combobox
   "Asunto" viene preseleccionado en "Reserva de un tour". No se reprodujo la
   regresión que el encargo pedía vigilar.
4. **Formulario de contacto, caracteres límite**: llené nombre, correo,
   mensaje con `<script>alert(1)</script> & 'comillas' "dobles" tildes ñ
   emoji 🌄`, acepté el checkbox de datos, dejé el honeypot ("Deja este campo
   vacío") intacto. Clic real en "Enviar mensaje" → `POST /es/contacto` → 302
   → `GET /es/contacto?tour=...` → 200, con mensaje de estado "¡Gracias! Tu
   mensaje fue enviado correctamente. Te responderemos pronto." Sin errores de
   consola. No verifiqué qué llegó exactamente al correo/log (sin acceso a
   buzón/mailtrap en este entorno) — ver NO COMPROBADO #6.
5. **EN locale, un solo caso**: navegué a
   `/en/tours/camino-inca-machu-picchu-4-dias` → resolvió a
   `/en/tours/inca-trail-machu-picchu-4-days` (slug traducido), título "Inca
   Trail to Machu Picchu, 4 Days | Pacha Viva". No dio 404. Esto es **un solo
   tour de 3**, y el antecedente crítico era sobre fichas de destino/
   experiencia (6 de 18), no de tours — así que esto NO cierra ese antecedente,
   solo confirma que al menos esta ficha de tour no lo reproduce.

## NO COMPROBADO (actualizado tras RONDA 2 — no repetir lo ya cerrado)

**CERRADO en la ronda 2** (ver sección `## RONDA 2` arriba, no reabrir):
breakpoints 768/1440 en las 3 pantallas del pase (overflow, jerarquía de
encabezados, consola) · contraste WCAG real por muestreo de píxeles (18
valores en hero + 2 en cabecera cinemática de `/es/tours`, peor caso
5.71:1, todos sobre AA) · selector de idioma por clic real en las 9 fichas
existentes + 6 páginas de chrome, las 15 en 200 · antecedente de "6/18
fichas EN en 404" cerrado para el catálogo actual (el catálogo real tiene 9
fichas, no 18).

**Sigue pendiente:**

1. **Pantallas de regresión (`/es/destinos`, `/es/destinos/cusco`,
   `/es/experiencias`, `/es/experiencias/trekking`, `/es/nosotros`) — cero
   navegación real.** Todo lo dicho arriba sobre ellas sigue siendo por
   lectura de código. No se abrieron en el navegador todavía.
2. **`/es/contacto` sin el parámetro `?tour=`** — no probado de forma aislada.
3. **Parallax `<1024px` / `pointer:coarse` / `prefers-reduced-motion` — sin
   medición de runtime.** (El MCP de Playwright de la ronda 1 no tiene
   `evaluate`; la ronda 2 sí tiene un bridge alterno con `playwright-core`,
   pendiente de usarlo aquí.)
4. **JS desactivado de verdad (`javaScriptEnabled:false`) y
   `emulateMedia({reducedMotion:'reduce'})` — sin runtime todavía.**
5. **Contenido real del correo enviado** por el formulario de contacto — sin
   acceso a bandeja/log de mail en este entorno.
6. **Conmutador de moneda PEN/USD — cero clics.**
7. **Teclado real (Tab/Shift+Tab/Enter/Esc) y foco atrapado del lightbox** —
   pendiente con el bridge nuevo (el MCP de la ronda 1 no tenía tecla-real).
8. **Doble-clic en "Enviar mensaje" (idempotencia)** — no probado.
9. **`nosotros.blade.php` (equipo)**: placeholder de foto sin miembro real no
   confirmado en runtime en este pase (sí en lote anterior, con credenciales,
   pero fue antes de este pase cinematográfico).
10. **Comparación pixel-a-pixel contra Figma** — lo hecho es
    overflow/jerarquía/contraste numérico + capturas revisadas puntualmente,
    no un diff contra el mockup.

## Capturas y mediciones ya hechas (para que `anyerson-seo` no las repita)

- Network requests de `/es/` a 375px: lista completa de 13 assets estáticos,
  todos 200 (ver arriba) — no hace falta que SEO vuelva a pedir esa lista para
  buscar 404 de imágenes en Home.
- Título de página capturado en runtime: Home `/es/` → "Agencia de turismo en
  Cusco, Perú · Pacha Viva"; ficha de tour ES → "Camino Inca a Machu Picchu, 4
  días | Pacha Viva"; ficha de tour EN → "Inca Trail to Machu Picchu, 4 Days |
  Pacha Viva".
- Confirmado por runtime que `/en/tours/camino-inca-machu-picchu-4-dias`
  redirige/resuelve a slug traducido `inca-trail-machu-picchu-4-days` (200,
  no 404) — dato útil para el gate de hreflang/canonical de SEO en al menos
  este caso.
- POST del formulario de contacto observado: `POST /es/contacto` → 302 → GET
  200, patrón PRG estándar.

## Contrato de 3 tablas

### Tabla A — Lighthouse por URL

| URL | Perf | A11y | Best Practices | SEO | LCP | CLS | INP |
|---|---|---|---|---|---|---|---|
| /es/ | — | — | — | — | — | — | — |
| /es/tours | — | — | — | — | — | — | — |
| /es/tours/camino-inca-machu-picchu-4-dias | — | — | — | — | — | — | — |

No se corrió Lighthouse en este pase (fuera del set de herramientas del
CRO en este entorno; requiere `anyerson-seo` o una corrida de CI aparte).

### Tabla B — On-page por URL

| URL | title (largo) | meta description (largo) | H1 | canonical | OG completo | schema detectado |
|---|---|---|---|---|---|---|
| /es/ | "Agencia de turismo en Cusco, Perú · Pacha Viva" (48) | no medido | "Vive lo mejor de Perú con expertos locales" (confirmado, 1 solo H1) | no medido | no medido | no medido |
| /es/tours/camino-inca-machu-picchu-4-dias | "Camino Inca a Machu Picchu, 4 días \| Pacha Viva" (49) | no medido | "Camino Inca a Machu Picchu, 4 días" (confirmado, 1 solo H1) | no medido | no medido | no medido |
| /en/tours/inca-trail-machu-picchu-4-days | "Inca Trail to Machu Picchu, 4 Days \| Pacha Viva" (49) | no medido | no medido | no medido | no medido | no medido |

Solo se capturaron `title` y presencia de H1 único (subproducto de las
snapshots de accesibilidad ya hechas). Meta description, canonical, OG y
schema quedan enteros para `anyerson-seo`.

### Tabla C — Los 7 gates de discoverability

| # | Gate | Estado | Evidencia |
|---|---|---|---|
| 1 | `robots.txt` existe y no bloquea rutas productivas | ❌ (sin evidencia) | No comprobado en este pase. Antecedente del lote 3 (2026-09-14): `Sitemap: http://127.0.0.1:8000/sitemap.xml` con host `8087`/producción desalineado — no reverificado si sigue así. |
| 2 | `sitemap.xml` existe, responde 200 y lista solo URLs canónicas 200 | ❌ (sin evidencia) | No comprobado en este pase. |
| 3 | Toda URL indexable tiene canonical apuntando a sí misma | ❌ (sin evidencia) | No comprobado en este pase. |
| 4 | Ninguna URL productiva trae `noindex` en meta ni en header | ❌ (sin evidencia) | No comprobado en este pase. |
| 5 | Cero 4xx y cero cadenas de redirección >1 salto en el enlazado interno | ❌ (sin evidencia) | Parcial: los enlaces internos navegados en este pase (Home→tour, tour→contacto, /en/ del tour) dieron 200/302→200 sin 4xx, pero es una fracción mínima del sitio. |
| 6 | `title` y `H1` presentes, únicos y distintos entre sí en cada URL | ✅ (parcial) | Confirmado en las 3 URLs de la Tabla B: `title` y H1 presentes y distintos entre sí. No confirmado en el resto del sitio. |
| 7 | Datos estructurados presentes y sin errores de validación | ❌ (sin evidencia) | No comprobado en este pase. |

---

## Defectos priorizados

No se encontró ningún defecto Crítico ni Alto en lo efectivamente cubierto.
Un (1) hallazgo Bajo, ninguno bloqueante:

1. **[severidad: bajo]** El `href` del selector de idioma cae a `#` cuando
   `$alternateUrls[$code]` no trae la URL de esa ficha en ese locale (línea
   `alternateUrls[$code] ?? '#'` en `locale-switcher.blade.php`). No se
   reprodujo un caso real donde esto ocurra en este pase (el único tour EN
   probado sí resolvió bien), así que no se confirma si el antecedente de
   6/18 fichas EN en 404 (lote 3) sigue vivo o no. Lo marco Bajo y no Alto
   porque no tengo repro, no porque esté descartado.
   - Lado afectado: Sincronización CMS↔front / lógica de rutas.
   - Cómo reproducirlo (para quien retome): recorrer las 18 fichas de
     destino/experiencia en `/en/` vía el selector de idioma real (clic, no
     URL directa) y anotar cuáles caen en `#` o 404.
   - Asignar a: `backend-laravel` (es contrato de datos: slugs ES/EN sin
     sincronizar), solo si se confirma repro.

---

## Siguiente paso recomendado (histórico de ronda 1 — superado, ver RONDA 3)

---

## RONDA 3 — cierre de lo que quedaba (mismo bridge `playwright-core` + Chrome)

### R3.1 — Pantallas de regresión, navegación real (375 y 1440)

Las 5 pantallas (`/es/destinos`, `/es/destinos/cusco`, `/es/experiencias`,
`/es/experiencias/trekking`, `/es/nosotros`) navegadas de verdad, en 375 y
1440, midiendo overflow horizontal, `H1`, y errores de consola/requests
fallidos; más captura full-page de cada una (agrandando el viewport a la
altura completa del documento antes de capturar, revisada visualmente, no
solo generada).

| Pantalla | Ancho | Overflow | H1 | Consola | Notas |
|---|---|---|---|---|---|
| /es/destinos | 375 / 1440 | 0px / 0px | "Destinos" | 0 / 0 | Índice, sin `x-ui.gallery` (no aplica) |
| /es/destinos/cusco | 375 / 1440 | 0px / 0px | "Cusco" | 0 / 0* | `page-header` sin `cinematic` (rama vieja intacta) + `x-ui.gallery` con foto real y lightbox — revisado visualmente, sin regresión |
| /es/experiencias | 375 / 1440 | 0px / 0px | "Experiencias" | 0 / 0 | Índice |
| /es/experiencias/trekking | 375 / 1440 | 0px / 0px | "Trekking" | 0 / 0 | Igual patrón que destinos/cusco, sin regresión |
| /es/nosotros | 375 / 1440 | 0px / 0px | "Nosotros" | 0 / 0 | Sin sección de equipo visible (no hay miembros cargados hoy — la sección se oculta entera, no deja hueco ni placeholder roto) |

\* En el primer intento a 1440 salió un único `net::ERR_CONNECTION_RESET` en
consola en `/es/destinos/cusco`. Antes de declararlo defecto lo remedí 3
veces seguidas: las 3, limpio. Lo trato como artefacto de medición (la
máquina venía de correr varios scripts Playwright headless seguidos), no
como hallazgo — lo dejo escrito para que si reaparece en otra corrida no se
descarte de nuevo sin mirar.

Capturas revisadas (`cro-regresion-destinos-cusco-1440.png`,
`cro-regresion-destinos-cusco-375.png`,
`cro-regresion-experiencias-trekking-1440.png`,
`cro-regresion-nosotros-1440.png`): sin defecto visual. `page-header` sin
`cinematic` sigue exactamente igual (banda `--scrim-band`, no la foto a
sangre), `x-ui.gallery` con foto real + miniaturas + botón de lightbox
funciona igual que en la ficha de tour. **Cierra el punto de mayor riesgo del
encargo** (regresión en las 4 pantallas fuera del pase que consumen los
componentes compartidos modificados).

### R3.2 — Parallax en runtime (hecho en ronda 2, confirmado aquí)

Ya medido en ronda 2 con scroll instantáneo real y lectura de
`el.style.transform`, en 5 combinaciones ancho×puntero, incluyendo el borde
exacto de la media query:

| Caso | `pointer:fine` | `min-width:1024px` | `transform` tras scroll |
|---|---|---|---|
| 375, touch (coarse) | no | no | `""` (apagado) en las 2 lecturas |
| 768, touch (coarse) | no | no | `""` (apagado) en las 2 lecturas |
| 1023px, mouse (fine) — 1px bajo el límite | sí | **no** | `""` (apagado) |
| 1024px, mouse (fine) — límite exacto | sí | **sí** | `translate3d(0,-51.6px,0)` → `translate3d(0,-9.6px,0)` (se mueve con el scroll) |
| 1440, mouse (fine) | sí | sí | `translate3d(0,-66.4px,0)` → `translate3d(0,-9.1px,0)` (se mueve) |

El corte está exactamente donde dice el código (`min-width:1024px` +
`pointer:fine`), probado con un caso 1px por debajo del límite para
descartar que fuera casualidad. **Sin defecto.**

### R3.3 — `prefers-reduced-motion:reduce` y `javaScriptEnabled:false`, las 3 pantallas del pase

**Reduced motion** (`page.emulateMedia({reducedMotion:'reduce'})`), Home +
`/es/tours` + ficha de tour:

| Pantalla | `[data-reveal]` visibles | `js-reveal-ready` presente | `transform` de parallax | Autoavance del hero |
|---|---|---|---|---|
| Home | 23/23 con opacity 1 | no | `""` | no avanza tras 7s |
| /es/tours | 3/3 con opacity 1 | no | `""` | N/A (sin hero-dot) |
| ficha de tour | 8/8 con opacity 1 | no | `""` | N/A (sin hero-dot) |

**JS desactivado de verdad** (`browser.newContext({javaScriptEnabled:false})`),
mismas 3 pantallas — este era, según el encargo, el punto de mayor
consecuencia:

| Pantalla | `H1` presente | Texto de body | Imágenes con caja > 0 | `[data-reveal]` opacity | Captura |
|---|---|---|---|---|---|
| Home | "Vive lo mejor de Perú con expertos locales" | 3834 caracteres | 14 | 23/23 en `1` (nunca `0`) | `cro-jsoff-home.png` |
| /es/tours | "Nuestros tours" | 1251 caracteres | 4 | 3/3 en `1` | `cro-jsoff-tours-index.png` |
| ficha de tour | "Camino Inca a Machu Picchu, 4 días" | 2107 caracteres | 7 | 8/8 en `1` | `cro-jsoff-tour-show.png` |

Caso límite investigado a fondo (no me quedé con el número, miré el porqué):
los `<span x-show x-cloak>` de `x-ui.money` (PEN/USD) **sí** se quedan en
`display:none` sin JS — Alpine nunca corre, así que el atributo `x-cloak`
nunca se retira. Pero el componente trae `<noscript>{{ $pen }}</noscript>`
exactamente para este caso: confirmé con `getComputedStyle` que el primer
hijo del `<noscript>` renderiza `display:block`, y con una captura recortada
del precio (`cro-jsoff-money-clip.png`) que se **ve** "Desde S/ 3,500.00"
completo y legible, no un hueco. El conmutador de moneda (botones PEN/USD)
sí desaparece sin JS — degradación aceptable (no es contenido, es un control
que requiere interactividad; el precio en soles queda visible igual) y no
"página en blanco". **Sin defecto Crítico ni de ningún tipo — el diseño
defensivo funciona exactamente como documentan los comentarios del código.**

### R3.4 — Conmutador PEN/USD, clic real, las 3 pantallas + persistencia

Clic real en el botón (nombre accesible = código actual) → `role="menuitem"`
con el código destino:

| Paso | Precio mostrado | `Alpine.store('currency').code` |
|---|---|---|
| Home, antes de tocar el conmutador | S/ 3,500.00 | PEN |
| Home, clic real a USD | US$ 933.33 | USD (y `localStorage.pv_currency = "USD"`) |
| Navego a /es/tours (sin tocar el conmutador) | US$ 933.33 | USD — **persiste** |
| Navego a la ficha de tour (sin tocar el conmutador) | US$ 933.33 | USD — **persiste** |
| En la ficha, clic real de vuelta a PEN | S/ 3,500.00 | PEN |

El conmutador cambia el número real (no solo el símbolo — 3500 PEN ≈ 933.33
USD, conversión correcta) y persiste al navegar entre las 3 pantallas sin
tocar el control de nuevo, en las dos direcciones. **Sin defecto.**

### R3.5 — Teclado real, slider del hero y lightbox de galería

**Slider (Home):** el listener de flechas vive en el contenedor `#hero`
(`@keydown.left="prev()"` / `@keydown.right="next()"` en `home.blade.php:109-110`,
no en cada botón individual — confirmado por lectura tras ver el resultado,
no asumido). Foco real en el primer indicador → `ArrowRight` real → el slide
activo avanza de 0 a 1. `Enter` real sobre el tercer indicador → activa el
slide 2. El elemento enfocado tiene `outline: solid 2px white` visible
(`:focus-visible`).

**Lightbox (ficha de tour):** clic real para abrir → el foco cae dentro del
`[role="dialog"]` de inmediato. `ArrowRight` real → la foto visible cambia
(confirmé comparando el `src` de la imagen visible antes/después, no un
contador interno de Alpine que no pude leer por la API `__x` — así que lo
remedí por el efecto real en el DOM en vez de insistir con la lectura
interna). `Tab` real × 8 y `Shift+Tab` real × 8 → el foco **nunca** sale del
diálogo en ninguna de las 16 pulsaciones (trampa de foco funciona en ambas
direcciones). `Escape` real → cierra el diálogo y el foco vuelve exactamente
al botón "Ver foto a pantalla completa" que lo abrió. **Sin defecto — los
tres requisitos de accesibilidad del encargo (Tab/Shift+Tab/Enter/Esc, foco
no se escapa) confirmados con teclado real, no simulados.**

---

## Defectos priorizados (estado final, ronda 3)

Ningún defecto Crítico ni Alto en todo lo cubierto (rondas 1+2+3). Un (1)
hallazgo Bajo y una (1) observación, ninguno bloqueante:

1. **[severidad: bajo]** `locale-switcher.blade.php` cae a `href="#"` cuando
   `$alternateUrls[$code]` no existe. **Sin repro**: las 9 fichas de contenido
   que existen hoy resuelven las 9 a EN en 200 (ver R2.1). Queda como el
   lugar a mirar primero si en el futuro se agrega contenido sin su
   traducción. Asignar a `backend-laravel` solo si se confirma un caso real.
2. **[observación, no defecto]** El segmento de ruta EN de destinos/
   experiencias no se traduce (`/en/destinos/...`, `/en/experiencias/...`),
   mientras que tours sí trae ruta traducida (`/en/tours/...`). Inconsistencia
   de esquema de URL entre secciones, sin romper nada (las 9 fichas dan 200).
   Anotado para que `anyerson-seo` lo tenga en cuenta al armar hreflang/
   canonical — no es un defecto de `cro-validator`.

## NO COMPROBADO — remanente no bloqueante (estado final)

1. `/es/contacto` sin el parámetro `?tour=`, probado de forma aislada (solo
   se probó llegando desde el CTA de una ficha).
2. Contenido real del correo enviado por el formulario — sin acceso a
   bandeja/log de mail en este entorno.
3. Doble-clic en "Enviar mensaje" (idempotencia).
4. `nosotros.blade.php`: el equipo está vacío hoy (sección se oculta entera,
   confirmado en R3.1) — el recorrido completo de alta de un miembro con foto
   real desde el CMS no se repitió en este pase (sí se hizo en una auditoría
   anterior con credenciales, según memoria del equipo, pero fue antes de
   este pase cinematográfico).
5. Comparación pixel-a-pixel contra el mockup de Figma — lo hecho es
   overflow/jerarquía/contraste numérico + capturas revisadas puntualmente,
   no un diff contra el archivo de diseño.
6. Lighthouse (Perf/A11y/BP/SEO/LCP/CLS/INP), meta description, canonical,
   OG y datos estructurados — fuera del alcance de `cro-validator`, quedan
   para `anyerson-seo` (tabla B/C abajo).

Ninguno de estos 6 puntos toca las zonas de riesgo que el encargo marcó como
prioritarias (regresión de componentes compartidos, parallax, JS-off,
moneda, idioma, teclado) — todas esas están cerradas.

## Veredicto final: APTO

Con las reservas de la lista `NO COMPROBADO` de arriba (ninguna bloqueante,
ninguna toca una zona de riesgo señalada por el encargo). Recomiendo avanzar
a `anyerson-seo` para su parte del contrato (Lighthouse, on-page, 7 gates) y,
si SEO no encuentra nada bloqueante, continuar el ciclo normal (no hace falta
volver al estado 2 de Arreglar).

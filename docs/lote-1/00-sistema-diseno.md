# Lote 1 · Etapa A — Sistema de diseño

Identidad provisional "Pacha Viva" (EN REVISIÓN por colisión de nombre). Layout tomado de
los mockups de `docs/lote-1/mockups/`; marca del lote 0 (`docs/lote-0/identidad/`). Todo lo
de este documento corresponde **solo a la etapa A** (sistema de diseño) — Home, Nosotros y
Contacto son las etapas B/C y no se maquetaron.

## 1. Cadena de assets — qué manda sobre qué

```
docs/lote-0/identidad/*.svg   (fuente de los SVG de marca, documentación)
        │  copiados sin editar a
        ▼
public/images/brand/*.svg     (servibles) + public/favicon.svg
        │  inlineados por
        ▼
resources/views/components/brand/mark.blade.php   (único componente que conoce el logo/nombre)

resources/css/tokens.css      (ÚNICA fuente de color: HSL derivada de --brand-h)
        │  mapeado en @theme de
        ▼
resources/css/app.css         (ÚNICA fuente de la cadena Tailwind: --color-*, --font-*)
        │  consumido como utilidades por
        ▼
resources/views/components/**/*.blade.php
```

**`tailwind.config.js` se ELIMINÓ** (no `git rm`, borrado del working tree; lo confirma el
propio commit del lote cuando Anyerson lo cierre). Con `@tailwindcss/vite`, Tailwind 4 es
CSS-first: un `tailwind.config.js` sin una directiva `@config` en la CSS **no se lee en
absoluto** — así que antes del lote 1 existían dos definiciones de `colors.brand`/
`fontFamily` (una en el JS, una implícita en `tokens.css`) y solo la CSS ganaba, en
silencio. Se optó por `@theme` nativo de v4 en vez de reintroducir `@config` para no cargar
un formato de v3 sin necesidad; si el proyecto necesitara alguna vez configuración
imperativa (plugins JS de Tailwind, por ejemplo), ahí sí correspondería reconsiderar `@config`.

**Prueba de vida (no asumida — medida en el navegador, `_styleguide`, Chrome headless
propio en :9333 porque el MCP de Playwright estaba tomado):**

```js
// antes
getComputedStyle($('.bg-brand-600')).backgroundColor // "rgb(27, 105, 73)"
document.documentElement.style.setProperty('--brand-h', '12')
// después, SIN recompilar nada
getComputedStyle($('.bg-brand-600')).backgroundColor // "rgb(105, 43, 27)"
```

El mismo cambio se verificó también sobre el `fill` del logo inline en el header
(`var(--action, #1b6949)`): pasó de `rgb(27,105,73)` a `rgb(105,43,27)` en vivo. La cadena
es real de punta a punta, incluida la marca.

**Bug real que esta prueba destapó (y quedó arreglado):** la rampa `bg-brand-{{ $stop }}`
de la guía de estilos usa interpolación PHP, así que el escáner estático de Tailwind no la
ve como texto literal — `npm run build` solo generaba `bg-brand-50` (la única variante
escrita como string literal en otro archivo) y las demás (`100`..`900`) no existían en el
CSS compilado hasta que se agregó `@source inline("{bg,text,border}-brand-{50,...,900}")`
en `app.css`. Sin este hallazgo el swatch de la guía se habría visto roto en producción.

## 2. Tokens de color — tabla con el ratio WCAG medido

Todos los ratios de abajo están **medidos** (calculados a mano con la fórmula WCAG y
verificados por muestreo de píxel real en el navegador vía `--contrast` del puente CDP,
que captura el elemento y mide el fondo pintado, no el declarado). Umbral: 4.5:1 texto
normal, 3:1 texto grande (≥18.66px bold o ≥24px) y componentes no textuales.

| Token | Valor | Uso | Contraste medido | Resultado |
|---|---|---|---|---|
| `--brand-text` (=`brand-700`) | `hsl(155 62% 20%)` → `#135338` | texto de marca | 8.32:1 sobre `--ground`, 9.04:1 sobre `--surface` | **PASA** AA normal |
| `--on-action` sobre `--action` (`brand-600`) | `#fff` / `#1b6949` | botón primario | 6.64:1 | **PASA** AA normal |
| `--on-action` sobre `--action-hover` (`brand-700`) | `#fff` / `#135338` | botón primario:hover | 9.04:1 | **PASA** AA normal |
| `--text` (`ink`) | `#14201a` | cuerpo | 15.44:1 (`--ground`), 16.78:1 (`--surface`) | **PASA** AA normal |
| `--text-2` (`ink-2`) | `#3d4a42` | cuerpo secundario | 8.56:1 (`--ground`), 9.31:1 (`--surface`) | **PASA** AA normal |
| `--text-muted` (`ink-3`) | `#647069` (**corregido en etapa B**, era `#6c7972`) | metadatos | 4.76:1 sobre `--ground`, 5.17:1 sobre `--surface` — medido con la fórmula WCAG en Node y reverificado en vivo inyectando `--text-muted`/`--ground` en un nodo de prueba (`__ratio` del puente CDP: `4.76` exacto) | **PASA** AA normal en las dos superficies |
| `--amber` | `#c9821f` | ícono/relleno decorativo | 3.14:1 sobre `--surface` | **FALLA** como color de texto; **pasa** como no-textual (≥3:1) |
| `--amber-text` (nuevo) | `#93590c` | texto sobre ámbar (badge "Certificado") | 5.71:1 (`--surface`), 5.26:1 (`--ground`) | **PASA** AA normal |
| `--danger` | `#ab3a30` | error de formulario | 6.21:1 sobre `--surface` | **PASA** AA normal |
| `--accent-blue` (=`--info`) | `#2c6fa8` | badge "Recomendado", texto blanco encima | 5.32:1 | **PASA** AA normal |
| `--accent-orange` (nuevo) | `#c2410c` | badge "Más vendido", texto blanco encima | 5.18:1 (medido por muestreo de píxel real sobre foto, `_styleguide`) | **PASA** AA normal |
| Eyebrow `--brand-text` sobre `--brand-50` | `#135338` / `#eff6f3` | pastilla "Estamos para ayudarte" | 8.25:1 | **PASA** AA normal |
| Destino: texto blanco sobre degradado `ink/80→transparent` | `#fff` / fondo real muestreado `rgb(20,52,38)` | nombre de destino sobre foto | 13.56:1 (muestreado en píxel, no asumido) | **PASA** AA normal |
| ADVERSARIAL — blanco sobre `--brand-100` | `#fff` / `#d7eae2` | (no es un uso real; solo prueba del checker) | 1.25:1 | **FALLA** correctamente — confirma que el checker detecta fallos |

**Nota sobre "verde de marca sobre foto clara" (advertencia del brief):** se midió en
píxeles reales dónde cae el texto verde en los dos mockups (`01-home.png` en la palabra
"Perú" del titular, `03-contacto.png` en "aventura"). En ambos casos el fondo real detrás
del texto es blanco/gris casi puro (`rgb ≈ 250,252,253`), **no la fotografía** — la foto
del hero queda confinada a la columna derecha. La advertencia del brief no aplica a estas
dos instancias puntuales tal como están maquetadas hoy; si una futura Home usa un hero
*full-bleed* con texto de marca directamente sobre la foto, ese caso deberá resolverse con
texto blanco + velo oscuro (`--ink/80`, ya usado y verificado en `x-ui.destination-card`),
nunca `--brand-text` crudo sobre foto sin velo.

**Nota sobre `--text-muted` (corregida en etapa B):** el valor original (`#6c7972`) fallaba
AA de texto normal sobre `--ground` (4.19:1) y quedó documentado como advertencia en vez de
arreglado — defecto arrastrado (B10 del brief de la Home). Se oscureció a `#647069`
manteniendo el mismo matiz, calculando en Node el punto donde el ratio cruza 4.5:1 y
tomando un valor con margen (4.76:1 sobre `--ground`, 5.17:1 sobre `--surface`). Ya no hace
falta restringir su uso a texto grande/UI: pasa como cuerpo de texto normal en ambas
superficies del sistema.

**Acentos de catálogo — el sistema admite exactamente 4, ninguno más sin re-medir:**
verde (`--accent-green` = marca), ámbar (`--accent-amber` = `--amber-text`), naranja
(`--accent-orange`, nuevo) y azul (`--accent-blue` = `--info`). Naranja y azul están fuera
del hue 155 a propósito (badges "MÁS VENDIDO"/"RECOMENDADO" de los mockups no son de marca).

## 3. Tipografía

| Rol | Familia | Pesos usados | Dónde |
|---|---|---|---|
| `font-display` | Fraunces (variable, `opsz 9..144, wght 300..700`) | 600 (semibold) en H1-H3 y precios | Títulos, precios destacados |
| `font-sans` | Figtree | 400, 500, 600, 700 | Cuerpo, nav, botones, formularios |
| `font-script` | Caveat | 500, 600 | Firma "Equipo Pacha Viva" (uso puntual, Nosotros — etapa B) |

Sin archivos locales servibles más allá de las fuentes de Filament (`public/fonts/filament`),
así que se cargan por Google Fonts, en **un solo lugar**: `resources/views/components/layout.blade.php`
(`<link>` en el único `<head>` del sitio). Ninguna vista repite el `<link>`.
`Instrument Sans` (fuente del esqueleto de Laravel) queda completamente reemplazada en
`--font-sans`; `welcome.blade.php` (fuera de alcance, Home es etapa B) sigue con su propio
`<link>` de Bunny Fonts porque no usa el layout nuevo todavía.

## 4. Escala de espaciado, radios y sombras

No se introdujo una escala propia: se usa la de Tailwind 4 sin modificar (`--spacing: .25rem`
como unidad base, `rounded-lg`/`rounded-2xl`/`rounded-full` para tarjetas/botones/badges,
`shadow-sm`/`shadow-md`/`shadow-lg` de Tailwind). Mantener la escala del framework evita un
segundo sistema de números compitiendo con `tokens.css`.

## 5. Inventario de componentes (A7)

Guía viva en **`/_styleguide`** (noindex). Todos los props documentados con `@props` en cada
archivo.

| Componente Blade | Uso |
|---|---|
| `x-layout` | Shell HTML único: head, fuentes, favicon, header, footer, `<main>` |
| `x-brand.mark` | Único componente con el nombre/logo (variantes horizontal/vertical/mono/symbol, SVG inline) |
| `x-site.header` | Header responsive con nav, buscador (afordancia), moneda, idioma, CTA |
| `x-site.footer` | Footer con bloques legales condicionados a `Setting` |
| `x-header.currency-switcher` | Selector PEN/USD real (Alpine store + localStorage) |
| `x-header.locale-switcher` | Selector de idioma con ES activo y EN/PT-BR reservados/deshabilitados |
| `x-ui.button` | primary\|secondary\|ghost\|link, tamaños sm/md, estado disabled, ícono opcional, `type` button\|submit\|reset (nuevo, ronda 2, ver §14.1) |
| `x-ui.badge` | Pastilla sólida (4 acentos) para esquina de tarjeta sobre foto |
| `x-ui.eyebrow` | Pastilla suave + punto, uso en hero/encabezados de sección |
| `x-ui.mincetur-badge` | Envuelve `x-ui.eyebrow`; **no se renderiza sin `Setting::get('rnavt_number')`** |
| `x-ui.section-title` | Título de sección + subrayado verde corto. **Extendido en etapa B** con un slot opcional `action` (el enlace "Ver todos" a la derecha del título); sin ese slot el marcado y el margen quedan idénticos a la etapa A — no rompe `/_styleguide`. El wrapper usa `ml-auto` en el `action`, no `justify-between` en el contenedor, porque `justify-between` deja el enlace pegado a la izquierda cuando el `flex-wrap` lo manda a su propia línea en mobile (defecto real, encontrado y corregido: medido a 360px, `x` pasó de 16 a 169.6 con `right: 344` = borde derecho del contenedor). |
| `x-ui.stat` / `x-ui.stats-strip` | Estadística individual / franja completa; **vacías sin dato real en `Setting`** |
| `x-ui.tour-card` | Imagen, badge opcional, título, resumen, chips, `x-ui.money`, botón |
| `x-ui.destination-card` | Imagen + degradado + nombre, contraste verificado por muestreo |
| `x-ui.experience-card` | Imagen + ícono + título + descripción |
| `x-ui.testimonial-card` | Estrellas, cita, autor; prop `sample` marca visualmente "Muestra" |
| `x-ui.team-member-card` | Foto, nombre, rol, bio, redes |
| `x-ui.faq-item` | `<details>/<summary>` nativo — teclado y lector de pantalla sin ARIA a mano |
| `x-ui.carousel-shell` | Scroll-snap real + flechas + puntos (Alpine, sin librería) |
| `x-ui.money` | Único punto que decide PEN/USD en pantalla; formatea siempre vía `App\Support\Money` |
| `x-ui.form.input` / `.select` / `.textarea` / `.checkbox` | `<label for>` real, `aria-invalid`/`aria-describedby` en error, foco visible |
| `x-ui.trust-badge` **(nuevo, etapa B)** | Ícono + etiqueta corta; fila de 5 sellos de confianza del hero. Copy de marketing genérico (no es cifra ni cae bajo "cero cifras inventadas"), viene de `lang/es/site.php`. |
| `x-ui.feature-card` **(nuevo, etapa B)** | Ícono + título + descripción sin foto; las 4 cajas de "¿Por qué elegir viajar con nosotros?". Mismo patrón que `x-ui.experience-card` sin la imagen. |
| `x-ui.value-item` **(nuevo, etapa D)** | Ícono + título + descripción SIN tarjeta (sin borde/sombra/fondo propio) — las 4 cajas de "Nuestros valores" en Nosotros. Distinto de `x-ui.feature-card`: el mockup dibuja los valores como fila plana sobre `--ground`, no como tarjetas. |
| `App\Support\PlaceholderImage` **(nuevo, etapa B, no es Blade)** | SVG de relleno en base64 (sin red), misma técnica que la función local de `styleguide.blade.php` (etapa A, no tocada). Lo usa la Home porque `Destination`/`Experience` **no tienen columna de imagen en el esquema** y `tour_images` está vacía hoy — no hay ninguna foto real que leer todavía. Declarado como brecha de esquema para backend-laravel en un lote futuro, no resuelto acá. |

## 6. Decisión: moneda e idioma en el header (A5)

**Moneda (real, no decorativa).** Alpine `store('currency')` con `code` persistido en
`localStorage` (`resources/js/app.js`). Cualquier `<x-ui.money>` de la página escucha ese
store. Verificado con clic real: alternar PEN→USD→PEN en el header cambia el texto visible
de una tarjeta de tour de `S/ 3,500.00` a `US$ 95.00` y de vuelta, sin recargar. No hay
carrito ni sesión todavía (lote 3+), así que `localStorage` es suficiente para esta etapa.

**Idioma (reservado, honesto).** Solo `es` está activo (`config('cms.active_locales')`).
El selector itera `config('cms.locales')` completo (es/en/pt_BR): la opción activa se marca
con check y las inactivas se muestran **deshabilitadas** (`aria-disabled`, sin `href`, con
etiqueta "Próximamente") — no son enlaces rotos a `/en` o `/pt-br`, que no existen. Esto
reserva el ancho y el alto del control ahora mismo, así el lote 5 no reordena la barra.

**Buscador.** Sin implementación (no hay índice ni endpoint). Botón real `disabled` con
`title`/`aria-label` "Buscador — próximamente" — afordancia declarada, no un buscador falso.

**Ícono de usuario.** Eliminado: `users` es tabla de administración (`is_admin`), no hay
cuentas de cliente. Decidir cuentas es del lote 4.

**Ajuste de breakpoint (defecto real encontrado y corregido):** a 1024px el conjunto
logo + 6 ítems de nav + buscador + moneda + idioma + botón **no entraba** — medido con
`document.documentElement.scrollWidth` (1089px) vs `clientWidth` (1024px), un
desbordamiento horizontal real de 65px con scrollbar visible. Se resolvió cediendo el
buscador (la única afordancia sin función real) hasta `xl` (1280px) y ajustando gaps/padding
del contenedor a `lg`; moneda e idioma, que sí son funcionales, se mantienen visibles desde
1024px. Reverificado: `horizontalOverflow: false` en 360/768/1024/1280/1440.

**Z-index de los desplegables:** vive en el panel (`z-50`, `absolute`), no en la barra
(`sticky z-40`) — confirmado con `elementFromPoint` en el centro del primer ítem del menú de
moneda: el hit test devuelve el propio `<button>`, no el header. En el drawer móvil, moneda
e idioma van **en línea** (segmentado / chips), no en un desplegable — un dropdown ahí queda
fuera de la vista sin ahorrar nada (lección ya conocida de otro proyecto del estudio).

## 7. Reglas duras — cómo se cumplieron

- **Cero cifras inventadas:** `x-ui.stats-strip` lee `Setting::get('stat_*')`; sin dato, no
  se renderiza nada (verificado: `querySelector` no encuentra el nodo en el DOM cuando no
  hay `Setting`, no solo que esté vacío visualmente).
- **Cero reseñas inventadas:** `x-ui.testimonial-card` acepta `sample` y pinta el badge
  "Muestra"; ninguna página real (fuera de `/_styleguide`) instancia el componente sin datos
  verificables — decisión que le toca a la etapa B/C, no a este componente.
- **MINCETUR condicionado:** `x-ui.mincetur-badge` no imprime nada sin
  `Setting::get('rnavt_number')`. Probado en ambas direcciones: sin el dato no aparece
  `"RNAVT"` en el DOM; al fijar `Setting::set('rnavt_number', '12345', 'string')` sí aparece
  — el check no es vacuo.
- **Marca en un solo lugar:** `x-brand.mark` es el único archivo que referencia el logo y
  usa `config('app.name')` para el texto alternativo; ningún otro Blade escribe "Pacha Viva".
- **`[MUESTRA]`:** los tours sembrados se muestran con el prefijo intacto en `x-ui.tour-card`.
- **i18n:** todo el copy de header/footer vive en `lang/es/site.php` (+ espejo `lang/en/site.php`,
  aunque EN no esté activo aún).
- **Moneda:** `x-ui.money` es el único punto que llama `App\Support\Money`; ningún Blade
  formatea "S/" o "$" a mano.

## 8. Verificaciones hechas y su evidencia (resumen — detalle en el reporte final)

Todas corridas contra `/_styleguide` con el puente CDP (Chrome headless propio, puerto 9333,
porque el MCP de Playwright estaba tomado por otra sesión) en 360/768/1024/1280/1440:
overflow horizontal, clic real de moneda e idioma con `elementFromPoint`, toggle de moneda
verificado por `innerText` antes/después, apertura nativa de `<details>`, scroll real del
carrusel, y contraste por muestreo de píxel (`--contrast`) en 5 pares distintos.

## 9. Pendiente declarado (no tapado)

- No se pudo levantar el sitio contra **MySQL** (el binario de MySQL de este Laragon no
  tiene `lib/plugin` y falla al cargar un componente huérfano registrado en
  `mysql.component`); toda la verificación de este lote corrió contra **SQLite** temporal
  (`database/preview.sqlite`, no comiteado, `.env` restaurado al terminar). Sugerido:
  reinstalar/reparar el MySQL de Laragon antes del lote 2 en adelante para no repetir este
  rodeo.
- Home, Nosotros y Contacto (etapas B/C) no se maquetaron — fuera de alcance de esta etapa.
- Nav del header a "Tours"/"Destinos"/"Experiencias"/"Nosotros"/"Contacto" apunta a `#`
  mientras esas rutas no existan (solo "Inicio" resuelve a `/`, que sigue siendo el
  `welcome.blade.php` del esqueleto — tampoco se tocó, es Home/etapa B).
- No se verificaron lectores de pantalla reales (NVDA/VoiceOver), solo semántica ARIA/nativa.

## 10. Home (lote 1, etapa B)

`resources/views/home.blade.php`, ruta `/` (`routes/web.php`). `welcome.blade.php` del
esqueleto de Laravel se eliminó: sin más referencias en el repo tras el cambio de ruta.

**Composición:** hero, tours destacados, destinos, "por qué elegir viajar con nosotros",
experiencias y newsletter, todo con los componentes del inventario de la sección 5 (dos
nuevos: `x-ui.trust-badge`, `x-ui.feature-card`; uno extendido: `x-ui.section-title` con el
slot `action`). La sección de testimonios **no se instancia** — `x-ui.testimonial-card`
sigue viéndose solo en `/_styleguide` con `:sample="true"`, porque `reviews` no tiene
migración (lote 4/5) y los 3 testimonios del mockup son reseñas falsas.

**Datos reales, sin cifras/reseñas inventadas — verificado en las dos direcciones:**

- `x-ui.stats-strip` y `x-ui.mincetur-badge`: con la base tal como está hoy (sin
  `Setting` de stats ni `rnavt_number`), el DOM no contiene ni el nodo de la franja de
  estadísticas ni el texto "RNAVT"/"MINCETUR" (confirmado con `querySelector`/`innerText`,
  no solo visualmente). Sembrando `rnavt_number`, `stat_years_experience` y
  `stat_happy_travelers` por Tinker, ambos aparecen correctamente y el layout no se rompe
  con solo 2 de los 4 stats presentes; se revirtieron los tres `Setting` de prueba y se
  limpió su caché (`Cache::forget`) al terminar, la base quedó como estaba.
- `Tour::scopeFeatured()` (nuevo, un `where('is_featured', true)` — mismo patrón que
  `scopePublished`/`scopeOrdered` ya existentes) + `Destination`/`Experience` filtrados por
  `is_published`. Ninguna rejilla asume 3 tours / 4 destinos / 4 experiencias del mockup:
  destinos y experiencias usan `grid-template-columns: repeat(auto-fit, minmax(...))` +
  `justify-center` (medido: con 2 y 3 elementos respectivamente, la rejilla centra el
  contenido real en vez de dejar columnas vacías a la derecha); tours destacados usa
  `x-ui.carousel-shell` desde 2 elementos y una tarjeta suelta sin carrusel con 1, y la
  sección entera no se renderiza con 0.
- **Brecha de esquema declarada:** `Destination` y `Experience` no tienen columna de
  imagen (no es una fila vacía, es una columna que no existe), y `tour_images` está vacía
  hoy. Toda foto de catálogo en la Home es un placeholder SVG local
  (`App\Support\PlaceholderImage`, sin red) — no se inventó ninguna foto. Pendiente para
  backend-laravel en un lote futuro: agregar la columna de imagen a esos dos modelos.
- **Artefacto de datos encontrado, no corregido acá:** el tour `id=3` ("Camino Inca Corto a
  Machu Picchu") es contenido de prueba de QA (`project_peru_local_lote2`, ya documentado
  como pendiente de `migrate:fresh --seed`), tiene `is_featured=1` y **no lleva el prefijo
  `[MUESTRA]`** — hoy se renderiza primero en el carrusel (su `order=0` es menor que el `1`
  del tour de muestra) y es indistinguible de contenido real de la clienta. No se borró
  desde acá (limpieza de datos de otro lote, no de maquetado); queda declarado para que
  backend-laravel lo resuelva antes de cualquier entrega a la clienta.

**Precios:** `x-ui.tour-card` usa `x-ui.money` sin excepción. Verificado con clic real sobre
el selector de moneda del header + `innerText` (no `textContent`) antes/después: los dos
tours pasaron de `S/ 450.00`/`S/ 3,500.00` a `US$ 120.00`/`US$ 95.00` y de vuelta.

**Newsletter (B4):** sin tabla ni endpoint. El campo de correo y el botón "Suscribirme"
están **deshabilitados** con `title`/`aria-label` declarando que no está disponible —
mismo patrón que el buscador del header, nunca un formulario que finge funcionar.

**Contraste sobre fondos reales de esta página** (no asumido de la sección 2, vuelto a
medir porque esta página usa placeholders de color distintos y un fondo `--ground` nuevo):

| Texto | Contra | Ratio medido | Resultado |
|---|---|---|---|
| H1, palabra "Perú" (`text-brand-text`, 48px/600) | `--surface` (columna izquierda del hero, la foto queda confinada a la derecha) | 9.04:1 | PASA |
| Sello de confianza (`text-text-2`, 14px) | `--surface` | 9.31:1 | PASA |
| Chip de duración/categoría (`text-text-muted`, 12px, token ya corregido) | `--surface` | 5.17:1 | PASA |
| `--text-muted` sobre `--ground` (inyectado directo, sin depender de dónde caiga en el layout) | `--ground` | 4.76:1 | PASA |
| Título/descripción del newsletter (`--ink`/`--text-2`) | `--brand-50` (calculado, no hay ese fondo en la sección 2) | 15.30:1 / 8.48:1 | PASA |
| Nombre de destino/experiencia (blanco) sobre degradado `ink/80` | peor caso calculado (foto blanca pura bajo el velo, más claro que cualquier placeholder real usado) | 8.77:1 | PASA incluso en el peor caso |

**Responsive — cero desborde horizontal, `scrollWidth` vs `clientWidth`:** 360→360,
768→753, 1024→1009, 1280→1265, 1440→1425 (los cuatro últimos con scrollbar descontada por
el propio navegador). Un defecto real se encontró y corrigió: en mobile, el enlace "Ver
todos los tours" quedaba **pegado a la izquierda** en vez de a la derecha al envolver
dentro del `flex` del título de sección (`justify-between` ancla a la izquierda una línea
con un solo ítem) — se cambió a `ml-auto` en el `action` de `x-ui.section-title` y se
reverificó: `x=169.6, right=344` (borde derecho real del contenedor) a 360px.

**Carrusel:** clic real sobre la flecha "Siguiente" mueve `scrollLeft` de `0` a `143` en un
viewport angosto (480px, donde hay overflow real que desplazar); a 1440px las 2 tarjetas ya
entran completas en el contenedor y `scrollLeft` no se mueve — es el comportamiento
correcto con solo 2 tours, no un carrusel roto.

**Peso de página:** 229.7 KB transferidos (HTML+CSS 59.28 KB+JS 106.88 KB+favicon 0.8 KB,
medido con la Resource Timing API del propio navegador; Google Fonts no expone su tamaño
por CORS). Muy por debajo del presupuesto de ~1.5 MB — todas las fotos de catálogo son SVG
inline en base64, no archivos de red.

**Suite:** 40 tests, 139 aserciones, verdes contra MySQL, antes y después de los cambios de
esta etapa (`Tour::scopeFeatured()` no tiene test propio todavía — cubierto solo
indirectamente por no romper nada existente).

## 11. Contacto (lote 1, etapa C)

`resources/views/contact.blade.php`, ruta `/contacto` (nombrada `contact` en
`routes/web.php`) — el header y el footer ya apuntaban a `route('contact')` condicionado con
`Route::has('contact')`; con la ruta creada, el enlace del nav resuelve solo, sin tocar esos
componentes. La marca del mockup ("Perú Local") queda descartada; la página usa `x-layout` y
por tanto Pacha Viva de punta a punta (header/footer del lote 0/etapa A, sin tocarlos).

**Composición:** hero partido (migas de pan + eyebrow + h1 + 3 atributos con ícono + foto a
la derecha), formulario + información de contacto (2 columnas), preguntas frecuentes + mapa
(2 columnas). Todo compuesto con el inventario de la sección 5 más 3 componentes nuevos:

| Componente Blade | Uso |
|---|---|
| `x-ui.breadcrumbs` **(nuevo, etapa C)** | Migas de pan genéricas (`items: [{label, href?}]`). El último ítem nunca es enlace (`aria-current="page"`); los intermedios solo son `<a>` si traen `href` real — nunca un enlace a "#". Reutilizable en cualquier página futura con miga de pan. |
| `x-ui.hero-attribute` **(nuevo, etapa C)** | Ícono + título + descripción del hero partido de Contacto. Distinto de `x-ui.feature-card` (sin borde/sombra de tarjeta) y de `x-ui.trust-badge` (una sola línea, sin descripción). El mockup dibuja el ícono sin caja; se mantuvo el fondo `bg-brand-50`/`text-action` por consistencia con el resto del inventario de íconos del sistema (decisión de diseño, no un olvido). |
| `x-ui.contact-info-card` **(nuevo, etapa C)** | Tarjeta de la columna "Información de contacto". Mismo patrón que `x-ui.stat`: recibe un booleano `has` ya resuelto por el caller y **no se renderiza si es falso** — nunca una tarjeta hueca. El valor (teléfono/correo/dirección/redes) llega por el slot, ya con su propio enlace. |

**C1 — formulario sin persistencia, declarado:** `contact_messages` no tiene migración (está
en el contrato de datos del lote 2, se implementa en el lote 3). El `<form>` no lleva
`action`/`method`, tiene `onsubmit="return false"` y el botón de envío está `disabled` con
`title`/`aria-label` = "Formulario de contacto — próximamente, sin envío real todavía" —
mismo patrón que el buscador del header y el newsletter de la Home. Medido en el navegador:
`{ type: "button", disabled: true, title: "...próximamente...", ariaLabel: "...próximamente..." }`
— nunca `type="submit"` porque `x-ui.button` fija `type="button"` siempre que no hay `href`
(así ningún consumidor del componente dispara un submit accidental).

**C2 — datos de contacto reales, verificado en las dos direcciones:** `contact_phone`,
`contact_email`, `contact_address`, `social_instagram_url`, `social_facebook_url`,
`social_youtube_url` y `privacy_policy_url` son claves nuevas de `Setting` (no requieren
migración: `Setting` ya es un almacén clave/valor genérico). Hoy, sin ningún dato sembrado:
0 tarjetas en el DOM (`querySelector` no encuentra ninguna) y se muestra el texto
`site.contacto.info.empty`. Sembrando las 6 claves por Tinker: aparecen las 4 tarjetas
(teléfono/WhatsApp, correo, dirección, redes con 3 íconos + WhatsApp derivado del teléfono),
el enlace `tel:`/`mailto:` correctos, y el bloque de ayuda inmediata con WhatsApp bajo el FAQ.
Se revirtieron las 7 claves de prueba (`Setting::query()->where('key', ...)->delete()` +
`Cache::forget`) al terminar — confirmado `0 filas restantes` y el DOM vuelto a 0 tarjetas.

**Defecto real encontrado y corregido durante esta verificación:** pasar una URL con query
string a `<x-ui.button href="{{ $mapsUrl }}">` (interpolación con `{{ }}`) queda escapada dos
veces — Blade escapa `&` a `&amp;` al pasarla como atributo del componente, y `x-ui.button`
vuelve a escapar `{{ $href }}` internamente, dejando `&amp;amp;` en el HTML servido. Medido:
`outerHTML` mostraba `...api=1&amp;amp;query=...` antes del fix. Se corrigió a `:href="$mapsUrl"`
(sintaxis de dos puntos, pasa el valor de PHP sin re-escapar) en los dos botones de esta
página que arman una URL con `&` (el CTA de WhatsApp y "Ver en Google Maps"); reverificado con
`getAttribute('href')` → `https://www.google.com/maps/search/?api=1&query=Av.+El+Sol+123%2C+Cusco`
(un solo `&`, correcto). Cualquier futuro `x-ui.button href="{{ ... }}"` con una URL de más de
un parámetro de query arrastra el mismo riesgo — usar siempre `:href=`.

**C3 — mapa sin iframe, qué falta para activarlo de verdad:** el pin del mockup apunta a un
punto de Cusco que no es de la clienta, y un `<iframe>` de Google Maps mete un tercero que
puede chocar con la CSP en producción (ya bloqueó analítica en otro proyecto del estudio —
ver `project_lima_csp_analitica` en la memoria del estudio). Se resolvió con un marcador de
posición estático (SVG de pin, sin red, `aspect-[4/3]` con borde punteado) que se muestra
siempre, y un enlace real "Ver en Google Maps" que **solo existe si `contact_address` tiene
valor** — construido como `https://www.google.com/maps/search/?api=1&query=` + dirección
(búsqueda por texto, nunca coordenadas inventadas). Sin dirección, se muestra el texto
`site.contacto.map.missing` en su lugar. **Para activar un mapa embebido real** hace falta,
en este orden: (1) que la clienta confirme la dirección exacta y, si se quiere un pin preciso
en vez de una búsqueda por texto, las coordenadas (lat/lng) de su oficina; (2) una decisión
explícita de Anyerson sobre iframe vs. enlace externo, evaluando el CSP que se defina para
producción (si el iframe entra, `frame-src` tiene que permitir `google.com/maps` en esa
política); (3) si se opta por iframe, generarlo con el modo "sin API key" de Google
(`https://www.google.com/maps?q=...&output=embed`) para no depender de facturación de Google
Cloud, o evaluar un proveedor sin cookies de terceros si el CSP resulta muy estricto.

**C4 — privacidad (Ley 29733):** la casilla usa `x-ui.form.checkbox`, que asocia `<label for>`
de verdad (confirmado: `label[for="field-privacy"]` existe y contiene el texto completo). El
enlace a la política solo se imprime si `Setting::get('privacy_policy_url')` tiene valor (la
misma clave que ya usa el footer); sin ella, texto plano "política de privacidad (en
preparación por la clienta)" sin ningún `<a>` — confirmado `hasLink: false` en el DOM. Nunca
un `href="#"`.

**C5 — FAQ, qué debe redactar la clienta:** las 5 preguntas viven en
`lang/es/site.php` → `contacto.faq.items`. Las primeras 4 son copy plausible de marketing
(reserva, pago, personalización, qué incluye el precio) — quedan como contenido de arranque
del lote 1, migran a CMS en el lote 3. La quinta, **cancelación y reembolso, NO se redactó
acá**: la respuesta declara explícitamente que la política todavía no está publicada y que la
clienta debe redactarla y aprobarla antes de habilitar reservas en línea (es un obligatorio
legal, no un texto de relleno). Pendiente para la clienta: aprobar/corregir las 4 primeras y
escribir la de cancelación.

**C6 — accesibilidad:** los 4 campos de texto (`x-ui.form.input`/`.select`/`.textarea`) usan
`<label for>` real (no `placeholder` como etiqueta); el `focus-visible` global de
`resources/css/app.css` no se desactivó en ningún control — medido con `Tab` real hasta el
input "Nombre completo": `outlineStyle: solid, outlineColor: rgb(27, 105, 73), outlineWidth: 2px`.
El acordeón de FAQ reusa `x-ui.faq-item` (`<details>/<summary>` nativo, sin tocar) — probado
con foco + tecla `Enter` real: `openBefore: false` → `openAfter: true`. No se agregó ningún
estado de error en vivo (el formulario no envía nada hoy); los primitivos de formulario ya
soportan `error`/`aria-invalid`/`aria-describedby` para cuando el lote 3 conecte el backend
real, no hizo falta tocarlos.

**C7 — i18n:** todo el copy de Contacto vive en `lang/es/site.php` → clave `contacto` (no
`home`, como pidió el brief). Nada cableado en `contact.blade.php` salvo los `href` técnicos
(`tel:`, `mailto:`, `wa.me`, Google Maps) y las clases Tailwind.

**C8 — ruta y navegación, verificado con clic real:** desde `/` (Home), clic en "Contacto" del
`<nav aria-label="Principal">` del header navega a `http://…/contacto` (`title: "Contacto ·
Pacha Viva"`); la miga de pan en Contacto renderiza `"Inicio / Contacto"` con "Inicio"
enlazado a `/` y "Contacto" marcado `aria-current="page"`. El resto del nav
(Tours/Destinos/Experiencias/Nosotros) sigue en `#`, sin tocar — son lote 3 y Nosotros
(delegado aparte), no de esta etapa.

**Responsive — cero desborde horizontal, `scrollWidth` vs `clientWidth`:** 360→360, 768→768,
1024→1024, 1280→1280, 1440→1440 (exacto, sin diferencia) en el estado real de hoy (sin
Settings sembrados) y reverificado igual con los 6 Settings de prueba sembrados en
1024/1280. El hero partido colapsa a 1 columna con la foto debajo del texto por debajo de
`lg`, y los 3 atributos con ícono pasan de fila a columna en `<sm`; las dos rejillas de 2
columnas (formulario/información, FAQ/mapa) colapsan a 1 columna por debajo de `lg`, sin
recortes ni scroll lateral en ningún punto intermedio.

**Contraste — muestreado en el navegador sobre el fondo real de esta página:**

| Texto | Contra | Ratio medido | Resultado |
|---|---|---|---|
| H1, palabra "aventura" (`text-brand-text`, 48px/600) | `--surface` (la foto queda confinada a la columna derecha, igual que en Home) | 9.04:1 | PASA |
| Eyebrow "ESTAMOS PARA AYUDARTE" (`text-brand-text`, 12px) | `--brand-50` | 8.25:1 | PASA (mismo par ya documentado en §2) |
| Teléfono y correo en la tarjeta de info (`text-brand-text`, **14px** — el caso puntual que advertía el brief: "verde de marca en texto chico") | `--surface` (fondo blanco de la tarjeta) | **9.04:1** | PASA, con margen — no hizo falta un tono nuevo |
| Título/descripción de los 3 atributos del hero (`text-ink`/`text-2`) | `--surface` | 15.44:1 (ink) | PASA |

Ningún par de esta página necesitó un token nuevo: `--brand-text` ya pasa AA incluso al
tamaño más chico usado (14px), a diferencia de `--text-muted` que sí se corrigió en la etapa B.

**Peso de página:** 224.1 KB transferidos (229,488 bytes por la Resource Timing API del propio
navegador, sin Settings sembrados — la foto de contacto es el mismo tipo de placeholder SVG
en base64 que usa Home, sin archivos de red). Por debajo de los 229.7 KB de la Home.

**Suite:** no se corrió con la suite en verde de fondo — el agente de backend-laravel estaba
migrando (`add_cover_image_to_destinations_table`, `add_cover_image_to_experiences_table`,
`create_team_members_table`) en paralelo mientras se maquetaba esta etapa, y dos corridas
consecutivas de `vendor/bin/phpunit` dieron números de tests y fallos distintos entre sí
(65-66 tests, 3-4 fallos primero, 13 errores después), siempre dentro de
`AuditSampleDataCommandTest`, `TeamMemberCatalogTest` y `*CoverImageUploadSecurityTest` — los
tres archivos nuevos del backend, ninguno tocado por esta etapa. Ningún archivo de Contacto
(`contact.blade.php`, `routes/web.php`, `lang/es/site.php`, los 3 componentes nuevos) aparece
en ninguna traza de fallo. No se puede declarar "suite verde" en este momento porque el
estado de la base es un blanco móvil compartido con otro agente; sí se puede declarar que
esta etapa no agregó ninguna falla nueva atribuible a sus propios archivos.

## 12. Nosotros + fotos de catálogo de la Home (lote 1, etapa D — última de maquetación)

Con las brechas de esquema de `docs/lote-1/01-esquema-lote1.md` ya cerradas por
backend-laravel (S1: `cover_image_path`/`cover_image_alt` en `Destination`/`Experience`; S2:
`TeamMember`), esta etapa hace dos cosas: `resources/views/nosotros.blade.php` (ruta
`/nosotros`, nombrada `about` en `routes/web.php` — el header/footer ya apuntaban a
`route('about')` condicionados con `Route::has('about')`, sin tocar esos componentes) y
conectar `home.blade.php` a las fotos reales de catálogo.

**Parte 1 — fotos de catálogo de la Home:** `destination-card`/`experience-card` ahora reciben
`$model->coverImageUrl() ?? PlaceholderImage::svg(...)` (el placeholder como respaldo, nunca al
revés) y `cover_image_alt` como alt real, con fallback al nombre del modelo si el alt está
vacío — nunca el nombre del archivo ni un alt vacío en una imagen con significado. Verificado
en las dos direcciones: hoy, sin ninguna foto cargada, los 2 destinos/2 experiencias sembrados
siguen mostrando el placeholder SVG exactamente igual que antes de esta etapa; sembrando
`cover_image_path`/`cover_image_alt` en un `Destination` real (`Storage::disk('public')->put()`
+ `save()`, foto JPEG real, no el path a mano), la tarjeta sirve `http://.../storage/destinations/qa-test.jpg`
(HTTP 200 confirmado con una petición real, no solo el atributo `src`) con el alt sembrado en
el DOM. Revertido: `cover_image_path`/`cover_image_alt` a `null` y el archivo borrado del disco.

**Composición de Nosotros:** hero partido (igual patrón que Contacto: migas de pan + h1 + línea
verde de marca + párrafo + foto a la derecha, sin la foto sobre el texto) → tarjeta flotante de
estadísticas (D1) → "Nuestro propósito" (2 párrafos + firma en `font-script`/Caveat + foto) →
"Nuestros valores" sobre `--ground` (4 `x-ui.value-item`, nuevo) → "Nuestro equipo" (D2,
condicional) → banda de CTA (foto + texto sobre `--ground`, sin foto detrás del texto — mismo
patrón que el newsletter de la Home, nunca un texto sobre foto sin velo).

**D1 — cero cifras inventadas, reusando `x-ui.stats-strip` sin duplicar su lógica:** la tarjeta
flotante es literalmente `<x-ui.stats-strip />` (mismo componente de la Home), montada como
hermana entre el hero y "Nuestro propósito" con margen negativo (`-mt-20 sm:-mt-16 lg:-mt-14`),
no `absolute`+`overflow-visible` — así ningún `overflow-hidden` de una sección vecina puede
recortarla. Verificado en las dos direcciones: **sin `Setting` sembrado (estado real de la base
hoy)**, `document.body.innerText` no contiene "Años de experiencia" (`false`, no solo
visualmente vacío) y las 5 resoluciones no tienen desborde horizontal
(`scrollWidth === clientWidth` exacto en 360/768/1024/1280/1440). **Sembrando 2 de 4**
(`stat_years_experience=10`, `stat_happy_travelers=500`, vía `Setting::set()`, nunca INSERT
crudo) aparecen "10+ Años de experiencia" y "500+ Viajeros felices" sin que la rejilla se rompa
con 2 en vez de 4, y la geometría real (`getBoundingClientRect`, navegación fresca con
`scrollTo(0,0)` antes de cada medición para no arrastrar scroll de un paso anterior) confirma
el solape real: la tarjeta empieza **56 a 80 px por encima** del borde del hero (según
breakpoint) y termina **56 a 138.5 px por encima** del título "Nuestro propósito" — se
superpone al hero sin nunca tocar el texto de la sección siguiente, en los 5 breakpoints, sin
overflow horizontal en ninguno. Revertido: 0 filas de `Setting` con esas 2 claves y su caché
(`Cache::forget`), confirmado por consulta y por releer la página (0 coincidencias de "Años de
experiencia"/"[QA]" en el HTML servido).

*Nota de proceso:* una primera lectura visual de la captura sugirió una franja gris entre la
tarjeta y "Nuestro propósito"; el muestreo real (`elementFromPoint` + `getComputedStyle` en
varios puntos) descartó el defecto — es la sombra (`shadow-sm`) propia de la tarjeta sobre
fondo blanco, no un color de fondo distinto. Se documenta porque "se ve raro en la captura" no
bastaba como evidencia y casi se reporta como defecto sin serlo.

**D2 — el equipo arranca vacío, sección condicionada:** sin registros publicados,
`document.body.innerText` no contiene "Nuestro equipo" (la sección entera no existe en el DOM,
no solo el título). Sembrando 2 `TeamMember` de prueba (prefijo `[QA]`, publicados): la sección
aparece con las 2 tarjetas dentro de `x-ui.carousel-shell` (el mismo componente de "Tours
destacados"), sin asumir 4 columnas. Carrusel probado con **clic real** en "Siguiente"/"Anterior"
a 480px (donde hay overflow real que desplazar) y `scrollLeft` medido en el elemento, no
capturado: `0 → 48 → 0`. Con 1 solo miembro (no probado en este lote, mismo patrón ya usado por
"Tours destacados" en la Home) cae a tarjeta suelta sin carrusel. Revertido: `TeamMember::where('name->es','like','[QA]%')->delete()`,
0 filas confirmadas por consulta y por releer la página.

**D3 — copy pendiente de aprobación de la clienta:** el texto de "Nuestro propósito" y
"Nuestros valores" vive en `lang/es/site.php` → `nosotros.purpose`/`nosotros.values` con
comentarios `PENDIENTE clienta` en el propio archivo. Dos afirmaciones concretas quedan
marcadas explícitamente porque son verificables sobre la empresa, no relleno: el párrafo del
hero/propósito afirma "impacto positivo en las comunidades locales y en el medio ambiente", y
"Sostenibilidad" en valores promete "cuidado del medio ambiente y las culturas locales". La
clienta debe aprobar o redactar de nuevo estas frases antes de producción — mismo criterio ya
aplicado al FAQ de cancelación de Contacto (C5).

**D4 — i18n:** todo el copy de Nosotros vive en `lang/es/site.php` → clave `nosotros` (no
`home` ni `contacto`). Nada cableado en `nosotros.blade.php` salvo los SVG de íconos (mismo
patrón que Home/Contacto) y las clases Tailwind.

**D5 — ruta y navegación, verificado con clic real:** desde `/` (Home), clic en "Nosotros" del
`<nav aria-label="Principal">` navega a `http://.../nosotros` (`title: "Nosotros · Pacha Viva"`,
`document.title` real, no asumido); la miga de pan renderiza "Inicio / Nosotros" con "Inicio"
enlazado a `/`. Desde Nosotros, clic en "Contacto" navega a `/contacto`, y desde Contacto, clic
en "Nosotros" vuelve a `/nosotros` — los 3 saltos con clic real (`page.click`), no con
`goto()` directo. El resto del nav (Tours/Destinos/Experiencias) sigue en `#`, sin tocar — son
lote 3.

**Responsive — cero desborde horizontal, `scrollWidth` vs `clientWidth`:** 360→360, 768→768,
1024→1024, 1280→1280, 1440→1440, exacto en los dos estados (sin `Setting`/equipo sembrados, y
con la tarjeta de stats + carrusel de equipo sembrados — el estado más frágil según el brief).
El hero partido colapsa a 1 columna con la foto debajo del texto por debajo de `lg`; "Nuestros
valores" pasa de 4 a 2 a 1 columna (`lg`/`sm`/base); la banda de CTA apila foto arriba y texto
abajo por debajo de `lg`.

**Contraste — medido en el navegador sobre el fondo real de esta página (no asumido):**

| Texto | Contra | Ratio medido | Resultado |
|---|---|---|---|
| H1 "Nosotros" (`text-ink`, 48px/600) | `--surface` (blanco real muestreado, `rgb(255,255,255)`) | 16.78:1 | PASA |
| Línea verde "Conectamos viajeros..." (`text-brand-text`, 18px/600) | `--surface` | 9.04:1 | PASA (mismo par ya documentado en §2/§10) |
| Título banda CTA (`text-ink`, 30px/600) | fondo real muestreado `rgb(244,246,243)` = `--ground` (confirmado, no asumido: la banda usa `bg-ground`, no `bg-brand-50` como el newsletter de Home) | 15.44:1 | PASA |
| Descripción banda CTA (`text-text-2`, 16px) | `--ground` (idem) | 8.56:1 | PASA |
| "Nuestros valores": fondo de sección muestreado antes de fijar el color del texto | `rgb(244,246,243)` = `--ground` | — | Confirma `--ground`, no blanco: reusa el par `ink`/`text-2` sobre `--ground` ya PASA en §2 |

Ningún par de esta página necesitó un token nuevo. La banda de CTA se resolvió con texto sobre
un panel de color sólido (`--ground`), igual que el newsletter de la Home — nunca texto de
marca directamente sobre la foto, evitando el riesgo que advertía el brief para un hero
*full-bleed*.

**Peso de página:** 210.7 KB transferidos (Resource Timing API, estado real de hoy sin
`Setting`/equipo sembrados) — por debajo de Home (229.7 KB) y Contacto (224.1 KB). La foto de
catálogo real sembrada en la Home durante la verificación (JPEG generado con GD, ~7 KB) no se
dejó en la base; el peso de Home en producción sigue siendo 100% placeholders SVG hasta que la
clienta cargue fotos reales desde el panel.

**Suite:** 65 tests, 267 aserciones, verdes contra MySQL (`pachaviva`), antes y después de
todos los cambios de esta etapa, incluida la corrida final tras revertir los datos de prueba.

**Base de datos al cierre de esta etapa:** confirmado por consulta directa — 0 filas en
`team_members`, 0 `Setting` con las claves `stat_years_experience`/`stat_happy_travelers`,
`destinations.id=1` con `cover_image_path`/`cover_image_alt` de vuelta en `null`, y el archivo
`storage/app/public/destinations/qa-test.jpg` borrado del disco. La base queda exactamente
como estaba antes de este lote.

**Pendiente declarado (no tapado):**

- D3: todo el copy de "Nuestro propósito" y "Nuestros valores" espera aprobación de la clienta
  (ver comentarios `PENDIENTE clienta` en `lang/es/site.php`).
- Fotos reales: hero, propósito, equipo (por persona) y CTA de Nosotros siguen en placeholder
  SVG — igual que toda la Home/Contacto, a la espera de que la clienta suba material real desde
  el panel de Filament.
- No se probó el camino de "1 solo miembro de equipo" (tarjeta suelta sin carrusel) con datos
  reales en este lote — el patrón ya existe y está probado en "Tours destacados" de la Home,
  pero no se repitió la prueba puntual para Nosotros por presupuesto de tiempo.
- No se verificaron lectores de pantalla reales (NVDA/VoiceOver), solo semántica ARIA/nativa —
  mismo pendiente ya declarado en la etapa A.

## 13. Arreglo post-auditoría (02/09/2026) — carrusel y `<head>` de SEO

Tres arreglos sobre `docs/lote-1/qa-cro-2026-09-02.md` (defectos Medio/Bajo) y
`docs/lote-1/seo-2026-09-02.md` (S-02, S-03, S-12). Verificado con Playwright sobre CDP
(`127.0.0.1:9333`, el MCP estaba tomado) — clic y pulsación reales, nunca por captura.

### 13.1 Carrusel — se quitó el patrón tablist a medias (defecto Medio)

`role="tablist"`/`role="tab"` estaba a medio implementar (sin flechas de teclado entre
pestañas, sin roving tabindex, puntos sin `aria-label`). Decisión: **se quitó el patrón de
pestañas**, no se completó. Razón mirando el código y el uso real
(`home.blade.php`/`nosotros.blade.php`): es una región de tarjetas con scroll-snap (tours
destacados, equipo), no paneles de contenido intercambiables — un tablist ahí le habría
prometido al lector de pantalla una navegación por flechas que nunca iba a existir. Ahora:
`role="region"` (ya existía) + `role="group"` en el contenedor de puntos + cada punto con
`aria-label`/`aria-current` reales, sin fingir semántica de pestaña.

Verificado con teclado real (Home, `/`, viewport 1360×900): `Tab` llega al primer punto en el
paso 16 (logo → menú → selector de idioma → selector de moneda → CTA "Contáctanos" → CTAs del
hero → tarjetas de tours → punto 1), con `aria-label="Ir a la tarjeta 1 de 2"`,
`aria-current="true"` y outline visible (`solid 2px`) ya en foco — sin regresión en el orden de
tabulación general. `Enter` y `Espacio` activan el botón (comportamiento nativo, sin JS extra
necesario). `ArrowRight` **no** mueve el foco entre puntos (confirmado:
`arrowRightChangedFocus: false`) — correcto para botones sueltos, sería un defecto si esto
fuera un tablist real.

**Hallazgo colateral (resuelto en la ronda 2, ver §14.3):** con el carrusel de 4 slides del
`/_styleguide` en 1024px, `track.scrollWidth` (1072) - `clientWidth` (945) deja solo 127px de
scroll real; al clicar el punto 3 el `go()`/`sync()` existente pide `scrollTo(544)` pero el
navegador lo recorta a 127, y `sync()` sigue marcando el punto 1 como actual. Mismo patrón en
Home con 2 tours: ahí `maxScroll` es 0 (`scrollWidth === clientWidth`), así que el segundo punto
jamás produce un cambio visual/de estado al activarse. Quedó expuesto justo por tener pocos
elementos que ya caben en pantalla — resuelto ocultando flechas y puntos cuando no hay nada que
desplazar (§14.3), en vez de arreglar la matemática de `go()`/`sync()` para el caso de 4 slides
que sí desbordan (ese caso sigue teniendo el límite descrito arriba, pero no es un control roto:
ahí sí hay overflow real y los puntos 1-2 sí navegan).

### 13.2 Área de toque de los puntos (defecto Bajo)

Antes: 8×8 px (inactivo) y 24×8 px (activo), bajo el mínimo de 24×24. Se envolvió el punto
visual (sin cambiar su tamaño gráfico) en un `<button class="flex h-6 w-6 items-center
justify-center ...">`; el punto sigue siendo un `<span aria-hidden="true">` interior de 8×2/24×2
(en `rem`: `h-2`, `w-2`/`w-6`).

Medido con `getBoundingClientRect()` real (no CSS leído, clic real de por medio):

| Página | Viewport | Punto 1 | Punto 2 |
|---|---|---|---|
| Home | 360×900 | 24×24 | 24×24 |
| Home | 768×900 | 24×24 | 24×24 |
| Home | 1360×900 | 24×24 | 24×24 |
| `/_styleguide` (4 puntos) | 1024×900 | 24×24 | 24×24 (ídem puntos 3 y 4) |

Sin desborde horizontal introducido: `scrollWidth === clientWidth` del documento en 360, 768,
1024, 1280 y 1440 (medido de nuevo tras el cambio, sigue en cero — no se rompió el gate del
CRO).

### 13.3 `<head>` — canonical, meta description, Open Graph, Twitter Card (S-02/S-03/S-12)

`resources/views/components/layout.blade.php` gana 4 props nuevos: `description`, `canonical`,
`ogImage`, `ogType` (todos opcionales, con fallback).

- **Canonical (S-02):** `url()->current()` — **nunca** `config('app.url')` (ese es el mismo
  patrón que ya rompió las imágenes por `APP_URL=http://tours-word.test`, un host que no
  resuelve en esta máquina — Defecto 5 del CRO / S-04 del SEO). Verificado sirviendo en este
  entorno: `<link rel="canonical" href="http://127.0.0.1:8000/nosotros">` — refleja el host real
  de la request, no el `.env` roto.
- **Prefijo de idioma (S-08, decisión pendiente del backend):** no target a ninguna ruta con
  nombre porque `Route::get('/', ...)` no tiene `name()` hoy (y no me toca `routes/web.php`).
  Elegí `url()->current()` en vez de esperar nombres de ruta: como lee la request entrante, el
  canonical/hreflang siguen siendo correctos el día que el backend anteponga `/es/`, `/en/`,
  `/pt-br/` — no hay nada que reescribir en el layout cuando eso pase.
- **`hreflang` (S-08, punto 4):** con un solo locale activo se emite un único alternate
  autorreferencial (`hreflang="es"`) + `x-default` apuntando también a `es` (mercado primario
  hispanohablante, tal como recomienda el informe). Se agregan solo en páginas indexables
  (`@unless($noindex)`).
- **Meta description (S-03):** cada vista pasa la suya vía `lang/es/site.php`
  (`home.meta.description` 154 caracteres, `nosotros.meta.description` 131 caracteres, medidos
  con `mb_strlen`). `contact.blade.php` no es mío en este lote (el backend está haciendo que el
  formulario envíe de verdad) y no le agregué el prop: cae al fallback
  `site.seo.default_description` (142 caracteres) — un one-liner para quien toque ese archivo en
  la ronda 2 (`description="{{ __('site.contacto.meta.description') }}"` siguiendo el mismo
  patrón).
- **Title de Home (S-12):** ya no cae al fallback de solo-marca. `site.home.meta.title` =
  "Agencia de turismo en Cusco, Perú" (33 caracteres); title final servido: "Agencia de turismo
  en Cusco, Perú · Pacha Viva".
- **Open Graph / Twitter Card (S-03):** los 5 tags mínimos + `og:site_name`/`og:locale` +
  `twitter:card=summary_large_image`. `og:image`/`twitter:image` usan el logo real de marca
  (`public/images/brand/logo.svg` vía `asset()`, que sí refleja el host real de la request — no
  el disco `public` de `Storage`, ese es el que rompe con `APP_URL`) como *stand-in*: **no existe
  todavía una imagen de 1200×630 diseñada para compartir en redes** (las fotos de hoy son
  placeholders SVG inline sin archivo real que enlazar). Aviso explícito: SVG no es
  universalmente soportado como `og:image` — algunos validadores de Facebook/LinkedIn lo
  rechazan. Pendiente antes de publicar: un JPG/PNG de 1200×630 real.
- **JSON-LD (S-07):** no se implementó. El propio informe de SEO lo bloquea parcialmente por
  datos (NAP/`Setting` vacío, Defecto 1 del CRO) y exige escapado explícito
  (`JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP`) para no repetir el XSS ya ocurrido en
  otro proyecto de la cartera con JSON-LD sin escapar — me pareció que decidir esa fuente de
  datos y centralizar el escapado le corresponde al backend en la ronda 2, tal como habilita el
  brief ("si el escapado seguro te parece que excede este arreglo, decilo").
- **`/_styleguide` sigue `noindex`:** verificado sirviendo — `<meta name="robots"
  content="noindex, nofollow">` presente, y sin ningún tag de OG/Twitter/hreflang (excluidos a
  propósito en páginas `noindex`, no aportan nada a una página que no se debe compartir ni
  indexar).

Verificado por `curl` contra las 4 rutas después de `view:cache` + `npm run build`: `/`,
`/nosotros`, `/contacto`, `/_styleguide` → HTTP 200, sin tocar `routes/web.php` ni ningún archivo
de `app/`.

## 14. Ronda 2 de arreglos (02/09/2026) — botón submit, `old()` del select y carrusel sin desborde

Tres defectos de componentes, verificados con Playwright sobre el puente CDP (`127.0.0.1:9333`,
el MCP estaba tomado), clic/envío reales, nunca por captura. Las rutas ya están prefijadas por
idioma (`/es/...`) por el cambio en paralelo del backend.

### 14.1 `x-ui.button` acepta `type` (button|submit|reset)

`resources/views/components/ui/button.blade.php` fijaba `type="button"` siempre que no fuera un
`<a>`, así que no servía para enviar un formulario — el formulario de Contacto tenía que usar un
`<button>` nativo con las clases del variant "primary"/size "md" copiadas a mano. Se agregó el
prop `type` (default `'button'`, validado contra una lista blanca `button|submit|reset` antes de
imprimirlo) y se volvió a usar el componente en `contact.blade.php`
(`<x-ui.button type="submit" :icon="$iconSend">`).

**Verificado con envío real del formulario** (POST a `contact.store`, `contact_messages` ya
migrada por el backend en esta ronda): antes de la corrida, `ContactMessage::count()` = 0.
Enviado el formulario con datos válidos, la página redirige a `/es/contacto` con "¡Gracias!" en
el DOM y el botón medido tiene `type="submit"` real (no asumido: `getAttribute('type')` sobre el
botón antes del clic). Tras el envío, `ContactMessage::count()` = 1 y el registro guardado
coincide campo a campo con lo tipeado
(`{"name":"QA Ronda2","email":"qa-ronda2@example.com","phone":"+51 987654321","subject":"reserva","message":"..."}`).
Fila de prueba borrada al terminar, `count()` vuelto a 0.

**Ningún botón existente se volvió submit por accidente:** `x-ui.button type="submit"` solo
aparece en esta única línea del proyecto (grep confirmado); todo el resto de instancias (CTAs de
home/nosotros/header, flechas y puntos del carrusel — estos últimos son `<button>` nativos, no
`x-ui.button`) sigue con el default `button` sin tocar.

### 14.2 `old()` en el select de "Asunto"

`resources/views/components/ui/form/select.blade.php` no marcaba ningún `<option>` como
`selected` en función del valor previo: tras un error de validación, el `<select>` volvía siempre
a mostrar el placeholder, perdiendo la elección del visitante aunque los inputs de texto sí la
conservaran. Se agregó el prop `value` (mismo patrón que `x-ui.form.input`) y `@selected()` en el
placeholder (`blank($value)`) y en cada opción (`(string) $value === (string) $optionValue`).
`contact.blade.php` pasa `:value="old('subject')"`.

**Verificado provocando un error real:** formulario enviado con asunto = "Otro" (la última
opción, elegida a propósito para que un falso positivo por "primera opción por defecto" se
notara) y correo inválido (`esto-no-es-un-correo`). El POST devuelve `role="alert"` visible
(`hasErrorAlert: true`) y, en el DOM ya recargado con la respuesta del servidor,
`#field-subject`.`value` = `"otro"` y el texto de la opción seleccionada = `"Otro"` — idéntico a
lo elegido antes de enviar. `ContactMessage::count()` siguió en 0 (la validación bloqueó el
guardado, como debía). Nombre y correo tecleados también se preservaron (`old('name')`/`old('email')`
ya funcionaban antes de este fix, no regresionados).

### 14.3 Carrusel — flechas y puntos se ocultan cuando no hay nada que desplazar

`resources/views/components/ui/carousel-shell.blade.php`: se agregó `maxScroll` al estado Alpine,
medido con un `ResizeObserver` sobre el track (`scrollWidth - clientWidth`, recalculado solo al
cambiar el tamaño real del track, sin listener de `window` a mano) y la fila completa de
paginación (puntos + flechas "Anterior"/"Siguiente") se oculta con `x-show="maxScroll > 0"`
+ `x-cloak` (la regla `[x-cloak]{display:none}` ya existía en `app.css`). Se eligió ocultar en vez
de deshabilitar: con `x-show`, ningún botón queda en el DOM con `offsetParent` no nulo cuando no
tiene función, y reaparece solo al redimensionar a un ancho donde sí hay overflow — sin depender
de una decisión manual de "disabled" que hubiera dejado el control enfocable con Tab si se
olvidaba el atributo.

**Medido en dos anchos de Home (`Tours destacados`, 2 tours) donde el resultado es distinto,**
con `getBoundingClientRect()`/`getComputedStyle()` reales sobre el track y la fila de paginación,
no CSS leído en el código:

| Viewport | `scrollWidth` | `clientWidth` | `maxScroll` | `display` de la fila de paginación | Puntos/flechas focusables |
|---|---|---|---|---|---|
| 1440×900 | 1216 | 1216 | **0** | `none` | 0 de 2 / 0 de 2 |
| 360×800 | 576 | 313 | **263** | `flex` | 2 de 2 / 2 de 2 |
| 1440×900 (vuelto a redimensionar) | 1216 | 1216 | **0** | `none` | 0 de 2 / 0 de 2 |

El tercer renglón confirma que la reacción es en vivo (mismo `ResizeObserver`, sin recargar la
página): al volver a 1440px tras pasar por 360px, la fila vuelve a ocultarse sola.

**Sin overflow horizontal introducido** — `document.documentElement.scrollWidth` vs
`clientWidth`, iguales en las 5 resoluciones (360/768/1024/1280/1440) en Home, Contacto y
Nosotros (el gate del CRO no se rompió).

**Pendiente declarado, no de este alcance:** el caso de `/_styleguide` con 4 slides que sí
desbordan pero cuyo último punto no siempre puede marcarse `aria-current` por el recorte de
`scrollTo()` (documentado en §13.1) no se tocó — ahí sí hay overflow real (`maxScroll > 0`), así
que la fila de paginación se sigue mostrando correctamente; el límite es de la matemática interna
de `go()`/`sync()` con slides de ancho variable, no del criterio de visibilidad agregado en esta
ronda.

### 14.4 Qué no se tocó

`app/`, `database/`, `config/`, `routes/web.php`, `bootstrap/app.php`,
`resources/views/components/layout.blade.php`, `resources/views/layouts/**`,
`lang/es/contact-form.php`, `.env` — sin cambios. No se corrió la suite (la corre el backend). No
hizo falta `npm run build`: ningún cambio de esta ronda agrega una clase Tailwind nueva (el fix
del botón es un atributo `type`, el del select es `@selected()`, el del carrusel son directivas
Alpine `x-show`/`x-cloak` sobre clases ya compiladas); sí se corrió `view:clear` antes de medir,
para no verificar contra Blade compilado viejo.

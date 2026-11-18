# QA del port Home Inka Palace Travel sobre main

Veredicto: **APTO CON HUECOS** (0 P0, 3 P1, 4 P2, varios P3).

## 1. Suite
`DB_PORT=3307 php artisan test` -> 361 passed (1562 aserciones), 69 s. Verde.

## 2. Status
| URL | Resultado |
|---|---|
| `/` | 301 -> `/es` (sin barra final) |
| `/es/`, `/es/?muestra=1`, `/en/`, `/es/tours`, `/es/destinos`, `/es/experiencias`, `/es/nosotros`, `/es/contacto`, `/admin/login`, `/robots.txt` | 200 |
| Fichas (tours muestra-camino-inca-4-dias y muestra-tour-gastronomico-arequipa; destinos cusco, arequipa; experiencias cultura, trekking; /en/tours, /en/destinos/cusco) | 200 |
| `/sitemap.xml` | 404 por diseno (`Indexability::siteIsIndexable()` falso mientras `catalog_demo_content=true` e `is_staging_mirror`). Los slugs se tomaron de los enlaces de los listados. |
| `/es/styleguide` | 404 |

## 3. Barrido (17 URLs x 6 viewports = 102 combinaciones; scripts en scratchpad/pp/qa1.mjs)
- Overflow horizontal: 0/102.
- Console error/warning: 0/102. Requests >=400: 0/102.
- `<img>` sin atributo alt: 0. Imagenes rotas: 0. H1 unico: 102/102.
- Hosts externos: 102/102 piden `fonts.googleapis.com` (Caveat); `/es/nosotros` ademas `fonts.gstatic.com`.
- Foco visible: outline 2 px en los 14 primeros Tab de la home (ver `qa/main-focus--1440.png`).
- Targets <44 px (medidos con getBoundingClientRect, viewport <=768): la home no tiene ninguno. Internas: migas de pan 37x20 / 87x20, `Filtrar` 40 px de alto, boton favorito 40x40, `Solicitar reserva` 40 px de alto, enlaces de tarjeta 24 px de alto (el area clicable real es la tarjeta con `::before`), `<summary>` del FAQ de contacto 24 px, inputs 42 px. Admin Filament 36 px (fuera de alcance del rebrand).

## 4. Interacciones (Puppeteer, clic real)
PASS: hero siguiente/anterior/pausa (pausa se mantiene 7,5 s con aria-label "Reanudar carrusel")/miniaturas/Enter en el boton. FAQ abre/cierra, ArrowDown mueve el foco. Filtros de planes (1 tarjeta por rango, 4 en "Todos"). Moneda: PEN -> USD cambia los precios y persiste tras recargar y en listado y ficha (clave localStorage `pv_currency`). Selector EN desde `/es/tours` -> `/en/tours`; desde `/en/` -> `/es`. Buscador con Destino=Cusco -> `/es/tours?destino=cusco`, lista solo Cusco y el select queda preseleccionado. Menu movil 375: abre, `aria-expanded` correcto, items de 48 px y pills de 44 px, Esc cierra (`qa/main-menu-open--375.png`).
Observacion: el hero no responde a flechas izquierda/derecha del teclado (solo Tab + Enter). Los ArrowLeft/Right del JS son del lightbox de la galeria.

## 5. Subcarpeta
`php -S` en :8001 con APP_URL y ASSET_URL = `http://127.0.0.1:8001/tour-word/public` (solo en el comando). En 7 paginas: todo `fonts/`, `img/`, `build/` sale con el prefijo (4 build, 10 fonts, 2-31 img por pagina). CSS y JS compilados: 0 `url(/...)` ni cadenas `"/fonts|/img|/build|/storage"`. Los enlaces `route()` salen del host de la request (la simulacion no reproduce el prefijo de la ruta real: es limitacion de la prueba, no del codigo). PASS.

## 6. SEO / noindex
- `<meta robots noindex, nofollow>` en todas las paginas publicas, tanto con `IS_STAGING_MIRROR=true` como con el .env actual (porque `catalog_demo_content` esta fijo en true en config/cms.php). No se pudo probar el estado "indexable" sin editar codigo; lo cubren los tests.
- `/robots.txt`: `Disallow: /admin`, sin `Sitemap:` mientras no sea indexable (coherente con el 404 del sitemap).
- hreflang: solo en `x-layout` (es, en, x-default en la home). Las fichas con traduccion en fallback no emiten hreflang (diseno documentado).
- title y `og:site_name` = Inka Palace Travel; canonical `/es` y `/en`. `/admin/login` no lleva meta robots (cubierto por `Disallow: /admin`).

## 7. Contenido / marca
- Cifras, resenas y testimonios salen como marcadores visibles: `[4,9]`, `[N] resenas`, `[N]+ viajeros felices`, `[Testimonio ...]`, `[Nombre del viajero]`, `[PRECIO]`, `[Idiomas]`, `[Respuesta por completar]`, `[medios de pago]`. Sin cifras inventadas fuera de marcadores.
- "Pacha Viva" visible: 0 en home, `/en`, listados, nosotros, contacto, admin/login y emails. **Si aparece en las 2 fichas de tour** (title, og:title, twitter:title).

## Hallazgos
### P1
1. **Contraste blanco sobre dorado `rgb(224,166,58)` = 2,17:1** (minimo 4,5:1). Afecta a todos los `.btn--accent` (Ver tours en Lima, Buscar tours, Ver tour), `.badge--accent`, `.filter.is-active`, avatar MC y la franja de newsletter (titulo y texto). Captura `qa/main-contraste-btn-accent--1440.png`. Fix: texto oscuro sobre dorado (o dorado mas oscuro) en tokens.css.
2. **"Pacha Viva" en `<title>`, og:title y twitter:title de las fichas de tour** (`[MUESTRA] Camino Inca 4 dias | Pacha Viva`, idem Arequipa, es y en). Origen: `tours.meta_title` en la BD local (2 filas); no esta en ningun seeder (`grep database/` = 0). Limpiar la BD de staging o el campo antes de publicar.
3. **Host externo fonts.googleapis.com / fonts.gstatic.com en las 17 URLs** (Caveat). Es una decision documentada en layout.blade.php lineas 190-218, pero incumple "sin hosts externos" y las fuentes autoalojadas; ademas Caveat solo se usa en Nosotros. Autoalojar Caveat o quitar el `<link>` del layout general.

### P2
4. Datos de muestra incoherentes: Camino Inca S/ 3,500.00 en PEN pero US$ 95.00 en USD en tarjeta y plan, y US$ 933.33 en la franja "Cupos limitados" de la misma pagina. Corregir `price_usd_cents` del seeder/BD.
5. Targets tactiles <44 px en moviles de las internas (migas de pan 20 px, `Filtrar` 40 px, `Solicitar reserva` 40 px, favorito 40x40, summary FAQ 24 px). La home esta limpia.
6. Boton flotante de WhatsApp tapa texto en la home (nota "Incluye tarjetas de muestra..." de Destinos imperdibles; ver capturas del scroll en `qa/main-contraste-btn-accent--1440.png`).
7. Claims no marcados que el cliente debe validar: "Asistencia 24/7", "Cancelacion flexible / Cambia tu fecha" (la politica de cancelacion es un marcador en el FAQ), "Sin respuestas automaticas".

### P3
8. `/` redirige a `/es` y no a `/es/`; ambas sirven 200.
9. `/sitemap.xml` 404 (esperado hoy; recordar al pasar a produccion).
10. Hero sin navegacion con flechas de teclado.
11. Clave localStorage `pv_currency` conserva prefijo del proyecto anterior (no visible).
12. Texto sobre imagen (`Cusco` en tarjetas, `Trekking`) marca falso positivo de contraste en el script por ser fondo imagen; revisar a ojo con overlay.
13. Marcador `[Texto del slide de Ica: por completar]` visible en el hero (esperado; pendiente de la clienta).

## Evidencia
`C:\laragon\www\tours-word\qa\main-<slug>--{1440,375,320}.png` (17 URLs), `main-menu-open--375.png`, `main-focus--1440.png`, `main-contraste-*.png`.

## Regresión final (barrido corto, árbol congelado)
Alcance: 12 URLs x 1440/768/375/320 (48 cargas). Evidencia: `qa/reg-<slug>--{1440,375}.png`, `qa/reg-tours-scrolled--{375,1440}.png`.
- OK: 0 overflow horizontal; 0 errores de consola; 0 respuestas >=400; 0 hosts externos (Caveat ya autoalojada); 0 `<img>` sin atributo alt ni rotas; h3 en Poppins; contraste texto sobre dorado = 7,69:1 (todos los `.btn--accent`, badges, filtro activo, newsletter); placeholder Inka dorado/crema (no azul); /es/contacto muestra teléfono, correo y dirección (sin aviso vacío); franja Camino Inca = tarjeta en PEN (S/ 3,500.00) y USD (US$ 95.00); "Pacha Viva" solo en title/og/twitter de la ficha de tour demo (3 ocurrencias, meta_title de BD).
- P2: targets <44 px en móvil en internas: migas de pan (20 px), campos del formulario de contacto (42 px), checkbox (16 px), enlace de teléfono en tarjeta (20 px), enlace de título en cards (24 px, pero su ::before cubre la tarjeta). Home limpia.
- P3: /admin/login (Filament) inputs/botón 36 px y botón "Entrar" con falso positivo de contraste; breadcrumb de ficha a 375 envuelve y deja "/" huérfana al inicio de la 2.ª línea; las cards `.reveal` salen en opacity 0 si no se hace scroll (captura fullPage las muestra vacías; con scroll real OK).

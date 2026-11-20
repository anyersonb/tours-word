# 22 - QA Home/paleta PDF (pasada de inventario completo)

Fecha: 2026-11-19. Entorno: http://127.0.0.1:8000 (local). Navegador: Playwright Python headless + Chrome del sistema, cookie `cookie_consent=all`. Capturas en `docs/rediseno-2026/capturas-22-qa/`.

## Veredicto: GO CONDICIONAL para staging
Sin criticos. Un test rojo (stale) y un bug de enlaces del footer a corregir antes o inmediatamente despues. El resto son Medio/Bajo.

## 1. Tests y build
- `DB_PORT=3307 php artisan test`: **384 passed, 1 failed** (2 corridas, determinista). No son 385.
  - FAIL `HomeCatalogLinksTest::test_the_destination_and_experience_cards_link_to_their_real_pages` (tests/Feature/HomeCatalogLinksTest.php:75). `Destination::factory()` / `Experience::factory()` no traen imagen y la nueva regla "sin imagen no entra al Home" los filtra (home.blade.php:52 y :99). El producto esta bien; el test (o las factories) quedaron desfasados.
- `public/build/manifest.json` -> `assets/app-Uxepya8m.css` y `assets/app-D7hVXUuO.js`, ambos existen en `public/build/assets`. PASS.
- `camino-inca-1118.webp`: ninguna referencia en resources/app/config/lang/scripts/database/public. Solo aparece en `docs/.../capturas-21/info-1920.json` (doc). PASS.

## 2. Matriz URL x viewport (1440 / 390)
Criterios: status, consola sin errores, red sin 4xx/5xx (salvo esperados), sin overflow horizontal, 1 H1, header blanco, sin imagen rota.

| URL (ES y EN) | 1440 | 390 | Notas |
|---|---|---|---|
| /es, /en (home) | PASS | PASS | header rgb(255,255,255), 35 imgs OK, 59 srcset resueltos 200 |
| /{l}/tours | PASS | PASS | 2 tours de muestra |
| /{l}/tours/muestra-camino-inca-4-dias | PASS | PASS | |
| /{l}/tours/muestra-tour-gastronomico-arequipa | PASS | PASS | |
| /{l}/destinos, /destinos/cusco, /destinos/arequipa | PASS | PASS | |
| /{l}/experiencias, /cultura, /gastronomia, /trekking | PASS | PASS | |
| /{l}/nosotros | PASS | PASS | |
| /{l}/contacto (+?tour=) | PASS | PASS | eyebrow contraste 4.46 (ver B-05) |
| /{l}/gracias | PASS | PASS | |
| 404 (/es/ruta-inexistente, /en/nonexistent) | PASS (404) | PASS (404) | el 404 de red es el esperado |
| /{l}/_styleguide | PASS | PASS | solo interno, noindex |
| /llms.txt, /robots.txt | PASS 200 | PASS | |
| /sitemap.xml | 404 | 404 | ESPERADO: documentado en SitemapController (sitio en noindex mientras `cms.catalog_demo_content`) |
| /admin/login | PASS | PASS | ver B-04 |

Consola: 0 errores en todo el inventario. Red: 0 fallos 4xx/5xx salvo los 404 esperados. Overflow horizontal: 0 casos. Imagenes rotas: 0 (tras scroll; el full-page sin scroll muestra tarjetas vacias solo por lazy-load, no es bug: `home-es--1440.png` vs `home-es-scrolled--1440.png`).
Contraste: los avisos del script sobre texto blanco en tarjetas de foto (Cusco/Arequipa/experiencias, precio del hero de ficha) son falsos positivos (texto sobre imagen con overlay oscuro, verificado en captura). Texto sobre dorado: botones dorados (.btn--accent) llevan texto cafe, verificado visualmente en home 1440/390.

## 3. Home funcional (1440; 390 en lo aplicable)
| Prueba | Resultado |
|---|---|
| Hero: flecha siguiente/anterior, miniaturas 1-3, pausa | PASS (01->02->01, miniatura 3 -> 3, pausa mantiene slide 8 s, label "Reanudar carrusel") |
| Buscador | PASS parcial: destino=cusco / arequipa -> /es/tours?destino=... filtra al tour correcto. Ver B-03: fecha y viajeros no se envian |
| Filtros Planes (Todos/4 h/1 dia/2-3/4+) | PASS: Todos=4 tarjetas; 4 h=City Tour Lima; 1 dia=Montana 7 Colores; 2-3 dias=Ica y Nazca; 4+=Camino Inca (1440 y 390) `home-filtro-4h--*.png` |
| FAQ | PASS: 6 preguntas, una abierta a la vez, Enter abre y Space cierra |
| WhatsApp flotante + burbuja | PASS: 1440 burbuja visible arriba, 0 solapes en 13 puntos de scroll, `is-away` se aplica en 7/13 (hero/footer/forms). 390: solo circulo; ver B-06 |
| Newsletter | PASS: invalido -> "Escribe un correo valido."; valido -> "Gracias. La suscripcion aun no esta activa en esta version del sitio." (es un placeholder; ver B-07) |
| Selector PEN/ES | PASS: desplegable Moneda (PEN/USD) cambia precios a US$ 933.33; Idioma -> /en |
| Menu movil | PASS: abre/cierra, monedas e idioma dentro del menu |
| Enlaces de la home (11 internos) | PASS: todos 200 |
| Formulario contacto: vacio | PASS (queda en /es/contacto con errores); valido -> /es/gracias PASS (se creo 1 mensaje "QA Prueba / qa@test.com" en la BD local) |

## 4. Bugs
| ID | Sev | Donde | Detalle / evidencia | Archivo:linea probable |
|---|---|---|---|---|
| B-01 | Medio | Footer (todas las paginas) | Los 6 destinos enlazan siempre a `/destinos` (indice), aun con Cusco y Arequipa publicados (existen `/es/destinos/cusco` y `/arequipa`). Causa probable: `Destination::query()->published()->whereIn('slug', ...)` sobre columna `slug` traducible (JSON por idioma), nunca coincide. Verificado: href de Cusco = `.../es/destinos` | resources/views/components/site/footer.blade.php:50 (y :68) |
| B-02 | Medio | Suite de tests | 1 test rojo (ver seccion 1) por la regla nueva "sin imagen no entra al Home" | tests/Feature/HomeCatalogLinksTest.php:75 (usar `->withImage()`/imagen en las factories de Destination/Experience) |
| B-03 | Bajo-Medio | Buscador del hero | `#s-fecha` y `#s-viajeros` no tienen `name`: solo `destino` viaja en la URL; "Buscar tours" ignora fecha y viajeros | resources/views/home.blade.php (form.search) |
| B-04 | Bajo | Panel /admin/login | El panel conserva su paleta anterior (sage #566b50 en boton "Entrar", fondo marfil): no adopta el verde #24483a/dorado del PDF. Funciona y es legible (blanco sobre sage OK). Confirmar si se quiere alinear | tema Filament (fuera de tokens.css) |
| B-05 | Bajo | /contacto | Eyebrow y chip WhatsApp: texto `text-brand-text` sobre `bg-brand-50` = 4.46:1 (AA pide 4.5) | resources/css/tokens.css (brand-text/brand-50) |
| B-06 | Bajo | Movil 390 | El circulo flotante de WhatsApp (48 px) cubre parcialmente texto en 4 de 19 puntos de scroll (bordes de .guarantee, .sec-text, .reason, .ig__handle). Normal para un flotante, sin tapar CTAs; vigilar | wa-float CSS (inka.css) |
| B-07 | Info | Newsletter | Muestra "suscripcion aun no activa": mensaje de desarrollo visible al publico; decidir texto final antes de produccion | home.blade.php (x-data msg) |
| B-08 | Info | /llms.txt | Etiquetas mezcladas ("English . Inicio/Tours/Destinos") en EN | LlmsController (previo a esta ronda) |

Sin Criticos ni Altos. Lo no verificado: panel /admin dashboard (no se probo login: `php artisan dev:ensure-admin` quedo colgado >2 min en esta maquina y no se reintento), de modo que solo se valido /admin/login. Pixel-perfect vs PDF no es parte de esta pasada (lo cubre el gate `ojo`, docs 21 y 23).

## 5. Capturas clave
`es--1440.png`, `en--390.png` (barrido completo en `<slug>--<viewport>.png` para todo el inventario), `home-es-scrolled--1440.png`, `home-filtro-4h--1440.png`, `home-faq--1440.png`, `home-menu-abierto--390.png`, `header-moneda-abierto--1440.png`, `gracias-tras-envio--1440.png`, `admin-login--1440.png`.

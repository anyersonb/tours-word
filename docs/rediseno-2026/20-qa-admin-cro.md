# 20 - QA de cierre: Admin Filament (Lote 1) + Fixes CRO mobile (Lote 2)

Fecha: 2026-11-19. Local `http://127.0.0.1:8000`, MCP chrome-devtools. Sin cambios de codigo ni commits.

## Veredicto: GO CONDICIONADO
Sin Criticos. 1 Alto (CTA principal de la ficha tapado por el flotante WhatsApp en escritorio, regresion del Lote 2). Recomendado corregirlo antes de subir; el resto puede ir al siguiente ciclo.

## Suite de tests
`DB_PORT=3307 php artisan test`: 373 passed (1626 assertions), 0 fallos, 48 s. Coincide con lo reportado por el agente (373).

## Lote 2 - Sitio publico
Barrido scriptado: 30 URLs (15 rutas x es/en: home, contacto, destinos(+cusco, arequipa), experiencias(+trekking, gastronomia, cultura), gracias, nosotros, tours(+2 fichas), _styleguide) x 360/390/768/1280/1440 = 150 cargas (iframe del ancho exacto, DOM real).

| Chequeo | Resultado |
|---|---|
| scrollWidth > innerWidth | 0 de 150 |
| "[" visible en innerText | 0 de 150 |
| Imagenes rotas (naturalWidth=0) | 0 de 150 |
| H1 unico | 150/150 |
| Flotante WhatsApp presente | 150/150 (48x48 en movil, pildora 268x62 en 1440) |
| /llms.txt, robots.txt, webp nuevos, admin-poppins.css, logo | 200 |
| 404 reales (es/inexistente, en/tours/no-existe) | 404 correcto |
| /sitemap.xml | 404 por diseno (Indexability::siteIsIndexable() false en local); verificar en PROD |

Funcional movil 390: hamburguesa abre/cierra (aria-expanded false al cerrar); slider hero rota 1 slide cada ~5,5 s sin textos solapados en estado estable; formulario vacio muestra 5 errores inline en espanol sin overflow; formulario valido redirige a `/es/gracias` (POST 302 -> 200). Carruseles "Tours destacados" y "Tours por ciudad": scroll-snap x mandatory, avanzan por swipe/scroll. Consola: solo issue "Lazy-loaded images should have explicit dimensions" y debug del embed de Google Maps. Red: 0 4xx/5xx propios (unico ERR_ABORTED es la primera carga del iframe de Maps, que se reintenta).
Mensaje de prueba "QA-BORRAR Prueba" creado y borrado (count 0).

Regresion de escritorio 1440: home y ficha renderizan completos, header/footer/grid intactos (`home-1440-full.png`, `ficha-1440-full.png`). Excepcion: BUG-01.

Flotante vs contenido (rects al hacer scroll, 10 URLs x 390/768/1440): ver BUG-01/02.

## Lote 1 - Admin
| Verificacion | Resultado |
|---|---|
| Login 1440 y 390 (logo horizontal, Poppins, rampa de marca, sin dark) | PASS. Boton Entrar blanco sobre #556B52 = 5,82:1. Error de clave en espanol |
| Inicio: Bienvenida + CatalogoStats + UltimosMensajes | PASS en 1440 y 390, sin overflow. Estado vacio digno tras borrar el mensaje |
| Menu y orden | PASS: Inicio > Mensajes (badge nuevos) > Catalogo (Tours, Destinos, Experiencias) > Nosotros (Equipo) > Ajustes (Configuracion) |
| Fuente | Poppins cargando |
| helperTexts | PASS en Tours, Destinos, Experiencias, Equipo, Configuracion, Mensajes |
| Filtros Publicado/Destacado en Tours | PASS (390) |
| Modal de borrado | PASS: "No, volver" conserva el registro; "Si, eliminar" borra solo el registro QA-BORRAR (mensaje e integrante) |
| Consola admin | solo issue "form field should have id or name" (x2), sin errores JS |

### Backend <-> Frontend (evidencia = HTML servido con curl, marca unica, luego restaurado)
| Cambio en admin | Reflejo en front | Restaurado |
|---|---|---|
| Tour 1, Resumen = "QA-MARCA-7731..." | aparece en /es/tours/muestra-camino-inca-4-dias y /es/tours | si (0 apariciones tras revertir) |
| Configuracion, Telefono = +51 900 111 222 | 13 enlaces `wa.me/51900111222` en home/contacto/ficha/gracias/en, `tel:+51900111222` en contacto, telefono en footer | si; vacio vuelve al fallback `wa.me/51959173246` (contact_phone = NULL) |
| Destino Cusco, Descripcion = "QA-MARCA-8842" | /es/destinos/cusco, /es/destinos, /es | si |
| Experiencia Trekking, Descripcion = "QA-MARCA-5519" | /es/experiencias/trekking, /es/experiencias, /es | si |
| Equipo: crear integrante publicado | aparece en /es/nosotros (nombre y rol) | borrado: desaparece de /es/nosotros (200), storage/team vacio |
| Mensaje de contacto creado en el front | aparece en Inicio y en la lista admin; al borrar, Inicio muestra estado vacio | borrado |

Nota: el `fill("")` del MCP no persistio al vaciar un campo (artefacto de la herramienta); vaciando con Ctrl+A/Supr si persiste y el front cae a fallback.

## Tabla de bugs
| ID | Sev | URL / viewport | Descripcion | Evidencia | Archivo probable | A quien |
|---|---|---|---|---|---|---|
| BUG-01 | Alto | /es/tours/{slug} y /en/..., 1440 (y 390 al scrollear) | El flotante WhatsApp (pildora 268x62 en escritorio) tapa parte del CTA principal "Solicitar reserva" en el primer viewport (scrollY 0), luego "Ver mas tours" y el correo del footer. En 390 pasa sobre "Solicitar reserva" y "Consultar por WhatsApp" durante el scroll. | `ficha-1440-full.png`; rects medidos | resources/views/components/site/whatsapp-float.blade.php, resources/css/inka.css (`wa-float`, `wa-float--lift`) | maquetacion: en escritorio circulo 48 px o moverlo fuera de la columna de reserva |
| BUG-02 | Medio | /es 390, scrollY 0 | El flotante cubre el borde derecho de la tarjeta hero "02 Ica" en el primer viewport; tambien pasa sobre selects del buscador y del formulario de contacto (limite ya conocido) | `home-390-hero.png`, `contacto-390-errores.png` | inka.css | maquetacion (o aceptar) |
| BUG-03 | Medio | FAQ del Home (lang/es/home.php:254, lang/en/home.php:251) | El telefono +51 959 173 246 esta escrito a mano en la respuesta: si la clienta cambia el telefono en Configuracion, la FAQ sigue mostrando el viejo (verificado con +51 900 111 222) | curl | lang/es/home.php, lang/en/home.php, home.blade.php | programacion: usar el telefono de `App\Support\WhatsApp`/Setting |
| BUG-04 | Medio | /admin/tours 1440 | La tabla desborda 80 px (1136 vs 1056); barra horizontal y la accion "Editar" queda cortada | `admin-tours-lista-1440.png` | app/Filament/Resources/Tours/Tables/ToursTable.php (ocultar por defecto columnas USD/Orden) | programacion |
| BUG-05 | Medio | /es/contacto 390 | Tras enviar el formulario vacio la pagina recarga arriba: los errores quedan bajo el pliegue sin scroll ni resumen | `contacto-390-errores.png` | ContactMessageController / contact.blade.php (ancla al formulario o foco al primer error) | programacion/maquetacion |
| BUG-06 | Medio | Admin Destinos/Experiencias/Tours 1440 | Etiquetas en ingles en panel en espanol: "Gallery" (Destino, Experiencia), "Images" (Tour), "Experiences" (Tour) | `admin-destino-edit-1440.png`, `admin-tour-edit-1440.png` | Schemas/*Form.php (`->label()` en galeria y relacion) | programacion |
| BUG-07 | Bajo | /admin/tours | Dificultad sin tilde: "Dificil" / "Facil" | `admin-tour-edit-1440.png` | TourForm.php / enum | programacion |
| BUG-08 | Bajo | /admin/contact-messages | Titulo y migas "Mensajes De Contacto" (Title Case) vs "Mensajes de contacto" del menu | `admin-mensajes-1440.png` | ContactMessageResource.php | programacion |
| BUG-09 | Bajo | /admin 1440 y 390 | En Bienvenida los botones quedan pegados al parrafo | `admin-inicio-1440.png`, `admin-inicio-390.png` | app/Filament/Widgets (Bienvenida) | maquetacion |
| BUG-10 | Bajo | /es hero | En el crossfade (~0,5 s) el texto de ambos slides esta a opacity 1 y los contenedores suman 1: el texto se mezcla brevemente (no hay captura del cuadro intermedio, solo medicion cada 100 ms). El unico H1 queda visibility:hidden 2/3 del tiempo | medicion de opacidades | hero.blade.php / inka.css | maquetacion + SEO |
| BUG-11 | Bajo | Carruseles home 390 | Sin dots ni flechas: solo se descubre deslizando | n/a | home.blade.php | maquetacion (opcional) |
| BUG-12 | Bajo | Home 1440 | Varias tarjetas (Arequipa, tours sin foto, experiencias, plan) caen a bloque verde liso por falta de imagen | `home-1440-full.png` | contenido pendiente de la clienta | contenido |
| BUG-13 | Bajo | /admin/experiences/1/edit | En una captura la imagen mostro el input nativo en vez de la zona arrastrar/soltar; probable carrera de carga de FilePond (Destinos y Equipo cargan bien) | `admin-experiencia-edit-1440.png` | por reproducir | qa |
| BUG-14 | Bajo | /en/experiencias/gastronomia, /en/tours/... | Titulos de contenido en espanol en /en | barrido | traducciones pendientes | contenido |
| OBS | - | Equipo | El integrante nuevo nace con Publicado desactivado (decision de diseno; el helper lo explica) | `admin-equipo-create-1440.png` | - | - |

## No cubierto en esta pasada
- Bateria CMS completa C1-C10/E1-E6/D1-D6: solo crear/editar/borrar con reflejo en front. Imagen destacada rota, categoria con posts y papelera no ejecutados.
- 320 px (minimo usado: 360, segun encargo).
- Crossfade del hero en movimiento: medido por opacidades, no capturado visualmente.
- Rol editor vs admin: no probado.
- `dev:ensure-admin` actualizo la clave del admin local dev@pachaviva.test (solo local).

## Capturas
`docs/rediseno-2026/capturas-20/` (23 PNG): login 1440/390, inicio 1440/390, listas y formularios del admin, modal de borrado, menu hamburguesa 390, hero 390, contacto con errores 390, gracias 390, home y ficha 1440.

## Loop back 1 - programación

Tests: `DB_PORT=3307 php artisan test` verdes (373 previos + 4 nuevos en `tests/Feature/LoopBack1ProgramacionTest.php`).

| BUG | Estado | Detalle |
|---|---|---|
| BUG-03 | Corregido, validado con curl | FAQ usa `:phone` en `lang/es/home.php` y `lang/en/home.php`; `home.blade.php` pasa `WhatsApp::phone()` (nuevo; Setting `contact_phone` con fallback a `brand.contact.phone`). También la tarjeta de contacto de la FAQ (teléfono y correo) ignoraba Configuración: ahora usa Setting con fallback. E2E: Configuración = +51 900 111 222 -> es y en lo muestran, 0 apariciones del viejo; restaurado a NULL -> vuelve +51 959 173 246. No quedan otros teléfonos/correos hardcodeados en `lang/` ni vistas públicas. |
| BUG-04 | Corregido en código; medición en navegador PENDIENTE | `ToursTable`: USD, Orden y Destacado ocultos por defecto (activables en el selector de columnas), Destino limitado a 24 caracteres. Falta medir scrollWidth a 1280/1440 (ver Bloqueos). Otras tablas no tocadas. |
| BUG-06 | Corregido | Causa: `->label('')` es falsy y Filament autogenera "Gallery/Images/Experiences". Ahora `->label('Galería'|'Imágenes'|'Experiencias')->hiddenLabel()` en Destino, Experiencia y Tour. No hay otros `label('')` en `app/Filament`. |
| BUG-07 | Corregido | `TourDifficulty` implementa `HasLabel` (`getLabel()` = textos con tilde de `lang/*/tours.php`). Valores en BD (`facil|moderado|dificil`) sin cambio. |
| BUG-08 | Corregido | `ContactMessageResource::getTitleCasePluralModelLabel()` = "Mensajes de contacto" (título y migas). |
| BUG-09 | Corregido | `bienvenida.blade.php`: `mt-4 md:mt-0` en el bloque de botones. |
| BUG-14 | Diagnóstico: dato faltante, no bug | Tours 1-2, Experiencias 1-3 y Destinos 1-2 tienen la traducción EN vacía en BD; `fallback_locale=en` no aplica y el modelo cae al ES. Hay que cargar los títulos EN en el CMS (contenido de la clienta). No se inventó nada. |

## Loop back 1 - maquetacion
Fecha: 2026-11-19. Tests `DB_PORT=3307 php artisan test`: 377 passed. `npm run build` OK (sin `public/hot`).

| BUG | Estado | Arreglo |
|---|---|---|
| BUG-01 (Alto) | Corregido | Flotante = circulo (56 px escritorio, 48 px movil), sin pildora de texto; `aria-label` + `title`, `?text=` prellenado intacto. `setupWaFloat()` (app.js) le pone `.is-away` (fade, sin pointer-events) mientras su caja se solape con footer, enlaces/botones del panel de reserva, `.btn`, `.link-more`, CTAs/lista del hero y campos de formulario; reaparece al salir. La barra sticky de la ficha sigue resuelta con `wa-float--lift`. En 1440 el circulo queda debajo de "Solicitar reserva" y "Consultar por WhatsApp" (`ficha-1440.png`). |
| BUG-02 (Medio) | Corregido | En 390 el flotante se esconde mientras cubriria las tarjetas del hero o campos; aparece tras pasar el hero (`home-390-hero.png`: sin flotante sobre "02 Ica"). Contacto: se esconde sobre selects/inputs. |
| BUG-05 (Medio) | Corregido | `contact.blade.php`: el resumen de errores lleva `data-form-errors` + `scroll-mt-28`; `setupFormErrorFocus()` (app.js) hace scroll al resumen (`role="alert"`) y da foco al primer campo `aria-invalid` (en 390: scrollY 1225, resumen visible arriba, foco en `#field-name`; `contacto-390-errores.png`). No se toco el controller. Envio valido: 302 a `/es/gracias`; mensaje QA-BORRAR eliminado (0 restantes). |
| BUG-10 (Bajo) | Corregido | H1 unico fijo `sr-only` (texto del slide 1) fuera de los slides: siempre en el DOM y visible para lectores/bots. Los titulos visibles de cada slide son `<p>` sin rol heading. Timing: el slide saliente apaga su texto (.15 s) antes de que su contenedor se desvanezca (retraso .2 s) y el entrante enciende el suyo a .3 s. Medido cada 60 ms: opacidad de texto saliente 0 antes de que el entrante pase de 0.07 (sin solape); captura a mitad de transicion `home-390-hero-transicion.png`. |
| BUG-11 (Bajo) | Corregido | `setupCarouselDots()` crea puntos (botones 28x44, `aria-label`, `aria-current`) bajo los `.grid-4--snap` con >=2 items; oculto en >=1024. Probado: click lleva al item y el activo sigue al scroll (`home-390-carrusel-dots.png`). |

Barrido scriptado (iframe del ancho exacto; scroll en pasos de 30-40 % del viewport; solape flotante visible vs `.btn`, botones, campos, labels, footer, `.link-more`, enlaces del panel de reserva, barra sticky, tarjetas del hero):
- 360, 390, 768, 1280, 1440 x /es, /es/tours, ficha, /es/contacto, /en (+ ficha /en en 390 y 1440): overflow 0 en todas; 0 solapes, salvo 1 artefacto en 360 contacto (label del checkbox de privacidad) que no reproduce con emulacion movil real (0 solapes, 360x740).
- Consola: 0 errores/warnings.

Notas para QA:
- Efecto colateral buscado: el flotante no se ve en el primer viewport del Home en movil, ni en el primer viewport de la ficha en 1440 (columna de reserva ocupa el borde derecho; el circulo reaparece al scrollear). La ficha tiene su propio CTA de WhatsApp.
- Las claves `home.whatsapp_float.*` ya no se usan (lang/home.php no se toco).
- CSS/JS global: afecta a todas las paginas publicas (flotante, `.hero__slide`, puntos de carruseles). QA: inventario completo.
- Capturas: `docs/rediseno-2026/capturas-20/fix/` (las nuevas: ficha-1440.png, ficha-390.png, home-390-hero.png, home-390-hero-transicion.png, home-390-carrusel-dots.png, contacto-390-errores.png).

## Re-QA loop back 1
Fecha: 2026-11-19. Local `http://127.0.0.1:8000`, MCP chrome-devtools. Sin cambios de código ni commits. Capturas en `docs/rediseno-2026/capturas-20/reqa/`.
Tests: `DB_PORT=3307 php artisan test` = 377 passed (1640 assertions). Nota: `dev:ensure-admin` sin interacción solo genera una clave aleatoria que no se puede leer sin imprimirla, así que se fijó una clave local conocida al usuario dev@pachaviva.test (solo local).

### Veredicto: NO-GO (por 3 bugs abiertos, todos de fix corto)
Sin Críticos. 0 overflow en el inventario completo; contacto, hero, carruseles y BUG-03/05/06/07/08/10/11 quedan cerrados. Abiertos: BUG-09 (el fix no surtió efecto, causa raíz distinta), BUG-16 (el flotante aún solapa CTAs reales que no están en su lista de evitación; BUG-01 cerrado solo en parte) y BUG-17 (bug nuevo: tabla de Mensajes desborda a 1280). Es el último loop permitido (cap 2): pasan a Bloqueos.

### Estado por BUG
| BUG | Estado | Evidencia |
|---|---|---|
| BUG-01 (Alto) | PARCIAL: CTA principal cerrado; residual Medio = BUG-16 | Flotante círculo 48 px (<1024) / 56 px (>=1024). Ficha 1440: "Solicitar reserva" y "Consultar por WhatsApp" ya no tapados. Barra sticky de ficha, campos de formulario y footer: 0 intersecciones en las 150 cargas con scroll |
| BUG-02 (Medio) | Cerrado | `/es` 390: 0 intersecciones con tarjetas `.hero__item` ni selects del buscador; sobre campos de contacto se esconde (`is-away`) |
| BUG-03 (Medio) | Cerrado | Configuración (UI) Teléfono = +51 900 333 444: `/es` y `/en` con 13 enlaces `wa.me/51900333444`, 4 apariciones del número (FAQ y tarjeta), 0 del viejo; contacto `tel:+51900333444` y 7 wa.me nuevos; ficha 6 wa.me nuevos. Vaciado por UI: BD `contact_phone` = NULL y el front vuelve a `wa.me/51959173246` y "+51 959 173 246" |
| BUG-04 (Medio) | Cerrado en Tours, Destinos, Experiencias y Equipo; nuevo caso en Mensajes = BUG-17 | Con datos a 1280 y 1440: scrollWidth = clientWidth (896 / 1056) y "Editar" visible (right 1224 / 1384). `PASS-admin-tours-1440.png` |
| BUG-05 (Medio) | Cerrado | `/es/contacto` 390 vacío: scrollY 1225, resumen `role=alert` visible (top 208), foco en `#field-name` visible, 0 overflow. Válido: 302 y `/es/gracias`; mensaje QA-BORRAR eliminado. `PASS-contacto-390-errores-scroll-foco.png` |
| BUG-06 (Medio) | Cerrado | Formularios Tour (crear y editar), Destino, Experiencia: "Galería" y "Experiencias"; 0 apariciones de Gallery/Images/Experiences |
| BUG-07 (Bajo) | Cerrado | Select: Fácil / Moderado / Difícil. Guardar Tour 2 sin tocar nada por UI ("Guardado"): BD sigue `facil`; Tour 1 `dificil` intacto |
| BUG-08 (Bajo) | Cerrado | `<title>`, H1 y migas: "Mensajes de contacto" |
| BUG-09 (Bajo) | ABIERTO | Botones siguen pegados al párrafo: 0 px en 1440 y 390. Causa raíz: las utilidades Tailwind de `bienvenida.blade.php` (`mt-4 md:mt-0`, `gap-4`, `flex-col`, `md:flex-row`, `mt-1`) no existen en el CSS compilado del panel Filament. Computed: contenedor `display:block`, `gap:normal`, `margin-top:0`. Fix: CSS propio en el tema del panel o estilos inline. `FAIL-admin-inicio-1440-botones-pegados.png`, `FAIL-admin-inicio-390-botones-pegados.png` |
| BUG-10 (Bajo) | Cerrado | `/es`: un único `h1.sr-only` fuera de los slides. Opacidad efectiva del texto cada 40 ms durante 15 s (5 transiciones): nunca dos slides con texto >0.05 a la vez. Observación SEO: el H1 es el título del slide 1 ("Lima Centro Histórico"), débil como H1 de marca |
| BUG-11 (Bajo) | Cerrado con observación | Puntos visibles a 360/768/1023 y ocultos a >=1024; click lleva al ítem (scrollLeft 0 a 606) y el activo sigue al scroll. 28x44 px. Ver BUG-18 |
| BUG-12/13/14 | Sin cambio | contenido pendiente de la clienta; BUG-13 no reproducido |
| `.htaccess` | OK (revisión de texto) | Línea 96 `^((?:build\|images\|img\|css\|js\|fonts)/.+\|favicon\.ico\|favicon\.svg\|favicon-32\.png\|apple-touch-icon\.png\|robots\.txt)$` a `public/$1 [L]`: paréntesis balanceados, grupo no capturante, un solo `$1`; probada con PHP contra `img/provisional/hero-lima-480.webp` (coincide); `public/img`, `favicon-32.png` y `apple-touch-icon.png` existen |

### Barrido público (inventario completo)
30 URLs (15 rutas x es/en) x 360, 390, 768, 1280, 1440 = 150 cargas en iframe del ancho exacto, scroll en pasos de 50 % del viewport, más barrido fino (60-80 px) en contacto, ficha, home, tours, nosotros, destino y experiencia.
| Chequeo | Resultado |
|---|---|
| scrollWidth > clientWidth | 0 de 150 |
| H1 único | 150/150 |
| Flotante presente | 150/150; 48 px en 360/390/768, 56 px en 1280/1440 |
| Intersección con barra sticky de ficha, footer y campos de formulario | 0 |
| Intersección con CTAs/enlaces fuera de la lista de evitación | Sí: BUG-16 |
| Consola (home, ficha, contacto, gracias) | solo el issue "Lazy-loaded images should have explicit dimensions"; 0 errores |
| Regresión de escritorio 1440 | sin cambios en header/footer/grids |

### Bugs nuevos y residuales
| ID | Sev | URL / viewport | Descripción | Evidencia | Archivo probable | A quién |
|---|---|---|---|---|---|---|
| BUG-16 | Medio | ficha 360-1440; contacto 360-1440; home 360/390 | El flotante visible (sin `.is-away`) solapa parcialmente enlaces/controles que `setupWaFloat()` no evita (su lista `AVOID` solo cubre `.btn`, `.link-more`, `.round-btn`, `main button`, `#panel-reserva a`, hero y campos). Área solapada del círculo: ficha "Ver más tours" (1224 px² a 390, 1348 a 1440, scrollY 960); contacto "Chatear por WhatsApp" (572 a 390), "Ver en Google Maps" (767 a 390, 722 a 1440), mailto (377 a 390), 5 `summary` de la FAQ (hasta 1791 a 360/390/768, sobre el chevron); home 390 "Consultar precio" (`.price-ask`, 1327) y mailto del pie | `FAIL-ficha-1440-ver-mas-tours.png`, `FAIL-contacto-390-google-maps.png` | `resources/js/app.js` `setupWaFloat()`: ampliar `AVOID` con `main a[class*="inline-flex"]`, `summary`, `.price-ask`, `a[href^="mailto:"]`, `a[href^="tel:"]` | maquetacion |
| BUG-17 | Medio | `/admin/contact-messages` 1280 (con >=1 mensaje) | Misma familia que BUG-04: scrollWidth 1000 vs clientWidth 896 (desborda 104 px); "Editar" a right=1328 > 1280, cortada. A 1440 pasa. Con tabla vacía no se ve | `FAIL-admin-mensajes-1280.png` | `app/Filament/Resources/ContactMessages/Tables/ContactMessagesTable.php` (ocultar por defecto Correo o Tour consultado) | programacion |
| BUG-18 | Bajo | puntos de carrusel (`.grid-4--snap`) | `toggleAttribute('aria-current', bool)` deja `aria-current=""` en el activo; valor vacío = `false`, el lector de pantalla no lo anuncia (el CSS `[aria-current]` sí lo pinta) | DOM: activo `""`, resto `null` | `resources/js/app.js` línea 459: `setAttribute('aria-current','true')` / `removeAttribute` | maquetacion |
| OBS | - | `/es`, `/es/destinos`, `/es/experiencias` 360-768 | El círculo aún cubre una esquina de tarjetas-enlace grandes (imagen) en algunos scrolls, sin tapar su texto. Párrafos tapados puntualmente por el círculo en móvil: inherente a un flotante fijo, se descarta | barrido | - | aceptar |

### Backend <-> Frontend de esta pasada
| Cambio | Reflejo en front | Restaurado |
|---|---|---|
| Configuración Teléfono = +51 900 333 444 (UI) | home es/en, FAQ, contacto, ficha (ver BUG-03) | Sí: NULL y fallback +51 959 173 246 visible |
| Mensaje creado desde `/es/contacto` | `/es/gracias`; aparece en `/admin/contact-messages` (badge "1" del menú) | borrado (count 0) |
| Integrante de Equipo creado (tinker) | medición de tabla con datos | borrado: 0 apariciones en `/es/nosotros` |
| Guardar Tour 2 sin cambios | dificultad conservada en BD | sin cambios (solo updated_at) |

### Bloqueos
BUG-09, BUG-16 y BUG-17 siguen abiertos tras el loop 1 (último permitido). Diagnóstico arriba; fixes de pocas líneas. Decisión de Anyerson: fix directo por el main (<=2 archivos por bug) con una pasada corta de verificación, o aceptar BUG-16 residual (el flotante ya nunca tapa el CTA principal, la barra sticky, el footer ni los campos).

### No cubierto
Captura del crossfade del hero a mitad de transición (medido por opacidades); 320 px; rol editor; hover/focus del círculo; `.htaccess` sin ejecutar (solo texto, según encargo).

## Cierre — fixes directos tras re-QA (main, 2026-11-19)

| BUG | Fix | Verificación (MCP chrome-devtools) |
|---|---|---|
| BUG-09 | `bienvenida.blade.php`: layout con estilos inline (las utilidades Tailwind no existen en el CSS del panel) | gap párrafo→botones 16 px a 1280 y 390 · CERRADO |
| BUG-16 | `app.js` `setupWaFloat`: `AVOID` incluye `main a`, `main summary` | barrido por scroll: 0 intersecciones visibles en /es (390), ficha (390 y 1440), contacto (390) · CERRADO |
| BUG-17 | `ContactMessagesTable`: Correo oculto por defecto (toggleable), `wrap()` en Nombre/Asunto/Tour, Tour `limit(40)` | 1280 con mensaje real: scrollWidth 896 = clientWidth, "Editar" right 1224 < 1248 · CERRADO |
| BUG-18 | `aria-current="true"` / `removeAttribute` en los puntos del carrusel | revisión de código · CERRADO |
| `.htaccess` | regla de assets incluye `img/`, `favicon-32.png`, `apple-touch-icon.png` (causa de imágenes rotas en staging) | revisión de texto; pendiente subir |

Tests: 377 passed. Veredicto: **GO**. Mensaje de prueba borrado.

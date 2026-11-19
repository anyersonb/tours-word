# 19 - Auditoria CRO + UX mobile (INKA PALACE Travel, LOCAL)

Fecha 2026-11-19. HEAD 2f9718b. http://127.0.0.1:8000 (ya estaba levantado; se deja como se encontro). MCP chrome-devtools. 390x844 (360x800 solo /en home). Capturas en `capturas-19/`, reportes Lighthouse en `capturas-19/lighthouse/`.

## Limites de la auditoria
- El MCP disponible no tiene click/scroll/evaluate_script: no se abrio el menu hamburguesa, no se midio overflow ni estilos computados. Tap targets y tamanos de fuente salen de CSS/blade (inka.css, form/input.blade.php), no de medicion en vivo.
- Los huecos en blanco de las capturas full-page (listado de tours, ficha) son `[data-reveal]` (IntersectionObserver, app.css:395) sin disparar. No es bug. Por eso no se vieron tarjetas de tour ni itinerario/incluye renderizados.
- Datos locales = MUESTRA. Los hallazgos de contenido son de plantilla pendiente.
- `public/hot` no existe; `public/build/manifest.json` existe.
- Experiencias, destinos y nosotros: solo se confirmo 200 por ruta, sin captura.

## Lighthouse mobile
| URL | Perf | A11y | Best Practices | SEO |
|---|---|---|---|---|
| /es | no entregado por la herramienta | 100 (1 fallo label-content-name) | 100 | 69 (solo noindex) |
| /es/tours/muestra-camino-inca-4-dias | no entregado | 93 | 100 | 69 (solo noindex) |

Traza /es (sin throttling, local): LCP 810 ms (TTFB 571, render delay 196), CLS 0.00. ImageDelivery: 520 kB desperdicio. Consola: 1 issue "Lazy-loaded images should have explicit dimensions". Red: sin 4xx/5xx, fuentes self-host. Perf con throttling mobile NO medida.
SEO 69 = `robots noindex,nofollow` en local. Verificar que PROD salga index.

## Matriz priorizada

### CRITICO
**C-1 Placeholders visibles al cliente final** (home, ficha, footer global)
- Evidencia: HTML /es contiene `[Texto del slide de Ica: por completar]` (lang/es/home.php:62; visible en capturas-19/home-fold-390.png), `[PRECIO]` x5, `[MUESTRA]` x7, `[Respuesta por completar]` x4, `[Nombre del viajero]` x3, `[Tour sugerido: Ica y Nazca]`, `[medios de pago]` en el footer de todas las paginas. Ficha: titulo "[MUESTRA] Camino Inca 4 dias".
- Impacto: destruye confianza, impide publicar.
- Fix: copy real, u ocultar el bloque si el valor empieza por "[" (footer "Pagos seguros" hasta tener logos). Va a: programacion (condicion en blade) y contenido del cliente.

**C-2 Formulario de contacto sin /gracias**
- Evidencia: `app/Http/Controllers/ContactMessageController.php:26` y `:78` hacen `back()->with('contact_success', true)`.
- Criterio: exito debe redirigir a /gracias trackeable. Sin conversion medible en GA4.
- Fix: redirect a `/{locale}/gracias` (ruta + vista con siguiente paso: WhatsApp / ver tours) y evento de conversion. Va a: programacion + maquetacion (vista).

### ALTO
**A-1 Inputs a 14px (iOS hace zoom al enfocar)**
- Evidencia: `components/ui/form/input|select|textarea.blade.php` usan `text-sm` (14px) en /es/contacto y en los selects de /es/tours.
- Fix: `text-base` (16px) en mobile, y `autocomplete="name|email|tel"` + `inputmode`. Va a: maquetacion.

**A-2 WhatsApp flotante solo en home y sin mensaje precargado**
- Evidencia: unico uso `home.blade.php:470` (`<x-home.whatsapp-float />`). `config/brand.php:26` = `https://wa.me/51959173246` sin `?text=`; header/footer/contacto igual. En la ficha el unico CTA es "Solicitar reserva".
- Criterio: camino a conversion <=2 taps desde cualquier interna; WhatsApp con `?text=` contextual.
- Fix: mover el componente a `components/layout.blade.php`, texto por pagina ("Hola, quiero cotizar {tour}"), y en la ficha subirlo sobre la barra sticky (barra z-40 vs wa z-60, show.blade.php:441). Va a: programacion (texto) + maquetacion (colision).

**A-3 Header mobile sin CTA de conversion y con carrito muerto**
- Evidencia: `components/site/header.blade.php:114` boton carrito `disabled`; solo logo + carrito + hamburguesa. El CTA (`.nav__cta`, inka.css:104) esta dentro del menu colapsado (menu no abierto en esta auditoria).
- Fix: sustituir el carrito por boton "Cotizar"/WhatsApp de 44px mientras no exista carrito. Va a: maquetacion.

**A-4 Home de ~17 590 px de alto en 390px (~21 pantallas)**
- Evidencia: capturas-19/home-full-390.jpeg (excede los 16 384 px de captura). Destinos repetidos en mosaico + ciudades + hero.
- Fix: fusionar mosaico y ciudades en <768 y usar carruseles snap (`.grid-4--snap`, inka.css:706). Va a: maquetacion.

**A-5 Hero: H1 no comunica la propuesta y el crossfade solapa textos**
- Evidencia: H1 = nombre del destino del slide; la oferta solo esta en `<title>`. En home-fold-390.png el slide 2 mostro titulo y CTAs del slide anterior superpuestos ("Ver tours en Ica" sobre "Ver tours en Lima") durante la transicion.
- Fix: ocultar el contenido saliente con opacity/visibility antes de entrar el nuevo (`.hero__slide`, inka.css) y agregar una linea fija de propuesta de valor. Va a: maquetacion.

**A-6 Ficha: sin garantias/resenas junto al CTA ni WhatsApp**
- Evidencia: barra sticky correcta (visible desde el fold, boton "Solicitar reserva", "Desde S/ 3,500.00"; tour-fold-390.png). Falta politica de cancelacion / "sin cargo al solicitar", resenas y canal WhatsApp.
- Fix: 2-3 trust badges bajo el boton del panel y bloque de resenas. Va a: maquetacion + programacion.

### MEDIO
**M-1 A11y ficha (93)**: `aside#panel-reserva dl.mt-6` tiene hijos `div > span` y `div > div` no validos (axe definition-list + dlitem x6; tambien rompe el arbol de accesibilidad para agentes). Fix: `<dl><div><dt/><dd/></div></dl>` en show.blade.php. Va a: maquetacion.

**M-2 A11y home**: `button.hero__item` con aria-label "Destino 2 de 3: Ica" que no contiene el texto visible "02 Ica Huacachina y sus dunas" (WCAG 2.5.3). `article.hero__slide` rompe el arbol a11y. Fix: aria-label que incluya el texto visible. Va a: maquetacion.

**M-3 Imagenes**: hero `public/img/provisional/hero-lima.jpg` pesa 511 kB en JPG (LCP local 810 ms, sera mucho mayor en 4G). ImageDelivery: 520 kB ahorrables. Fix: webp ~828w con srcset (usar `components/ui/picture.blade.php`) y width/height declarados. Va a: maquetacion/programacion.

**M-4 Contacto**: mapa es placeholder ("Mapa ilustrativo", capturas-19/contacto-full-390.jpeg) y no hay horario. Fix: embed lazy de Google Maps + horario. Va a: programacion.

**M-5 Descubribilidad**: `/llms.txt` responde 404; la ficha emite 1 solo JSON-LD (verificar tipo TouristTrip/Product + Offer); noindex activo en local (verificar PROD). Va a: programacion/SEO.

**M-6 Banner superior "Camino Inca 2027"** ocupa ~32px fijos en todas las paginas; confirmar si es link y llevarlo a la ficha Camino Inca. Va a: maquetacion.

### BAJO
- B-1 Contacto, bloque "Siguenos": muestra un solo icono sin etiqueta visible (contacto-full-390.jpeg). Agregar texto/aria y mas redes.
- B-2 Footer: "Preguntas frecuentes" apunta a `/es#faq` (home) y no a la FAQ de contacto; unificar.

## PASS (no tocar)
Fuentes self-host; CLS 0.00; Best Practices 100; LCP local 810 ms; precios en S/ con "Desde"; barra sticky de reserva en ficha; WhatsApp flotante 48px sin colision en 360x800 (capturas-19/en-fold-360.png); labels `for/id` visibles en el form; honeypot; checkbox de consentimiento; /en funciona; 404 real en rutas inexistentes (vista personalizada sin verificar).

## Pendiente de medir (requiere scroll/JS)
Menu mobile abierto, overflow horizontal, contraste computado, tap targets reales, errores inline/loading del form, ficha con itinerario/incluye, experiencias/destinos/nosotros, Performance con throttling.

## Siguiente paso
1) programacion: C-1, C-2, A-2. 2) maquetacion: A-1, A-3, A-5, M-1, M-2, M-3, A-4. 3) re-auditar con scroll + menu tras los fixes.

## Fixes aplicados (2026-11-19, frontend publico)
Validado por MCP chrome-devtools a 390x844 y 360x800 (home, listado, ficha, contacto con envio real -> /gracias, gracias, /en): scrollWidth = innerWidth en todas, 0 textos con "[", inputs de 16px, flotante WhatsApp 48px visible. Capturas en `capturas-19/fix/`. Tests: antes 366 OK, despues 373 OK (se actualizaron 3 archivos de test ligados al comportamiento anterior: redirect a /gracias y `<title>` sin "[MUESTRA]"; nuevo `CroMobileFixesTest`). Build Vite hecho, sin `public/hot`.

| ID | Estado | Detalle |
|---|---|---|
| C-1 | hecho | `App\Support\Placeholder` (`is/visible/title/items`): todo texto que empieza por "[" no se imprime (hero, conteos, rating/duracion/idiomas de tarjetas, stats, testimonios, sellos, FAQ del Home, medios de pago del footer, plan "[Tour sugerido]"). `[PRECIO]` -> "Consultar precio" (enlace a WhatsApp con el nombre del tour). Prefijo `[MUESTRA]` se quita al mostrar titulos (tarjetas, ficha, `<title>`). Chips/notas "Muestra" solo en local con `?muestra=1`. BD y lang sin tocar. Efecto: testimonios y FAQ del Home quedan ocultos hasta que la clienta cargue el texto real. |
| C-2 | hecho | Exito (y honeypot) redirige a `/{locale}/gracias` (vista `thanks.blade.php`, ES/EN, WhatsApp + ver tours + inicio, noindex, `dataLayer.push contact_form_success`). Errores siguen inline en el form. No hay GTM/GA4 instalado en el sitio: la URL ya es medible, el evento queda listo. |
| A-1 | hecho | input/select/textarea `text-base sm:text-sm` + `min-h-11`; `autocomplete` name/email/tel + `inputmode` email/tel; checkbox de privacidad con area tactil 44px. |
| A-2 | hecho | `x-site.whatsapp-float` montado en `x-layout` (todas las paginas), `?text=` por contexto (ficha: nombre del tour; gracias; default). Numero via `App\Support\WhatsApp` (Configuracion CMS si existe, si no `brand.contact.whatsapp_url`). En la ficha sube sobre la barra sticky (`wa-float--lift`) y el panel de reserva trae boton "Consultar por WhatsApp". Limite conocido: al scrollear puede cubrir el borde derecho de un boton de ancho completo (ej. "Filtrar") o el boton expandir de la galeria; el texto/CTA sigue accesible. |
| A-3 | hecho | Carrito muerto sustituido por boton WhatsApp 44px en el header (restituir `<button class="cart">` cuando exista carrito). |
| A-4 | parcial | Home 17 590 px -> ~12 190 px a 390 (-31%). Mosaico de destinos 2 columnas compacto, ciudades y planes en carrusel snap, menos padding; sin eliminar secciones. Quitar las secciones repetidas requiere decision de diseno/Figma. |
| A-5 | parcial | Texto saliente del hero se apaga antes de entrar el nuevo (sin solape) y linea fija "Agencia de turismo con expertos locales" sobre el slide. El H1 sigue siendo el nombre del destino del slide (cambiarlo es decision de contenido/SEO). Crossfade no verificado en movimiento (solo CSS). |
| A-6 | parcial | Panel de reserva: boton WhatsApp + 3 garantias con textos que el sitio ya promete (sin cargo al solicitar, respuesta rapida, cancelacion flexible). Bloque de resenas: no aplica sin datos reales (no se inventan). |
| M-1 | hecho | `dl` valido (`div > dt + dd`, icono dentro del dt). Verificado por DOM; axe no se re-corrio. |
| M-2 | hecho | `button.hero__item` sin aria-label (nombre accesible = texto visible); el aria-live usa `data-badge`. `article.hero__slide` role=group: no se toco. |
| M-3 | hecho | `scripts/imagenes-derivadas.php` ahora incluye `public/img/provisional` (webp 480/828/1200); `x-ui.picture` ya los sirve con srcset (hero-lima 511 kB -> 36 kB a 828w). Los .webp son archivos nuevos a subir en el deploy. |
| M-4 | parcial | Mapa: iframe lazy de Google Maps por direccion (reemplaza el placeholder). Horario: el campo `contact_schedule` ya existe en Configuracion; se muestra cuando la clienta lo cargue (no se inventa). Verificar CSP/consent en PROD. |
| M-5 | parcial | `/llms.txt` creado (200). JSON-LD de ficha: ya existia `StructuredDataTest`; no se re-audito el tipo. noindex local: no aplica, depende de `Indexability` (verificar PROD). |
| M-6 | hecho | Aviso "Camino Inca 2027" es enlace a `/{locale}#camino-inca` (seccion del Home), altura 44px. |
| B-1 | hecho | Redes de contacto como pills con texto visible y 44px. |
| B-2 | hecho | Header y footer "Preguntas frecuentes" -> `/{locale}/contacto#preguntas` (FAQ de contacto con respuestas reales; la del Home tiene placeholders). |

Pendiente para QA/ojo: slider del hero en movimiento, menu hamburguesa abierto, contraste computado, 768px y escritorio (CSS global tocado: `inka.css`, `x-layout`, header/footer, componentes de form -> afecta TODAS las paginas publicas).

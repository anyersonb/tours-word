# QA pase 25 (contenedor 1200, carrito decorativo, interruptor SHOW_PLACEHOLDERS)

Fecha 2026-11-20. Local http://127.0.0.1:8000. Playwright + Chrome del sistema, cookie `cookie_consent=all`.
Capturas: `docs/rediseno-2026/capturas-27-qa/` (62 full-page `<lang>-<slug>--<vp>.png` + extras). Datos crudos: `capturas-27-qa/sweep.json`.

## Veredicto: GO para staging (0 Criticos, 0 Altos, 0 Medios nuevos del pase; 3 Bajos + 1 Medio preexistente)

## Verificacion
| # | Check | Resultado |
|---|---|---|
| 1 | `DB_PORT=3307 php artisan test` | PASS 388 tests / 1700 assertions |
| 2 | `view:cache` + `npm run build` | PASS; manifest 2 entradas, 0 archivos faltantes |
| 3 | Inventario ES+EN (home, tours + 2 fichas, destinos + cusco + arequipa, experiencias + cultura + trekking, nosotros, contacto, gracias, 404) a 1440 y 390; 768 en home/tours/contacto (62 combinaciones) | PASS: status 200 (404 en la ruta inexistente), 0 overflow horizontal, 0 imagenes rotas, 0 errores de consola (salvo el 404 intencional), 0 respuestas >=400 en recursos, 1 H1 por pagina |
| 3b | `/llms.txt` 200, `/robots.txt` 200, `/admin/login` 200 (titulo correcto) | PASS. `/sitemap.xml` 404 por diseno (sitio noindex, SitemapController) |
| 3c | Menu movil abre/cierra (`aria-expanded` true/false), captura `es-home-menu-abierto--390.png` | PASS |
| 3d | Carrito: `<button disabled>`, fuera del orden de Tab (Tab: skip-link, telefono, aviso, logo, nav, PEN/ES, Contactanos) | PASS (ver B-1) |
| 4 | Interruptor: `SHOW_PLACEHOLDERS=false` -> texto visible sin ningun `[..]` en home/tours/contacto/ficha/nosotros/destinos/experiencias ES y EN (incluye footer); con `true` home trae 7x `S/ [`, 8x `[4,9`, 3x `[Nombre`, 3x `[Testimonio`, 2x `[Tour sugerido`, 1x `[medios`. `.env` restaurado a `true` (diff vs backup identico) y `config:clear` | PASS. Nota: un grep ingenuo de `\[[0-9]` da falsos positivos por clases Tailwind (`aspect-[4/5]`); se valido sobre texto sin script/style |
| 5 | Home funcional: flechas hero (0->1), miniatura (->0), autoplay quieto en hover 9 s y avanza al salir; filtros Planes (Todos/4 h/1 dia/2-3 dias/4+ dias) filtran; FAQ 6 items, 1a abierta, click abre la 3a y cierra la 1a; WhatsApp flotante + enlaces wa.me con numero 51959173246; newsletter valida correo y muestra mensaje honesto "aun no esta activa"; 15 enlaces internos sin 404; 33 URLs de imagen/srcset sin 404 tras scroll | PASS |
| 6 | Contacto sin privacidad -> errores inline por campo + resumen; con datos -> `/es/gracias` (1440 y 390) | PASS |
| - | Contenedor 1200 en fichas (galeria, ficha de precio, Incluye/No incluye), formularios, grillas | PASS visual 1440/768/390, sin desbordes |

## Bugs
| ID | Sev | Hallazgo | Archivo:linea | Evidencia |
|---|---|---|---|---|
| B-1 | Bajo | Carrito decorativo con `aria-label="Carrito: 1"`: un lector de pantalla puede anunciar "Carrito: 1, no disponible" sin que exista carrito real. Sugerido: `aria-hidden="true"` en el boton o quitar el "1" del label hasta el lote 4 | resources/views/components/site/header.blade.php:115 | `es-tour-camino-inca--1440.png` |
| B-2 | Bajo | Puntos del slider (`snap-dots__dot`) miden 28x44 px a 390 (ancho <44; cumple WCAG 2.2 AA 24px). 13 en home | resources/css/inka.css:720 | sweep.json, `es-home--390.png` |
| B-3 | Bajo | Comentario obsoleto: dice "boton de pausa (WCAG 2.2.2)" pero el boton se elimino en el pase 25 | resources/views/components/home/hero.blade.php:7 | lectura de codigo |
| B-4 | Medio (preexistente, no del pase) | Respuesta 429 (limite de 5 envios/10 min en `/contacto`) es la pagina cruda de Laravel en ingles "429 TOO MANY REQUESTS" sin marca ni navegacion; no hay `resources/views/errors/429.blade.php` | resources/views/errors/ (falta 429) | `es-contacto-error-servidor--1440.png` (primera corrida) |

Observaciones sin accion: validacion HTML nativa en envio vacio (burbuja del navegador) y errores del servidor inline correctos; aviso superior mide 41 px de alto visible (el area tactil de 44 px va por `::before`, correcto); `sweep.json` guarda las capturas por viewport.
Efectos secundarios locales: 2 mensajes de contacto de prueba ("QA Pase25", qa-pase25@example.com) en la BD local y cache limpiada para reiniciar el throttle.

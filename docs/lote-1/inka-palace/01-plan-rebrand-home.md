# Lote 1 — Rebrand del Home a "Inka Palace Travel"

Fuente: `Inka-Palace-1-Home.pdf` (una sola página, 1080 x 6803 pt). **El PDF es una imagen JPEG
única (2880 x 18142 px, equivale a un viewport de 1440 con retina x2)**: no hay capas, ni
fuentes, ni assets sueltos, ni versión móvil. Copia de trabajo en `docs/lote-1/referencia/`
(`s00..s10.jpg` = franjas de 900 px de alto a 1440 de ancho; `y_pdf = (n*900 + y_franja)*2`).

Rama: `rebrand-home-inka-palace`. Sin push, sin deploy.

## 1. Lectura del diseño

Marca: **Inka Palace Travel** (logo: viajero con bastón + montaña + avioncito, "INKA PALACE / TRAVEL").
Contacto visible: +51 959 173 246, maribel@inkapalacetravel.com, Jr. Carabaya 161 Lima,
www.inkapalacetravel.com, @inkapalacetravel. Gerente: Maribel Condori Gonzales.

Paleta (muestreada, a confirmar al maquetar): marrón-café oscuro (~#2b1b12, topbar/footer/botón
oscuro), dorado (~#dfa43a, CTA y acentos), verde bosque (~#234a3c, bloque Camino Inca / tarjeta
contacto), crema (~#f6f2ed, fondos alternos), WhatsApp verde (~#1fa855), acentos turquesa y
terracota/rojo en etiquetas. Cinta andina decorativa (franjas rojo/verde/azul/ocre/dorado)
bajo topbar, antes del bloque oscuro y antes del footer.

Tipografías (inferidas de la imagen, **verificar**): titulares en capitales con versalitas
tipo **Cinzel**; cuerpo y UI **Poppins**. Ambas OFL (gratis, self-host). Sin fuentes de pago.

Secciones, en orden:
1. Topbar: "Operador local con reserva segura", "Respondemos en <24 h", WhatsApp, aviso "Camino Inca 2027".
2. Header: logo, menú (Inicio, Tours, Destinos, Experiencias, Nosotros, Preguntas, Contacto), selector PEN/ES, carrito con contador, botón Contáctanos.
3. Hero a pantalla casi completa con slider de 3 destinos (Lima Centro, Ica, Machu Picchu): píldora "Destino 1 de 3", H1, texto, 2 CTAs, lista lateral con miniaturas, flechas y progreso.
4. Buscador (Destino / Fecha / Viajeros / Buscar tours) superpuesto al hero.
5. Franja de 5 garantías (operador local, reserva segura, guías, atención, cancelación flexible).
6. Botón flotante WhatsApp "¿Te ayudamos?".
7. "Un país, mil paisajes": mosaico de destinos (1 grande Machu Picchu + 4).
8. "Tours destacados": 4 tarjetas (insignia, corazón, ciudad, rating, reseñas, título, duración, idiomas, cancelación flexible, precio "Desde", Ver tour).
9. Banner Camino Inca "se agota meses antes" (fondo verde, foto, precio flotante, 2 CTAs).
10. "Tours por ciudad": 4 tarjetas (Ica, Cusco, Lima, Nazca) con contador de tours.
11. "Experiencias para vivir Perú": 3 tarjetas (Trekking, Gastronomía, Cultura).
12. Banda oscura con foto "Paisajes que no caben en una foto": 2 CTAs + 3 cifras.
13. "Encuentra un plan para tu tiempo": filtros (Todos/4 h/1 día/2–3 días/4+ días) + 4 tarjetas oscuras.
14. "Viaja con quienes conocen el camino": 4 razones.
15. "Lo que dicen nuestros viajeros": 3 testimonios + 3 sellos de plataformas.
16. FAQ (acordeón, 6 preguntas) + tarjeta de contacto de la gerente.
17. Tira de 6 fotos con bloque Instagram.
18. Newsletter (franja dorada).
19. Footer (logo, destinos, enlaces, contacto, redes, copyright, medios de pago).

## 2. Estático vs CMS

| Bloque | Origen | Nota |
|---|---|---|
| Topbar, header, footer, garantías, razones, FAQ, textos de hero | Estático (lang `es` + config de marca) | Textos en `lang/es/home.php`; datos de contacto en `config/brand.php` |
| Destinos (mosaico, por ciudad, footer) | CMS `destinations` | El modelo NO tiene imagen ni subtítulo: la foto se resuelve por slug desde un mapa en `config/brand.php`; sin match, tarjeta sin foto con fondo de marca |
| Experiencias | CMS `experiences` | Idem: icono + foto por slug |
| Tours destacados / por tiempo | CMS `tours` (`is_featured`, `duration_label`, precios) vía `Money::site()` | Sin rating, reseñas ni idiomas en el modelo: **se muestran como en el PDF, con marcadores "[4,9]" / "[N] reseñas"** hasta que exista el módulo de reseñas |
| Hero slider | 3 destinos configurables en `config/brand.php` | |
| Buscador, carrito, favoritos, filtros de duración | Solo UI (sin backend en este lote) | Carrito y reserva son lote 4 |
| Testimonios, sellos de plataformas, cifras | **Marcadores visibles** `[Nombre del viajero]`, `[4,9]`, `[N]+` | Regla 3 y 4 del proyecto: nada de cifras ni reseñas inventadas |
| Newsletter | UI sin envío | Sin backend de correo todavía |
| Instagram | 6 fotos estáticas | Sin API |

## 3. Qué falta / bloqueos para Anyerson

1. **Alcance del nombre**: Home solo, o todo el sitio (APP_NAME, tokens, panel Filament, correos). Recomendación: marca visible en todo el sitio público + panel (`APP_NAME="Inka Palace Travel"` en `.env.example`), pero **no** renombrar la base de datos ni los dominios. Hecho así en esta rama.
2. **Fotos**: el PDF solo trae recortes pequeños de las fotos (sin originales). Se recortan del PDF como **provisionales** (`public/img/provisional/`). Necesarias las originales en alta y su licencia/derechos (algunas parecen fotos de clientes reales: personas identificables).
3. **Logo**: solo existe en raster dentro del PDF (muy chico). Se recorta provisional; hace falta SVG/AI.
4. **Fuentes**: Cinzel + Poppins son una inferencia visual. Confirmar con la diseñadora; ambas gratuitas.
5. **Datos reales vs marcadores**: precios, rating, reseñas, número de viajeros, testimonios, medios de pago. Salvo los que ya están en el PDF como dato (contacto, "S/ 3,500.00" del Camino Inca) quedan como marcador.
6. **Versión móvil/tablet**: el PDF es solo escritorio (1440). El responsive lo resuelve maquetación con criterio propio; **no hay diseño que comparar** en móvil.
7. **"Camino Inca 2027" y "Cupos limitados"**: son afirmaciones comerciales; confirmar con la clienta que son verdad hoy.
8. **Idiomas**: Home solo en español (EN/PT es lote 5). El selector PEN/ES se maqueta; la moneda ya funciona con `Money`.
9. Datos de contacto/razón social/RUC/RNAVT siguen pendientes (checklist lote 0).

## 4. Fases y responsables

| Fase | Agente | Entrega |
|---|---|---|
| 0. Plan + referencia | jefe | este documento, `referencia/` |
| 1a. Datos y marca | webtilia-programacion | `config/brand.php`, `lang/es/home.php`, `HomeController` + ruta, datos del CMS (destinos, tours destacados, experiencias, conteos), tests; sin tocar `resources/` ni `phpunit.xml` |
| 1b. Tokens + Blade/Tailwind | webtilia-maquetacion | fuentes self-host, `tokens.css` Inka Palace, layout, componentes, recortes provisionales de imágenes, JS mínimo (slider hero, FAQ, filtros) |
| 2. QA | webtilia-qa | `npm run build`, tests, humo de rutas, consola, overflow en 6 viewports |
| 3. Fidelidad | webtilia-ojo | comparación lado a lado con `referencia/` en 1440 |
| 4. Cierre | jefe | informe, decisiones pendientes; commit por rama |

Regla de repo: `view:cache` antes de `npm run build`. No tocar `phpunit.xml`.
Tests: `DB_PORT=3307 php artisan test` (43 verdes de base).

# Lote 1 — Contrato del Home (backend -> vista) · versión sobre main (con prefijo /{locale}/)

Ruta: `GET /{locale}/` (`es`, `en`) nombrada `home`, middleware `locale`, dentro del grupo `{locale}` de `routes/web.php`
-> `App\Http\Controllers\HomeController` (invocable) -> `view('home', ...)`. `GET /` sigue siendo `301 -> /es/`.
Textos estáticos: `__('home.*')` (`lang/{es,en}/home.php`, paridad de claves 1:1, cubierta por `LangKeyParityTest` y `HomeTest`).
Marca/contacto/imágenes de marca: `config('brand.*')` (`config/brand.php`).
Sin caché (regla del proyecto: sin invalidación automática no se cachea). Todo se imprime con `{{ }}`.

## Cambios respecto a la rama vieja (leer antes de maquetar)

- **Las imágenes ya vienen como URL pública final**, lista para `src="{{ $x['image'] }}"`: las de `config/brand.php` salen por `asset()` (respeta la
  subcarpeta `/tour-word/public/`), y las del CMS por `coverImageUrl()` / `TourImage::url()` (disco `public`, ya respeta `PUBLIC_STORAGE_URL`).
  **No volver a envolver con `asset()`** una imagen del CMS (empieza con `/`): duplicaría el prefijo. `asset()` sobre una URL absoluta es idempotente,
  pero sobre `/storage/..` no.
- **Prioridad de imagen**: foto real del CMS (`cover_image_path` / primera `TourImage`) > provisional del mapa de marca por slug > `null` (la vista pone fondo de marca).
- **Las `url` ya no son `null`**: `route('destinations.show' | 'experiences.show' | 'tours.show', ['slug' => ...])` con el prefijo del idioma actual
  y el slug traducido de ese idioma (mismo mecanismo que las páginas internas; `URL::defaults` pone `{locale}`).
- **`key`** (nuevo en destinos, experiencias y tours): slug del idioma por defecto (`es`), estable entre idiomas. Usarlo para decisiones de maqueta
  (p. ej. destino grande = `key === 'machu-picchu'`) y no `slug`, que cambia por idioma. Los mapas de `brand.images` y los `home.*.taglines.*` se indexan por `key`.
- Precio: además del `price` formateado (moneda por defecto), cada tour trae `price_pen_cents` y `price_usd_cents` (int|null) para usar `<x-ui.money>`
  con el selector PEN/USD que ya existe en el header. El Camino Inca trae `incaTrailPrice` (PEN) e `incaTrailPriceUsd`.
- Se usan los scopes `published()`, `featured()`, `ordered()` (+ `orderBy('id')` como desempate) y `Money`.

## Variables de la vista

### `$hero` (array de 3 slides, orden fijo lima, ica, machu-picchu)
`slug, name, subtitle, title, text, cta_primary, cta_secondary, image, thumb, badge, url_primary, url_secondary`
- `badge` = "Destino 1 de 3: Lima Centro". `image`/`thumb`: URL pública completa.
- `url_primary` = ficha del destino real si existe publicado con ese slug (`key`), si no `tours.index`. `url_secondary` = `destinations.index`.
- El texto del slide de Ica es el marcador `[Texto del slide de Ica: pendiente de la clienta]` (el PDF no lo trae).

### `$destinations` (Collection, publicados, orden `order`,`id`; puede estar vacía)
`name, slug, key, image (URL|null), tagline (descripción del CMS o frase por key en lang), city_tagline (para "Tours por ciudad": `home.cities.taglines.{key}`; esa sección usa este campo, no `tagline`), tours_count (int, solo publicados), url`.
Mosaico: la vista decide el grande (sugerido `key === 'machu-picchu'`, insignia `home.destinations.most_wanted`).
Cuenta: `trans_choice('home.destinations.tours_count', $d['tours_count'])`; si 0 puede mostrar `home.destinations.placeholder_count`.

### `$experiences` (Collection, publicadas; puede estar vacía)
`name, slug, key, image (URL|null), description (CMS o lang por key), link_label, url`.

### `$featuredTours` (Collection, `featured()` + `published()`, máx. 4; puede estar vacía)
`title, slug, key, destination_name, duration_label, price (string Money|null), price_pen_cents, price_usd_cents, image (URL|null), image_alt, url`.
Rating, reseñas e idiomas no existen en el modelo: marcadores `home.featured.rating_placeholder|reviews_placeholder|languages_placeholder`.
Si `price` es null: `home.featured.price_placeholder` ("[PRECIO]").

### `$planTours` (Collection, máx. 4: uno por grupo de duración inferido de `duration_label`; vacía si no se puede inferir)
Mismas claves que `$featuredTours` más `bucket` (`4h|1dia|2-3dias|4mas`), `bucket_label`, `bucket_kicker`.
Vacía -> `home.plans.empty` (marcador) u ocultar. Filtros: `home.plans.filters.*` (solo UI).

### `$incaTrailPrice` (string|null) / `$incaTrailPriceUsd` (string|null)
"S/ 3,500.00" con Money (dato del PDF, a confirmar con la clienta; `config('brand.inca_trail.price_pen_cents')` = 350000). `null` si la moneda por defecto no es PEN.
`incaTrailPriceUsd`: equivalente con el tipo de cambio de Configuración; `null` si no está configurado (nunca lanza).

## Config de marca (`config('brand.*')`)
`name, tagline, logo, contact.{phone,whatsapp,whatsapp_url,email,address,website,website_url,instagram_handle}`, `manager.{name,role,initials}`,
`social.{tripadvisor,viator,youtube,instagram,facebook,tiktok}` (todas NULL: sin enlace), `inca_trail.price_pen_cents`, `images.{key}`,
`fixed_images.{camino_inca,paisajes,plan_nazca,tours.*,instagram[6]}` (rutas relativas a `public/`: en la vista usar `asset()`), `hero.slides`.

## Textos (`__('home.*')`)
Claves: `meta, topbar, menu, hero, search, guarantees[5], whatsapp_float, destinations, featured, inca_trail, cities, experiences, landscapes (stats[3]),
plans, reasons.items[4], testimonials.items[3] + seals[3], faq.items[6] (q,a), instagram, newsletter, footer`. EN = traducción directa, mismas claves.
Marcadores visibles (no sustituir; en EN se conservan tal cual los de cifra/nombre): `[Nombre del viajero]`, `[4,9]`, `[N]`, `[PRECIO]` / `[PRICE]`, `[medios de pago]`,
respuestas FAQ 2-6 pendientes. Cifras del diseño conservadas (confirmar con la clienta): "5 destinos en Perú", "Camino Inca, 4 días, S/ 3,500.00", "Respondemos en menos de 24 h".
`home.php` NO entra en `PublicCopyHasNoInternalNotesTest` a propósito: sus marcadores `[... pendiente de la clienta]` son los placeholders visibles que pide el proyecto.

## Marca en el resto del sitio (APP_NAME)
`APP_NAME="Inka Palace Travel"` (`.env.example` y `.env` local). `<title>`, `og:site_name`, aria-label del logo, copyright del footer y firma del correo de contacto
leen `config('app.name')`, así que cambian solos. No existe JSON-LD de Organization hoy. Cambios de texto en `lang/{es,en}/site.php`: ver reporte
(marca "Pacha Viva" -> "Inka Palace Travel"; "agencia en Cusco" -> "en Perú"; tagline del topbar -> "Agencia de turismo con expertos locales").
Seeder demo `DemoTourSeeder`: `meta_title` "... | Pacha Viva" -> "... | Inka Palace Travel" (solo afecta a quien vuelva a sembrar).
No se renombró BD (`pachaviva`), dominios de prueba (`pachaviva.test`) ni comentarios internos.

## Deploy (para quien despliegue)
- **Migraciones nuevas: ninguna.**
- `.env` del servidor: `APP_NAME="Inka Palace Travel"` (antes de `config:cache`). El resto sin cambios (`PUBLIC_STORAGE_URL`, `IS_STAGING_MIRROR`, etc.).
- Imágenes: deben existir en `public/img/provisional/` y `public/img/brand/` (las entrega maquetación; mapa en `config/brand.php`).
- Post-deploy: `php artisan config:clear && php artisan config:cache && php artisan route:cache && php artisan view:clear` (el cambio de closure a controlador exige refrescar `route:cache`).
- Si el catálogo demo ya está en BD, los `meta_title` sembrados antes seguirán diciendo "Pacha Viva" hasta editarlos en el panel (no se tocó la BD).

## Pendiente de la clienta (sin cambios)
URLs de redes, rating/reseñas/testimonios reales, texto del slide de Ica, respuestas FAQ 2-6, medios de pago, fotos originales y logo vectorial, confirmar "Camino Inca 2027" y precio S/ 3,500.00.

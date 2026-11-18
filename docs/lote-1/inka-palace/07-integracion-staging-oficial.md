# Integración de la hoja de marca oficial (staging-2026-10-13) en main

Fecha: 2026-11-18. Decisión de Anyerson: manda la hoja oficial (2026-10-12) para **paleta, tokens, logo/emblema/favicons y reglas de contraste**; manda el PDF para **estructura y composición del Home**; main conserva rutas `/es`, páginas internas, contenedor 1440/40, fixes de QA y tests.

## 1. Qué se tomó de cada lado

De **staging-2026-10-13** (lo que hoy corre en el servidor):
- Migraciones `2026_10_12_120000_rebrand_inca_palace_stored_text` y `2026_10_13_120000_rebrand_inka_palace_stored_text` (reescriben texto almacenado "Pacha Viva"/"Perú Local" -> "INCA PALACE Travel" -> "INKA PALACE"; tabla de respaldo, `down()` y reintento idempotente).
- Vistas de error (`resources/views/errors/*`) y `lang/{es,en}/errors.php`.
- `lang/{es,en}/site.php` (marca "INKA PALACE Travel"; claves nuevas de tour-card y `strip`), `DemoTourSeeder`, `TourFactory`, `AuditSampleDataCommand`, `config/cms.php`, `config/session.php`.
- Archivos de despliegue en subcarpeta: `.htaccess` raíz, `index.php` raíz, `database/.htaccess`.
- Favicons (`favicon.svg/.ico/-32.png`, `apple-touch-icon.png`), `public/images/brand/*` (logo, logo horizontal, emblema, webp/png), `public/images/site/og-default.jpg`, fuente Cinzel variable (latin + latin-ext).
- `ui/badge` (variantes `gold`, `recommended`, tamaño `lg`), `ui/carousel-shell` (fix de centrado de carrusel), `ui/eyebrow`, componente `brand/mark` (variantes `full` y `horizontal`).

De **main** (se conserva): estructura de header/footer/layout/home y `components/home/*`, `inka.css`, `HomeController`, rutas con prefijo de locale, contenedor 1440/40, a11y, `asset()` seguro en subcarpeta, Alpine, hero, tests (366).

## 2. Conflictos y resolución (21 archivos)

| Archivo | Resolución |
|---|---|
| `lang/{es,en}/site.php`, `DemoTourSeeder.php` | Lado staging en los hunks en conflicto: solo difería la marca ("INKA PALACE Travel" y "en Cusco, Perú"). Los hunks sin conflicto de main se mantienen. Efecto: se recupera "agencia de turismo en Cusco, Perú" en los textos de `site.seo/nosotros`; si no se quiere, es un cambio de copy. |
| `destinations/*`, `experiences/show`, `tours/show`, `styleguide` | Lado staging (comentarios de marca y color de placeholder `556b52`). |
| `layout`, `header`, `footer`, `home`, `tokens.css`, `app.css`, `ui/button`, `ui/feature-card`, `ui/tour-card`, `ui/trust-badge`, `ui/value-item` | Lado main (estructura). La paleta de `tokens.css` se aplicó en el commit B. |
| `brand/mark.blade.php` | Lado main en el merge; en el commit B se reemplazó por el de staging (logo oficial) con `full` por defecto. |
| `fonts/cinzel/OFL.txt` | Idénticos; se dejó el de main. |
| `home.blade.php` (auto-merge) | Git insertó la "franja de 4 íconos" de staging, que usa `$heroTrustIcons` inexistente en main (500 en el Home). Se quitó: el Home vuelve a ser el de main/PDF. |

## 3. Migraciones sobre la BD local (3307)

Revisadas, `migrate --pretend` limpio y ejecutadas con `migrate --force`: ambas fueron `DONE`. Solo tocan columnas de texto de `destinations`, `experiences`, `tours`, imágenes, `team_members` y las claves visibles de `settings` (`contact_address`, `contact_schedule`, `site_tagline`, `site_description`); saltan emails/URLs/rutas; guardan el original en `rebrand_inca_palace_backup` y `rebrand_inka_palace_backup`; `down()` restaura. En el servidor **ya están aplicadas** (el snapshot de staging las incluye), así que `migrate` no hará nada.

## 4. Paleta oficial en tokens (commit B)

Hoja: Espresso `#3E2F24`, Sage `#556B52`, Champagne Gold `#C9A96A`, Ivory `#FAF7EE`. Derivadas: `--gold-deep #7A5A1F` (texto dorado sobre claro), `--sage-text #3F5340`, `--sage-hover #475B45`, `--espresso-deep #2B2019`.
Mapeo de roles del PDF: café oscuro -> Espresso; verde bosque (banner Camino Inca, tarjeta de contacto) -> Sage; turquesa del PDF (no existe en la hoja) -> `--sage-text`; terracota de etiquetas -> `--danger #AB3A30` de la hoja; crema -> Ivory/arena; dorado -> Gold.
Contrastes medidos (WCAG 2.1) y verificados en el navegador:
- Texto espresso sobre relleno dorado: **5.72:1** (hover `#B8975A`: 4.65:1).
- Dorado vivo sobre ivory: 2.09:1, por eso **nunca como texto sobre claro**; texto dorado sobre claro usa `--gold-deep` (5.92:1 ivory). Se corrigieron enlaces de `.prose-pv` y el hover de enlaces de contacto, que usaban el dorado vivo.
- Sage con blanco 5.82:1; botón oscuro blanco/espresso 12.83:1.
- WhatsApp conserva verde propio `#158045`: **4.99:1** (≈5:1).
- Sobre espresso: `--on-dark-2` 9.26:1, `--on-dark-3` 5.83:1, dorado 5.72:1.
Logo: `public/images/brand/inka-palace-logo*` (apilado, transparente) en header (76px móvil / 96px escritorio) y pie (placa ivory); se eliminó el recorte provisional `public/img/brand/inka-palace-logo.png`; `config('brand.logo')` apunta al nuevo.

## 5. Decisión de fuente de cuerpo: Poppins (no Inter)

Staging usa Inter. El PDF muestra Poppins. Evidencia (`referencia/evidencia-fuente-cuerpo.png`, recortes de `s02.jpg` junto a render de ambas fuentes):
- Forma de letra: en "Laguna Humantay" y "Montaña de 7 Colores" la **"a" es de un solo piso** y la "g" y la "y" son geométricas, con "o" circular: rasgo de Poppins. Inter tiene "a" de dos pisos.
- Medidas sobre el PDF (1440 px): el párrafo "Los favoritos de nuestros viajeros... guía en" mide 521 px y "español o inglés." 132 px. Con **Poppins** el tamaño implícito es 15.9 y 15.8 px (= 16 px, tamaño redondo); con **Inter** 16.6 y 16.55 px (no redondo). El título de tarjeta (600) mide 172 px: Poppins 18.1 px (= 18 px); Inter 19.6 px.
Veredicto: **Cinzel para titulares + Poppins para cuerpo/UI**. Se retiran del repo Inter y Caveat (sin uso; la hoja oficial solo pide dos familias) y los Cinzel estáticos 600/700 de main, sustituidos por el Cinzel variable de staging. Las vistas de error también pasan a Poppins.

## 6. Cambios menores fuera de color

- Marca visible unificada a "INKA PALACE Travel" (config/brand, `.env.example`, lang/home, tests) para igualar la hoja y las migraciones.
- Favicons y `theme-color #3e2f24` ya venían en el layout.

## 7. Lista para el deploy (respecto al servidor = staging-2026-10-13)

Ver el reporte de la tarea: `git diff --name-status staging-2026-10-13 main`. Resumen:
- **Variables .env**: ninguna nueva (`APP_NAME` ya es "INKA PALACE Travel" en servidor; no cambiar `cms.is_staging_mirror`/`catalog_demo_content`).
- **Migraciones**: ninguna nueva (las dos de rebrand ya corrieron).
- **Borrados en public/** (existen en el servidor y ya no en main): `public/fonts/inter/` (2 woff2 + OFL), `public/fonts/bodoni-moda/BodoniModa-Variable.woff2`, `public/fonts/public-sans/PublicSans-Variable.woff2`. **Nuevos**: `public/fonts/poppins/*` (5 woff2 + OFL) y `public/img/provisional/*` (30 archivos, fotos del PDF).
- **Post-deploy**: `php artisan view:clear`, `php artisan config:clear`, `php artisan cache:clear`, `npm run build` y subir `public/build/` completo (está en .gitignore).

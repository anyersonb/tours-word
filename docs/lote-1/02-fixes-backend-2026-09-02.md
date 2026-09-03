# Lote 1 — Fixes de backend (2026-09-02)

Estado de entrada: auditoría del lote 1 en **NO APTO**
(`docs/lote-1/qa-cro-2026-09-02.md`). Este documento cubre los dos arreglos
asignados a backend-laravel. Trabajo en paralelo con maquetador-frontend:
reparto de archivos respetado (ver instrucciones de la tarea), sin tocar
`lang/es/site.php`, `resources/views/components/**`, `resources/views/layouts/**`,
`resources/css/**`, `home.blade.php` ni `nosotros.blade.php`.

## Arreglo 1 — El CMS no tenía dónde cargar los datos (defecto bloqueante)

### Diagnóstico confirmado

`app/Filament/Pages/Configuracion.php` exponía un solo campo (tipo de
cambio). `grep -r "Setting::get" resources/views` encontró 19 claves leídas
por el front sin ningún campo de formulario:

- Contacto: `contact_phone`, `contact_email`, `contact_address`
- Legal (Perú): `company_ruc`, `company_legal_name`, `rnavt_number`,
  `esnna_poster_url`, `complaints_book_url`, `privacy_policy_url`,
  `cancellation_policy_url`
- Redes: `social_instagram_url`, `social_facebook_url`, `social_youtube_url`
- Cifras (Home/Nosotros vía `x-ui.stats-strip`): `stat_years_experience`,
  `stat_happy_travelers`, `stat_tours_completed`, `stat_destinations_count`

Se agregó `contact_schedule` (horario de atención), nuevo tanto en Setting
como en el front: se muestra en una tarjeta nueva de `contact.blade.php`
("Horario de atención"), así el campo tiene un consumidor real desde el
día uno (nunca un campo que nadie lee).

### Cambios

- `app/Filament/Pages/Configuracion.php`: 4 secciones nuevas (Contacto,
  Identidad legal, Redes sociales, Cifras) sobre las 19+1 claves de arriba,
  con ayuda en lenguaje no técnico por campo. Validación: `email()`/`url()`
  en lo que corresponde, `RUC` con `regex:/^\d{11}$/` (11 dígitos Perú).
- `app/Models/Setting.php`: `set()` ahora persiste `NULL` real (no `''`
  ni `'0'`) cuando el valor es `null`, y `castValue()` devuelve `null` de
  inmediato si la columna `value` es `NULL`, sin pasar por el cast del tipo.
  Antes, un campo numérico vacío se guardaba como `''` y `(int) ''` daba
  `0` — exactamente el "0 publicable" que la auditoría prohíbe. Las
  cifras en `Configuracion::save()` nunca escriben `0` por defecto: un
  campo en blanco llama `Setting::set($key, null, ...)`.
- `resources/views/contact.blade.php`: nueva tarjeta "Horario de atención"
  (icono de reloj, mismo patrón `x-ui.contact-info-card` que las demás).

### Verificación del recorrido completo (sin `cache:clear`)

Test `ConfiguracionSettingsFormTest::test_a_saved_contact_setting_is_visible_on_the_public_contact_page_and_updates_again_without_clearing_cache`:

1. Guardo `contact_phone = '+51 900 111 222'` desde el panel (Livewire).
2. `GET /contacto` → aparece `+51 900 111 222` en el HTML.
3. Guardo de nuevo `contact_phone = '+51 900 333 444'` (sin ningún
   `Cache::flush`/`cache:clear` en el medio).
4. `GET /contacto` → aparece `+51 900 333 444` **y ya no aparece**
   `+51 900 111 222`.

Confirma lo que decía la auditoría: la invalidación de caché
(`Cache::forget` en `Setting::booted()`/`set()`) ya estaba bien
implementada — este fix solo le dio un formulario a esas claves.

## Arreglo 2 — Contacto adelantado del lote 3 (autorizado por Anyerson)

### Esquema

`database/migrations/2026_09_02_190000_create_contact_messages_table.php`:
`name`, `email`, `phone` (nullable), `subject`, `message`, `status`
(default `nuevo`), `channel` (default `web`, pensado para una futura bandeja
de WhatsApp/Instagram sin cambiar el esquema), `ip_address` (nullable),
`privacy_consent_at` (**NOT NULL** — Ley 29733: se guarda CUÁNDO se dio el
consentimiento, no solo que la validación de la casilla pasó).

`App\Models\ContactMessage` + `App\Enums\ContactMessageStatus`
(`nuevo`/`en_proceso`/`atendido`, con color para el badge de Filament).

### Flujo de envío

`App\Http\Requests\StoreContactMessageRequest` valida nombre, correo,
teléfono (opcional), asunto (contra
`array_keys(__('site.contacto.form.subject_options'))` — la MISMA lista que
ya usa el front en `lang/es/site.php`, sin duplicarla) y mensaje. La
casilla de privacidad usa la regla `accepted`.

`App\Http\Controllers\ContactMessageController::store()`:

1. **Honeypot** (`website`): si viene lleno, `Log::info` y `return
   back()->with('contact_success', true)` — antes de tocar la base o el
   correo. Un bot que llena todo, incluido el honeypot, recibe el mismo
   "éxito" que un visitante real; nunca un error que le confirme que fue
   detectado.
2. Guarda el mensaje (`privacy_consent_at = now()`, `ip_address`,
   `channel = 'web'`, `status = Nuevo`).
3. Envía `App\Mail\NewContactMessageReceived` **dentro de un try/catch**: si
   falla el envío, se loguea (`contact_message.notification_failed`) pero
   el mensaje YA está guardado — un fallo de correo nunca se come el
   mensaje.

**Antispam sin CAPTCHA de terceros**: honeypot (arriba) + límite de tasa
`throttle:5,10` en `routes/web.php` (`config('contact.rate_limit_*')`,
`config/contact.php`). Sin terceros que puedan chocar con la CSP en
producción (ya pasó con analítica en otro proyecto de la cartera).

### Panel de administración

`app/Filament/Resources/ContactMessages/**`: tabla con estado (badge de
color), asunto (traducido con el mismo array de `site.php`), filtro por
estado, badge de navegación con la cantidad de mensajes `Nuevo`. El
formulario de edición tiene TODO deshabilitado salvo "Estado" — es una
bandeja de leads, no un editor de contenido; probado que cambiar el estado
no altera nombre/mensaje/etc. Sin página "Crear": los mensajes solo llegan
del formulario público.

### `resources/views/contact.blade.php`

- `<form method="POST" action="{{ route('contact.store') }}">` + `@csrf`.
- Honeypot oculto de verdad: `position:absolute` fuera de pantalla (no
  `display:none`, que algunos bots ignoran), `tabindex="-1"`,
  `autocomplete="off"`, con su propia `<label>` (accesible si alguien usa
  lector de pantalla y decide no rellenarlo, igual que un humano).
  el enlace enlazado sigue viniendo del `Setting` del arreglo 1 (si no hay
- Cada campo con `:value="old(...)"` (o slot para el textarea) y
  `:error="$errors->first(...)"` — los componentes `x-ui.form.*` ya
  soportan ambas props, no hicieron falta cambios ahí.
- Banner de éxito (`session('contact_success')`) y resumen de error
  (`$errors->any()`), copy en `lang/es/contact-form.php` (mío).
- Quité `disabled` y el aviso "próximamente" **solo** del botón de envío.
  El newsletter y el buscador del header siguen deshabilitados: no
  entraban en este alcance y no los toqué.

### Defecto de frontend encontrado (reportado, no arreglado)

`resources/views/components/ui/button.blade.php` (fuera de mi alcance)
imprime **siempre** `type="button"` cuando no es un `<a>`, sin importar qué
atributos se le pasen — pasar `type="submit"` termina duplicado en el HTML
y el navegador se queda con el primero (`button`). `x-ui.button` no puede
usarse como submit real de un `<form>` tal como está. Usé un `<button
type="submit">` nativo con las mismas clases del variant `primary`/`md`
en `contact.blade.php` (mío) para no tocar el componente. Igual, el
`select` de asunto (`x-ui.form.select`) no tiene forma de pre-seleccionar
una opción con `old()`: si la validación falla, el desplegable vuelve al
placeholder. Ninguno de los dos es mío para arreglar — quedan para
`maquetador-frontend`.

### Correo: qué hace falta en producción

Local (`.env` actual): `MAIL_MAILER=log` — el aviso se escribe en
`storage/logs/laravel.log`, no se envía a ningún lado. Verificado con un
envío real contra el servidor de desarrollo: el HTML completo del correo
aparece en el log.

`App\Mail\NewContactMessageReceived` **no está en cola** (`ShouldQueue`) a
propósito: `QUEUE_CONNECTION=database` y no hay ningún `queue:work`
corriendo ni en local ni confirmado en producción. Poner el correo en cola
sin un worker supervisado falla en silencio — exactamente el mismo patrón
que ya quemó semanas de correos sin enviar en otro proyecto de la cartera,
solo que un paso más atrás. El envío es síncrono dentro de un try/catch
(arriba). Si más adelante se agrega un worker supervisado en producción,
recién ahí conviene volver a `ShouldQueue`.

**Para producción hace falta:**

- `MAIL_MAILER` real (`smtp`, `ses`, `postmark`, etc.) con credenciales.
- `CONTACT_NOTIFY_EMAIL` en `.env` — el correo que recibe el aviso de cada
  mensaje nuevo. Si se deja vacío, cae a `MAIL_FROM_ADDRESS` (ver
  `config/contact.php`). Es una variable **separada** del `Setting`
  `contact_email` (el que ve el visitante en el sitio): pueden coincidir o
  no, según decida la clienta.

## Nota de entorno: dos archivos de test quedaron bloqueados por el disco

Al editar `tests/Feature/ConfiguracionPageTest.php` y
`tests/Feature/SettingTest.php` ambos quedaron con el mismo bug de
sistema de archivos de Windows ya documentado para
`tests/Feature/AdminPanelAccessTest.php` (el archivo desaparece del disco,
`git status` lo marca `D`, y ni `Write` ni `git checkout -- <archivo>`
pueden recrearlo — EPERM/"Permission denied" incluso escribiendo directo
por shell). No se perdió cobertura: el contenido de ambos archivos
(los tests originales + los nuevos de este fix) vive ahora en
`tests/Feature/ConfiguracionSettingsFormTest.php` y
`tests/Feature/SettingCastingTest.php`. Cuando se reinicie la máquina y se
libere el lock, lo prolijo es borrar los dos archivos viejos (ya vacíos en
disco) y, si se quiere, renombrar los nuevos de vuelta a sus nombres
originales — no es bloqueante para este lote.

## Suite

`vendor/bin/phpunit -d memory_limit=512M` contra `pachaviva_test`:
**88 tests, 392 aserciones, verde** (65/267 antes del fix + 23 tests nuevos
netos, ver detalle en el reporte final).

## Datos de prueba

Sin artefactos de QA en `pachaviva` (BD de desarrollo/navegador): se hizo
un envío real de humo contra `http://127.0.0.1:8000/contacto` para
confirmar el recorrido completo (guardado + correo en log + banner de
éxito) y se borró con SQL directo, confirmado con
`SELECT COUNT(*) FROM contact_messages` = 0 y
`SELECT \`key\` FROM settings` = solo `exchange_rate_pen_usd` (el
preexistente).

---

# Ronda 2 (2026-09-02) — Prefijo de idioma + fix de `APP_URL`/imágenes

Entrada: `docs/lote-1/seo-2026-09-02.md`, Bloque 6 (S-08) y S-04, más el
Defecto Alto del CRO sobre `Storage::disk('public')->url()`. Reparto de
archivos de esta ronda respetado: no toqué `resources/views/components/ui/**`,
`resources/views/contact.blade.php`, `lang/es/site.php` ni `resources/css/**`.

## Arreglo 3 — Prefijo de idioma en todas las URLs (S-08)

### Decisión implementada (Anyerson)

Prefijar TODAS las URLs por locale desde ya, incluido español: `/es/`,
`/en/`, `/pt-br/` (guion, nunca `pt_BR`, por convención de slugs web). `/`
devuelve **301** a `/es/`. Se implementa ahora porque el sitio no está
publicado ni indexado (costo hoy: un `Route::group`; costo después de la
primera indexación: un 301 por URL — precedente de 76 redirecciones en
otro proyecto de la cartera).

### Cómo quedó

- `app/Support/Locale.php` (nuevo): única fuente de la conversión entre el
  locale interno de `config('cms.locales')` (`es`, `en`, `pt_BR`) y el
  segmento de URL (`es`, `en`, `pt-br`). `toSegment()`/`fromSegment()`/
  `isActive()`.
- `app/Http/Middleware/SetLocaleFromUrl.php` (nuevo): lee `{locale}` de la
  URL, lo valida contra `config('cms.locales')` (el esquema completo, no
  `active_locales`), llama `App::setLocale()` y fija
  `URL::defaults(['locale' => ...])` para que `route('about')`,
  `route('contact')`, `route('home')`, etc. sigan generando bien sin que
  ninguna vista pase `locale` a mano. **Nunca** resuelve por sesión, cookie
  ni `Accept-Language` — es el antipatrón que el contrato del proyecto pide
  evitar.
- `bootstrap/app.php`: alias de middleware `'locale' =>
  SetLocaleFromUrl::class` (Laravel 12 no tiene `Kernel.php`).
- `routes/web.php`: `Route::redirect('/', '/es/', 301)` + todas las rutas
  existentes movidas dentro de `Route::prefix('{locale}')->middleware('locale')`.
  **Los nombres de ruta no cambiaron** (`about`, `contact`, `contact.store`,
  `styleguide`); se agregó `name('home')` a la ruta `/` (no tenía nombre
  antes, así que no rompe el mandato de "conservar los nombres tal cual").

### Decisión documentada: `/en/` antes de estar activo

**404, no redirección a `/es/`.** Verificado con `App::setLocale()`
llamado solo cuando el locale está en `config('cms.active_locales')`.
Redirigir simularía que `/en/` ya existe sirviendo contenido en español
bajo una URL que dice ser inglesa — la misma inconsistencia de hreflang
que el propio informe SEO advierte evitar. Cuando el lote 5 active
`en`/`pt_BR` en `config/cms.php`, esas rutas empiezan a responder 200 sin
tocar el middleware ni las rutas.

### Efecto colateral encontrado y corregido: enlaces "Inicio" hardcodeados

`components/site/header.blade.php` (nav + logo) y `footer.blade.php`
tenían `href="/"`/`'route' => '/'` cableado a mano (no usaban
`route('home')` porque esa ruta no existía todavía). Con el prefijo
activo, esos enlaces seguían funcionando (pasan por el 301 de `/` →
`/es/`) pero agregaban un salto de redirección innecesario en cada clic al
logo. Corregido con `route('home')` en ambos archivos (no están en la
lista de exclusión: solo `components/ui/**` lo está) y en
`resources/views/nosotros.blade.php` (breadcrumb "Inicio").

**Pendiente para `maquetador-frontend`**: `resources/views/contact.blade.php`
línea 69 tiene el mismo patrón (`'href' => '/'` en el breadcrumb) — es
idéntico al de `nosotros.blade.php` que sí corregí, pero ese archivo está
fuera de mi reparto en esta ronda. No rompe nada (el 301 lo resuelve),
solo agrega un salto de redirección de más en el breadcrumb de Contacto.

### Tests nuevos

`tests/Feature/LocalePrefixRoutingTest.php` (8 tests): 301 de `/` a
`/es/`, 200 en `/es/`, `/es/nosotros`, `/es/contacto`; 404 para un locale
fuera del esquema (`/de/`); 404 (no redirect) para uno del esquema pero
inactivo (`/en/`); 404 para las URLs viejas sin prefijo; que el segmento
de la URL de verdad mueva `App::currentLocale()` (activando `en` solo
para ese test, para que el assert no sea vacío contra el fallback `es`);
`pt-br` vs `pt_BR`; y que los nombres de ruta sigan resolviendo con el
prefijo.

### Tests existentes actualizados

`tests/Feature/ContactMessageSubmissionTest.php` y
`tests/Feature/ConfiguracionSettingsFormTest.php`: todas las rutas
`/contacto` pasaron a `/es/contacto` (`sed` sobre el literal, verificado
uno por uno con grep antes y después).

## Arreglo 4 — `APP_URL` rompía las imágenes del CMS en silencio (Defecto Alto del CRO / S-04)

### Diagnóstico confirmado

`config/filesystems.php` armaba la URL del disco `public` con
`rtrim(env('APP_URL'), '/').'/storage'`. Con `.env` en
`APP_URL=http://tours-word.test` (host que no resuelve en esta máquina,
verificado con `curl`), toda imagen servida por
`Destination::coverImageUrl()`, `Experience::coverImageUrl()`,
`TeamMember::photoUrl()` y `TourImage::url()` se rompía en el navegador
(`ERR_NAME_NOT_RESOLVED`, `naturalWidth: 0`) sin que la página fallara de
ninguna otra forma — el riesgo real es de producción: si `APP_URL` llega
mal, nada avisa.

### Cambio

`config/filesystems.php`: `'url' => '/storage'` (relativa, ya no depende
de `APP_URL`). El disco sigue sirviendo desde `storage/app/public` vía el
symlink de `storage:link` (no se tocó `config('filesystems.links')`), y
`og:image`/canonical en `layout.blade.php` siguen absolutos porque usan
`asset()`/`url()->current()`, no el disco `public` (Open Graph sí exige
absoluta ahí, no se tocó ese comportamiento).

`.env` y `.env.example`: `APP_URL` cambiado a `http://127.0.0.1:8000`
(el servidor que ya estaba corriendo) — sigue haciendo falta para `asset()`
en contexto de consola (sitemap futuro, S-04) y para el `og:image`.

### Tests nuevos

`tests/Feature/PublicDiskRelativeUrlTest.php` (6 tests), contra el disco
`public` REAL (nunca `Storage::fake`, porque el bug estaba en la config
del disco, no en el driver):

- Control negativo: reproduce la fórmula vieja y confirma que SÍ mete el
  host roto en la URL (prueba que el test puede fallar).
- `Storage::disk('public')->url()` da `/storage/...` aun con
  `APP_URL` apuntando a un host inexistente.
- Los 4 accessors (`Destination`, `Experience`, `TeamMember`, `TourImage`)
  devuelven la URL relativa sobre un archivo escrito con `Storage::put()`
  (contenido real, no `UploadedFile::fake()` — no hacía falta simular
  subida, el bug estaba en cómo se lee la URL de un archivo ya guardado).

**Verificado a mano que el check no es vacío**: reescribí temporalmente
`config/filesystems.php` a la fórmula vieja, corrí
`PublicDiskRelativeUrlTest` y 5/6 tests pasaron a **FALLAR** (todas menos
el control negativo, que ya esperaba el host roto), y luego restauré el
fix.

## Verificación contra el servidor vivo (`http://127.0.0.1:8000`)

- `GET /` → **301** → `Location: /es`.
- `GET /es/`, `/es/nosotros`, `/es/contacto` → **200**.
- `GET /en/` → **404** (locale del esquema, inactivo).
- `GET /de/` → **404** (fuera del esquema).
- `GET /nosotros`, `/contacto` (sin prefijo) → **404**.
- Formulario de contacto real: `POST /es/contacto` con token CSRF real →
  **302** a `/es/contacto`, fila insertada en `contact_messages` (id 4,
  confirmada por SQL), borrada después
  (`DELETE ... WHERE email='qa-ronda2@example.com'`, `COUNT(*)` final = 0).
- Imagen real: escribí un PNG de 52 bytes en
  `storage/app/public/qa-smoke/test.png` y pedí
  `GET /storage/qa-smoke/test.png` → **200**, `Content-Type: image/png`,
  52 bytes. Archivo borrado después; el mismo `GET` vuelve a dar **403**
  (ya no hay nada que listar/servir).
- `<link rel="canonical">`, `og:url` y los `hreflang` en `/es/nosotros`
  siguen siendo absolutos y correctos (`url()->current()` no se tocó).

## Suite

`vendor/bin/phpunit -d memory_limit=512M` contra `pachaviva_test`:
**102 tests, 430 aserciones, verde** (88/392 antes de esta ronda + 14
tests nuevos / 38 aserciones netas: `LocalePrefixRoutingTest` +
`PublicDiskRelativeUrlTest`).

## Qué quedó abierto (no lo hice)

- `resources/views/contact.blade.php` línea 69: mismo `href="/"` hardcodeado
  del breadcrumb que corregí en `nosotros.blade.php`, fuera de mi reparto
  — para `maquetador-frontend`.
- No probé el formulario ni la imagen "por el panel" con un navegador real
  (no tengo herramienta de navegación en este rol): el envío de contacto se
  probó con `curl` end-to-end (token CSRF real, cookie de sesión real) y la
  imagen se probó escribiendo el archivo directo al disco `public` — ambos
  ejercitan el mismo código que dispara el panel, pero no es lo mismo que un
  clic real. Queda para `anyerson-qa`.
- No corrí `config:cache`/`route:cache` ni verifiqué el comportamiento con
  config cacheada (no hay `bootstrap/cache/config.php` en este entorno). Si
  producción cachea config, `APP_URL` debe quedar bien puesto ANTES de
  `config:cache`, igual que ya advierte S-04 del informe SEO.
- `security-engineer` corre después de esto, según el flujo del proyecto.

# Ronda 3 (2026-09-02) — Limpieza de archivos huérfanos, sitemap.xml y robots.txt

Última ronda de arreglos antes del gate de QA de este lote. Alcance: 4 tareas
cortas (S-01, S-05, defecto Bajo de archivos huérfanos, y un enlace
cableado en `contact.blade.php` autorizado excepcionalmente).

## Arreglo 5 — Archivos huérfanos al borrar el registro (defecto Bajo)

### Diagnóstico confirmado

`TeamMember`, `Destination`, `Experience` y `TourImage` no tenían ningún
hook `deleting`: borrar el registro desde el panel dejaba el archivo físico
huérfano en `storage/app/public`.

Caso concreto pedido en el brief -- confirmado por lectura y por test:
`tour_images.tour_id` tiene `cascadeOnDelete()` a nivel de MySQL
(`database/migrations/2026_09_01_221902_create_tour_images_table.php`).
Cuando se borra un `Tour`, MySQL borra las filas de `tour_images`
directamente por la constraint de la base de datos, **sin pasar por
Eloquent**: el hook `deleting` de `TourImage` nunca se dispara en ese
caso. Verificado con un test que crea un `Tour` con una `TourImage`, borra
el `Tour`, y confirma que la fila desaparece (por la cascada de SQL) pero
que el archivo seguía en disco hasta que agregué el hook explícito en
`Tour::booted()`.

### Cambios

- `app/Models/Concerns/DeletesStoredFileOnDelete.php` (nuevo trait): hook
  `deleting` que borra el archivo del disco `public` best-effort (nunca
  bloquea el borrado del registro si el archivo ya no está o el disco
  falla; solo deja un `Log::warning`). Cada modelo declara qué atributo
  guarda la ruta vía `storedFileAttribute()`.
- `app/Models/TeamMember.php`, `Destination.php`, `Experience.php`,
  `TourImage.php`: usan el trait (`photo_path` / `cover_image_path` /
  `cover_image_path` / `path` respectivamente).
- `app/Models/Tour.php`: `booted()` agrega `static::deleting()` que
  recorre `$tour->images` y llama a `$image->deleteStoredFile()` **antes**
  de que la cascada de MySQL borre las filas -- es el único punto donde se
  puede intervenir, porque después de la cascada las filas ya no existen
  para consultarlas.

### Tests nuevos

`tests/Feature/OrphanedFileCleanupTest.php` (7 tests, incluye el caso de
la cascada y el caso de borrar un registro con archivo ya ausente del
disco). Verificación negativa: quité a mano el bloque `static::deleting()`
de `Tour::booted()` y corrí
`test_deleting_a_tour_deletes_its_images_files_despite_the_db_cascade` --
falló con "Found unexpected file or directory at path
[tours/gallery/cascade.jpg]", confirmando que el test detecta la ausencia
del fix. Restauré el archivo desde el backup y volvió a pasar.

## Arreglo 6 — `sitemap.xml` (S-01)

Nuevo `App\Http\Controllers\SitemapController` (invocable), registrado en
`routes/web.php` como `GET /sitemap.xml` **fuera** del grupo `{locale}`
(un sitemap agrupa todos los idiomas en un único documento). Construye
las URLs cruzando `config('cms.active_locales')` (hoy solo `es`) con los 3
nombres de ruta indexables (`home`, `about`, `contact`) vía `route()`, así
que:

- No lista `styleguide` (noindex) ni nada de `/admin`.
- El día que el lote 5 active `en`/`pt_BR` en `config/cms.php`, el sitemap
  los lista solos, sin tocar el controlador (cubierto por
  `test_activating_a_new_locale_adds_its_urls_without_touching_the_mechanism`).
- El día que el lote 3 agregue tours/destinos como páginas públicas, se
  extiende `SitemapController::ROUTE_NAMES` o se alimenta una query de
  registros publicados al mismo builder de XML -- no hay que rehacer el
  mecanismo.

**Aviso de despliegue (bien visible a propósito):** las URLs del sitemap
son absolutas y salen de la resolución normal de `route()`/`url()` de
Laravel, que en una request HTTP real usa el Host de esa request. Aun así,
`APP_URL` en el `.env` de producción **debe ser el dominio real**, porque
también alimenta `config('filesystems.disks.public.url')` (URLs de
imágenes, el mismo patrón que ya causó un defecto Alto en este lote, ver
Arreglo 4) y cualquier generación futura por consola. Confirmar `APP_URL`
antes de cachear config en el deploy.

### Tests nuevos

`tests/Feature/SitemapTest.php` (5 tests). Nota de instrumentación: el
primer intento de test usaba `$xml->xpath('//url/loc')` y daba un array
vacío en TODOS los casos -- no porque el sitemap estuviera vacío (`curl`
contra el servidor vivo mostraba las 3 URLs bien), sino porque
`<urlset>` declara un namespace por defecto que SimpleXML no resuelve sin
`registerXPathNamespace()`. Un xpath que no matchea nada da array vacío,
no error: es exactamente el tipo de check que pasa en falso sobre una
extracción vacía. Corregido registrando el namespace con prefijo antes de
consultar.

## Arreglo 7 — `Sitemap:` en `robots.txt` (S-05)

`public/robots.txt` ahora declara `Sitemap: http://127.0.0.1:8000/sitemap.xml`
y agrega `Disallow: /admin` (el panel de Filament no estaba bloqueado:
`Disallow:` vacío permitía rastrear todo, panel incluido). Es un archivo
**estático** -- Laravel no lo genera ni lo sirve por ruta -- así que dejé
un comentario dentro del propio archivo recordando que el dominio hay que
actualizarlo a mano en cada entorno (staging/producción), a diferencia de
`/sitemap.xml` que se resuelve solo.

Test: `test_robots_txt_declares_the_sitemap_and_blocks_the_admin_panel` lee
el archivo del disco (`file_get_contents(public_path(...))`), no por HTTP
-- `$this->get('/robots.txt')` da 404 en el kernel de testing porque los
archivos estáticos del docroot no pasan por el router de Laravel.

## Arreglo 8 — Enlace cableado en las migas de pan de contacto

`resources/views/contact.blade.php:69`: `'href' => '/'` cableado a mano
(mismo patrón ya corregido en header/footer/`nosotros.blade.php`).
Cambiado a `route('home')`. Única línea tocada de ese archivo, por
excepción autorizada explícitamente para esta ronda.

## Qué quedó abierto (no lo hice)

- No verifiqué el comportamiento de `/sitemap.xml` ni de `robots.txt` con
  `config:cache`/`route:cache` activos (no hay `bootstrap/cache/*.php` en
  este entorno). El aviso de `APP_URL` de arriba aplica igual.
- No corrí la suite completa en un solo proceso al final de esta ronda por
  el "falso hang" de PHPUnit en Windows con salida redirigida a un pipe
  (el proceso corre bien pero el buffer de stdout no se vacía hasta que
  termina; ver `feedback_phpunit_falso_hang_windows` en memoria del
  agente). Corrí cada archivo de test nuevo por separado en foreground
  (`SitemapTest`: 5/5 verde; `OrphanedFileCleanupTest`: 7/7 verde) y dejé
  la corrida completa en curso al cierre de mi turno -- confirmar el
  conteo final antes de dar el lote por cerrado.
- `anyerson-qa` y `security-engineer` corren después de esto, según el
  flujo del proyecto.

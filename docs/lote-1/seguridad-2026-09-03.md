# Auditoría de seguridad — Lote 1 "Pacha Viva"

- Rama `lote-1-sistema-diseno`, commit `7adf79e`, árbol limpio (`git status --porcelain` sin salida).
- Laravel 12.69.1 · PHP 8.2.1 · Filament 4.12 · Tailwind 4.
- Fecha: 2026-09-03. Auditor: security-engineer. **No se aplicó ningún fix.**

## Veredicto en dos niveles

| Nivel | Veredicto |
|---|---|
| **Para seguir** (validación de cliente) | **APTO** — no hay hallazgo Crítico. Ningún defecto encontrado es explotable por un visitante anónimo. |
| **Para producción** | **NO APTO** — 3 bloqueantes Altos, todos de despliegue/legales, ninguno de código de aplicación. |

---

## Hallazgos

### CRÍTICO
Ninguno.

### ALTO

#### A-1 · Consentimiento recogido contra una política de privacidad que no existe (Ley 29733)
- **Dónde**: `resources/views/contact.blade.php:207-220` (casilla), `lang/es/site.php:172-175`, `app/Http/Controllers/ContactMessageController.php:35`.
- **Daño concreto**: no es un exploit técnico, es incumplimiento legal. El formulario ya está activo y persiste nombre, correo, teléfono, mensaje libre e **IP**. La casilla dice "Acepto la política de privacidad y el tratamiento de mis datos", pero cuando `privacy_policy_url` está vacío (estado actual) el enlace se degrada a texto plano *"política de privacidad (en preparación por la clienta)"* (línea 218). El controlador sella `privacy_consent_at = now()`, pero no existe el documento consentido ni se guarda a qué versión se consintió. El formulario tampoco **informa** identidad del titular del banco de datos, finalidad, plazo de conservación, ni cómo ejercer derechos.
- **Base legal peruana (obligación, no buena práctica)**: Ley 29733 art. 5 (consentimiento **informado**, previo, expreso) y art. 18 (derecho a la información del titular), con el detalle del D.S. 003-2013-JUS. Un consentimiento sin documento informativo es impugnable ante la ANPD, y el banco de datos personales no está declarado.
- **Remedio**: (a) publicar la política y cargar `privacy_policy_url` **antes** de abrir el formulario; (b) mientras no exista, dejar el formulario cerrado o sin persistir en base; (c) guardar la versión/URL consentida junto a `privacy_consent_at` (columna `privacy_policy_version`); (d) agregar bajo la casilla el aviso mínimo del art. 18.
- **A quién**: `backend-laravel` (columna + aviso) y `maquetador-frontend` (texto en la vista). Decisión de negocio: Anyerson/clienta.

#### A-2 · Se desplegaría sin ninguna cabecera de seguridad, sin HTTPS forzado y con la cookie de sesión sin `Secure`
- **Dónde**: `bootstrap/app.php:14-21` (no hay middleware de cabeceras), `config/session.php:172` (`'secure' => env('SESSION_SECURE_COOKIE')`, sin valor en `.env`/`.env.example`), ausencia de `URL::forceScheme`.
- **Evidencia**: `curl -D - http://127.0.0.1:8000/es/` devuelve únicamente `X-Powered-By: PHP/8.2.1` y dos `Set-Cookie` con `samesite=lax`. Sin `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` ni `Strict-Transport-Security`. `grep -rn 'X-Frame-Options|Content-Security-Policy|Strict-Transport|forceScheme' app config bootstrap` → sin resultados.
- **Cómo se explota**: sobre HTTP (o el primer salto antes del redirect a HTTPS) la cookie `pacha-viva-session` viaja en claro → robo de sesión del panel en red compartida. Sin `X-Frame-Options`/CSP `frame-ancestors`, `/admin` se puede enmarcar para clickjacking sobre el borrado de mensajes. Falla **abierta**: si nadie toca nada al desplegar, sale insegura.
- **Remedio**: middleware de cabeceras en el grupo `web` y en el panel; `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax` explícito; `URL::forceScheme('https')` cuando `app()->isProduction()`; HSTS en el vhost. CSP: arrancar en `report-only` — el precedente de Lima View (Clarity y `/g/collect` de GA4 bloqueados en producción) obliga a inventariar orígenes antes de `enforce`.
- **A quién**: `backend-laravel` (middleware + config), `deployer` (vhost/HSTS y variables de entorno).

#### A-3 · Configuración de despliegue: `APP_DEBUG=true`, `APP_ENV=local`, `APP_URL` local y admin de desarrollo con clave conocida
- **Dónde**: `.env:2-5` (`APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://127.0.0.1:8000`), `.env.example:2-5` (mismos valores como plantilla), usuario `dev@pachaviva.test` con `is_admin=1` (verificado: `SELECT id,name,email,is_admin FROM users` → una sola fila, id 3).
- **Cómo se explota**: con `APP_DEBUG=true` cualquier error 500 publica traza, código y variables de entorno (incluidos `APP_KEY` y credenciales de base). El admin de desarrollo con clave conocida es acceso total al panel: **es exactamente el fallo que en DP Development dejó `admin@dp.local/password` activo en producción**; el precedente ya existe en la cartera.
- **Verificado como correcto**: `.env` NO está versionado (`git ls-files | grep '\.env'` → solo `.env.example`), y `.env.example` no trae secretos reales (`APP_KEY=` vacío, `MAIL_PASSWORD=null`). `.gitignore` cubre `.env`, `.env.production`, `/vendor`, `/node_modules`, `*.log`. `DatabaseSeeder.php` no crea usuarios.
- **Remedio**: checklist de despliegue (abajo). El admin de desarrollo se **borra**, no se le cambia la clave; el admin real se crea en el servidor con clave generada y nunca queda en un seeder.
- **A quién**: `deployer`.

### MEDIO

#### M-1 · `preventFilePathTampering()` desactivado: la rama "string" del campo de subida no se valida nunca
- **Dónde**: `app/Filament/Support/SecureImageUpload.php:34-49` (no llama a `preventFilePathTampering()`) y la copia en línea de `app/Filament/Resources/Tours/Schemas/TourForm.php`. Sumidero: `app/Models/Concerns/DeletesStoredFileOnDelete.php:47` (`Storage::disk('public')->delete($path)`).
- **Evidencia**: PoC en proceso → `SecureImageUpload::configure(...)->shouldPreventFilePathTampering()` devuelve **`false`**. En `vendor/filament/forms/src/Components/BaseFileUpload.php:752-753` el cierre que aplica las reglas de archivo hace `array_filter($value, fn ($file) => $file instanceof TemporaryUploadedFile)`: **los valores string se descartan y no se validan con nada**. El guard que sí los valida (líneas 728-749) solo se registra si `shouldPreventFilePathTampering()` es true.
- **Cómo se explota**: requiere sesión de panel (la clienta, o quien consiga una). En la edición de un TeamMember/Destination/Experience se fuerza el estado Livewire del campo a un string arbitrario (`data.photo_path.<key> = 'tours/otra-foto.jpg'`). Pasa validación, se guarda literal en la columna, y al borrar el registro la app borra el archivo apuntado — el de **otro** registro. También permite dejar la columna con un path inventado (imagen rota en producción sin rastro).
- **Límite verificado**: no se escapa del disco. `Storage::disk('public')->delete('../../../../../evil.txt')` y `'equipo/../../private/evil.txt'` → ambos lanzan `PathTraversalDetected` (Flysystem). `'equipo/nada.jpg'` pasa sin excepción. Es decir: borrado arbitrario **dentro** del disco `public`, no fuera. Sin XSS: el path se renderiza con `{{ }}`.
- **Remedio**: `->preventFilePathTampering()` en `SecureImageUpload::configure()` (una línea, cubre los tres campos nuevos) y en la copia de `TourForm`. Extra: validar el formato del path guardado (`regex:/^[a-z0-9\-\/]+\.(jpg|png|webp)$/`) antes de pasarlo a `delete()`.
- **A quién**: `backend-laravel`.

#### M-2 · Sin proxies de confianza: detrás de CDN/proxy el límite de tasa se vuelve un cubo global y la IP registrada no identifica a nadie
- **Dónde**: `bootstrap/app.php:14-21` — no hay `->trustProxies(...)`; no existe `config/trustedproxy.php`. Consumidores: `routes/web.php:54` (`throttle:5,10`, clave por IP) y `ContactMessageController.php:32` (`ip_address`).
- **Cómo se explota**: con el `TrustProxies` por defecto y sin proxies declarados, `$request->ip()` es siempre `REMOTE_ADDR`. Si producción queda detrás de Cloudflare/nginx/balanceador, eso es **la IP del proxy para todos los visitantes**: (a) los 5 intentos por 10 minutos se comparten entre el mundo entero, así que un solo spammer deja el único canal de captación de la agencia en 429 para todos; (b) `ip_address` guarda la IP del CDN, o sea que la evidencia recogida "por Ley 29733" no sirve de evidencia. La ausencia de configuración falla **cerrada** para spoofing (no se puede falsear con `X-Forwarded-For`), lo cual está bien: el arreglo es declarar los rangos reales, **nunca `'*'`**.
- **No verificado**: la topología real de producción (proxy sí/no). Si el servidor atiende directo, este hallazgo no aplica y se cierra como "no corresponde".
- **Remedio**: `->trustProxies(at: ['<rangos del proxy>'])` en `bootstrap/app.php`, y confirmar tras el despliegue que dos visitantes distintos consumen cubos distintos.
- **A quién**: `backend-laravel` + confirmación de `deployer`.

#### M-3 · Sin política ni mecanismo de retención de datos personales
- **Dónde**: `database/migrations/*_create_contact_messages_table.php`, `app/Models/ContactMessage.php`. Verificado: no hay trait `Prunable` en `app/` (`grep -rln 'Prunable' app` → sin resultados) y el único comando es `AuditSampleDataCommand`.
- **Daño**: nombre, correo, teléfono, mensaje libre e IP se acumulan indefinidamente. Ley 29733 art. 8 (principio de calidad: conservar solo el tiempo necesario para la finalidad) + el plazo debe estar **declarado** en la política de privacidad, que de todos modos hay que pedirle a la clienta (A-1).
- **Remedio**: `ContactMessage` con `Prunable` (`prunable(): Builder { return static::where('created_at', '<', now()->subMonths(12)); }`) + `model:prune` en el cron, y el plazo escrito en la política. Alternativa mínima: anonimizar `ip_address` a /24 pasados 30 días.
- **A quién**: `backend-laravel`.

#### M-4 · No se registra ningún evento crítico
- **Dónde**: los únicos `Log::` de la app son `ContactMessageController.php:23` (honeypot), `:42` (fallo de correo) y `DeletesStoredFileOnDelete.php:49`. No hay registro de: inicio de sesión exitoso/fallido en `/admin`, cambio de cualquiera de las ~20 claves de `Configuracion`, ni **borrado de mensajes de contacto** (`ContactMessagesTable.php:47-51` expone `DeleteBulkAction`).
- **Daño**: un borrado masivo de datos personales o un cambio de las URLs legales del sitio no deja rastro de quién ni cuándo. Es la rendición de cuentas que acompaña a A-1/M-3 y lo que hace falta para reconstruir un incidente.
- **En el otro sentido**: `Log::info('contact_message.honeypot_triggered', ['ip' => ...])` escribe una IP (dato personal) en `storage/logs` con `LOG_LEVEL=debug`. Resuelta la retención en base (M-3), el log seguiría conservándola.
- **Remedio**: escuchar `Illuminate\Auth\Events\{Login,Failed}` y registrar usuario+IP; registrar en `Configuracion::save()` las claves modificadas (no los valores) con el id del usuario; registrar el borrado de `ContactMessage` con id y actor. `LOG_LEVEL=warning` en producción y `LOG_STACK=daily` con rotación.
- **A quién**: `backend-laravel`.

#### M-5 · Un solo rol: cualquier cuenta con `is_admin=1` lee, exporta y borra todos los datos personales y reescribe las URLs legales
- **Dónde**: `app/Models/User.php:50-53` (`canAccessPanel()` devuelve `is_admin`), sin `app/Policies/`, sin `canAccess()` en `app/Filament/Pages/Configuracion.php`, sin `authorize*` en los cuatro recursos.
- **Contando todas las puertas**: (1) panel → `is_admin`, cerrado; (2) alta de usuarios → **no existe** `UserResource` ni ruta de registro (árbol completo de `app/Filament` revisado); (3) `is_admin` fuera de `$fillable` **y** en `$guarded` (`User.php:31-42`) — redundante pero correcto: si mañana alguien vacía `$fillable`, `$guarded` sigue protegiendo; (4) recursos de Filament, página de Configuración y `DeleteBulkAction` → **sin control propio**: todo admin puede todo. Ninguna puerta quedó abierta por descuido al volver mutable la Configuración; la observación es que no hay granularidad. Aceptable **hoy** (una sola cuenta) y hay que declararlo como decisión: el día que entre un segundo usuario "editor", Configuración y el borrado de mensajes necesitan Policy.
- **Remedio (cuando entre el segundo usuario)**: `Configuracion::canAccess()` restringido, `ContactMessagePolicy::delete()` y `viewAny()`.
- **A quién**: `backend-laravel`, no ahora — anotarlo en el seguimiento del proyecto.

### BAJO

#### B-1 · El mensaje del visitante se interpreta como Markdown en el correo al equipo
- **Dónde**: `app/Mail/NewContactMessageReceived.php:32` (`->markdown('emails.contact-message')`), `resources/views/emails/contact-message.blade.php:15`.
- **Evidencia**: rendericé el mailable con un payload de auditoría. El HTML **no** tiene XSS (`<script>` sale como `&lt;script&gt;`, `<img onerror>` sale escapado completo, y `[texto](javascript:alert(1))` se renderizó como `<a>` **sin atributo href** — CommonMark con `allow_unsafe_links => false`). Pero `[texto](https://...)` sí produce un `<a href>` real.
- **Cómo se explota**: un visitante anónimo envía `[Verifique su cuenta de PayPal](https://phishing.example)` en el campo mensaje. El correo que llega a la bandeja de la agencia trae un enlace clickeable con texto arbitrario, con remitente y plantilla legítimos del sitio. Phishing dirigido al personal de la clienta.
- **Remedio**: no pasar el cuerpo del visitante por Markdown. Vista HTML plana con `{!! nl2br(e($contactMessage->message)) !!}` (escapado explícito antes del `nl2br`), o envolverlo en un bloque de código.
- **A quién**: `backend-laravel`.

#### B-2 · Inyección del encabezado `Host` en `/sitemap.xml`
- **Dónde**: `app/Http/Controllers/SitemapController.php:47` (`route($name, ...)` usa el Host entrante); `bootstrap/app.php` sin `->trustHosts(...)`.
- **Evidencia**: `curl -s -H "Host: evil.example.com" http://127.0.0.1:8000/sitemap.xml` devuelve `<loc>http://evil.example.com/es</loc>` y las otras dos rutas con el mismo host.
- **Impacto real hoy**: bajo. No hay caché de respuesta, ni recuperación de contraseña, ni correo con enlaces generados por Host. El daño posible es un vhost mal configurado sirviendo un sitemap que anuncia URLs ajenas.
- **Remedio**: `->trustHosts(at: fn () => [parse_url(config('app.url'), PHP_URL_HOST)])`, o construir el sitemap desde `config('app.url')` (esto último hace obligatorio A-3: `APP_URL` con el dominio real).
- **A quién**: `backend-laravel`.

#### B-3 · `/es/_styleguide` responde 200 sin autenticación
- **Dónde**: `routes/web.php:35-37`. **Evidencia**: `curl -o /dev/null -w '%{http_code}'` → **200**.
- **Sí, debería estar cerrada en producción.** `noindex` es una instrucción a buscadores, no un control de acceso: la URL es adivinable y publica el inventario completo de componentes, colores y estados. No filtra datos ni credenciales, de ahí la severidad baja.
- **Remedio**: registrar la ruta solo si `! app()->isProduction()` (preferible) o `->middleware('auth')`.
- **A quién**: `backend-laravel`.

#### B-4 · Fuga de versión de plataforma y endpoint de salud público
- `X-Powered-By: PHP/8.2.1` en toda respuesta (verificado por `curl -D -`) → `expose_php=Off` en el php.ini de producción.
- `/up` responde 200 sin autenticación (verificado) → health check por defecto de Laravel 12; restringir por IP en el vhost o cambiarle la ruta.
- **A quién**: `deployer`.

#### B-5 · `PlaceholderImage::svg()` escapa la etiqueta pero no el color
- **Dónde**: `app/Support/PlaceholderImage.php:20-27`.
- `$label` **sí** va escapado (`htmlspecialchars($label, ENT_QUOTES, 'UTF-8')`, línea 22) y es el único parámetro que recibe datos del CMS — verificado en los llamadores: `home.blade.php:127` pasa `$tour->title`, `:185` `$destination->name`, `:258` `$experience->name`. `$hex` solo recibe `ltrim($hex, '#')` (línea 20) y hoy todos los llamadores le pasan literales de la paleta.
- **Riesgo**: latente. Si un lote futuro hace el color configurable desde el CMS, `2c6fa8" /><script>...` cierra el atributo e inyecta nodos en el SVG. Hoy además sería inerte: el SVG viaja en base64 dentro de un `data:` en `src` de `<img>`, contexto donde los scripts no se ejecutan.
- **Remedio**: `preg_match('/^[0-9a-f]{3,8}$/i', $hex) ? $hex : '2c6fa8'`.
- **A quién**: `backend-laravel`.

#### B-6 · Nota de instrumento: los tests de subida no ejercen la detección real de MIME
No es una vulnerabilidad del producto, es un aviso sobre la medición — y la razón por la que hice mi propio PoC.
- `tests/Feature/TourImageUploadSecurityTest.php:113` usa `UploadedFile::fake()->create('evil.pht', 1, 'image/gif')`: ese MIME es el **declarado**, no el detectado.
- `vendor/livewire/livewire/src/Features/SupportFileUploads/TemporaryUploadedFile.php:56-64`: `getMimeType()` tiene una rama `if (app()->runningUnitTests() && filename contains '-mimeType=')` que devuelve el MIME **tomado del nombre del archivo**. Bajo la suite, el MIME puede no venir de finfo.
- Consecuencia: el caso peligroso —contenido poliglota GIF/PHP **declarando** `image/png`— no está cubierto por la suite. Lo cubrí fuera de ella (ver S3) y **el control lo rechaza correctamente**.
- **Remedio**: agregar a la suite un caso con contenido real de poliglota y MIME declarado `image/png`, para que el test pueda fallar si alguien afloja la lista blanca.
- **A quién**: `anyerson-qa` (cobertura) o `backend-laravel`.

---

## Controles que declaro correctos, y cómo lo comprobé

### S1 · Formulario público
| Control | Cómo lo comprobé | Resultado |
|---|---|---|
| CSRF activo | `POST /es/contacto` sin `_token`, con cookie de sesión válida | **419** |
| Validación del lado del servidor | `POST` con `subject=NOEXISTE` (fuera de `Rule::in(array_keys(__('site.contacto.form.subject_options')))`) y otro sin `privacy` | ambos 302 con errores; **0 filas** en `contact_messages` (`SELECT COUNT(*)` = 0) |
| Límite de tasa que de verdad limita | 7 `POST` consecutivos por HTTP | intentos 1-2 → 302, **intentos 3-7 → 429**. Cuenta también los rechazados por validación (los 3 previos): no se puede agotar la validación gratis |
| Asignación masiva | PoC con `status=atendido`, `channel=HACKEADO`, `ip_address=6.6.6.6`, `privacy_consent_at=1999-01-01`, `id=999999` | fila guardada: `status=nuevo`, `channel=web`, `ip_address=203.0.113.9` (el `REMOTE_ADDR`), `privacy_consent_at=2026-09-03`. **Los cuatro campos de servidor ganaron.** Mecanismo: `$request->safe()->only([...])` solo devuelve claves validadas y las de servidor se escriben *después* del spread (`ContactMessageController.php:28-36`) |
| XSS almacenado en el correo | rendericé `NewContactMessageReceived` con `<script>`, `<img onerror>` y `" onmouseover="` en `name` y `message` | `&lt;script&gt;` / `&lt;img src=x onerror=alert(1)&gt;`; sin `<script>` crudo, sin `href="javascript:`. Escapado correcto (ver B-1 por el Markdown) |
| XSS almacenado en el panel | lectura de código: `ContactMessagesTable.php` y `ContactMessageForm.php` usan `TextColumn`/`TextInput`/`Textarea` sin `->html()`; `grep -rn '\->html()\|HtmlString\|{!!' app/Filament resources/views/filament` → **sin resultados**. Filament escapa por defecto y los `formatStateUsing` devuelven `string`, no `HtmlString` | correcto — **por lectura de código; no rendericé el panel autenticado** |
| Inyección de encabezados de correo | `name` = `"Bob\r\nBcc: victima@evil.com\r\nSubject: secuestrado"`, construí el mensaje Symfony y volqué el crudo | **no aparece `Bcc:`**. El CRLF sale codificado RFC 2047: `Subject: Nuevo mensaje de contacto: =?utf-8?Q?Bob?=` + `=?utf-8?Q?Subject=3A?= secuestrado`. Symfony Mime codifica el cuerpo de la cabecera; falla cerrada |
| Honeypot | lectura de `ContactMessageController.php:22-26`: devuelve éxito falso sin persistir ni enviar, y está **dentro** del throttle | correcto |

### S3 · Subida de archivos — PoC independiente
Reglas que Filament arma realmente para `SecureImageUpload`: `['file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:4096']` aplicadas por archivo (`vendor/filament/forms/src/Components/BaseFileUpload.php:258-266` registra la regla `mimetypes:` desde `acceptedFileTypes()`; `:752-770` las ejecuta).

Ejecuté los payloads con `TemporaryUploadedFile` **real** (fuera de la suite, por eso `getMimeType()` usa finfo de verdad):

| Fixture | MIME detectado (finfo) | Resultado | Esperado |
|---|---|---|---|
| PNG real | `image/png` | **PASA** | control positivo ✓ |
| SVG con `<script>`, `.svg` | `image/svg+xml` | FALLA | ✓ |
| SVG con `<script>` renombrado `.png` | `image/svg+xml` | FALLA | ✓ |
| Poliglota GIF/PHP `.pht` | `image/gif` | FALLA | ✓ |
| Poliglota GIF/HTML `.html` | `image/gif` | FALLA | ✓ |
| PNG real + `<?php` anexado, `.php` | `image/png` | FALLA | ✓ |
| GIF real `.gif` (fuera de la lista) | `image/gif` | FALLA | ✓ |

Motivo en los seis negativos: *"El campo photo path debe ser un archivo de tipo: image/jpeg, image/png, image/webp."* El control positivo pasa y los negativos fallan: **la aserción puede fallar**, no es un check vacío.

- **La lista blanca está cerrada** (`SecureImageUpload.php:32`, tres MIME) y se evalúa sobre el **contenido**, no sobre lo que dice el cliente: `TemporaryUploadedFile::getMimeType()` → `detectMimeTypeFromContents()` → `FinfoMimeTypeDetector` (`vendor/livewire/.../TemporaryUploadedFile.php:56-90`). El nombre del cliente no entra en la decisión en ningún punto.
- **La extensión guardada se deriva del MIME detectado**, con `default => 'bin'`: falla cerrada, y esa rama es inalcanzable mientras la lista blanca aplique (`SecureImageUpload.php:41-48`).
- **No hay escape de directorio por el nombre**: el nombre almacenado es `Str::ulid().'.'.<ext>`; el del cliente se descarta por completo. Aunque llegase un path con `..`, Flysystem lo rechaza (`PathTraversalDetected`, verificado).
- **Nada se sirve desde un lugar que ejecute**: el destino es el disco `public` = `storage/app/public`, expuesto por el enlace simbólico `/storage` (`config/filesystems.php:44-58`); el temporal de Livewire es `storage/app/private/livewire-tmp`, fuera del docroot. Ningún `.php`/`.pht` llega a `public/`.
- El hueco real de este bloque es M-1, y está en la rama *string*, no en la rama *archivo*.

### S4 · Autorización
Ver M-5: conté las cuatro puertas. `is_admin` fuera de `$fillable` **y** en `$guarded`; no existe `UserResource` ni ruta de registro; el login del panel tiene límite de tasa propio (`vendor/filament/filament/src/Auth/Pages/Login.php:76` → `$this->rateLimit(5)`); `/admin` sin sesión → **302 a `/admin/login`** (verificado por curl). El panel corre con `AuthenticateSession` y `VerifyCsrfToken` (`AdminPanelProvider.php:44-57`), así que la sesión se invalida al cambiar la contraseña. Nada falla abierto.

### S5 · Middleware de locale
Lista blanca **cerrada**: `Locale::fromSegment()` (`app/Support/Locale.php:31-42`) compara por igualdad exacta contra `array_keys(config('cms.locales'))` y devuelve `null` si no hay coincidencia; el middleware aborta 404 con `null` o con locale inactivo (`SetLocaleFromUrl.php:37-39`). `App::setLocale()` recibe siempre una clave del esquema, **nunca el segmento crudo**.

Verificado por HTTP: `/es/` → 200; `/en/` y `/pt-br/` → **404** (existen en el esquema, no están activos: coincide con la decisión documentada de no redirigir); `/xx/` → 404; `/..%2F..%2Fetc/` → 404; `/` → **301** a `/es`. La redirección de `/` tiene destino fijo (`routes/web.php:13`) y no admite parámetro: **no hay redirección abierta**. `URL::defaults()` recibe `Locale::toSegment($locale)`, valor derivado de la config y no de la URL.

Un detalle que sale de mi alcance y derivo: `/ES/nosotros` responde **200** y la página emite a la vez `href=".../ES/nosotros"` y `href=".../es/nosotros"` — es URL duplicada / canónica, no seguridad. **Va para `anyerson-seo`**.

### S6 · URLs que vienen del CMS
- **La validación sí lo impide.** PoC con el validador real del proyecto sobre la regla `url`, que es la que `TextInput->url()` registra del lado del servidor (`vendor/filament/forms/src/Components/TextInput.php:224-226` → `$this->rule('url', $condition)`):

| Valor | `rule:url` | Esperado |
|---|---|---|
| `https://instagram.com/pachaviva` | **PASA** | control positivo ✓ |
| `javascript:alert(document.cookie)` | FALLA | ✓ |
| `JaVaScRiPt:alert(1)` | FALLA | ✓ |
| `data:text/html;base64,PHNjcmlwdD4...` | FALLA | ✓ |
| `javascript://x%0Aalert(1)` | FALLA | ✓ |
| `no-es-una-url` | FALLA | ✓ |

  (`filter_var('javascript:alert(1)', FILTER_VALIDATE_URL)` devuelve `false` en PHP 8.2.1: el bypass clásico no aplica en esta versión.) Las siete claves de URL de `Configuracion.php` (`complaints_book_url:155`, `esnna_poster_url:157`, `privacy_policy_url:162`, `cancellation_policy_url:167`, `social_*_url:176-178`) llevan `->url()`, y `Configuracion::save()` parte de `$this->form->getState()`, que valida antes de devolver estado.
- **El render también está limpio**: `grep -rn '{!!.*Setting::\|{!!.*setting(' resources/views` → **sin resultados**. Ningún valor de `settings` sale por `{!! !!}`. Los doce `{!! !!}` de las vistas son cadenas SVG **literales**, definidas en el mismo archivo (`contact.blade.php:38-52`) o pasadas como prop desde arrays cableados.
- **Residual asumido**: `Setting::set()` no valida por sí mismo, así que una escritura por tinker/seeder podría meter un `javascript:`. Hoy no existe ese camino y el único escritor es la página validada. Endurecimiento opcional: validar el esquema dentro de `Setting::set()`, o escapar en el render con un helper `safe_url()`.

### S8 · Dependencias
- `composer audit` (con `/g/laragon/bin/php/php8.2.1/php.exe` + `/g/laragon/bin/composer/composer.phar`): **"No security vulnerability advisories found"**, salida 0.
- `npm audit` y `npm audit --omit=dev`: **found 0 vulnerabilities**.
- Nada que reportar y nada que aplique a este código.

---

## Lo que debe cambiar antes del despliegue (checklist)

Bloqueantes (A-1, A-2, A-3):
- [ ] **Publicar la política de privacidad** y cargar `privacy_policy_url` en Configuración. Si no está lista, el formulario de contacto **no sale a producción activo**. (A-1)
- [ ] Agregar bajo la casilla el aviso del art. 18: titular del banco de datos, finalidad, plazo de conservación y cómo ejercer derechos. (A-1)
- [ ] Guardar la versión/URL de la política junto a `privacy_consent_at`. (A-1)
- [ ] `APP_ENV=production`, `APP_DEBUG=false`. (A-3)
- [ ] `APP_URL` = dominio real con esquema `https` y la variante www/no-www que se vaya a canonizar. Sin esto el sitemap sale inservible. (A-3)
- [ ] `APP_KEY` **generado en el servidor** (`php artisan key:generate`), distinto del local. (A-3)
- [ ] **Borrar el usuario `dev@pachaviva.test`** en la base de producción y crear el admin real con clave generada. No cambiarle la clave: borrarlo. Verificar después con `SELECT id,email,is_admin FROM users`. (A-3)
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`, `SESSION_ENCRYPT=true`. (A-2)
- [ ] Middleware de cabeceras de seguridad + `URL::forceScheme('https')` en producción + HSTS en el vhost. CSP en `report-only` primero. (A-2)
- [ ] Credenciales reales de correo (`MAIL_MAILER` ≠ `log`) y `CONTACT_NOTIFY_EMAIL` con la bandeja de la agencia. Hoy `notify_email` cae en `MAIL_FROM_ADDRESS` = `hello@example.com`: **con `MAIL_MAILER=smtp` y esa dirección, cada mensaje del formulario se envía a un dominio ajeno.**
- [ ] `DB_USERNAME`/`DB_PASSWORD` de producción: usuario dedicado, no `root` sin clave.
- [ ] Confirmar que existe un `queue:work` supervisado, o dejar el mailable sincrónico (hoy lo es, a propósito y bien documentado).

No bloqueantes, recomendados antes de abrir al público:
- [ ] `->preventFilePathTampering()` en `SecureImageUpload` y en `TourForm`. (M-1)
- [ ] Declarar los rangos del proxy con `trustProxies()`, o confirmar que el servidor atiende directo. Nunca `'*'`. (M-2)
- [ ] `Prunable` en `ContactMessage` + `model:prune` en el cron, con el plazo escrito en la política. (M-3)
- [ ] Registro de login, cambios de Configuración y borrado de mensajes; `LOG_LEVEL=warning`, `LOG_STACK=daily`. (M-4)
- [ ] Quitar `/es/_styleguide` de producción. (B-3)
- [ ] Dejar de pasar el mensaje del visitante por Markdown. (B-1)
- [ ] `expose_php=Off` y `/up` restringido. (B-4)
- [ ] `trustHosts()` o sitemap desde `config('app.url')`. (B-2)
- [ ] Validar `$hex` en `PlaceholderImage`. (B-5)

Post-despliegue (para `deployer`):
- [ ] Confirmar por `curl -I` que las cabeceras están y que `Set-Cookie` trae `secure` y `httponly`.
- [ ] Confirmar que `/sitemap.xml` devuelve el dominio real.
- [ ] Confirmar que un `POST` al formulario llega a la bandeja correcta y que el 6.º intento da 429.
- [ ] Confirmar que `dev@pachaviva.test` no existe.

---

## Qué no pude auditar

1. **El panel autenticado renderizado.** Verifiqué el escapado del inbox de mensajes **por lectura de código** (sin `->html()`, sin `HtmlString`, sin `{!! !!}` en `app/Filament` ni `resources/views/filament`), no navegando `/admin` con sesión: el login de Filament es Livewire y no lo atravesé por curl dentro del presupuesto. Recomiendo que `anyerson-qa` pegue un `<script>` por el formulario público y mire la lista y la ficha en el navegador.
2. **M-1 explotado de punta a punta.** Demostré que el guard está apagado (`shouldPreventFilePathTampering() === false`) y que el cierre de validación descarta los strings (código de Filament citado). No emití la llamada Livewire manipulada contra el panel: exige sesión autenticada y, con el árbol congelado y QA en paralelo, no era el momento.
3. **La topología de producción.** No sé si habrá proxy/CDN delante, así que M-2 queda condicional y el hosting sin verificar. Tampoco existe todavía un `.env` de producción que revisar: audité `.env` local y `.env.example`.
4. **La suite de tests.** No la corrí: Anyerson ya la verificó verde (114 tests / 413 aserciones) y dos corridas concurrentes contra `pachaviva_test` se pisan. Mis PoC corrieron **fuera** de la suite, contra MySQL `pachaviva` real — que además es lo correcto acá, porque bajo `runningUnitTests()` Livewire cambia la resolución del MIME (ver B-6).
5. **Cabeceras y TLS reales.** Medidos contra `127.0.0.1:8000` (servidor de desarrollo). El vhost de producción puede añadir cabeceras que aquí no veo: hay que volver a medir después del despliegue.
6. **Rastro histórico de secretos en git.** Confirmé que `.env` no está versionado *hoy*; no recorrí todo el historial buscando un `.env` en un commit viejo. El repo es nuevo, así que el riesgo es bajo, pero no es una verificación que haya hecho.

## Fuera de mi alcance, derivado
- `/ES/nosotros` responde 200 y genera enlaces con mayúsculas → URL duplicada. **`anyerson-seo`**.
- La decisión de negocio de abrir o no el formulario sin política de privacidad → **Anyerson / clienta**.

---

## Rastro de las pruebas
Los PoC vivieron en el scratchpad de la sesión, no en el repo. Fixtures de subida creados en `storage/app/private/livewire-tmp/AUDIT*` y **borrados** al final del script (verificado: 0 archivos `AUDIT` restantes). La fila de prueba en `contact_messages` fue eliminada (`SELECT COUNT(*)` final = 1, la preexistente). `git status --porcelain` debe seguir mostrando solo este documento.

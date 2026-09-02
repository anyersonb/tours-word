# Auditoría de seguridad — Lote 2

- **Repo/commit**: `tours-word`, rama `main`, `c759ec8` (árbol limpio: `git status` vacío).
- **Stack**: Laravel 12.69.1 · PHP 8.2.1 · Filament 4.12.8 · Livewire 3.8.7 · spatie/laravel-translatable 6.11.4.
- **Fecha**: 2026-09-01. Auditor: `security-engineer`.
- **Sin despliegue**: no hay producción, staging ni dominio. Todo lo relativo a producción es prospectivo y está marcado como tal.
- **No se aplicó ningún fix.** No se editó ningún archivo de la aplicación. Las pruebas se ejecutaron desde archivos fuera del repo (scratchpad de sesión) contra `pachaviva_test` (MySQL 8, no SQLite).

---

## Veredicto

| | Veredicto |
|---|---|
| **Seguir al lote 3** | **APTO CON OBSERVACIONES** — ningún hallazgo crítico ni alto de seguridad. Los dos hallazgos medios son minas latentes que hay que desactivar *antes* de que el lote 3 abra superficie pública. |
| **Producción** | **NO APTO** — ver la lista de bloqueantes al final. Ninguno es una vulnerabilidad explotable hoy; son configuración de despliegue que aún no existe. |

Además, y fuera de mi rol: **la página Configuración está rota (500 para un admin)**. Es un defecto funcional, lo derivo a `backend-laravel` y `anyerson-qa`, pero lo documento aquí porque **me impidió verificar** la validación del tipo de cambio en el formulario.

---

## Hallazgos

### Críticos (bloqueantes)

Ninguno.

### Altos

Ninguno de seguridad.

### Medios

#### M-1 · La subida de imágenes acepta SVG y polígotas con extensión peligrosa — CONFIRMADO

- **Ubicación**: `app/Filament/Resources/Tours/Schemas/TourForm.php:147-152`
  ```php
  FileUpload::make('path')->label('Imagen')->image()->disk('public')->directory('tours')->required(),
  ```
- **Cadena**:
  - `->image()` (`vendor/filament/forms/src/Components/FileUpload.php:130-137`) equivale a `acceptedFileTypes(['image/*'])`.
  - `acceptedFileTypes()` (`vendor/filament/forms/src/Components/BaseFileUpload.php:258-268`) genera **una sola** regla: `mimetypes:image/*`.
  - El nombre en disco es `Str::ulid().'.'.$file->getClientOriginalExtension()` (`BaseFileUpload.php:137`): el ULID impide colisiones y *path traversal*, pero **la extensión la elige quien sube**.
  - Aterriza en `storage/app/public/tours/` y se sirve **desde el mismo origen** en `/storage/tours/…` (symlink `public/storage` verificado presente).
- **Cómo se reproduce** (probe de solo lectura, `Validator::make(['f'=>$file], ['f'=>'mimetypes:image/*'])` con ficheros reales):

  | caso | MIME real | veredicto |
  |---|---|---|
  | PNG legítimo `ok.png` | `image/png` | ACEPTADO |
  | **CONTROL** texto plano `nota.txt` | `text/plain` | rechazado |
  | SVG con `<script>` y `onload` `x.svg` | `image/svg+xml` | **ACEPTADO** |
  | HTML puro `x.html` | `text/html` | rechazado |
  | polígota `GIF89a;<?php …` como `evil.php` | `image/gif` | rechazado |
  | polígota `GIF89a;<?php …` como `evil.phtml` | `image/gif` | rechazado |
  | polígota `GIF89a;<?php …` como **`evil.pht`** | `image/gif` | **ACEPTADO** |
  | polígota `GIF89a;<script>…` como **`evil.html`** | `image/gif` | **ACEPTADO** |

  El control (texto plano rechazado) prueba que la comprobación **puede fallar**: no es un "OK" vacío.
- **Por qué `.php` sí se bloquea**: `vendor/laravel/framework/src/Illuminate/Validation/Concerns/ValidatesAttributes.php:1758-1771` (`shouldBlockPhpUpload`) veta la lista `php, php3, php4, php5, php7, php8, phtml, phar`. **`.pht` no está en esa lista** y `.html`/`.svg` tampoco.
- **Vector**: una cuenta de admin (o una sesión de admin secuestrada) sube `foto.svg` o `foto.html`; el archivo queda en `/storage/tours/<ulid>.svg` y ejecuta JavaScript **en el origen del sitio** cuando se navega directamente a esa URL — robo de sesión del CMS. Con `.pht`, si el servidor de producción mapea esa extensión al handler de PHP (config de Apache/LiteSpeed que hoy **no puedo verificar porque no hay servidor**), es ejecución remota de código.
- **Impacto**: hoy, limitado — solo un `is_admin` puede subir. Sube a Alto en el momento en que el lote 4/5 abra cualquier subida no autenticada (fotos en reseñas, adjuntos de contacto).
- **Remedio concreto** (en `TourForm.php`, sustituyendo `->image()`):
  ```php
  ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
  ->maxSize(4096)                       // 4 MB; hoy hereda los 12 MB de Livewire
  ->getUploadedFileNameForStorageUsing(fn ($file) => Str::ulid().'.'.match ($file->getMimeType()) {
      'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
  })                                     // la extensión sale del MIME, no del cliente
  ```
  Y en el `Repeater::make('images')` (`TourForm.php:143`): `->maxItems(20)`.
  Defensa en profundidad para producción: `Content-Disposition: attachment` o `X-Content-Type-Options: nosniff` + un `.htaccess` sin handlers en `storage/app/public`.
- **Dato relacionado (confirmado)**: el endpoint temporal de Livewire corre con los defaults —
  `FileUploadConfiguration::rules() = ['required','file','max:12288']` (12 MB, **sin restricción de tipo**) y `middleware() = 'throttle:60,1'`. No hay `config/livewire.php` publicado.

#### M-2 · `is_admin` es asignable en masa — CONFIRMADO (no explotable hoy)

- **Ubicación**: `app/Models/User.php:23-28`, `is_admin` en la línea 27.
- **Cómo se reproduce**: `(new User)->fill(['is_admin' => true])->is_admin` devuelve `true`.
- **Estado actual**: **no explotable**. No hay ruta de registro (`routes/web.php` solo tiene `/`), el panel no habilita `->registration()` ni `->passwordReset()` (`app/Providers/Filament/AdminPanelProvider.php:26-57`), y no existe un `UserResource`. Ningún formulario alimenta hoy `User::create()` / `fill()`.
  Confirmado además por el coordinador: **no existe ni un solo `$request->all()`, `request()->all()` ni `->fill($request…)` en todo `app/`**.
- **Por qué se reporta igual**: `is_admin` es *el único* gate del panel (`User::canAccessPanel()`, `app/Models/User.php:36-39`). El primer `User::create($request->validated())` que aparezca en el lote 3 (registro, alta de usuarios, invitación) convierte esto en escalada de privilegio directa. Es exactamente el patrón "abrir un campo destapa el código que llevaba meses sin correr".
- **Remedio**: sacar `'is_admin'` de `$fillable` y añadir `protected $guarded = ['is_admin'];`. Asignarlo solo explícitamente (`$user->is_admin = true; $user->save();`) desde un punto único y auditado.

### Bajos / hardening

#### B-0 · `Tour::slugTaken()` construye la ruta JSON por interpolación — DESCARTADO como inyección, queda como hardening

Lo investigué como posible SQLi y **no lo es**. Lo dejo escrito con la evidencia porque es
justo el sitio donde el lote 3 va a meter datos del request.

- **Ubicación**: `app/Models/Tour.php:148-154`, línea **151**: `->where("slug->{$locale}", $slug)`.
- **Cómo lo probé**: log de consultas activado, tres payloads en `$locale`, mirando el SQL emitido:

  | payload en `$locale` | SQL generado | resultado |
  |---|---|---|
  | `es` (control) | `json_extract(\`slug\`, '$."es"')` | ejecuta, `false` |
  | `es"))=1 OR ((1` | `json_extract(\`slug\`, '$."es"))=1 OR ((1"')` | ejecuta, `false` |
  | `es'` | `json_extract(\`slug\`, '$."es''"')` | ejecuta, `false` |
  | `es') = 1 OR (json_extract(...` | `json_extract(\`slug\`, '$."es'') = 1 OR (json_extract(\`slug\`, ''$."es""')` | ejecuta, `false` |

- **Veredicto**: la comilla simple **se duplica** (`es'` → `$."es''"`), que es el escape SQL
  correcto. El payload nunca sale del literal de cadena: las cuatro consultas ejecutan sin
  error y devuelven `false`, ninguna altera la lógica del `where`. **No hay inyección.**
  El valor del slug además va enlazado (`= ?`).
- **Estado del llamador**: hoy `$locale` sale siempre de `config('cms.active_locales')` —
  `app/Filament/Resources/Tours/Schemas/TourForm.php:75-79` (la clausura captura el locale con
  el que `TranslatableTabs` construyó la pestaña) y `app/Filament/Support/TranslatableTabs.php:26-32`.
- **Hardening recomendado, no bloqueante**: cuando el lote 3 resuelva slugs desde la URL, el
  locale vendrá del request. Añadir entonces la lista blanca:
  ```php
  abort_unless(array_key_exists($locale, config('cms.locales')), 400);
  ```

#### B-1 · Precios negativos y desbordados: la base los frena, pero con un 500 — CONFIRMADO

- **Ubicación**: `app/Filament/Resources/Tours/Schemas/TourForm.php:114-129` (sin `minValue`/`maxValue`), `app/Support/Money.php:53-62` (`parseToCents` no acota signo ni magnitud), `database/migrations/2026_09_01_221900_create_tours_table.php:49-50` (`unsignedInteger`).
- **Cómo se reproduce** (contra MySQL real, `strict => true`, no SQLite):
  - `Money::parseToCents('-150.00')` → `-15000`; `Money::parseToCents('1e9')` → `100000000000`; `Money::parseToCents('abc')` → `0`.
  - `Tour::factory()->create(['price_pen_cents' => -15000])` → `SQLSTATE[22003] 1264 Out of range value`. Idem con `4294967296` y `99999999999`.
  - Y a través del **formulario real de Filament** (`Livewire::test(CreateTour::class)`), que es la prueba que importa:

    | precio PEN tecleado | errores de validación | resultado |
    |---|---|---|
    | `0` (**control positivo**) | ninguno | guardado, 0 céntimos |
    | `25.5` (**control positivo**) | ninguno | guardado, 2550 céntimos |
    | `-150` | **ninguno** | `QueryException` SQLSTATE[22003] → 500 |
    | `99999999` | **ninguno** | `QueryException` SQLSTATE[22003] → 500 |

    Es decir: el formulario **no rechaza** el signo ni el techo; quien frena es la columna `unsignedInteger`.
- **Lectura**: **no se puede almacenar un precio negativo ni desbordado** — falla cerrado, que es lo correcto. Pero falla con una excepción no capturada: en producción es un 500 genérico en vez de un mensaje de validación.
- **Remedio**: `->minValue(0)->maxValue(42949672)` en ambos campos de precio (`unsignedInteger` tope 4 294 967 295 céntimos ≈ S/ 42 949 672,95), y acotar en `Money::parseToCents()` (`max(0, …)` o lanzar `InvalidArgumentException`).

#### B-2 · `Money::exchangeRate()` devuelve tasas inválidas a quien la pida directo — CONFIRMADO

- **Ubicación**: `app/Support/Money.php:114-119`.
- **Cómo se reproduce**: guardando el setting con `'0'`, `'-3.75'`, `'abc'` y `''`, `Money::exchangeRate()` devuelve `0.0`, `-3.75`, `0.0`, `0.0` respectivamente.
- **Lo que SÍ está bien**: `Money::convertTo()` (`app/Support/Money.php:134`) valida `$rate === null || $rate <= 0` y lanza `RuntimeException`. Los cuatro casos anteriores lanzan la excepción. **No hay división por cero ni tasa negativa aplicada.** Verificado uno por uno.
- **Remedio**: mover el guard dentro de `exchangeRate()` (devolver `null` si `<= 0`), para que el `exchange_rate_used` que el lote 4 va a congelar en `bookings` no pueda copiar un `0.0` o un negativo.

#### B-3 · `.env.example` es la plantilla de despliegue y trae `APP_DEBUG=true` / `APP_ENV=local`

- **Ubicación**: `.env.example:2-4`.
- Es el default de Laravel y hoy es correcto para local. Se anota porque **de ahí se copiará el `.env` de producción**. Faltan además, para HTTPS: `SESSION_SECURE_COOKIE=true` (hoy `config/session.php` lee `env('SESSION_SECURE_COOKIE')` sin default → `null` → cookie sin `Secure`).
- El resto de la sesión está bien: `http_only` default `true`, `same_site` default `lax`, driver `database`.

#### B-4 · `RichEditor` guarda HTML del admin en `tours.description`

- **Ubicación**: `app/Filament/Resources/Tours/Schemas/TourForm.php:86-87`.
- No hay defecto hoy: **no existe ninguna vista que lo imprima** y no hay un solo `{!! !!}` en todo `resources/views/`.
- Se deja anotado como regla para el lote 3: cuando la ficha pública pinte ese campo hará falta `{!! !!}`, y ahí debe pasar por un sanitizador (`mews/purifier` o el `RichContentRenderer` de Filament), no crudo.

---

## Lo que revisé y salió limpio

### 1 · Autorización del panel — TODAS las puertas contadas

Cuatro superficies, las cuatro cerradas. Evidencia con **el trío completo** (invitado / autenticado no-admin / admin como control positivo):

| URL | invitado | `is_admin=false` | `is_admin=true` |
|---|---|---|---|
| `/admin` | 302 → `/admin/login` | **403** | 200 |
| `/admin/tours` | 302 | **403** | 200 |
| `/admin/tours/create` | 302 | **403** | 200 |
| `/admin/tours/{id}/edit` | 302 | **403** | 200 |
| `/admin/destinations` | 302 | **403** | 200 |
| `/admin/destinations/create` | 302 | **403** | 200 |
| `/admin/destinations/{id}/edit` | 302 | **403** | 200 |
| `/admin/experiences` | 302 | **403** | 200 |
| `/admin/experiences/create` | 302 | **403** | 200 |
| `/admin/configuracion` | 302 | **403** | 500 (defecto funcional, ver abajo) |

Que el admin obtenga 200 prueba que el 403 no es incondicional: la comprobación puede fallar.

- **Puerta 2 — endpoint de Livewire.** Prueba real de *replay*: capturé un `wire:snapshot` **válido** renderizado por el admin (el checksum de Livewire va firmado con `APP_KEY` y **no está atado al usuario**) y lo reenvié a `POST /livewire/update` con otras sesiones:
  - invitado → **401**
  - autenticado `is_admin=false` → **403** (`Symfony\…\HttpException`)
  - admin → pasa el gate (llega al componente)

  El mecanismo: `vendor/filament/filament/src/FilamentServiceProvider.php:106-117` registra `Filament\Http\Middleware\Authenticate` como *persistent middleware* de Livewire, y ese middleware (`vendor/filament/filament/src/Http/Middleware/Authenticate.php:35-40`) hace `abort_if(! $user->canAccessPanel($panel), 403)`. Es decir, `canAccessPanel()` se re-evalúa en cada request de Livewire, no solo en la carga inicial.
- **Puerta 3 — acciones masivas.** `DeleteBulkAction` (`app/Filament/Resources/Tours/Tables/ToursTable.php:61-65`) y las de Destinos/Experiencias viven dentro de componentes Livewire del panel, cubiertos por la puerta 2. No hay `BulkAction` con lógica propia ni ruta expuesta.
- **Puerta 4 — `mount()` sin comprobar.** `App\Filament\Pages\Configuracion::mount()` (`app/Filament/Pages/Configuracion.php:40-45`) no valida nada por su cuenta, pero es una `Filament\Pages\Page` descubierta por el panel: la ruta lleva el `authMiddleware` y el `mount()` por Livewire lleva el persistente. Confirmado con el 403 del no-admin sobre `/admin/configuracion`.
- **Login**: `->login()` sin `->registration()` ni `->passwordReset()` (`AdminPanelProvider.php:30`). `Login::authenticate()` trae `rateLimit(5)` por minuto y `Timebox` anti-enumeración (`vendor/filament/filament/src/Auth/Pages/Login.php:76,86-110`), y vuelve a comprobar `canAccessPanel()` antes de emitir el reto MFA (`Login.php:184`). `AuthenticateSession` y `VerifyCsrfToken` están en la pila (`AdminPanelProvider.php:48,50`).

### 2 · Credenciales y secretos

- `.env` está en `.gitignore:9` (`git check-ignore -v .env` lo confirma) y **nunca estuvo rastreado**: el único fichero con "env" en el historial completo (`git log --all --name-only`) es `.env.example`.
- `.env.example:3` trae `APP_KEY=` **vacío**. Ninguna `APP_KEY` real en el historial.
- Ningún seeder crea usuarios: `database/seeders/DatabaseSeeder.php:18-24` solo llama a `SettingSeeder` y `DemoTourSeeder`, y ninguno de los dos toca `users`. **El aviso de `docs/lote-2/README.md` es correcto.** La contraseña del usuario `dev@pachaviva.test` no está en el repo (el README solo trae el placeholder `TU_PASSWORD_AQUI`).
- `database/factories/UserFactory.php:31` usa `Hash::make('password')` — es una factory de test, solo corre contra `pachaviva_test`, y no la invoca ningún seeder. Correcto.

### 3 · Inyección

- Ninguna `DB::raw`, `whereRaw`, `havingRaw`, `orderByRaw` ni `selectRaw` en `app/`.
- La búsqueda de la tabla de tours (`ToursTable.php:25`) usa `$query->where('title->es', 'like', "%{$search}%")`: el término va como **valor enlazado**, la ruta JSON es una constante literal. Correcto.
- El único punto con una ruta JSON dinámica es `Tour::slugTaken()`: investigado a fondo y DESCARTADO como inyección (ver B-0, con los cuatro payloads y el SQL emitido).

### 4 · XSS

- Cero ocurrencias de `{!! !!}` en todo `resources/views/` (solo hay dos vistas: `welcome.blade.php` y `filament/pages/configuracion.blade.php`).
- No hay JSON-LD ni campos de schema editables (el vector recurrente de otros proyectos). El único HTML editable es el `RichEditor` de B-4, que aún no se imprime en ningún lado.

### 5 · Dinero

- Céntimos enteros de punta a punta; ninguna vista escribe `S/` ni `$` (los símbolos salen de `config/cms.php:40-43` vía `Money::format()`).
- Tasa de cambio: `Money::convertTo()` **falla cerrado** con 0, negativo, texto y vacío. Verificado con los cuatro valores.
- Precios negativos/desbordados: bloqueados por el esquema. Ver B-1.

### 6 · Configuración y rutas de diagnóstico

- `routes/web.php` tiene **una sola ruta**: `GET /` → `view('welcome')`. **No hay ninguna ruta de diagnóstico** tipo `/_diag/*`, ni `telescope`, ni `horizon`, ni un endpoint de prueba de correo. Limpio.
- `routes/console.php` solo trae el comando `inspire` de fábrica.
- CSRF: `VerifyCsrfToken` en la pila del panel, sin excepciones en ninguna parte (no existe un `VerifyCsrfToken` propio en `app/`).
- `config/session.php` sin modificar respecto del default de Laravel 12: `http_only=true`, `same_site='lax'`, driver `database`.
- `phpunit.xml:31-37` fuerza `pachaviva_test` en MySQL, con el comentario explicando por qué no es SQLite. Correcto y coincide con la trampa de medición conocida (columnas DATE con cast).

### 7 · Validación de entrada del catálogo

- El slug de tour lleva `->rule('alpha_dash')` (`app/Filament/Resources/Tours/Schemas/TourForm.php:73`). Probado contra el formulario real de Filament:

  | slug | veredicto |
  |---|---|
  | `ok-slug` (**control positivo**) | ACEPTADO |
  | `../../etc/passwd` | RECHAZADO (`validation.alpha_dash`) |
  | `<script>x</script>` | RECHAZADO |
  | `a b` | RECHAZADO |

  Cierra *path traversal* y XSS por slug antes de que exista la ruta pública que lo consuma.
- La unicidad de slug por locale se valida en aplicación (`Tour::slugTaken()`), correcto: una columna JSON no admite índice `UNIQUE` por locale.

### 8 · Dependencias

- `composer audit` → **"No security vulnerability advisories found."**
- **Control de que la comprobación puede fallar**: consulté la API de advisories de Packagist para `laravel/framework` y devuelve avisos reales (p. ej. `GHSA-crmm-hgp2-wgrp`, "Temporary Signed URL Path Confusion", afecta `<12.61.1`). La versión instalada es **12.69.1**, por encima de la línea de corrección. El "sin hallazgos" es un resultado, no un silencio.
- Versiones auditadas: `laravel/framework 12.69.1`, `filament/* 4.12.8`, `livewire/livewire 3.8.7`, `spatie/laravel-translatable 6.11.4`, `symfony/http-foundation 7.4.18`, `league/flysystem 3.35.3`, `guzzlehttp/guzzle 7.15.5`.

---

## No verificado (y por qué)

1. **`npm audit`** — no hay `package-lock.json` ni `node_modules/` en el árbol; el comando aborta con `ENOLOCK`. Atenuante: las seis dependencias de `package.json` son **todas `devDependencies`** (vite, tailwind, axios, concurrently, laravel-vite-plugin) y ninguna se envía al navegador en runtime de Laravel. **Pendiente**: correr `npm install && npm audit` cuando exista el lock, antes del lote 3.
2. **Validación `minValue(0.01)` del tipo de cambio en el formulario** — bloqueada por el 500 de la página Configuración (ver abajo). Por lectura de código *debería* funcionar: `TextInput::minValue()` (`vendor/filament/forms/src/Components/TextInput.php:126-136`) añade la regla de servidor `min:0.01`, y `->numeric()` (`TextInput.php:139-146`) añade `numeric`. Pero **no lo pude ejecutar**, así que no lo declaro confirmado. Lo que sí está confirmado es que aunque un valor malo llegase a la BD, `Money::convertTo()` lo rechaza (B-2).
3. **Ejecución de `.pht` como PHP** — depende de la configuración del handler del servidor web, y **no hay servidor**. Se debe verificar en el primer despliegue subiendo un `.pht` inocuo y comprobando que se descarga en vez de ejecutarse.
4. **Cabeceras de seguridad** (CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy) — no hay middleware que las emita y no hay servidor donde medirlas. Va a la lista de producción.

---

## Defecto funcional detectado de paso (no es mi rol arreglarlo)

**La página Configuración devuelve 500 a un administrador.**

- **Ubicación**: `resources/views/filament/pages/configuracion.blade.php:5`
  ```blade
  <x-filament-panels::form.actions :actions="$this->getFormActions()" class="mt-6" />
  ```
- **Causa**: ese componente **no existe en Filament 4.12.8**. `vendor/filament/filament/resources/views/components/` no contiene ningún directorio `form/` (sí `page`, `layout`, `header`, `sidebar`, `topbar`…). El prefijo `filament-panels` se registra en `vendor/filament/filament/src/FilamentServiceProvider.php:46`.
- **Cómo se reproduce**: autenticado con `is_admin=true`, `GET /admin/configuracion` → **500**. Error exacto: `Unable to locate a class or view for component [filament-panels::form.actions]`. Los demás Resources del panel devuelven 200 con el mismo usuario, así que no es sesión ni entorno.
- **Por qué me importa a mí**: es el **único** lugar del CMS donde se edita `settings.exchange_rate_pen_usd`. Mientras esté roto, la clienta no puede corregir el tipo de cambio, y yo no pude probar su validación (punto 2 de "No verificado").
- **Derivación**: `backend-laravel` para el fix; `anyerson-qa` para el humo post-fix (pulsar Guardar, no solo cargar la pantalla). El diseño del fix no me corresponde.

---

## Lista para producción (nada de esto existe todavía)

1. `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` generada en el servidor (nunca reutilizar la local).
2. `SESSION_SECURE_COOKIE=true` (HTTPS obligatorio) y forzar `https` en la aplicación.
3. **Borrar `dev@pachaviva.test`**. Comprobación ya documentada en `docs/lote-2/README.md`:
   `SELECT id, email, is_admin FROM users WHERE is_admin = 1;` — la única fila debe ser la cuenta real de la clienta.
4. Usuario de MySQL dedicado con permisos mínimos. Hoy `.env.example:26-27` trae `root` sin contraseña (correcto para Laragon, inadmisible fuera de local).
5. Cabeceras de seguridad: CSP, HSTS, `X-Content-Type-Options: nosniff`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`.
6. Cerrar M-1 (tipos de imagen y extensión derivada del MIME) **antes** de que exista una URL pública que sirva `/storage/tours/`.
7. Cerrar M-2 (`is_admin` fuera de `$fillable`) antes de que el lote 3 introduzca cualquier alta de usuarios.
8. Verificar que el servidor no ejecuta `.pht`/`.phtml` bajo `public/storage`.
9. Rotar el tipo de cambio placeholder (`3.75`, `database/seeders/SettingSeeder.php:23`) por el valor real de la clienta — y para eso hace falta que Configuración funcione.
10. Arreglar el 500 de Configuración y B-1 (precios sin cota → 500 en vez de mensaje de validación).

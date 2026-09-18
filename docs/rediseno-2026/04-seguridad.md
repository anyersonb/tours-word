# 04 · Seguridad — Pacha Viva, despliegue en `limaviewtours.com/tour-word/`

**Agente:** `security-engineer` · **Fecha:** 2026-09-18 · **Rama:** `lote-1-sistema-diseno`
**Alcance:** superficie de ataque del despliegue como subdirectorio en el dominio de
producción de OTRO cliente (Lima View Tours), sobre SQLite y PHP 8.2, publicado por FTP.
Más el diff del lote que introduce `is_staging_mirror`.

**Estado del árbol auditado:** `2590ec5` + 17 archivos modificados sin commitear.
No apliqué ningún fix ni toqué ningún archivo fuera de este informe.

---

## Veredicto: **RECHAZADO para publicar hoy en `/tour-word/`**

No es un rechazo del código del lote — el código está en general bien construido y con
decisiones de seguridad deliberadas y documentadas (`DatabaseSeeder` que se niega a sembrar
usuarios, `EnsureDevAdminCommand` que se niega a correr fuera de `local`, `trustHosts()`,
`notify_email` que falla cerrado). **El rechazo es de la FORMA del despliegue.**

Hay **3 hallazgos Críticos** y **4 Altos**, casi todos en la capa de despliegue/configuración.
Se resumen en una frase: *la manera propuesta de publicar deja la raíz de la aplicación —
con el `.env` y la base de datos entera en un solo archivo — dentro del árbol web de un
dominio de producción ajeno, el panel de administración accesible desde Internet, y las
cookies de la demo pisando las del sitio real que comparte ese origen.*

Ninguno requiere reescribir el lote. Todos salvo uno se cierran en el procedimiento de
despliegue; el que no (M-1, §5) es un defecto de diseño de la bandera `is_staging_mirror`
que hoy queda tapado por otra bandera, y que hay que corregir en código antes de que esa
otra se apague.

**Matiz añadido en la segunda pasada (§4 y §5):** el veredicto sigue siendo RECHAZADO, pero
el peso se movió. Antes era "el despliegue expone archivos de Pacha Viva". Ahora hay además
**un defecto que daña al sitio de Leo aunque Pacha Viva funcione perfecto** (A-4, colisión de
`XSRF-TOKEN` en el origen compartido) y **un riesgo de reputación de envío del dominio
anfitrión** (M-2). Esos dos no los paga el proyecto que se está demostrando.

### Lo que hay que cerrar antes de publicar (checklist bloqueante)

| # | Bloqueante | Severidad | Dueño |
|---|---|---|---|
| C-1 | La raíz de la app no puede quedar dentro del árbol web (o, si queda, con el `.htaccess` de deny literal de §1.4 **verificado por HTTP**) | Crítico | quien despliega (`deployer`) |
| C-2 | `database.sqlite` fuera del docroot, con ruta absoluta en `DB_DATABASE` | Crítico | `deployer` |
| C-3 | La SQLite que se suba debe verificarse **antes de subirla**: cero usuarios, o un solo admin con clave generada y correo que no sea `dev@pachaviva.test` | Crítico | `deployer` |
| A-1 | `.env` de despliegue propio: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` **nueva**, `EXTRA_TRUSTED_HOSTS` con `www.limaviewtours.com` | Alto | `deployer` |
| A-2 | El panel `/tour-word/admin` debe quedar cerrado por IP/Basic Auth, no solo por login | Alto | `deployer` |
| A-3 | `/tour-word/` queda fuera de los bloqueos anti-WordPress y del filtro de escáneres del `.htaccess` vivo → replicarlos dentro | Alto | `deployer` |
| A-4 | **Acotar las cookies al subdirectorio** (`SESSION_PATH=/tour-word`, `SESSION_COOKIE` propio). Sin esto el `XSRF-TOKEN` de la demo pisa el de Lima View y le rompe los POST por AJAX de su sitio real (§4.3) | Alto | `deployer` |
| M-1 | `is_staging_mirror` falla **abierto** (indexable) en TODOS sus modos de fallo, medido (§5). Bloqueante vía la capa `X-Robots-Tag` del `.htaccess`, que no depende de la config; el arreglo del código va aparte | Medio (bloqueante) | `backend-laravel` + `deployer` |
| M-2 | `MAIL_MAILER=log` y `CONTACT_NOTIFY_EMAIL` **sin declarar** en el `.env` de la demo. Nunca credenciales SMTP del dominio anfitrión (§4.4) | Medio | `deployer` |
| M-3 | Cabeceras de seguridad en el `.htaccess` de `/tour-word/`: la app no emite ninguna y las del dominio las emite la app de Lima View, no el servidor (§4.2) | Medio | `deployer` |

---

## 0. Nota metodológica: un artefacto de medición que hay que declarar

Sondeé el dominio vivo con `curl` (solo GET/HEAD, ninguna escritura, ningún exploit).
**A partir de la tercera tanda de sondas el servidor empezó a devolver `200` con ~12 KB
en TODAS las URLs, incluidas `/.env` y `/wp-admin/`**, con `<title>One moment,
please...</title>` / `<title>Loader</title>`: es una página-desafío anti-bot del hosting
que se disparó por mi propio volumen de peticiones.

Consecuencia honesta: **las mediciones de las tandas 1 y 2 son válidas; las de la tanda 3
(anti-WP en subdirectorio, filtro de user-agent) NO lo son** y quedan marcadas como *no
verificado* en §3. No las presento como hallazgo medido. Vale la pena que quien despliegue
sepa además que ese desafío existe: **un despliegue o un humo post-deploy hecho por HTTP
contra ese dominio puede recibir la página-desafío en vez de la respuesta real**, y un
chequeo que solo mire el código `200` daría verde sobre un sitio caído.

---

## 1. [CRÍTICO] Exposición de archivos por la forma del despliegue

### 1.1 Qué mide esto y con qué evidencia

El patrón que se propone replicar es el de Lima View: la app completa en
`/public_html/<subdir>/` y un `.htaccess` que reenvía a `public/`. Bajo ese patrón la raíz
de la aplicación **está dentro del árbol web** — y el repositorio de Pacha Viva **no trae
ningún `.htaccess` en su raíz** (verificado: `ls -a` en `G:\laragon\www\tours-word` no lista
`.htaccess`; el único del proyecto es `public/.htaccess`). Es decir: **hoy no existe en el
repo la pieza que impediría servir esos archivos.** Si se sube tal cual, no hay barrera.

El `public/.htaccess` de Laravel **no protege nada de esto**: su regla es
`RewriteCond %{REQUEST_FILENAME} !-f` → todo archivo real bajo el docroot lo sirve el
servidor directamente, antes de que PHP arranque. Ningún middleware de Laravel llega a verlo.

### 1.2 Qué está protegido hoy en el dominio anfitrión y qué NO

Sondas contra `https://www.limaviewtours.com` (tandas válidas, `Server: LiteSpeed`,
`X-Powered-By: PHP/8.3.27`):

| URL sondeada | Respuesta | Lectura |
|---|---|---|
| `/.env` | **403** | Hay regla de deny para `.env` |
| `/zzz-inexistente/.env` | **403** | La regla aplica también en subdirectorios |
| `/zzz/.git/config` | **403** | Hay deny para `.git` |
| `/composer.json` (raíz) | **403** | Denegado en la raíz |
| `/zzz/composer.lock` | **404** | **NO denegado** — 404 porque no existe, no porque esté bloqueado |
| `/zzz/database.sqlite` | **404** | **NO denegado** — mismo caso |
| `/storage/logs/laravel.log` | **404** | **NO denegado** — mismo caso |
| `/tour-word/` | **301** → `/limaprogramacion/public/tour-word` | El catch-all vivo hoy se traga `/tour-word/` |
| `/limaprogramacion/.env` | **301** → `/.env` (y ahí 403) | El deny es por `RewriteRule`, **no** por `<Files>` |

**El hallazgo está en las dos filas de 404.** Un `404` sobre un archivo que no existe
**no es prueba de bloqueo**: es el control negativo que demuestra que esa extensión pasa de
largo por el catch-all y la sirve el servidor. `composer.lock`, `database.sqlite` y
`*.log` **no tienen regla de deny en el `.htaccess` vivo**. El día que existan bajo
`/public_html/tour-word/`, se descargan.

Prueba adicional de que el deny de `.env` es una `RewriteRule` y no un bloque `<Files>`:
`/limaprogramacion/public/.env` devolvió **301** (no 403). Un `<Files ".env">` habría
denegado por *basename* sin importar el prefijo de ruta. Esto importa mucho para §3.

### 1.3 URLs exactas que quedarían alcanzables

Si se replica el patrón sin `.htaccess` propio, con la app completa en `/public_html/tour-word/`:

| Archivo | URL | ¿Lo cubre el `.htaccess` vivo? |
|---|---|---|
| `.env` (con `APP_KEY` y credenciales) | `https://www.limaviewtours.com/tour-word/.env` | **Depende del orden de reglas** — ver §3, no verificado |
| **`database/database.sqlite`** | `https://www.limaviewtours.com/tour-word/database/database.sqlite` | **NO** (evidencia: fila de 404 de §1.2) |
| `storage/logs/laravel.log` | `https://www.limaviewtours.com/tour-word/storage/logs/laravel.log` | **NO** |
| `composer.json` / `composer.lock` | `https://www.limaviewtours.com/tour-word/composer.lock` | **NO** |
| `.git/` | `https://www.limaviewtours.com/tour-word/.git/config` | Sí en la raíz del dominio; en `/tour-word/` depende del orden (§3) |
| Listado de directorio | `https://www.limaviewtours.com/tour-word/database/` | **NO** — sin `Options -Indexes` propio |

**Vector de ataque e impacto, uno por línea:**

- **`database.sqlite`**: un GET sin autenticación descarga **la base de datos entera** —
  tabla `users` con los hashes, `sessions`, y `contact_messages` con nombre, correo,
  teléfono, mensaje libre e **IP** de cada visitante (datos personales bajo Ley 29733).
  Además habilita crackeo *offline* del hash del administrador: sin límite de intentos,
  sin disparar ningún throttle y sin dejar rastro en ningún log.
- **`.env`**: `APP_KEY` → **falsificación de cookies de sesión y de cualquier payload
  firmado o encriptado de Laravel**. Si ese `.env` llevara además credenciales SMTP del
  dominio anfitrión, el impacto salta al cliente ajeno (§4.4).
- **`laravel.log`**: rutas absolutas del servidor, trazas con fragmentos de consulta y, en
  este proyecto en particular, entradas estructuradas que incluyen IP de visitantes
  (`contact_message.honeypot_triggered`, `ContactMessageController.php:26`).
- **`composer.lock`**: inventario exacto de versiones → selección dirigida de CVEs.

### 1.4 Remediación

**Opción A — la que recomiendo, y no depende de ningún `.htaccess`.**
Sacar la raíz de la app del árbol web:

```
/home/<usuario>/tour-word-app/        <- app completa (app, config, database, storage, vendor, .env)
/public_html/tour-word/               <- SOLO el contenido de public/
```

y en `/public_html/tour-word/index.php`, las dos rutas que apuntan fuera:

```php
require __DIR__.'/../../tour-word-app/vendor/autoload.php';
$app = require_once __DIR__.'/../../tour-word-app/bootstrap/app.php';
```

Con esto `.env` y `database.sqlite` son **físicamente inalcanzables por HTTP**, sin
depender de que una directiva se aplique, de que `AllowOverride` esté permitido, ni de que
el cliente FTP haya subido un archivo cuyo nombre empieza por punto. Es la única opción sin
modo de fallo silencioso.

**Opción B — si el acceso FTP obliga a dejar todo bajo `public_html`.**
Crear `/public_html/tour-word/.htaccess` con este bloque **literal**:

```apache
# /public_html/tour-word/.htaccess
# Raiz de la app Laravel dentro del arbol web: todo lo que NO sea public/
# se deniega ANTES de cualquier reescritura.

Options -Indexes -MultiViews

# (1) Deny por nombre de archivo (basename). Apache 2.4 / LiteSpeed.
<FilesMatch "(?i)^(\.env.*|\.htaccess|\.htpasswd|composer\.(json|lock)|package(-lock)?\.json|artisan|phpunit\.xml.*|vite\.config\.js|\.gitignore|\.gitattributes|\.editorconfig|README\.md|.*\.sqlite.*|.*\.db|.*\.sql|.*\.log|.*\.bak|.*\.ini|.*~)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>

# (2) Deny por DIRECTORIO. Imprescindible: (1) solo mira el nombre del
#     archivo, no la carpeta que lo contiene.
RewriteEngine On
RewriteRule ^(app|bootstrap|config|database|docs|lang|node_modules|resources|routes|scripts|storage|tests|vendor|\.git|\.claude|\.github)(/|$) - [F,L]

# (3) Recien ahora, reenvio interno del resto a public/
RewriteCond %{REQUEST_URI} !^/tour-word/public/
RewriteRule ^(.*)$ public/$1 [L]
```

Nota sobre el patrón `.*\.sqlite.*`: es a propósito más ancho que `\.sqlite$`, porque
SQLite crea archivos hermanos `-wal` y `-shm` en el mismo directorio y un patrón anclado
al final **no los cubriría**.

Y, como segunda barrera independiente (por si `AllowOverride` no aplica el de arriba, o
por si el cliente FTP no sube archivos que empiezan por punto), un `.htaccess` con **solo**
estas líneas dentro de `/public_html/tour-word/storage/` y de
`/public_html/tour-word/database/`:

```apache
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
</IfModule>
```

**En cualquiera de las dos opciones, además (esto es C-2 y no es negociable):**
la base SQLite no va dentro de `database/`. Va fuera del docroot, con ruta absoluta:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/home/<usuario>/tour-word-data/pachaviva.sqlite
```

Motivo: incluso en la Opción B, un `.htaccess` es **una sola** directiva de la que depende
un archivo que contiene *toda* la base. Un archivo fuera del docroot no depende de nada.

**Verificación obligatoria post-deploy (si da otra cosa, no se publica el enlace):**

```bash
for p in .env database/database.sqlite storage/logs/laravel.log composer.lock \
         composer.json .git/config database/ storage/ ; do
  printf "%-36s " "$p"
  curl -s -o /dev/null -w "%{http_code}\n" "https://www.limaviewtours.com/tour-word/$p"
done
# Esperado: 403 o 404 en TODAS.
# Control positivo obligatorio en la MISMA corrida -- si esto no da 200, la
# prueba no midio nada y los 403 de arriba no significan nada:
curl -s -o /dev/null -w "control /tour-word/es -> %{http_code}\n" \
  "https://www.limaviewtours.com/tour-word/es"
```

### 1.5 `.git/` y el FTP que sube "la carpeta entera"

El proyecto **es** un repositorio git (`.git/` en la raíz). Si el despliegue se hace
arrastrando la carpeta completa por FTP, `.git/` viaja. Con `.git/` alcanzable se
reconstruye **todo el historial**, incluidos archivos borrados y cualquier secreto que haya
pasado por un commit antiguo. El deny de `.git` existe en la raíz del dominio (403 medido),
pero para `/tour-word/` cae en la misma incógnita de orden de reglas de §3.

**La lista de exclusión del FTP debe excluir explícitamente:** `.git/`, `node_modules/`,
`tests/`, `docs/`, `.env` (se escribe a mano en el servidor, no se sube), `.claude/`,
`storage/logs/*`, `.phpunit.result.cache`, `database/database.sqlite` (va a su ruta fuera
del docroot).

---

## 2. [CRÍTICO] El panel de administración y la base SQLite que se va a subir

### 2.1 Dónde queda el panel y si es alcanzable

`app/Providers/Filament/AdminPanelProvider.php:29` → `->path('admin')`, con `->login()`
(línea 30). Filament 4.12.8, Livewire 3.8.7, Laravel 12.69.1.

**URL pública resultante: `https://www.limaviewtours.com/tour-word/admin/login`**
— alcanzable desde Internet, sin ninguna capa delante. Es el mismo formulario de
administración del CMS, en el dominio de producción de otro cliente.

Lo que **sí** está bien hecho, y lo digo porque acota la severidad:

- `app/Models/User.php:50-53` — `canAccessPanel()` devuelve `$this->is_admin`. No es el
  `return true` por defecto de Filament: un usuario cualquiera no entra.
- `app/Models/User.php:31-34` — `is_admin` **fuera de `$fillable`**, con el docblock que
  explica por qué. Sin escalada por asignación masiva.
- `2026_09_01_221857_add_is_admin_to_users_table.php:19` — `->default(false)`.
- No hay ruta de registro público ni `->passwordReset()` en el panel: no hay superficie de
  recuperación de contraseña que atacar.
- Límite de tasa en el login: `vendor/filament/filament/src/Auth/Pages/Login.php:74`
  → `$this->rateLimit(5)`. Existe, pero es **5 intentos por minuto y por IP**: frena el
  fuerza-bruta ruidoso, no frena a quien rote IPs ni a quien ya tenga el hash offline.

### 2.2 Seeders: **verificado, ninguno siembra usuarios**

`database/seeders/DatabaseSeeder.php:19-26` llama solo a `SettingSeeder`, `DemoTourSeeder`
y `DemoCatalogImageSeeder`. Un `grep -rn -i "password|email|User::" database/seeders/`
devuelve **una única coincidencia, y es un comentario** (`DatabaseSeeder.php:15`). El
docblock lo dice explícitamente: *"a seeder that creates a user with a predictable password
is exactly the kind of thing that has leaked into production before"*.

`app/Console/Commands/EnsureDevAdminCommand.php` tampoco es un vector por sí mismo:
línea 56 aborta con `FAILURE` si `! $this->laravel->isLocal()`, **antes de tocar la base**,
y no tiene `--force`. La contraseña se pide interactiva o se genera con `Str::password(20)`
y se imprime una sola vez (líneas 110-132).

**Conclusión de este punto: el riesgo NO está en el código de siembra.** Los dos incidentes
previos del equipo están efectivamente cerrados por diseño. Lo dejo dicho porque era lo que
se me pidió verificar y el resultado es negativo.

### 2.3 [CRÍTICO] Dónde SÍ está el riesgo: el archivo SQLite que se suba

El `.env` local declara `DB_CONNECTION=mysql` / `DB_DATABASE=pachaviva`: **el desarrollo no
corre sobre SQLite**. El archivo `database/database.sqlite` que hay en el árbol (94.208
bytes, 2026-09-01) es un **residuo obsoleto**: lo abrí y contiene solo las tablas del
esqueleto de Laravel (`cache`, `cache_locks`, `failed_jobs`, `job_batches`, `jobs`,
`migrations`, `password_reset_tokens`, `sessions`, `users`) — **ninguna tabla de la
aplicación** (`tours`, `settings`, `contact_messages`) y **0 filas en `users`**. Está
además cubierto por `database/.gitignore` (`*.sqlite*`), así que no viaja en el repo.

Es decir: **la base "pre-sembrada" que se va a subir todavía no existe.** El hallazgo
Crítico es sobre *cómo se va a construir*, y el camino más probable es el peligroso:

1. Quien prepare el archivo correrá `migrate --seed` → `users` queda **vacía** → el panel
   queda inaccesible y la demo no se puede mostrar.
2. La salida natural a eso es correr `dev:ensure-admin`, que solo funciona con
   `APP_ENV=local`. Se corre en local, y **el `dev@pachaviva.test` recién creado queda
   dentro del archivo SQLite que se sube**.
3. Ese correo está documentado en el repo (`EnsureDevAdminCommand.php:44`) y en
   `docs/lote-2/README.md`. La contraseña sería la que el desarrollador tecleó en su
   máquina — típicamente reutilizada. Y el hash viaja dentro de un archivo que, si no se
   cierran C-1 y C-2, **se descarga con un GET** y se craquea sin límite de intentos.

Ese es exactamente el patrón de los dos incidentes anteriores, por una puerta distinta:
esta vez la credencial no entra por un seeder ni por un repositorio público, **entra dentro
del binario de la base de datos**.

### 2.4 Remediación de C-3

**(a) Construir el archivo sin ningún usuario, y verificarlo antes de subir.**

```bash
# Con un .env temporal apuntando al sqlite de salida:
php artisan migrate:fresh --force --seed      # NO correr dev:ensure-admin
```

**Compuerta de verificación — se corre siempre, y si no da `0` el archivo no sube:**

```bash
php -r '$d = new PDO("sqlite:/ruta/al/pachaviva.sqlite");
  $n = $d->query("SELECT COUNT(*) FROM users")->fetchColumn();
  echo "USUARIOS=$n\n";
  foreach ($d->query("SELECT id,email,is_admin FROM users") as $r)
      echo "  -> {$r["email"]} is_admin={$r["is_admin"]}\n";
  exit($n == 0 ? 0 : 1);'
```

Control obligatorio de que la compuerta puede fallar: correrla también sobre una copia con
un usuario insertado a mano — si ahí también imprime `USUARIOS=0`, la compuerta está rota y
no mide nada.

**(b) Si la demo necesita mostrar el panel**, entonces: un usuario creado a propósito, con
correo que **no** sea `dev@pachaviva.test` (que ya es público en el repo), contraseña
generada (`Str::password(24)`) entregada fuera de banda, y una fecha de baja anotada.
Nunca la clave que el desarrollador use en su máquina.

**(c) Y en cualquiera de los dos casos — A-2 — el panel no se expone a Internet.**
Delante del login de Filament va una capa que no depende de la aplicación. En
`/public_html/tour-word/.htaccess`, **antes** del reenvío a `public/` del bloque de §1.4:

```apache
# Panel de administracion: cerrado con HTTP Basic delante del login de Filament.
# Va ANTES de la regla (3) de reenvio a public/.
<IfModule mod_rewrite.c>
    RewriteCond %{REQUEST_URI} ^/tour-word/(admin|livewire)(/|$) [NC]
    RewriteRule ^ - [E=PROTEGIDO:1]
</IfModule>

AuthType Basic
AuthName "Pacha Viva - demo privada"
AuthUserFile /home/<usuario>/tour-word-secrets/.htpasswd
Require valid-user
Satisfy any
Order allow,deny
Allow from all
Deny from env=PROTEGIDO
```

Si el hosting lo permite, la variante más simple y más robusta es un archivo aparte:
`/public_html/tour-word/admin/.htaccess` no sirve (la ruta `/admin` es virtual, no existe
en disco), así que la opción limpia alternativa es **cambiar `->path('admin')` a una ruta
no adivinable** *más* Basic Auth — nunca solo lo primero: una ruta secreta no es un control
de acceso, solo reduce el ruido de los escáneres.

Nota de alcance: el `robots.txt` que este proyecto genera (`RobotsController.php:48`,
`Disallow: /admin`) **no aporta nada aquí** y no debe contarse como mitigación — en
`/tour-word/robots.txt` ningún rastreador lo lee, porque `robots.txt` solo se lee desde la
raíz del host. Eso ya lo dejó dicho `02-seo.md ## 6.3`; lo repito solo para que nadie lo
tome por un control de seguridad, que nunca lo fue.

---

## 3. [ALTO] Riesgo para el dominio anfitrión: la exclusión en el `.htaccess` vivo

### 3.1 Lo que sí está medido

- Hoy `/tour-word/` **no está excluido**: devolvió `301` → `/limaprogramacion/public/tour-word`.
  Es decir, el catch-all vivo se traga el subdirectorio nuevo. Sin la exclusión, la app de
  Pacha Viva no se serviría nunca. La exclusión hace falta, eso no está en discusión.
- **El deny de `.env` del `.htaccess` vivo está implementado como `RewriteRule`, no como
  bloque `<Files>`.** Evidencia: `/limaprogramacion/public/.env` devolvió `301` (la regla
  que quita el prefijo corrió primero) en vez del `403` que un `<Files ".env">` habría dado
  por *basename* sin importar la ruta. Este dato es el que hace que el orden importe.

### 3.2 El dictamen: **sí, la exclusión puede abrir un agujero — y depende del ORDEN**

`RewriteRule ^tour-word(/|$) - [L]` lleva la bandera `[L]`, que **detiene el procesamiento
del resto de reglas de reescritura de ese `.htaccess`** para esa petición. Por lo tanto:

> **Todo bloqueo del `.htaccess` vivo que esté implementado como `RewriteRule` y que esté
> escrito DESPUÉS de la línea de exclusión deja de aplicarse a `/tour-word/…`.**

Como §3.1 demuestra que al menos el deny de `.env` es una `RewriteRule`, y como los bloqueos
de IP y los filtros de user-agent en cPanel/LiteSpeed se escriben casi siempre como
`RewriteCond %{REMOTE_ADDR}` / `%{HTTP_USER_AGENT}` + `RewriteRule … [F]`, la conclusión
práctica es:

- **Si la exclusión se pega arriba del archivo** (que es el instinto natural, "antes del
  catch-all" suele leerse como "al principio"), `/tour-word/` queda **fuera del bloqueo de
  IPs, fuera del filtro de user-agents y fuera del deny de `.env`/`.git`** — y el
  subdirectorio nuevo se convierte en el punto ciego del dominio entero.
- **Si se pega inmediatamente antes del catch-all y después de todos los bloqueos**, los
  bloqueos siguen aplicando.

**La ubicación correcta, literal, dentro del `.htaccess` vivo de `/public_html/`:**

```apache
# ... AQUI van, sin tocar, TODOS los bloques existentes:
# bloqueo de IPs, deny de .env/.git, reglas anti-WordPress, filtro de user-agent ...

# --- Pacha Viva (demo). Va DESPUES de todos los bloqueos de arriba y
# --- INMEDIATAMENTE ANTES del catch-all hacia /limaprogramacion/public/.
# --- Si esta linea sube por encima de un bloqueo escrito como RewriteRule,
# --- ese bloqueo deja de proteger /tour-word/ (bandera [L]).
RewriteRule ^tour-word(/|$) - [L]

# ... y recien aqui el catch-all existente hacia /limaprogramacion/public/ ...
```

### 3.3 Lo que NO pude verificar (y por qué)

- **Si las reglas anti-WordPress y el filtro de escáneres cubren subdirectorios.** Las
  sondas que lo habrían medido cayeron dentro de la página-desafío anti-bot descrita en §0
  (control: `/.env`, que en la tanda válida dio `403`, pasó a dar `200` de 12 KB). Reintenté
  una vez más tarde con user-agent de navegador y el control seguía en `200`: el instrumento
  sigue contaminado, y no voy a seguir golpeando un sitio de producción para medirlo.
- **El contenido literal del `.htaccess` vivo.** No lo leí; no tengo acceso a ese servidor
  en esta sesión y no es mío el pedirlo.

Lo que sí puedo afirmar por mecanismo, no por medición: un patrón de `RewriteRule` anclado
con `^` en el `.htaccess` de `/public_html/` se compara contra la ruta **relativa a ese
directorio**. Una regla `^wp-login\.php$` empareja `/wp-login.php` pero **no**
`/tour-word/wp-login.php`. Salvo que estén escritas con `^(.*/)?wp-…`, las reglas
anti-WordPress ancladas en la raíz **nunca cubrieron ningún subdirectorio**, ni antes ni
después de esta exclusión.

### 3.4 Remediación: no depender del archivo del otro cliente

Sea cual sea el orden, lo correcto es que `/tour-word/` **no herede su seguridad de un
archivo que administra otro proyecto y que puede reescribirse mañana sin avisarnos**.
Replicar dentro de `/public_html/tour-word/.htaccess`, en el bloque de §1.4, justo después
de `Options -Indexes -MultiViews`:

```apache
# --- Replica de los bloqueos del dominio anfitrion, para no depender de su orden ---

# (a) Anti-WordPress: este proyecto es Laravel, ninguna de estas rutas existe.
RewriteRule (^|/)(wp-login\.php|wp-config\.php|xmlrpc\.php|wp-cron\.php)$ - [F,L,NC]
RewriteRule (^|/)(wp-admin|wp-includes|wp-content|wordpress|wp)(/|$) - [F,L,NC]

# (b) Rutas de CMS y de instaladores que nadie tiene por que pedir aca.
RewriteRule (^|/)(phpmyadmin|adminer\.php|\.well-known/acme-challenge/\.\.|setup\.php|install\.php|shell\.php|alfa.*\.php)(/|$) - [F,L,NC]

# (c) Filtro de escaneres por user-agent. Lista corta y explicita:
#     NO bloquear por "vacio" ni por "curl", que romperia el humo post-deploy.
RewriteCond %{HTTP_USER_AGENT} (sqlmap|nikto|nmap|masscan|acunetix|nessus|zgrab|WPScan|dirbuster|gobuster|feroxbuster) [NC]
RewriteRule ^ - [F,L]

# (d) Metodos HTTP que esta app no usa nunca.
RewriteCond %{REQUEST_METHOD} ^(TRACE|TRACK|DEBUG)$
RewriteRule ^ - [F,L]
```

Y un punto de coordinación que **no es técnico y hay que cerrarlo igual**: la exclusión
toca un archivo **vivo de producción de otro cliente**. Antes de editarlo:

1. **Copia de respaldo del `.htaccess` vivo, fechada, fuera del docroot.** Un error de
   sintaxis en ese archivo tumba `limaviewtours.com` entero con un 500, no solo
   `/tour-word/`.
2. **Humo inmediato del sitio anfitrión después de guardar**, no solo del subdirectorio
   nuevo: home, una ficha de tour y el flujo de reserva. La memoria del equipo ya registra
   un sitio dejado en 404 por asumir el docroot.
3. Hacerlo **fuera de la ventana comercial** de Lima View.

Esto último es procedimiento de despliegue: lo ejecuta `deployer`, no yo.

---

## 4. Configuración del despliegue, cabeceras y formulario de contacto

### 4.1 [ALTO] `APP_DEBUG` / `APP_ENV` / `APP_KEY` — el `.env` que hay hoy NO puede subir

El `.env` del árbol local declara literalmente:

```
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
EXTRA_TRUSTED_HOSTS=tours-word.test,127.0.0.1,localhost
DB_CONNECTION=mysql
```

Si ese archivo viaja por FTP tal cual (y viajará, si se arrastra la carpeta completa: está
en disco, y `.gitignore` no protege de un FTP), pasan cuatro cosas a la vez:

1. **`APP_DEBUG=true` en un dominio público**: cualquier excepción devuelve la página de
   error detallada de Laravel, con la traza completa, **las rutas absolutas del servidor y
   el volcado de variables de entorno**. Equivale a publicar el `.env` — sin necesidad de
   que el `.env` sea descargable. Respuesta a la pregunta explícita del encargo: **sí, una
   página de error de Laravel filtra rutas y variables, pero solo con `APP_DEBUG=true`.**
   Con `APP_DEBUG=false` la respuesta es genérica y no filtra nada.
2. **`APP_ENV=local`** reabre `dev:ensure-admin` en el servidor (§2.2: su única compuerta es
   `isLocal()`) y desactiva `TrustHosts`, que por diseño **no aplica nada en `local`**
   (`bootstrap/app.php:33-36`, comentario del propio proyecto). Se pierde la protección F-1
   contra un `Host` hostil justo en el entorno donde importa.
3. **`EXTRA_TRUSTED_HOSTS` sin `www.limaviewtours.com`** y `APP_URL` apuntando a
   `127.0.0.1`: con `APP_ENV=production`, `TrustHosts` devolvería **400 Invalid Host header**
   a todas las peticiones reales. La demo directamente no levantaría.
4. **`DB_CONNECTION=mysql`**: el despliegue es SQLite. Sin cambiarlo, error de conexión en
   la primera petición — que con `APP_DEBUG=true` se muestra con credenciales incluidas.

**`.env` mínimo del despliegue de `/tour-word/` (se escribe A MANO en el servidor, no se sube):**

```dotenv
APP_NAME="Pacha Viva"
APP_ENV=production
APP_KEY=                      # generar NUEVA: php artisan key:generate --show
APP_DEBUG=false
APP_URL=https://www.limaviewtours.com/tour-word
APP_TIMEZONE=America/Lima
APP_LOCALE=es

EXTRA_TRUSTED_HOSTS=www.limaviewtours.com,limaviewtours.com

LOG_CHANNEL=stack
LOG_LEVEL=error               # no "debug": el log crece y guarda IP de visitantes

DB_CONNECTION=sqlite
DB_DATABASE=/home/<usuario>/tour-word-data/pachaviva.sqlite

SESSION_DRIVER=database
SESSION_COOKIE=pachaviva_demo_session   # ver 4.3 -- NO dejar el default
SESSION_PATH=/tour-word                 # ver 4.3 -- afecta al dominio anfitrion
SESSION_DOMAIN=www.limaviewtours.com
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_ENCRYPT=true

CACHE_STORE=database
QUEUE_CONNECTION=sync

MAIL_MAILER=log               # ver 4.4
# CONTACT_NOTIFY_EMAIL sin declarar a proposito: falla cerrado (ver 4.4)

IS_STAGING_MIRROR=true        # ver 5
```

**`APP_KEY` nueva, no la del `.env` local.** Reutilizarla ata la seguridad de la demo (en un
dominio ajeno) a la clave de la máquina de desarrollo, y al revés.

Y el paso que la memoria del equipo ya registra como trampa: **`php artisan config:clear`
después de escribir el `.env`, y `config:cache` al final**. Un `bootstrap/cache/config.php`
subido desde la máquina local traería `APP_DEBUG=true` y `DB_CONNECTION=mysql` **congelados**,
y ningún cambio del `.env` tendría efecto. QA ya reprodujo ese comportamiento en vivo
(`03-qa.md`). **`bootstrap/cache/*.php` debe ir en la lista de exclusión del FTP.**

### 4.2 [MEDIO] La app no emite ninguna cabecera de seguridad

`grep -rn "X-Frame-Options|Content-Security-Policy|X-Robots-Tag|X-Content-Type"` sobre
`app/`, `public/` y el layout: **cero coincidencias**. No hay middleware de cabeceras.

Las cabeceras que sí medí en el dominio (`Content-Security-Policy`, `Strict-Transport-Security`,
`X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`) llegaron en una respuesta
**generada por PHP** (`X-Powered-By: PHP/8.3.27`) del sitio de Lima View: **las emite su
aplicación, no el servidor**. Por lo tanto **no se aplican a `/tour-word/`**. Pacha Viva se
serviría sin ninguna de ellas.

Remedio, al final del `.htaccess` de `/public_html/tour-word/` (§1.4). Sin `unsafe-eval` ni
terceros, porque este build no los necesita: Alpine va compilado por Vite.

```apache
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.bunny.net; font-src 'self' https://fonts.bunny.net data:; img-src 'self' data:; frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'"
</IfModule>
```

La CSP hay que **verificarla con la consola del navegador antes de publicar el enlace**: si
alguna vista carga un tercero que no listé, quedaría bloqueado. Esa verificación es de
`anyerson-qa`, no mía. Precedente propio del estudio: una CSP demasiado estricta dejó sin
medición la analítica de otro proyecto durante semanas.

Sobre la salida cruda de Blade: revisé todos los `{!! !!}` del proyecto. Imprimen o bien
iconos SVG definidos en el propio código (`components/ui/*.blade.php`), o bien
`json_encode(...)` con `JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP` dentro de
`<script type="application/ld+json">` (`components/seo/*-jsonld.blade.php`). **Ninguno
imprime entrada de usuario sin escapar.** Sin hallazgo de XSS reflejado ni almacenado.

### 4.3 [ALTO] Colisión de cookies con el dominio anfitrión — daño al cliente ajeno

Este es el hallazgo que menos tiene que ver con Pacha Viva y más con el que paga la factura
del dominio.

`config/session.php:146` → `'path' => env('SESSION_PATH', '/')`, y el `.env`/`.env.example`
declaran `SESSION_PATH=/`. Ese mismo valor es el que usa la cookie **`XSRF-TOKEN`**:
`vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/VerifyCsrfToken.php:203-214`
la construye con `$config['path']`, `$config['domain']` y **`httpOnly = false`** (séptimo
argumento).

Lima View Tours **también es Laravel**. Las dos aplicaciones quedarían en el **mismo origen**
`https://www.limaviewtours.com`, emitiendo ambas una cookie llamada exactamente `XSRF-TOKEN`
sobre el path `/`. Consecuencia: **cualquier visitante que pase por `/tour-word/` se lleva el
`XSRF-TOKEN` de Pacha Viva pisando el de Lima View**, y a partir de ahí el JavaScript de Lima
View que lea esa cookie enviará un token que no corresponde a su sesión → **419 Page Expired
en los POST por AJAX del sitio real**, incluido su flujo de reserva. No hace falta un
atacante: la colisión se produce sola, con tráfico normal.

Riesgos del mismo origen compartido, en la misma bolsa:

- Un XSS en Pacha Viva se ejecuta **en el origen de Lima View**: puede leer el DOM de las
  páginas de Lima View por XHR de mismo origen y actuar como el visitante en su sitio real.
  Esto sube el precio de cualquier fallo de la demo, de "molesto" a "problema del cliente que
  paga". Es la razón principal por la que la CSP de §4.2 no es opcional aquí.
- Cookie *tossing*: sobrescribir cookies de path `/` del host desde el subdirectorio.

**Remedio (ya incluido en el `.env` de §4.1), y es barato:**

```dotenv
SESSION_COOKIE=pachaviva_demo_session
SESSION_PATH=/tour-word
SESSION_DOMAIN=www.limaviewtours.com
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
```

`SESSION_PATH=/tour-word` acota **las dos** cookies (la de sesión y `XSRF-TOKEN`) al
subdirectorio, y `SESSION_COOKIE` evita cualquier colisión de nombre residual.

**Verificación:** cargar `/tour-word/es` y, en la pestaña Application del navegador,
comprobar que las cookies de Pacha Viva muestran `Path = /tour-word`; después cargar la home
de Lima View y comprobar que su `XSRF-TOKEN` de path `/` sigue intacto. Si las dos cosas no
se comprueban en la misma sesión de navegador, la verificación no midió nada.

### 4.4 [MEDIO] El formulario de contacto: qué puede y qué no puede hacer

Revisado: `routes/web.php:64-66`, `app/Http/Controllers/ContactMessageController.php`,
`app/Http/Requests/StoreContactMessageRequest.php:33-42`, `config/contact.php`,
`app/Mail/NewContactMessageReceived.php`.

**No es un relay abierto, y esto está bien construido:**

- El destinatario es **fijo**, de `config('contact.notify_email')` — nunca sale de la
  petición. Un visitante no puede elegir a quién se envía.
- `config/contact.php:35` falla **cerrado**: si `CONTACT_NOTIFY_EMAIL` no está o está vacío,
  el valor es `null`, el controlador lo comprueba explícitamente
  (`ContactMessageController.php:63`) y **no envía a nadie**, dejando un registro
  (`contact_message.notification_skipped_no_recipient`). El defecto previo que caía a
  `MAIL_FROM_ADDRESS` (un dominio de terceros) está cerrado.
- Honeypot `website` (`ContactMessageController.php:21`) y `throttle:5,10` por IP.
- Validación con tope de longitud en todos los campos (`max:120 / 255 / 30 / 2000`) y
  `subject` restringido con `Rule::in(...)`: nada de texto libre en el asunto. Los datos van
  a una vista Markdown que escapa por defecto.

**Lo que sí es riesgo en ESTE despliegue**, y es riesgo **del cliente anfitrión**: si el
`.env` de la demo configura un SMTP real —peor aún, el SMTP de `limaviewtours.com`— cada
envío del formulario sale **con el dominio de Lima View como remitente**. A 5 envíos cada 10
minutos por IP son ~720 mensajes diarios desde una sola IP, y sin techo rotando IPs. No
compromete nada técnicamente, pero **puede quemar la reputación de envío del dominio
anfitrión** y hacer que su correo comercial real empiece a caer en spam. Para una demo el
beneficio es nulo y el riesgo es de otro.

**Remedio (una línea):** en el `.env` de la demo, `MAIL_MAILER=log` y **no declarar**
`CONTACT_NOTIFY_EMAIL`. El formulario sigue funcionando de cara a la demostración (guarda el
mensaje, muestra el aviso de éxito) y no sale ni un correo. Bajo ninguna circunstancia poner
credenciales SMTP del dominio anfitrión en ese `.env`.

**Y un punto de datos personales que hay que decidir a propósito:** el formulario
**persiste** nombre, correo, teléfono, mensaje libre e **IP**
(`ContactMessageController.php:38-50`, campo `ip_address`) en la base de la demo. Si alguien
externo lo usa, queda PII real de un tercero en un archivo SQLite alojado en el dominio de
otro cliente. Al cerrar la demo el archivo debe **borrarse**, no solo despublicarse, y eso
debería quedar anotado con fecha.

### 4.5 Superficies menores, sin remedio bloqueante

- `bootstrap/app.php:14` declara `health: '/up'` → `https://www.limaviewtours.com/tour-word/up`
  quedará público. No filtra datos, pero delata la tecnología. Denegable por `.htaccess`.
- `routes/web.php:49` expone `/{locale}/_styleguide`, el inventario del sistema de diseño,
  público y sin sesión. No hay datos ahí, pero es superficie que nadie necesita en una demo
  de cliente: se puede denegar junto con `/up`.
- **Dependencias:** `composer audit` → *"No security vulnerability advisories found"*
  (Laravel 12.69.1, Filament 4.12.8, Livewire 3.8.7). Sin CVEs conocidos hoy.
  `npm audit` **no lo corrí**: las dependencias de front son de compilación (Vite/Tailwind) y
  no viajan al servidor — queda declarado como no verificado, no como limpio.

---

## 5. [MEDIO, bloqueante] `is_staging_mirror` falla **ABIERTO**: ante la duda, indexable

### 5.1 La cadena, leída línea por línea

```
config/cms.php:104          'is_staging_mirror' => env('IS_STAGING_MIRROR', false),
app/Support/Indexability.php:50
    return ! config('cms.catalog_demo_content') && ! config('cms.is_staging_mirror');
resources/views/components/layout.blade.php:101
    $isNoindex = $noindex || ! \App\Support\Indexability::siteIsIndexable();
```

La negación es el problema. `! config(...)` convierte **cualquier valor falsy en "no es
espejo de staging"**, y en PHP son falsy: `false`, `null`, `""`, `"0"`, `0`, `[]`. La clave
ausente devuelve `null` → `! null` es `true` → **indexable**. No hay ningún estado de la
configuración, salvo un `true` explícito y bien escrito, que produzca `noindex`.

### 5.2 Medido, no razonado

Arranqué la aplicación real (bootstrap completo, PHP 8.2.1) y llamé a
`Indexability::siteIsIndexable()` forzando cada modo de fallo. El archivo de la prueba se
ejecutó y se borró: no queda nada en el árbol (`ls` posterior → *No such file or directory*).
Escenario con `catalog_demo_content` ya en `false`, que es el día en que esta bandera existe
para servir de algo:

| Caso | `is_staging_mirror` | Resultado |
|---|---|---|
| A · Producción real de Pacha Viva | `false` | `TRUE` → indexable *(correcto, es el esperado)* |
| **B · Espejo declarado bien** | `true` | **`false` → noindex** *(control positivo: el chequeo SÍ puede fallar)* |
| C · Clave ausente por `config:cache` viejo | `null` | **`TRUE` → INDEXABLE** |
| D · `.env` perdido en el deploy | default `false` | **`TRUE` → INDEXABLE** |
| E · Typo en el nombre de la variable | default `false` | **`TRUE` → INDEXABLE** |
| F · `IS_STAGING_MIRROR=` (vacía) | `""` | **`TRUE` → INDEXABLE** |
| G · `IS_STAGING_MIRROR=0` | `"0"` | **`TRUE` → INDEXABLE** |

El caso B es el control que valida la medición: si el chequeo estuviera roto y devolviera
siempre `TRUE`, B también lo habría hecho. No lo hizo.

Verifiqué además el casteo de `env()` en esta versión de Laravel, porque de eso dependen F y
G: `"true"`→`bool(true)`, `"false"`→`bool(false)`, `"null"`→`NULL`, pero **`""`→`string("")`,
`"1"`→`string("1")`, `"0"`→`string("0")`, `"yes"`→`string("yes")`**. Es decir: `IS_STAGING_MIRROR=0`
y `IS_STAGING_MIRROR=` no son booleanos, son cadenas falsy, y caen del lado indexable.

**Los cinco fallos son silenciosos.** No hay excepción, no hay log, no hay página rota. La
única señal es la ausencia de un `<meta name="robots">` en el HTML — que nadie mira salvo que
lo busque a propósito.

El caso C merece un párrafo aparte porque es el más probable de todos: `config/cms.php` cambió
en este lote. Si el servidor arranca con un `bootstrap/cache/config.php` generado **antes** de
ese cambio (por ejemplo, subido por FTP desde la máquina local), Laravel carga solo ese array
y **nunca recorre `config/`** — la clave `cms.is_staging_mirror` sencillamente no existe, y
`config()` devuelve `null`. QA ya reprodujo en vivo la mitad de este comportamiento (`03-qa.md`:
cambiar `.env` sin `config:cache` deja la configuración vieja en efecto, en silencio). Es el
mismo mecanismo, una vuelta más atrás.

### 5.3 La suite **fija** el fallo abierto por escrito

`tests/Feature/StagingMirrorNoindexTest.php` pasa entero (5 tests, 39 aserciones, verde). Pero
el último test es:

```php
public function test_is_staging_mirror_defaults_to_false_without_the_env_variable(): void
{
    $this->assertFalse(config('cms.is_staging_mirror'));
}
```

con el docblock *"nunca debe hacer falta declarar la variable a propósito para que un
despliegue NO sea tratado como espejo de staging"*. O sea: el comportamiento de fallo abierto
**no es un descuido, es una decisión deliberada y clavada con un test**. Lo digo porque cambia
quién tiene que resolverlo: no es un bug que se parchea, es un criterio que hay que revisar.

### 5.4 Dictamen

**Para el uso al que se destina esta bandera, el default es un defecto de diseño y hay que
invertirlo.** El razonamiento del propio `config/cms.php:88-95` es correcto *para el
despliegue de producción de Pacha Viva* (que no debe quedar en `noindex` porque a alguien se
le perdió un `.env`), pero se aplicó a la bandera equivocada. Las dos banderas no tienen la
misma asimetría de costo:

- Si `catalog_demo_content` falla abierto: se indexa un catálogo de muestra en el dominio
  propio de la clienta. Molesto, reparable con un `noindex` y un tiempo de espera.
- Si `is_staging_mirror` falla abierto: se indexa un sitio de demostración **en el dominio de
  producción de otro cliente que ya sufrió exactamente ese incidente en julio de 2026**. El
  daño es de Leo, no nuestro, y la reparación no está en nuestras manos.

Ante costos así de distintos, el default tiene que ser distinto. Y el propio docblock de
`config/cms.php:88` ya pide que **producción declare explícitamente `IS_STAGING_MIRROR=false`**
— invertir el default solo hace que el código diga lo que la documentación ya dice.

**Remedio (dos capas; la primera es la que de verdad cierra).**

**(1) Capa que no depende de Laravel — esta es la bloqueante.** Va en
`/public_html/tour-word/.htaccess` y protege aunque el `.env` no exista, aunque la caché esté
podrida y aunque PHP devuelva un 500:

```apache
<IfModule mod_headers.c>
    # Espejo de demostracion en dominio ajeno: fuera del indice, siempre.
    # No depende de .env, ni de config:cache, ni de que la app arranque.
    # Cubre tambien PDF, imagenes y sitemap.xml, que una meta no alcanza.
    Header always set X-Robots-Tag "noindex, nofollow, noarchive"
</IfModule>
```

`Header always set` la emite también en respuestas de error (4xx/5xx), que es justo cuando la
bandera de PHP no está disponible. Ese "always" no es decorativo.

**(2) Arreglo del código, para el día que `catalog_demo_content` se apague.** Lo aplica
`backend-laravel`, no yo. En `app/Support/Indexability.php`:

```php
public static function siteIsIndexable(): bool
{
    // Ante la duda, NO indexable. Solo un "false" EXPLICITO en las dos
    // banderas concede indexacion; una clave ausente (config:cache viejo),
    // una cadena vacia o un typo cuentan como "no sabemos" -> noindex.
    // El segundo argumento de config() solo aplica si la clave NO existe,
    // que es exactamente el caso de la cache congelada.
    return config('cms.catalog_demo_content', true) === false
        && config('cms.is_staging_mirror', true) === false;
}
```

y en `config/cms.php`, que la ausencia de la variable no sea una decisión:

```php
'is_staging_mirror' => filled(env('IS_STAGING_MIRROR'))
    ? filter_var(env('IS_STAGING_MIRROR'), FILTER_VALIDATE_BOOL)
    : true,
```

Con eso, los siete casos de §5.2 quedan así: solo A (`false` explícito y bien escrito) es
indexable; B a G son `noindex`.

**Coste que hay que aceptar a cambio, y conviene decirlo antes de que sorprenda:** el
despliegue real de Pacha Viva pasa a **tener que declarar `IS_STAGING_MIRROR=false`**. Si no
lo declara, nace en `noindex`. Ese fallo es ruidoso y barato (se ve en la primera revisión de
SEO y se arregla con una línea); el actual es silencioso y caro (se ve cuando el sitio de otro
cliente ya está en Google). Prefiero el ruidoso.

**Y un aviso para quien aplique el arreglo:** el test
`test_is_staging_mirror_defaults_to_false_without_the_env_variable` **se pondrá rojo a
propósito**, y hay que reescribirlo para que afirme lo contrario. Si alguien lo "arregla"
devolviendo el default a `false`, deshace el fix sin darse cuenta. El comentario del test
tiene que decir por qué se invirtió.

### 5.5 Verificación post-deploy de esta sección

```bash
# (a) La meta del HTML
curl -s "https://www.limaviewtours.com/tour-word/es" | grep -i 'name="robots"'
# (b) La cabecera, que es la capa que no depende de PHP
curl -sI "https://www.limaviewtours.com/tour-word/es" | grep -i "x-robots-tag"
# (c) El sitemap debe estar neutralizado
curl -s -o /dev/null -w "sitemap -> %{http_code}\n" \
  "https://www.limaviewtours.com/tour-word/sitemap.xml"   # esperado 404
```

**Una extracción vacía en (a) o (b) es un FALLO, no un OK.** Si `grep` no imprime nada, la
lectura correcta es "no encontré la protección", nunca "salió limpio". Y (b) debe verificarse
también sobre una URL que devuelva error (por ejemplo `/tour-word/no-existe`): si ahí la
cabecera desaparece, el `always` no se aplicó y la capa 1 no está cerrando lo que se supone
que cierra.

---

## 6. Verificaciones realizadas y no realizadas

| OWASP | Resultado |
|---|---|
| A01 Broken Access Control | ✓ `canAccessPanel()` atado a `is_admin`; `is_admin` fuera de `$fillable`; sin registro público ni recuperación de contraseña en el panel. **Hallazgo A-2**: el panel es alcanzable desde Internet |
| A02 Cryptographic Failures | ✓ `Hash::make` (bcrypt, 12 rondas). **Hallazgos C-1/C-2**: `APP_KEY` y hashes descargables si la raíz queda en el árbol web. **A-1**: `APP_KEY` nueva obligatoria |
| A03 Injection | ✓ Sin `DB::raw` con entrada de usuario. Todos los `{!! !!}` revisados: SVG del propio código o `json_encode` con banderas HEX. Sin XSS reflejado ni almacenado |
| A04 Insecure Design | **Hallazgo M-1**: `is_staging_mirror` falla abierto por diseño, con test que lo fija (§5) |
| A05 Security Misconfiguration | **Hallazgos A-1, A-3, M-3**: `APP_DEBUG=true`/`APP_ENV=local` en el `.env` local; sin cabeceras de seguridad; bloqueos del anfitrión que no cubren el subdirectorio |
| A06 Vulnerable Components | ✓ `composer audit` → sin avisos (Laravel 12.69.1, Filament 4.12.8, Livewire 3.8.7). `npm audit` **no verificado** (dependencias solo de compilación) |
| A07 Auth Failures | ✓ Límite de tasa en el login (`Login.php:74`, `rateLimit(5)`), 5/min por IP. Débil para una cara pública → **A-2** lo compensa con Basic Auth |
| A08 Data Integrity | ✓ Sin deserialización insegura ni webhooks. Sin subida de archivos en la superficie pública de este lote |
| A09 Logging Failures | Parcial: el proyecto registra eventos del formulario con estructura. **Cuidado**: `contact_message.honeypot_triggered` guarda IP → `LOG_LEVEL=error` y el log fuera del árbol web |
| A10 SSRF | ✓ Sin `Http::get()` con URL controlada por el usuario en el código revisado |
| CSRF | ✓ Grupo `web` y `VerifyCsrfToken` en el panel. **Pero ver A-4**: la cookie `XSRF-TOKEN` de path `/` colisiona con la del anfitrión |
| Mass assignment | ✓ `$fillable` explícito en `User`; `is_admin` deliberadamente fuera |
| Secretos en el repo | ✓ `.env` cubierto por `.gitignore`; `database/.gitignore` cubre `*.sqlite*`; ninguna credencial cableada en seeders ni comandos |

**No verificado, y por qué:**

1. **El contenido literal del `.htaccess` vivo de `limaviewtours.com`.** No tengo acceso a ese
   servidor en esta sesión. Todo lo de §3 sobre el orden de reglas está razonado por mecanismo
   de Apache/LiteSpeed y por el comportamiento observado, no por lectura del archivo.
2. **Si las reglas anti-WordPress y el filtro de escáneres del anfitrión cubren
   subdirectorios.** Las sondas cayeron dentro de la página-desafío anti-bot (§0) y el control
   quedó contaminado. No insistí contra un sitio de producción.
3. **El comportamiento real de `/tour-word/` una vez desplegado.** Nada de lo de §1.4 está
   probado en el destino, porque el destino todavía no existe. Por eso cada remedio lleva su
   comando de verificación: se ejecutan **después** del despliegue y **antes** de compartir el
   enlace.
4. **`npm audit`.** No corrido.
5. **La base SQLite que se va a subir.** No existe todavía; lo auditado es el procedimiento
   para construirla (§2.3).

**Qué NO toqué, por no ser mi rol:**

- El veredicto visual y funcional del lote es de `anyerson-qa` (`03-qa.md`), que ya dio APTO;
  no lo reviso ni lo contradigo.
- La receta de indexación y su justificación SEO son de `anyerson-seo` (`02-seo.md` §6). Yo
  solo dictamino el **modo de fallo** de la bandera, no si la receta es la correcta.
- El arreglo de código de §5.4(2) lo aplica `backend-laravel`. Yo no apliqué ningún fix, no
  modifiqué ningún archivo del proyecto y no hice commit.
- El procedimiento de despliegue, el respaldo del `.htaccess` vivo y el humo post-deploy son
  de `deployer`.

---

## 7. Recomendaciones de hardening (no bloqueantes)

- **Poner fecha de caducidad a la demo.** Un staging en el dominio de un tercero sin fecha de
  baja se queda para siempre. Anotar la fecha de retiro el mismo día que se publica, y que
  incluya borrar el SQLite (§4.4) y revertir la línea del `.htaccess` vivo.
- **Un `robots.txt` no es el control.** Repito lo de §2.4: en `/tour-word/robots.txt` ningún
  rastreador lo lee. La protección real son el `X-Robots-Tag` y la meta.
- **Rotar la `APP_KEY` de la demo al retirarla**, si ese mismo build pasa después al dominio
  real de Pacha Viva.
- **Revisar el `X-Powered-By: PHP/8.3.27`** que el anfitrión expone hoy: delata la versión
  exacta de PHP. `expose_php = Off`. Es del otro proyecto, no de este — lo dejo dicho para que
  llegue a quien corresponda, no como hallazgo de Pacha Viva.
- **Cuando exista el dominio real de Pacha Viva**, volver sobre §4.2: las cabeceras deberían
  vivir en un middleware de la aplicación, no en un `.htaccess`, para que viajen con el
  proyecto y no dependan del hosting de turno.

---
---

# 8 · Re-verificación del paquete de despliegue (segunda pasada)

**Agente:** `security-engineer` · **Fecha:** 2026-09-18 · **Entrada:** `06-deploy.md` de `deployer`
**Alcance:** esto NO es una auditoría nueva. Es la verificación de que los artefactos
preparados en `06-deploy.md` cierran el checklist bloqueante de §0 de este mismo informe.
Todo lo anterior a esta línea queda como estaba: es la traza de por qué existe cada medida.

## 8.0 Método y su límite

No sondeé producción: el encargo prohíbe subir nada y la página-desafío anti-bot del
hosting (§0) invalidaría cualquier tanda. En su lugar **monté un Apache 2.4.54 real y
aislado** (el de Laragon, `G:/laragon/bin/apache/httpd-2.4.54-win64-VS16`, dos instancias en
127.0.0.1:8791 y :8792, docroot en el scratchpad de sesión) y pegué el `.htaccess` de
`06-deploy.md` §5.1 **literal**, con la única diferencia de la ruta del `AuthUserFile`
(apuntada a un `.htpasswd` real generado con `htpasswd.exe`). Ninguna de las dos instancias
sirve el proyecto ni toca `limaviewtours.com`.

- Instancia **8791**: con `mod_access_compat` cargado (el caso optimista).
- Instancia **8792**: idéntica **sin** `mod_access_compat` (el caso que hay que descartar,
  porque nadie sabe qué módulos carga el hosting del anfitrión).
- En cada tanda incluí el **control positivo** `/tour-word/es` → debe dar `200`. Una tanda
  donde el control no da `200` la descarté y la repetí (me pasó una vez: mi propia
  reconstrucción del archivo perdió la cola y el control cayó a `404`; el resultado de esa
  tanda no se usa en este informe).

Límite declarado: LiteSpeed (lo que probablemente corre el anfitrión) **no es** Apache.
Interpreta `.htaccess` de forma muy compatible, pero un comportamiento medido aquí no es
una promesa allá. Por eso todo lo de abajo termina en comprobaciones de humo ejecutables
sobre el servidor real, no en "queda cerrado".

**El documento se movió bajo mis pies:** leí `06-deploy.md` a las 14:48 (35.916 bytes) y a
las 14:56 `deployer` publicó una versión nueva (40.215 bytes) con el sondeo de FTP ya
ejecutado. Releí §3, §4, §8 y §9 en la versión nueva y corregí lo que cambiaba — está
señalado en §8.1 (fila C-2) y en §8.6 punto 2. **Lo que NO cambió entre las dos versiones es
el `.htaccess` de §5.1**: el bloque de autenticación y la CSP son byte a byte los que medí,
así que §8.3 y §8.7 se sostienen sobre el artefacto vigente. Verificado releyendo las líneas
330-360 del documento nuevo.

Los dos Apache de prueba quedaron apagados (PIDs 18200 y 11540, `taskkill`; 8791 y 8792 ya
no responden). El árbol del proyecto quedó intacto: corrí `route:cache` una vez para una
comprobación y borré el `routes-v7.php` resultante — `bootstrap/cache/` vuelve a tener solo
`packages.php` y `services.php`, y `git status` sigue mostrando 18 modificados + 2 sin
trackear, ninguno tocado por mí.

---

## 8.1 Estado del checklist, ítem por ítem

| Ítem | Veredicto de esta pasada | Evidencia |
|---|---|---|
| **C-3** SQLite verificada antes de subir | **CERRADO** | §8.2 — consulta ejecutada por mí sobre el archivo, con control negativo |
| **C-1** Raíz de la app en el árbol web con deny verificado | **ABIERTO** | §8.3 — el deny por nombre de archivo está **anulado** por el propio bloque de A-2. Medido: `403` → `200` |
| **C-2** SQLite fuera del docroot | **CERRADO en el plan, pendiente de ejecución** | §8.6 — el sondeo ya se hizo y la cuenta alcanza el `home`: Opción A viable. El hueco de la ruta está marcado y **falla cerrado** |
| **A-1** `.env` de despliegue | **CERRADO** | §8.5 |
| **A-2** Panel tras autenticación | **ABIERTO — el artefacto no funciona** | §8.3 — medido: `/tour-word/admin` responde `200` **sin credenciales** |
| **A-3** Réplica de bloqueos del anfitrión | **CERRADO en lo esencial, con un hueco** | §8.4 — la réplica es correcta y **necesaria** (medido); falta el bloqueo por IP |
| **A-4** Colisión de cookies | **CERRADO** | §8.5 |
| **M-1** `X-Robots-Tag` con `always` | **CERRADO como capa de servidor** | §8.5 y §8.8 |
| **M-2** `MAIL_MAILER=log` sin `CONTACT_NOTIFY_EMAIL` | **CERRADO** | §8.5 |
| **M-3** Cabeceras de seguridad | **ABIERTO** | §8.7 — la CSP tal como está escrita **rompe el sitio** (Alpine y las fuentes) |

Tres ítems siguen abiertos y dos de ellos son bloqueantes. **El veredicto sigue en
RECHAZADO.** Lo importante: no es que falte trabajo, es que **dos artefactos del paquete se
anulan entre sí**, y eso solo se ve ejecutándolos.

---

## 8.2 C-3 — La base SQLite: **CERRADO**, verificado por mí

El archivo existe en `C:\Users\USUARIO WEBTILIA\AppData\Local\claude\C--Users-USUARIO-WEBTILIA\3490999d-.../scratchpad\pachaviva-demo.sqlite`
(ojo: **no** está bajo `...\Local\Temp\claude\...`, que es la ruta que cita el documento;
es `...\Local\claude\...`. Quien lo vaya a subir tiene que mirar la ruta correcta o
regenerarlo con §2 de `06-deploy.md`, que es lo recomendable).

Consulta ejecutada por mí con `G:/laragon/bin/php/php8.2.1/php.exe` + PDO sqlite, no leída
de la tabla del informe:

```
pachaviva-demo.sqlite   192.512 bytes   md5 e75b09025ddd69f3d764e2561de8d8f9   2026-09-18 19:38
tablas (20): cache, cache_locks, contact_messages, destination_images, destinations,
             experience_images, experience_tour, experiences, failed_jobs, job_batches,
             jobs, migrations, password_reset_tokens, sessions, settings, team_members,
             tour_images, tour_slug_histories, tours, users
users = 0   tours = 3   destinations = 3   experiences = 3   settings = 1
contact_messages = 0    team_members = 0   sessions = 0   cache = 0
```

**`users = 0`.** Cumple la opción (a) de C-3: no hay con qué loguearse, no hay hash que
craquear, no hay correo `dev@pachaviva.test`. Y están las tablas `sessions` y `cache`, que
es lo que necesita el `.env` de §4 con `SESSION_DRIVER=database` y `CACHE_STORE=database`.

**Control negativo (mi comprobación tiene que poder fallar):** copié el archivo, inserté a
mano un usuario `control@example.test` con `is_admin=1`, y la misma consulta sobre la copia
devolvió `usuarios = 1` → **FALLA**. La compuerta distingue el caso roto. La copia se borró
en el mismo script (verificado en la salida).

**El residuo del 1-sep es, efectivamente, otro archivo y no debe subirse:**
`database/database.sqlite`, 94.208 bytes, md5 `baf131ba534860533d8cd4b5ab8b9295`, 9 tablas,
`migrations = 3`, **ninguna tabla de la app**. La prueba más limpia de que es anterior al
lote: mi consulta `SELECT id,email,is_admin FROM users` **abortó con
`no such column: is_admin`** — ese archivo ni siquiera tiene la columna que gobierna
`canAccessPanel()`. La caracterización del documento es correcta.

Correcciones de detalle (no cambian el veredicto): el documento dice "21 tablas"; son **20**
(21 es el número de filas en `migrations`). Y dice "17 archivos modificados"; hoy son 18 + 2
sin trackear — la evidencia que de verdad sostiene su §2.3 es el md5 del `.env`, que sí
cuadra.

**Observación fuera de mi rol, derivada a `deployer` y `anyerson-qa`:** `tour_images = 0`.
`DemoCatalogImageSeeder` no inserta ni una fila en esa tabla (verificado: cero coincidencias
de `tour_images`/`TourImage` en el seeder), y `resources/views/tours/show.blade.php:24-25`
cae a `PlaceholderImage::svg(...)` cuando la galería viene vacía. Es decir: **las fichas de
tour de la demo saldrían con placeholders**, no con las fotos que `client-validator` vio
contra la base local. No es un asunto de seguridad y no lo juzgo yo; lo señalo porque se
descubre justo al verificar C-3 y porque cambia lo que la clienta va a ver.

---

## 8.3 [CRÍTICO] El bloque de Basic Auth de §5.1 no protege el panel — y además desactiva el deny de archivos

Este es el hallazgo central de la re-verificación. Son **dos defectos independientes en el
mismo bloque**, los dos medidos y los dos aislados con su control.

### Medición 1 — el `.htaccess` de §5.1, literal, sobre Apache 2.4.54 con `mod_access_compat`

```
CONTROL /tour-word/es                        200   <- tanda válida
/tour-word/database/database.sqlite          403   ok
/tour-word/database/database.sqlite-wal      403   ok
/tour-word/database/                         403   ok
/tour-word/storage/logs/laravel.log          403   ok
/tour-word/wp-login.php                      403   ok
/tour-word/.env                              200   <-- NO deniega
/tour-word/composer.lock                     200   <-- NO deniega
/tour-word/admin           SIN credenciales  200   <-- NO pide nada
/tour-word/admin/login     SIN credenciales  200   <-- NO pide nada
/tour-word/livewire/update SIN credenciales  200   <-- NO pide nada
```

### Defecto A — `E=` puesto por `RewriteRule` llega tarde para `Deny from env=`

```apache
RewriteCond %{REQUEST_URI} ^/tour-word/(admin|livewire)(/|$) [NC]
RewriteRule ^ - [E=PROTEGIDO:1]
...
Deny from env=PROTEGIDO
```

`mod_rewrite` en contexto *per-directory* (`.htaccess`) corre en la fase **fixup**, que es
**posterior** al control de acceso. Cuando `Deny from env=PROTEGIDO` se evalúa, la variable
todavía no existe. Resultado: la condición nunca se cumple, `Satisfy any` + `Allow from all`
concede el acceso, y **el panel de Filament queda expuesto a Internet exactamente como
describe A-2**, que es lo que este bloque venía a cerrar.

**Aislamiento (prueba de causalidad, no correlación):** sustituí *solo* esas dos líneas por
`SetEnvIf Request_URI "^/tour-word/(admin|livewire)(/|$)" PROTEGIDO=1` — `mod_setenvif` en
`.htaccess` corre en `header_parser`, **antes** del control de acceso — dejando el resto del
archivo idéntico:

```
/tour-word/es   (control)              200
/tour-word/admin  SIN credenciales     401   <-- ahora sí
/tour-word/admin  CON credenciales     200
```

### Defecto B — `Satisfy any` + `Allow from all` anula el `Require all denied` del `<FilesMatch>`

Con el bloque de autenticación presente, `.env` y `composer.lock` dan `200`. Quitando **solo**
las líneas `AuthType/AuthName/AuthUserFile/Require valid-user/Satisfy any/Order/Allow/Deny`
y sin tocar nada más:

```
/tour-word/es   (control)   200
/tour-word/.env             403   <-- vuelve a denegar
/tour-word/composer.lock    403   <-- vuelve a denegar
```

`Satisfy any` convierte la autorización del directorio en un OR: el `Allow from all` del
final del archivo **satisface** la petición y deja sin efecto el `Require all denied` que el
`<FilesMatch>` de arriba aplica a `.env`, `composer.lock`, `*.sqlite`, `*.log`, etc. Es
decir: **el artefacto de A-2 desactiva la capa 1 de C-1.** Dos remedios que, juntos, se
anulan. Ninguno de los dos se ve leyendo el archivo; los dos se ven ejecutándolo.

Atenuante honesto: en la medición, `/tour-word/.env` devuelve `200` pero **con el cuerpo del
front controller**, no con el contenido del `.env` — la reescritura a `public/` lo intercepta
y en producción eso sería la página 404 de Laravel. O sea: hoy no hay fuga demostrada de
contenido. Pero la capa que este informe exigió *verificar por HTTP* no está funcionando, y
queda una sola barrera (la reescritura a `public/`) donde el diseño pedía dos. Eso no cumple
C-1.

### Defecto C — `Satisfy`/`Order`/`Allow`/`Deny` sin `<IfModule>`: 500 en todo el subdirectorio

Sobre la instancia **sin** `mod_access_compat`, con el mismo archivo:

```
/tour-word/es       500
/tour-word/.env     500
/tour-word/admin    500
error log: [core:alert] .../tour-word/.htaccess: Invalid command 'Satisfy',
           perhaps misspelled or defined by a module not included in the server configuration
```

Son directivas de Apache 2.2 que en 2.4 viven en un módulo **opcional**. El resto del archivo
sí envuelve sus directivas en `<IfModule>`; estas cuatro no. Si el anfitrión no lo carga, la
demo devuelve `500` en todas sus URLs. Acotación importante para calmar el peor miedo: este
`.htaccess` vive en `/public_html/tour-word/`, así que **el 500 no alcanza a
`limaviewtours.com`** — el sitio de Leo no se cae por esto. Cae la demo, entera.

### Remediación — bloque corregido, **probado**, no propuesto

Reemplazar íntegro el tramo que va desde `<IfModule mod_rewrite.c>` (el del `E=PROTEGIDO`)
hasta `Deny from env=PROTEGIDO` por esto. No usa ni una directiva de 2.2:

```apache
# Panel de administracion: HTTP Basic delante del login de Filament.
# SetEnvIf (fase header_parser) y NO RewriteRule [E=]: el E= de un .htaccess
# se fija en la fase fixup, DESPUES del control de acceso, y no llega a tiempo.
<IfModule mod_setenvif.c>
    SetEnvIf Request_URI "^/tour-word/(admin|livewire)(/|$)" PACHAVIVA_PROTEGIDO=1
</IfModule>
<IfModule mod_auth_basic.c>
    AuthType Basic
    AuthName "Pacha Viva - demo privada"
    AuthUserFile /home/<usuario_cpanel>/tour-word-secrets/.htpasswd
    <IfModule mod_authz_core.c>
        <RequireAny>
            Require expr "%{ENV:PACHAVIVA_PROTEGIDO} != '1'"
            Require valid-user
        </RequireAny>
    </IfModule>
</IfModule>
```

Medido con este bloque en su sitio, **en las dos instancias** (con y sin
`mod_access_compat`), y en la misma tanda que su control positivo:

```
                                          8791 (con compat)   8792 (sin compat)
CONTROL /tour-word/es                            200                 200   <- ya no hay 500
/tour-word/.env                                  403                 403   <- deny restaurado
/tour-word/composer.lock                         403                  -
/tour-word/database/database.sqlite              403                  -
/tour-word/storage/logs/laravel.log              403                  -
/tour-word/wp-admin/                             403                  -
/tour-word/admin            SIN credenciales     401                 401
/tour-word/admin            CON credenciales     200                  -
/tour-word/livewire/update  SIN credenciales     401                  -
X-Robots-Tag presente en /tour-word/es            sí                  -
X-Robots-Tag presente en la respuesta 403         sí                  -   <- el "always" hace su trabajo
```

Un detalle que conviene no "mejorar": bloquear `/livewire` **no** rompe el sitio público.
Verificado: no hay ni un `@livewire`, `livewireScripts` ni `Livewire::` en `resources/views/`
ni en `routes/`; la interactividad pública es Alpine compilado por Vite. Livewire solo lo usa
el panel de Filament, que es justo lo que queremos detrás de la contraseña.

---

## 8.4 A-3 y §5.2 — La réplica de bloqueos y la línea en el archivo vivo

Simulé el `.htaccess` del anfitrión (bloqueos + exclusión + catch-all a
`/limaprogramacion/public/`) para medir tres cosas que el documento afirma.

**(1) La réplica no es opcional: sin ella el subdirectorio queda descubierto.** Con el
bloqueo anti-WordPress presente **solo** en el archivo del anfitrión y borrado del hijo:

```
/tour-word/wp-includes/x.php          200   <-- el bloqueo del anfitrion NO lo cubre
/limaprogramacion/wp-includes/x.php   403   <-- control: ahi si bloquea
```

Y esto ocurre **con la exclusión colocada donde manda el documento**. La razón no es la
bandera `[L]`: es que el `.htaccess` hijo declara su propio `RewriteEngine On`, y en Apache
las reglas de reescritura *per-directory* del hijo **sustituyen** a las del padre salvo
`RewriteOptions Inherit`. Conclusión: **A-3 estaba bien diagnosticado y la réplica de
§5.1(a)-(d) es lo que de verdad protege.** Bien resuelto.

**(2) El hueco:** §5.1 replica anti-WordPress, rutas de CMS/instaladores, filtro de
user-agent y métodos HTTP — pero **no el bloqueo por IP** que el propio documento dice que
existe en el archivo del anfitrión (§5.2 lo nombra: "bloqueo de IPs"). Por lo medido en (1),
ese bloqueo tampoco cubrirá `/tour-word/`. No es bloqueante, pero es un ítem de A-3 que queda
sin replicar. **Remedio, una vez se tenga el archivo vivo delante (paso 2 de §9):** copiar al
`.htaccess` del subdirectorio los `Require not ip` / `Deny from` de IPs que haya en el del
anfitrión, envueltos en su `<IfModule>`.

**(3) La exclusión de §5.2 es inofensiva y su ubicación está bien razonada.** Medido: con la
exclusión después de los bloqueos, `/tour-word/es` sirve la demo y `/` sigue yendo al
anfitrión; y **sin la línea de exclusión el subdirectorio también funciona** (por la misma
sustitución padre/hijo de (1)). Es decir: la línea es cinturón y tirantes, no el mecanismo.
Eso baja mucho el riesgo de tocar el archivo del otro cliente, que era la preocupación
grande. Mantener igualmente la instrucción de ponerla **después** de todos los bloqueos e
inmediatamente antes del catch-all: es la posición conservadora y correcta si algún día el
hijo pierde su `.htaccess`.

La línea en sí (`RewriteRule ^tour-word(/|$) - [L]`) es sintácticamente correcta: patrón
anclado, sustitución `-`, una sola bandera. No abre ningún hueco en los bloqueos del
anfitrión siempre que vaya donde el documento dice.

**Aviso para quien edite ese archivo:** no cambiar `[L]` por `[END]`. `[END]` detendría
también el procesamiento del `.htaccess` del subdirectorio y rompería la demo.

**Sobre el filtro de escáneres y el humo post-despliegue:** confirmado, la lista no bloquea
las sondas propias. Medido en el laboratorio: `UA sqlmap → 403`, `UA curl → 200`,
`UA vacío → 200`. El documento decía haberlo tenido en cuenta y es cierto.

**Resto de la sintaxis de §5.1, directiva por directiva:** `<FilesMatch>`, los cuatro
`<IfModule>` y el `<IfModule mod_headers.c>` están correctamente abiertos y cerrados; las
expresiones regulares están bien formadas (las verifiqué ejecutándolas, no leyéndolas: el
servidor arrancó y sirvió sin un solo `Invalid command` salvo el `Satisfy` de §8.3-C); el
orden es el correcto — deny por nombre y por directorio **antes** de la reescritura a
`public/`, y esa reescritura al final con su `RewriteCond` anti-bucle. La cobertura de
directorios es completa: comparé la lista `(app|bootstrap|config|database|docs|lang|
node_modules|resources|routes|scripts|storage|tests|vendor|.git|.claude|.github)` contra el
listado real de la raíz del repo y no falta ninguna carpeta; de los archivos sueltos de la
raíz (`README.md artisan composer.json composer.lock package-lock.json package.json
phpunit.xml vite.config.js`) todos están cubiertos por el `<FilesMatch>`. La nota sobre
`.*\.sqlite.*` para cubrir `-wal`/`-shm` es correcta y la comprobé: `database.sqlite-wal`
también da `403`.

---

## 8.5 A-1, A-4, M-2 y M-1 (capa servidor): **cerrados**

Revisión del `.env` de §4 contra lo que exigí en §4.1, §4.3 y §4.4:

| Exigido | En el artefacto | |
|---|---|---|
| `APP_ENV=production` | sí | ok |
| `APP_DEBUG=false` | sí | ok |
| `APP_KEY` nueva, no la local | marcador explícito `GENERAR_UNA_NUEVA...` + punto 1 "no negociable" | ok |
| `EXTRA_TRUSTED_HOSTS` con el host real | `www.limaviewtours.com,limaviewtours.com` | ok |
| `SESSION_PATH=/tour-word` | sí | **ok — A-4 cerrado** |
| `SESSION_COOKIE` propio | `pachaviva_demo_session` | **ok — A-4 cerrado** |
| `MAIL_MAILER=log` | sí | ok |
| `CONTACT_NOTIFY_EMAIL` sin declarar | sí, y explicado | **ok — M-2 cerrado** |
| `IS_STAGING_MIRROR=true` | sí | ok, con la salvedad de §8.6 |

A-4 era el hallazgo que podía romper el sitio del cliente ajeno con tráfico normal y sin
atacante. Con `SESSION_PATH=/tour-word` la cookie `XSRF-TOKEN` de la demo deja de emitirse
en path `/`, y con `SESSION_COOKIE=pachaviva_demo_session` tampoco choca el nombre de la
cookie de sesión. Las dos mitades están. **A-4 cerrado.**

`SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`,
`SESSION_ENCRYPT=true` y `LOG_LEVEL=error` son añadidos correctos que yo no había pedido.

`X-Robots-Tag "noindex, nofollow, noarchive"` está con `always` y **medido sobrevive a una
respuesta de error** (aparece igual en el `403` de `/tour-word/.env`), que es exactamente lo
que M-1 necesitaba de una capa que no dependa de PHP. Queda dentro de
`<IfModule mod_headers.c>`: si el anfitrión no cargara `mod_headers`, desaparece **en
silencio**. No lo puedo descartar desde aquí → va como comprobación de humo obligatoria
(§8.8, punto 3), que el documento ya tenía planteada bien, probando también una URL que no
existe.

Observación menor, no bloqueante: `SESSION_DOMAIN=www.limaviewtours.com` deja la cookie
atada al host con `www`. Si alguien entra por el ápex sin `www` y el hosting no redirige, la
sesión no se establece. Es lo correcto desde seguridad (acota más); solo hay que saberlo si
aparece un "el formulario dice 419".

---

## 8.6 Lo que quedó resuelto "de palabra", sin artefacto

El encargo me pedía decirlo explícitamente. Son cuatro:

1. **[ALTO] El `.htpasswd` de A-2 no existe y no hay procedimiento para crearlo.** El
   `.htaccess` apunta a `/home/<usuario_cpanel>/tour-word-secrets/.htpasswd` y el documento
   nunca dice cómo se genera, con qué usuario, con qué contraseña ni por dónde se sube. Si
   ese archivo no existe cuando la directiva se activa, Apache responde `500` en las URLs
   protegidas. En cPanel el camino sin shell es **Directory Privacy** sobre la carpeta, que
   escribe el `.htpasswd` y su propia sección de auth; si se usa eso, hay que quitar el
   bloque de §8.3 para no duplicarlo. Sin este artefacto, A-2 no se puede dar por cerrado
   aunque el bloque corregido sea correcto.
2. **[MEDIO] El hueco de `DB_DATABASE` es aceptable. El gemelo silencioso de ese hueco, en
   el `.htaccess`, no lo es.** *(Punto reescrito: `06-deploy.md` cambió a las 14:56, mientras
   yo lo revisaba — ver la nota al final de §8.0. Releí §3, §4, §8 y §9 en su versión nueva.)*
   El sondeo de FTP ya se hizo y confirma que la cuenta **sí alcanza el `home`**: C-2 por
   Opción A es viable y la Opción B queda descartada con motivo. Lo que queda abierto es solo
   el nombre de usuario exacto, y el documento lo deja como
   `DB_DATABASE=RUTA_ABSOLUTA_PENDIENTE_DE_CONFIRMAR_CON_PWD_VER_COMANDO_1_DE_SECCION_9`.
   **Dictamen: dejarlo así es correcto y es la decisión que yo habría pedido.** Es un
   marcador ruidoso que **falla cerrado**: con ese valor Laravel no arranca ("unable to open
   database file"), así que es imposible publicar sin darse cuenta. Lo contrario —adivinar un
   `/home/<algo>/`— sí sería peligroso, porque un fallo silencioso ahí podría acabar con la
   base dentro del docroot, que es exactamente lo que C-2 vino a impedir.
   **Pero el mismo hueco aparece una segunda vez y ahí no es ruidoso:**
   `AuthUserFile /home/<usuario_cpanel>/tour-word-secrets/.htpasswd` en §5.1. Ese
   `<usuario_cpanel>` no está marcado como pendiente, va dentro de un bloque que se pega tal
   cual, y **no falla igual**: con el bloque original de §8.3 la autenticación no se dispara
   nunca, así que una ruta inválida no produce ningún error visible — el panel simplemente
   queda abierto. Dos huecos del mismo dato, uno que grita y otro que calla. **Remedio:
   resolver los dos con la misma salida del comando `PWD`, en el mismo paso, y no pegar el
   `.htaccess` con `<usuario_cpanel>` literal.** Con el bloque corregido de §8.3 el
   comportamiento pasa a ser el sano: si el `.htpasswd` no existe o la ruta es falsa, `/admin`
   responde `500` y nadie entra.
3. **[MEDIO] El runner de `config:cache` no es alcanzable donde el documento lo coloca.**
   §7b dice ponerlo en `/public_html/tour-word/deploy-runner-<token>.php`. Pero el propio
   `.htaccess` de §5.1 reescribe **todo** lo que no empieza por `public/` hacia `public/`.
   Medido en el laboratorio: pedir `/tour-word/deploy-runner-tok123.php` sirve el archivo que
   está en `public/`, y si solo existe el de la raíz la petición cae al front controller
   (404 de Laravel). Consecuencia real: el operador cree que el token está mal, y si desiste,
   **`config:cache` no se ejecuta** — que es justo el paso del que depende que
   `IS_STAGING_MIRROR=true` tome efecto (M-1, capa PHP). **Remedio:** el runner va en
   `/public_html/tour-word/public/`, y sus `require` pasan a `__DIR__.'/../vendor/autoload.php'`
   y `__DIR__.'/../bootstrap/app.php'`.
   Nota aparte: descarté un falso defecto propio — sospeché que `route:cache` fallaría por
   las rutas con closure de `routes/web.php` (líneas 38, 45 y 73). Lo ejecuté: **`Routes
   cached successfully`**. Laravel serializa esos closures. No es un problema; lo dejo escrito
   para que nadie lo vuelva a levantar.
4. **[BAJO] El token del runner viaja en la query string.** Queda escrito en los logs de
   acceso del anfitrión, que son de otro cliente y los puede leer quien tenga su cPanel. Dado
   que el runner se borra en el mismo minuto es aceptable, pero si se puede, mandarlo por
   cabecera (`curl -H "X-Deploy-Token: ..."` y `$_SERVER['HTTP_X_DEPLOY_TOKEN']`) sale gratis.
   Y comparar con `hash_equals()`.

---

## 8.7 [MEDIO, abierto] M-3 — La CSP tal como está escrita rompe el sitio

La cabecera de §5.1 no es solo de más o de menos: en dos puntos **contradice lo que el
proyecto carga**. Y es un fallo que **ninguna de las sondas `curl` de §9 puede detectar**,
porque todas devuelven `200` con el HTML correcto; lo que se rompe pasa en el navegador.

1. **`script-src 'self' 'unsafe-inline'` sin `'unsafe-eval'` mata Alpine.** El proyecto usa
   la build estándar de Alpine (`resources/js/app.js:2` → `import Alpine from 'alpinejs'`,
   `package.json:18` → `alpinejs ^3.17.1`), que evalúa cada expresión `x-data`/`x-on` con
   `new AsyncFunction(...)`. Verificado en el bundle que se va a subir:
   `public/build/assets/app-CjmmD5Mk.js` contiene `AsyncFunction`. Con esta CSP el navegador
   bloquea esa evaluación y **se cae toda la interactividad**: el slider del hero, la
   galería, el menú móvil y el selector de moneda (`Alpine.store('currency')`). El comentario
   del artefacto ("sin `unsafe-eval`: Alpine va compilado por Vite") confunde *compilado* con
   *CSP-safe*: compilar con Vite no cambia el evaluador.
   **Dos salidas:** añadir `'unsafe-eval'` a `script-src` (lo pragmático para una demo; se
   pierde parte del valor de la CSP) **o** migrar a la build CSP de Alpine (`@alpinejs/csp`),
   que exige reescribir las expresiones inline como métodos — trabajo de
   `maquetador-frontend`, fuera del alcance de un despliegue.
2. **`style-src`/`font-src` permiten `https://fonts.bunny.net`, que este proyecto no usa.**
   El layout carga Google Fonts: `resources/views/components/layout.blade.php:197-199`
   (`fonts.googleapis.com`, `fonts.gstatic.com`). Con esta CSP la hoja de estilos de las
   fuentes queda bloqueada y el sitio se ve con tipografías de sistema — justo lo que el lote
   1 vino a construir. Corregir a `https://fonts.googleapis.com` / `https://fonts.gstatic.com`,
   o autoalojar las fuentes (preferible: `public/fonts/` ya existe).

El resto de la cabecera (`nosniff`, `SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`,
`object-src 'none'`, `base-uri 'self'`, `form-action 'self'`, `frame-ancestors 'self'`) es
correcto y cierra M-3 en lo demás. `img-src 'self' data:` es suficiente: las únicas URLs
externas de las vistas son enlaces (`wa.me`, `google.com/maps/search/`), no subrecursos, y no
hay ni un `<iframe>`.

---

## 8.8 Condiciones de ejecución para el humo post-despliegue

Estas son cosas que **solo** se pueden verificar con el sitio arriba. No son pendientes
vagos: cada una tiene su criterio de fallo. Van **además** del checklist de §9 de
`06-deploy.md`, que en general está bien armado — y en particular su comprobación 8
(`/tour-word/admin` debe dar `401`) es la que habría cazado el defecto de §8.3 en producción.
Mérito suyo; el problema es que lo habría cazado *después* de publicar.

1. **El panel pide credenciales.** `/tour-word/admin` → `401`. Un `200` o un `302` al login
   de Filament significa que el bloque sigue siendo el original de §5.1. **Rollback, no "lo
   vemos luego".**
2. **El deny por nombre volvió a funcionar.** `/tour-word/.env` y `/tour-word/composer.lock`
   → `403`, con `/tour-word/es` → `200` en la misma tanda como control. Si dan `200`/`404` en
   vez de `403`, el `Satisfy any` sigue vivo en el archivo.
3. **`X-Robots-Tag` en las dos respuestas**, la que existe y la que no (ya está en §9-7). Si
   falta en la de error, `mod_headers` o el `always` no están haciendo efecto.
4. **No hay `500` en el subdirectorio.** Cualquier `500` uniforme en todas las URLs de
   `/tour-word/` apunta a una directiva no soportada en el `.htaccess`: pedir el `error_log`
   de cPanel y buscar `Invalid command`.
5. **Prueba en navegador real, no con `curl`** (esto es lo que ninguna sonda cubre): abrir
   `https://www.limaviewtours.com/tour-word/es`, abrir la consola, y confirmar que **no hay
   violaciones de CSP** y que el slider del hero avanza y la galería abre. Si la consola
   muestra `Refused to evaluate a string as JavaScript`, es el punto 1 de §8.7.
6. **Las fotos del catálogo cargan.** Las imágenes de demo viven en el disco público
   (`storage/app/public/destinations/...`, servidas por el symlink `public/storage`). Un
   symlink no suele viajar por FTP. Si las imágenes salen rotas, la solución es recrear el
   enlace o copiar los archivos a `public/storage/` — **nunca** aflojar la regla que deniega
   `storage/`. Medido: `/tour-word/public/storage/destinations/foto.jpg` → `200` y
   `/tour-word/storage/app/public/foto.jpg` → `403`; así debe quedar.
7. **El sitio del anfitrión sigue igual** (§9-9, ya previsto) y una reserva de prueba real en
   `limaviewtours.com` desde el navegador, que es donde se vería una colisión de cookies que
   no se hubiera cerrado.

---

## 8.9 Veredicto de la segunda pasada

# RECHAZADO

No por falta de trabajo — el paquete de `06-deploy.md` está bien construido y cierra C-3,
A-1, A-4, M-2, la capa de servidor de M-1 y lo esencial de A-3, con verificaciones que
pueden fallar. Se rechaza porque **dos artefactos se anulan entre sí y eso deja abiertos los
dos ítems críticos que motivaron el bloqueo**:

1. **A-2 sigue abierto:** el panel de administración responde sin credenciales. Medido.
2. **C-1 sigue abierto:** el deny por nombre de archivo está desactivado por el bloque de
   A-2. Medido.
3. **M-3 sigue abierto:** la CSP, tal como está, rompe Alpine y las fuentes del sitio.
4. Y hay riesgo de `500` en todo el subdirectorio (no en el sitio del anfitrión) si el
   hosting no carga `mod_access_compat`. Medido.

**Qué falta exactamente, para que nadie tenga que interpretar:**

- [ ] Sustituir en `06-deploy.md` §5.1 el tramo `<IfModule mod_rewrite.c>` … `Deny from env=PROTEGIDO`
      por el bloque corregido de §8.3 de este informe. *(dueño: `deployer`)*
- [ ] Corregir la CSP: `'unsafe-eval'` en `script-src` y `fonts.googleapis.com` /
      `fonts.gstatic.com` en `style-src` / `font-src`. *(dueño: `deployer`; si se prefiere la
      build CSP de Alpine, es `maquetador-frontend` y no entra en este despliegue)*
- [ ] Crear el `.htpasswd` y escribir en el documento dónde queda y cómo se generó, o decidir
      que se usa Directory Privacy de cPanel y quitar el bloque de auth del `.htaccess`.
      *(dueño: `deployer`)*
- [ ] Resolver el `<usuario_cpanel>` del `AuthUserFile` de §5.1 con la misma salida del
      comando `PWD` que completa `DB_DATABASE`, y no pegar el `.htaccess` con el marcador
      literal. El hueco de `DB_DATABASE` está bien como está (falla cerrado); el del
      `.htaccess` falla en silencio. *(dueño: `deployer`)*
- [ ] Mover el runner de §7b a `public/` y ajustar sus dos `require`. *(dueño: `deployer`)*
- [ ] Replicar en el `.htaccess` del subdirectorio los bloqueos por IP del anfitrión, una vez
      se tenga el archivo vivo delante. *(dueño: `deployer`)*

Con esos seis puntos resueltos sobre el papel, el resto es ejecución: las siete
comprobaciones de §8.8 deciden en el servidor real y se corren en menos de cinco minutos
después de publicar. No hace falta una tercera pasada de auditoría si esos seis cambios se
aplican tal como están escritos aquí; sí hace falta que **alguien ejecute §8.8 y deje el
resultado por escrito**, porque LiteSpeed no es Apache y lo que medí lo medí en Apache.

*Fin de la re-verificación.*

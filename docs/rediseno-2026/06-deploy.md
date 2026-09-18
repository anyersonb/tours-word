# 06 · Deploy — Pacha Viva demo en `www.limaviewtours.com/tour-word/`

**Agente:** `deployer` · **Fecha:** 2026-09-18 · **Rama:** `lote-1-sistema-diseno`
**Estado del documento:** COMPLETO como preparación, tras dos rondas de re-verificación
de `security-engineer`. Ver "Estado de las seis correcciones" y "Pendientes" al final:
cinco de seis correcciones aplicadas y verificables, una (réplica del bloqueo por IP)
declarada como abierta porque depende de un archivo que no puedo leer en esta sesión.

**No se subió nada. No se hizo commit. Todo lo de abajo es preparación y verificación
en local (`G:\laragon\www\tours-word`), más el paquete exacto para que Anyerson lo
ejecute él mismo.**

---

## Fase 0 — Gate de entrada

| Gate | Estado |
|---|---|
| `cro-validator` | APTO (`01-cro-contrato.md`) |
| `anyerson-qa` | APTO, 343/343 tests (`03-qa.md`) |
| `security-engineer` | **RECHAZADO, segunda pasada** (`04-seguridad.md` §8, veredicto en §8.9) — re-verificó este mismo documento montando dos Apache 2.4 reales y midió que el bloque de auth original no protegía el panel y que la CSP rompía el sitio (Alpine y las fuentes). Entregó seis correcciones puntuales, dueño `deployer` en las seis. **Este documento ya las incorpora** — ver "Estado de las seis correcciones" más abajo: cinco aplicadas y verificables, una declarada pendiente. El veredicto formal sigue en RECHAZADO hasta que alguien corra el checklist de §9 sobre el servidor real (LiteSpeed, no Apache) y lo deje por escrito — eso no lo puedo hacer yo desde aquí. |
| `client-validator` | OK con reservas (`05-cliente.md`) — reserva del parallax cerrada por Anyerson (queda como está); reserva de teléfono/correo de contacto es contenido pendiente de la clienta, **no bloquea el deploy** |

**Conclusión de la Fase 0:** no hay un "PUBLICADO" posible en esta sesión — el harness
bloquea subida FTP y el encargo prohíbe explícitamente subir nada. Lo que sigue es
preparación + verificación local + el paquete de comandos para que Anyerson decida y
ejecute. `security-engineer` ya reabrió `04-seguridad.md` sobre los artefactos de este
documento y dijo explícitamente que no hace falta una tercera auditoría si las seis
correcciones se aplican tal como quedaron escritas — lo que queda es que alguien ejecute
el checklist de §9 en el servidor real y deje el resultado por escrito. No es mi rol
autocertificar esa ejecución; sí es mi rol dejar el material listo y verificable.

---

## 1. La base SQLite — cuál es cuál (aviso del PM, resuelto)

**Hay dos archivos SQLite en juego. No son intercambiables:**

| Archivo | Qué es | ¿Se sube? |
|---|---|---|
| `G:\laragon\www\tours-word\database\database.sqlite` (94.208 bytes, 1-sep-2026) | Residuo previo al lote. Solo tablas de esqueleto de Laravel (`cache`, `jobs`, `sessions`, `users`, etc.), **ninguna tabla de la app** (`tours`, `destinations`, `settings`...). Cubierto por `database/.gitignore`. | **NO. Bajo ninguna circunstancia.** No se toca, no se sube, no se referencia en el `.env` de despliegue. |
| `pachaviva-demo.sqlite`, **regenerado** el 18-sep-2026 15:21 (192.512 bytes, md5 `429d37e0be95e53f237d3d8163dbddea`), en el scratchpad de esta sesión, fuera del repo | Base nueva, `migrate:fresh --seed` completo contra PHP 8.2.1 real, **con el seeder `DemoCatalogImageSeeder` ya corregido por `backend-laravel`** (los slugs de los 3 tours cambiaron el 14-sep y el seeder buscaba los viejos — corregido). Contenido de demo completo, incluidas las 14 fotos de galería. **Verificada tabla por tabla abajo, incluido `tour_images`.** | **SÍ — este es el archivo que se sube**, y solo después de copiarlo a la ruta fuera del docroot del servidor (§3). |

**Aviso sobre una versión anterior de este mismo archivo, para que no se confunda:** existió
una primera versión de `pachaviva-demo.sqlite`, construida a las 14:38, **md5
`e75b09025ddd69f3d764e2561de8d8f9`** — ese es el hash que quedó citado en
`04-seguridad.md` §8.2, porque `security-engineer` verificó esa versión antes de que se
descubriera el defecto de abajo. **Esa versión quedó descartada**: tenía `tour_images = 0`
(las fichas de tour habrían salido con placeholders en vez de fotos) por el problema de
slugs que se explica en §2.1. **Si alguien verifica el archivo que va a subir contra el
hash `e75b09...`, está verificando el archivo equivocado — el correcto hoy es
`429d37e0be95e53f237d3d8163dbddea`.**

Ruta local del archivo que sí se sube (se regenera bajo demanda, ver §2 para el
procedimiento reproducible):

```
C:\Users\USUARI~1\AppData\Local\claude\C--Users-USUARIO-WEBTILIA\3490999d-273c-4dbd-a2c3-a67991cba4e3\scratchpad\pachaviva-demo.sqlite
```

Este archivo vive en el scratchpad de la sesión, no en el repo. Antes de la subida real,
Anyerson debe volver a generarlo con el procedimiento de §2 (es determinista y barato:
~1 segundo) para tener una copia fresca en su propia máquina, y volver a correr la
compuerta de verificación completa (incluido `tour_images`) antes de subirlo — no reusar
un archivo de una sesión de IA anterior sin volver a verificarlo, y **no confiar en el
md5 de una corrida vieja**: el md5 cambia cada vez que cambia el contenido de la demo o
el seeder que la construye, como acaba de pasar aquí.

---

## 2. Construcción y verificación de la base SQLite (C-3) — REGENERADA en esta sesión

### 2.1 Por qué existe una segunda corrida, y qué cambió

La primera versión de este archivo (md5 `e75b09025ddd69f3d764e2561de8d8f9`, la que
`security-engineer` verificó en `04-seguridad.md` §8.2) tenía `tour_images = 0`. Causa,
diagnosticada por `backend-laravel`: el 14-sep-2026 se renombraron los slugs de los 3
tours y `DemoCatalogImageSeeder` seguía buscando los slugs viejos — al no encontrarlos, el
seeder **no hace nada y no avisa** (es defensivo por diseño). En local no se notaba porque
esa base de desarrollo nunca se había vuelto a sembrar desde cero; mi `migrate:fresh --seed`
fue la primera corrida limpia y expuso el hueco. `backend-laravel` corrigió las tres
cadenas de slug en `database/seeders/DemoCatalogImageSeeder.php` (visible en
`git status` como modificado — no es un archivo que yo haya tocado). Esta sección describe
la corrida **regenerada**, con el seeder ya corregido.

### 2.2 Procedimiento (reproducible)

Binario usado: `G:\laragon\bin\php\php8.2.1\php.exe` (el único PHP 8.2 real disponible en
esta máquina — el del PATH es 8.1.10 y el propio `composer` rechaza correr con él:
*"Your Composer dependencies require a PHP version >= 8.2.0"*. **Usar ese binario, no
`php` del PATH.**

Pasos ejecutados:

1. Respaldo de `.env` local (md5 verificado antes/después, ver §2.4).
2. `.env` temporal (nunca commiteado, nunca subido) con `DB_CONNECTION=sqlite` y
   `DB_DATABASE` apuntando al archivo del scratchpad, `APP_ENV=production`, `APP_KEY`
   recién generada.
3. Se borró el archivo `.sqlite` viejo (el de `tour_images = 0`) antes de regenerar, para
   que no quedara duda de qué corrida produjo el resultado final.
4. `php artisan migrate:fresh --force --seed` — corre las migraciones y los tres
   seeders del proyecto (`SettingSeeder`, `DemoTourSeeder`, `DemoCatalogImageSeeder`, este
   último ya con los slugs corregidos). **No se corrió `dev:ensure-admin` en ningún
   momento** (es justamente el vector que describe C-3 en `04-seguridad.md` §2.3).
5. Restauración del `.env` local original, verificada por md5.

### 2.3 Verificación de contenido — compuerta C-3, ampliada con `tour_images`

```
USUARIOS = 0
TOUR_IMAGES = 14
  camino-inca-machu-picchu-4-dias      = 5
  ruta-de-picanterias-arequipa         = 5
  valle-sagrado-pisac-maras-moray      = 4
```

**La compuerta ya no es solo "usuarios = 0": ahora exige también `tour_images = 14`, con
el desglose por tour.** Es la condición de aceptación que se nos escapó la vez anterior —
un archivo con cero usuarios pero sin fotos igual no es una demo publicable. Los tres
tours y su conteo coinciden exactamente con lo que `backend-laravel` reportó tras su
corrección (5 Camino Inca, 5 Ruta de picanterías, 4 Valle Sagrado).

**Dos controles negativos obligatorios** (que la compuerta pueda fallar, no solo pasar),
los dos sobre una copia — nunca sobre el archivo real:
- **Usuarios:** se insertó un usuario a mano (`control@example.test`, `is_admin=1`) en la
  copia; la consulta devolvió `1`, no `0`. La compuerta detecta el caso roto.
- **`tour_images`:** se borró una fila de `tour_images` en la copia; el conteo bajó de 14
  a `13`. La compuerta también distingue esto — no es un número que salga siempre igual
  sin importar el contenido.

La copia de control se borró en ambos casos después de la prueba; no viaja a ningún lado.

Tablas presentes en el archivo final: **20 tablas** (esqueleto de Laravel + todas las de
la app — `tours`, `destinations`, `experiences`, `settings`, `team_members`,
`contact_messages`, `tour_images`, etc.). Conteo de las tablas de contenido de demo:

| Tabla | Filas |
|---|---|
| `tours` | 3 |
| `destinations` | 3 |
| `experiences` | 3 |
| `settings` | 1 |
| `tour_images` | **14** |
| `team_members` | 0 |
| `contact_messages` | 0 |
| `users` | **0** |

Los 3 tours/destinos/experiencias coinciden con lo que `client-validator` reportó haber
visto y probado en `05-cliente.md` (Camino Inca, Ruta de picanterías Arequipa, Valle
Sagrado) — es la misma base de demo, no una casualidad, y ahora con las fotos que
`client-validator` efectivamente vio (antes de esta corrección, la demo las habría
mostrado en placeholder).

**Dato del archivo para verificar el día de la subida:**

```
Archivo: pachaviva-demo.sqlite
Tamaño:  192.512 bytes
md5:     429d37e0be95e53f237d3d8163dbddea
Fecha:   2026-09-18 15:21
```

**El md5 `e75b09025ddd69f3d764e2561de8d8f9` que aparece en `04-seguridad.md` §8.2
corresponde al archivo descartado (0 fotos) — no usarlo como referencia.** Ver también el
aviso al final de §1.

### 2.4 Verificación de que el `.env` local quedó como estaba

```
md5(.env restaurado)      = 0c11ce728c32b48a3591952510afccb0
md5(.env.local.backup)     = 0c11ce728c32b48a3591952510afccb0
```

Idénticos, verificado también en esta segunda corrida. `git status` tras la restauración
muestra los mismos 18 archivos modificados de antes **más uno nuevo**:
`database/seeders/DemoCatalogImageSeeder.php` (el fix de `backend-laravel`, no mío) — 19
modificados + 2 sin trackear, ninguno perdido ni tocado por mí fuera de ese archivo
externo. El `.env` **nunca está trackeado por git** en este repo (`.gitignore`), así que
el respaldo por fuera del repo (scratchpad) era la única red de seguridad; quedó
verificada dos veces en esta sesión.

### 2.5 Decisión sobre el panel de administración en la demo

**Por defecto, la base se construye con cero usuarios (opción "a" de C-3), no con un
admin.** Motivo: el recorrido de `client-validator` (`05-cliente.md`) prueba todo el
sitio público — portada, listado, ficha, galería, flujo de reserva — y en ningún
momento entra al panel `/admin`. No hay un requisito declarado de mostrar el backoffice
en esta demo. Menos superficie, menos riesgo: si nadie necesita loguearse, que no haya
con qué loguearse.

**Si Anyerson decide que la demo SÍ necesita mostrar el panel**, el procedimiento (C-3
opción b) es: antes de escribir el `.env` de producción sobre este mismo archivo,
en local y con `APP_ENV=local` temporalmente, crear un usuario con correo que **no**
sea `dev@pachaviva.test` (ya es público en el repo) y contraseña generada
(`Str::password(24)`), entregada fuera de banda — nunca la contraseña que el
desarrollador usa en su máquina. Esto no está hecho porque no hay pedido explícito de
mostrarlo; si se pide, es un paso de 2 minutos antes de congelar el archivo.

---

## 3. Alcance del FTP — sondeado y resuelto: **C-2 alcanzable, SQLite fuera del docroot**

Intenté verificar esto yo mismo (**solo lectura**, `curl.exe --list-only`, sin subir ni
borrar nada) y el clasificador del harness me bloqueó incluso la lectura ("Production
Reads"). El PM ejecutó el mismo sondeo de solo lectura desde fuera de esta sesión y trae
el resultado real:

```
.ftpquota  tmp  README.md  perl5  .clwpos  .softaculous  .cagefs  .koality
.cl.selector  test.limaviewtours.com  limaviewtours.com  .subaccounts
public_ftp  web  www  .trash  .dovecot.svbin  .wp-cli  .sitepad  sieve
.bash_profile  .caldav  .imunify_patch_id  .bash_logout
```

**La cuenta `<FTP_USER: fuera del repo>` NO está enjaulada a `public_html`: aterriza en
el `home` completo de cPanel** (se ven `public_ftp`, `.trash`, `.subaccounts`, etc. —
contenido de home, no de un docroot). Esto descarta la lectura conservadora que yo había
adoptado por patrón de nombre de cuenta (§3 original: "casi siempre chroot"), y la
reemplaza por evidencia directa.

**Precisión sobre qué cambia y qué no:** el encargo ya fija la ruta de despliegue en
`/public_html/tour-word/` — **la app (código, `vendor/`, `public/`) se queda ahí**, con
el patrón de `.htaccess` de §5.1 (lo que `04-seguridad.md` llama Opción B para la raíz
de la app). Lo que este sondeo cambia es **solo C-2**: como la cuenta sí alcanza el
`home` completo, **el SQLite no tiene por qué quedarse dentro de `public_html/tour-word/database/`
protegido nada más que por el `.htaccess`** — puede ir a una carpeta propia por encima
del docroot, que es la mitigación más fuerte porque no depende de que ninguna directiva
de Apache se aplique correctamente. El `.htaccess` de §5.1 se mantiene igual como capa
para todo lo demás (deny de `.env`, `composer.lock`, etc. si viajan por FTP dentro de
`public_html`), y la segunda barrera de `database/`/`storage/` queda como redundancia,
no como único candado.

**Opción B para el SQLite, descartada — con el sondeo de arriba como evidencia.** Antes
de este sondeo, la lectura conservadora por defecto era dejar `pachaviva.sqlite` dentro
de `public_html/tour-word/database/`, con el `.htaccess` como único candado (Opción B de
C-2). El listado del FTP demuestra que esa limitación no existe: la cuenta alcanza el
`home` completo, así que no hay motivo para aceptar el riesgo de que una directiva de
Apache falle en silencio cuando la alternativa fuera del árbol web es alcanzable y no
depende de nada. **Queda descartada; el `.env` de §4 y el orden de §8 ya asumen SQLite
fuera del docroot, no la Opción B.**

**Ruta absoluta real: `/home/limaview/`. Resuelta por el PM — con un método distinto al
que este documento proponía, y vale la pena dejarlo anotado.** El listado por FTP muestra
los *nombres* de las carpetas del home, pero no la ruta absoluta del sistema de archivos
que `DB_DATABASE` necesita — por eso el comando 1 de §9 pedía correr `PWD` por FTP. **Ese
comando no sirve en este servidor**: el FTP está enjaulado para el comando `PWD` y
responde `257 "/" is your current location` — la ruta relativa a la jaula, no la del
sistema de archivos real. El dato salió de otra fuente: `/public_html/error_log`, que
registra rutas absolutas del sistema en sus trazas de error. **Queda anotado para quien
repita esto en otro hosting: si `PWD` por FTP responde `"/"`, no confiar en él — buscar la
ruta real en un `error_log` u otro archivo que la revele, no adivinarla.** Con la ruta ya
confirmada, los dos marcadores que dependían de ella (`DB_DATABASE` en §4 y
`AuthUserFile` en §5.1) quedan resueltos más abajo con el valor real.

**Nota de contexto, no accionable ahora:** el listado muestra `test.limaviewtours.com`
como carpeta — la cuenta ya maneja al menos un subdominio. Confirma que mover esta demo
a un subdominio propio (la alternativa que `security-engineer` prefería y Anyerson
descartó a sabiendas) sería técnicamente viable el día que se decida. No cambia el plan
de hoy: sigue siendo subcarpeta, ejecutada con las mitigaciones de este documento.

---

## 4. El `.env` del destino (A-1, C-2, A-4, M-1 capa 1, M-2)

**Se escribe a mano en el servidor. No se sube por FTP** (evita que el archivo local con
`APP_ENV=local` viaje por accidente si alguien arrastra la carpeta completa).

```dotenv
APP_NAME="Pacha Viva"
APP_ENV=production
APP_KEY=base64:GENERAR_UNA_NUEVA_CON_key:generate_--show_NO_REUTILIZAR_LA_LOCAL
APP_DEBUG=false
APP_URL=https://www.limaviewtours.com/tour-word
APP_TIMEZONE=America/Lima
APP_LOCALE=es
APP_FALLBACK_LOCALE=es

EXTRA_TRUSTED_HOSTS=www.limaviewtours.com,limaviewtours.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=sqlite
DB_DATABASE=/home/limaview/tour-word-data/pachaviva-demo.sqlite

SESSION_DRIVER=database
SESSION_COOKIE=pachaviva_demo_session
SESSION_PATH=/tour-word
SESSION_DOMAIN=www.limaviewtours.com
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_ENCRYPT=true

CACHE_STORE=database
QUEUE_CONNECTION=sync

MAIL_MAILER=log
# CONTACT_NOTIFY_EMAIL sin declarar a propósito -- falla cerrado (M-2, ver
# 04-seguridad.md §4.4). NUNCA poner credenciales SMTP del dominio anfitrión aquí.

IS_STAGING_MIRROR=true
```

**`DB_DATABASE` traía un valor deliberadamente no funcional
(`RUTA_ABSOLUTA_PENDIENTE_DE_CONFIRMAR_CON_PWD_VER_COMANDO_1_DE_SECCION_9`), a
propósito, hasta que el PM resolvió la ruta real. Ya está reemplazado arriba por el valor
definitivo.** El sondeo del PM (§3) había confirmado que la cuenta SÍ alcanza el `home`
completo de cPanel — C-2 (SQLite fuera del docroot) viable — pero un listado por FTP no
revelaba la ruta absoluta de sistema de archivos que Laravel necesita, y el comando `PWD`
por FTP (comando 1 de §9) tampoco sirvió: el servidor está enjaulado para ese comando y
responde `257 "/" is your current location"`, la ruta relativa a la jaula, no la real. El
PM la sacó de `/public_html/error_log` (registra rutas absolutas del sistema) — **`/home/limaview/`**.
Con eso: `DB_DATABASE=/home/limaview/tour-word-data/pachaviva-demo.sqlite`, en una carpeta
propia con nombre explícito, fuera de `public_html` y **no en `tmp`** (que cualquier
proceso del panel puede limpiar).

**El mismo marcador, y a propósito el mismo texto literal, aparecía una segunda vez en el
`AuthUserFile` del `.htaccess` de §5.1 — también ya resuelto.** `security-engineer`
encontró en su re-verificación (`04-seguridad.md` §8.6 punto 2) que yo había dejado ese
segundo hueco como `<usuario_cpanel>` sin marcar — y a diferencia de `DB_DATABASE`, con el
bloque de autenticación *original* (el que tenía el defecto de §5.1 ya corregido abajo)
una ruta de `.htpasswd` inválida **no producía ningún error visible: la autenticación
simplemente no se disparaba y el panel quedaba abierto** — un hueco que calla, no uno que
grita. Con el bloque de autenticación ya corregido (ver §5.1), ese comportamiento cambia:
si el `.htpasswd` no existe o la ruta es falsa, `/admin` responde `500` y nadie entra —
pasa a fallar cerrado también. Los dos marcadores se resolvieron con el mismo dato
(`/home/limaview/`), en el mismo paso: `AuthUserFile /home/limaview/tour-word-data/.htpasswd`.

**Puntos que no son negociables, en orden de gravedad:**

1. **`APP_KEY` nueva**, generada con `php artisan key:generate --show` en el propio
   servidor (o localmente con el mismo PHP 8.2 y pegada a mano) — nunca la clave del
   `.env` local. Reutilizarla ata la seguridad de la demo a la máquina de desarrollo.
2. **`SESSION_PATH=/tour-word` + `SESSION_COOKIE` propio (A-4).** Sin esto, la cookie
   `XSRF-TOKEN` de la demo se emite en el mismo origen (`www.limaviewtours.com`) que la
   de Lima View, con el mismo path `/` — el visitante que pase por `/tour-word/` se
   lleva encima el token de Pacha Viva, y el sitio real de Lima View responde **419** en
   sus POST por AJAX (incluido su flujo de reserva). No hace falta atacante: pasa con
   tráfico normal. Esto es lo que puede tumbar el negocio del cliente ajeno, no el
   nuestro.
3. **`DB_DATABASE` con ruta absoluta fuera de `public_html`: `/home/limaview/tour-word-data/pachaviva-demo.sqlite`.**
   Confirmado viable por el sondeo de §3 y resuelto por el PM vía `/public_html/error_log`
   (el `PWD` por FTP no sirvió en este servidor — ver §3). Ya está en el `.env` de arriba.
4. **`MAIL_MAILER=log` y `CONTACT_NOTIFY_EMAIL` sin declarar (M-2).** El formulario
   sigue funcionando (guarda el mensaje, muestra éxito) pero no sale ni un correo. Si en
   cambio se configura SMTP real del dominio anfitrión, cada envío sale con el remitente
   de Lima View y puede quemar su reputación de envío — ver `04-seguridad.md` §4.4.
5. **`IS_STAGING_MIRROR=true` y `config:cache` van en el mismo paso** (§7). QA ya
   reprodujo que cambiar el `.env` sin recachear deja la config vieja en efecto, en
   silencio. La capa que de verdad protege contra esto — porque no depende de PHP ni de
   la caché — es el `X-Robots-Tag` del `.htaccess` en §5.3.
6. **`bootstrap/cache/*.php` va en la lista de exclusión del FTP** (§8). Un
   `config.php` cacheado subido desde la máquina local congelaría `APP_DEBUG=true` y
   `DB_CONNECTION=mysql` sin que ningún cambio del `.env` del servidor tenga efecto.

---

## 5. Los dos `.htaccess`

### 5.1 `/public_html/tour-word/.htaccess` (nuevo, se sube íntegro)

Combina: deny por nombre + por directorio (C-1/§1.4), réplica de bloqueos anti-WordPress
y filtro de escáneres del anfitrión (A-3/§3.4), cabeceras de seguridad (M-3/§4.2),
`X-Robots-Tag` que no depende de PHP (M-1 capa 1/§5.4), y Basic Auth delante del panel
(A-2/§2.4c). **Orden literal, no reordenar:**

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

# --- Replica de los bloqueos del dominio anfitrion, para no depender de su orden ---

# (a) Anti-WordPress y anti-Joomla: este proyecto es Laravel, ninguna de estas
#     rutas existe. Ampliado con las rutas reales del .htaccess vivo del
#     anfitrion (xmlrpc.php, wp-json, administrator/, media/system/,
#     language/en-GB/, elementor, wp-content/plugins/ -- ver nota de SS5.1
#     sobre el origen de esta lista).
RewriteRule (^|/)(wp-login\.php|wp-config\.php|xmlrpc\.php|wp-cron\.php)$ - [F,L,NC]
RewriteRule (^|/)(wp-admin|wp-includes|wp-content|wp-json|wordpress|wp)(/|$) - [F,L,NC]
RewriteRule (^|/)administrator(/|$) - [F,L,NC]
RewriteRule (^|/)media/system/ - [F,L,NC]
RewriteRule (^|/)language/en-GB/ - [F,L,NC]
RewriteRule elementor - [F,L,NC]
RewriteRule (^|/)wp-content/plugins/ - [F,L,NC]

# (b) Rutas de CMS y de instaladores que nadie tiene por que pedir aca.
RewriteRule (^|/)(phpmyadmin|adminer\.php|\.well-known/acme-challenge/\.\.|setup\.php|install\.php|shell\.php|alfa.*\.php)(/|$) - [F,L,NC]

# (c) Filtro de escaneres por user-agent. Lista corta y explicita, ampliada con
#     los user-agents reales del .htaccess vivo del anfitrion (ver nota abajo).
#     NO bloquear por "vacio" ni por "curl", que romperia el humo post-deploy.
RewriteCond %{HTTP_USER_AGENT} (sqlmap|nikto|nmap|masscan|nuclei|acunetix|nessus|zgrab|wpscan|dirbuster|gobuster|feroxbuster) [NC,OR]
RewriteCond %{HTTP_USER_AGENT} (ALittle\ Client|visionheight|nhs-data|fraud-research|Xpanse|Cortex-Xpanse) [NC]
RewriteRule ^ - [F,L]

# (d) Metodos HTTP que esta app no usa nunca.
RewriteCond %{REQUEST_METHOD} ^(TRACE|TRACK|DEBUG)$
RewriteRule ^ - [F,L]

# Panel de administracion: HTTP Basic delante del login de Filament.
# CORREGIDO tras la re-verificacion de security-engineer (04-seguridad.md SS8.3):
# el bloque original usaba RewriteRule [E=PROTEGIDO] + Deny from env=, y NO
# funcionaba -- E= de un .htaccess se fija en la fase fixup, DESPUES del
# control de acceso, asi que "Deny from env=PROTEGIDO" nunca veia la variable.
# Medido: /tour-word/admin devolvia 200 SIN credenciales.
# Ademas "Satisfy any" + "Allow from all" (directivas de Apache 2.2, sin
# <IfModule>) anulaban el "Require all denied" del <FilesMatch> de arriba --
# medido: /tour-word/.env y /composer.lock daban 200 en vez de 403 -- y si el
# host no carga mod_access_compat, esas directivas dan 500 en TODO el
# subdirectorio (medido en Apache sin ese modulo).
# Este bloque reemplaza el anterior integro, no usa ninguna directiva 2.2, y
# esta medido en dos instancias de Apache 2.4 (con y sin mod_access_compat):
# /tour-word/admin sin credenciales -> 401 en las dos; con credenciales -> 200;
# /tour-word/.env -> 403 en las dos; CONTROL /tour-word/es -> 200 en las dos.
<IfModule mod_setenvif.c>
    SetEnvIf Request_URI "^/tour-word/(admin|livewire)(/|$)" PACHAVIVA_PROTEGIDO=1
</IfModule>
<IfModule mod_auth_basic.c>
    AuthType Basic
    AuthName "Pacha Viva - demo privada"
    AuthUserFile /home/limaview/tour-word-data/.htpasswd
    <IfModule mod_authz_core.c>
        <RequireAny>
            Require expr "%{ENV:PACHAVIVA_PROTEGIDO} != '1'"
            Require valid-user
        </RequireAny>
    </IfModule>
</IfModule>

# Bloqueo por IP replicado del anfitrion (A-3, hueco senalado por
# security-engineer en 04-seguridad.md SS8.4 punto 2): el anfitrion SI tiene un
# bloqueo por IP en su .htaccess vivo (SS5.2 lo nombra) y, por el mismo
# mecanismo medido abajo (el .htaccess hijo declara su propio RewriteEngine y
# SUSTITUYE, no hereda, las reglas del padre), ese bloqueo NO cubre
# /tour-word/ salvo que se replique aqui tambien.
# --- Replica del bloqueo por IP del anfitrion (foto del .htaccess vivo, 18/09/2026).
#     NO se sincroniza sola: si Leo agrega IPs a su archivo manana, esta lista
#     se queda atras -- es una foto de un momento, no un espejo en vivo.
RewriteCond %{REMOTE_ADDR} ^20\.100\.191\.218$ [OR]
RewriteCond %{REMOTE_ADDR} ^141\.98\.11\.224$ [OR]
RewriteCond %{REMOTE_ADDR} ^64\.89\.161\.160$ [OR]
RewriteCond %{REMOTE_ADDR} ^188\.39\.109\.162$ [OR]
RewriteCond %{REMOTE_ADDR} ^3\.18\.186\.238$
RewriteRule ^ - [F,L]

# Cabeceras de seguridad (M-3). CSP corregida tras la re-verificacion de
# security-engineer (04-seguridad.md SS8.7): la version anterior rompia el
# sitio sin que ningun curl lo detectara (se rompe en el navegador, no en el
# codigo de respuesta).
# (1) script-src sin 'unsafe-eval' mataba Alpine: el bundle que se sube
#     (public/build/assets/app-*.js) usa AsyncFunction para evaluar x-data/
#     x-on, y CSP sin unsafe-eval lo bloquea -- se cae el slider del hero, la
#     galeria, el menu movil y el selector de moneda. "Compilado por Vite" no
#     es lo mismo que "CSP-safe": la build estandar de Alpine sigue evaluando.
# (2) style-src/font-src apuntaban a fonts.bunny.net, que este proyecto NO
#     usa -- el layout carga Google Fonts (fonts.googleapis.com,
#     fonts.gstatic.com, ver layout.blade.php:197-199). Con el origen viejo,
#     la hoja de fuentes queda bloqueada y el sitio cae a tipografia de
#     sistema.
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data:; frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'"

    # Espejo de demostracion en dominio ajeno: fuera del indice, siempre.
    # No depende de .env, ni de config:cache, ni de que la app arranque.
    # Cubre tambien PDF, imagenes y sitemap.xml, que una meta no alcanza.
    Header always set X-Robots-Tag "noindex, nofollow, noarchive"
</IfModule>

# (3) Recien ahora, reenvio interno del resto a public/
RewriteCond %{REQUEST_URI} !^/tour-word/public/
RewriteRule ^(.*)$ public/$1 [L]
```

**Cómo se comprueba que cada corrección quedó cerrada** (no basta con haberla escrito —
esto es lo que `security-engineer` pidió dejar por escrito en `04-seguridad.md` §8.9, y son
además los comandos 6, 7 y 8 de §9 más una prueba de navegador):

| Corrección | Cómo se comprueba | Resultado que cierra el punto |
|---|---|---|
| 1. Bloque de auth (`SetEnvIf`/`RequireAny`) | Comando 8 de §9: `curl` a `/tour-word/admin` sin credenciales | `401` — no `200` ni `302` al login de Filament |
| 1. (mismo bloque) deny de archivos ya no anulado | Comando 6 de §9: `.env` y `composer.lock` | `403` en la misma corrida donde `/tour-word/es` da `200` de control |
| 1. (mismo bloque) sin directivas 2.2 → sin `500` uniforme | Cualquier URL de `/tour-word/` en la primera visita tras publicar | Si todo `/tour-word/` da `500`, revisar `error_log` de cPanel buscando `Invalid command` — no debería aparecer, el bloque nuevo no usa `Satisfy`/`Order`/`Allow`/`Deny` sueltos |
| 2. CSP no rompe Alpine ni fuentes | **Prueba de navegador real, no `curl`** — abrir `/tour-word/es`, abrir la consola, confirmar que no hay `Refused to evaluate a string as JavaScript` ni fuentes bloqueadas, y que el slider, la galería, el menú móvil y el selector de moneda responden a un clic real | Sin violaciones de CSP en consola y los cuatro componentes interactivos funcionan — ninguna sonda `curl` puede confirmar esto, todas devuelven `200` con el HTML correcto aunque el navegador lo bloquee |
| 6. Bloqueo por IP replicado | Revisión manual: comparar el bloque de IPs copiado aquí contra el del `.htaccess` vivo del anfitrión, línea por línea, después de completarlo en el paso 2 de §8 | Las mismas IPs/rangos bloqueados en ambos archivos — no hay comando `curl` que lo pruebe sin conocer una IP bloqueada real, es una revisión de texto |

Segunda barrera independiente (por si `AllowOverride` no aplica el bloque de arriba, o
el FTP sube un archivo cuyo nombre empieza por punto): un `.htaccess` con **solo** estas
líneas dentro de `/public_html/tour-word/storage/` y de
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

**Nota del patrón `.*\.sqlite.*`:** es a propósito más ancho que `\.sqlite$`, porque
SQLite crea archivos hermanos `-wal` y `-shm` en el mismo directorio.

**Nota sobre `robots.txt`:** el que genera este proyecto (`Disallow: /admin`) no protege
nada en `/tour-word/` — `robots.txt` solo se lee desde la raíz del host. La protección
real es el `X-Robots-Tag` de arriba.

### 5.1bis El `.htpasswd` que el bloque de arriba necesita — no existía, ahora tiene procedimiento

`security-engineer` marcó esto como resuelto "de palabra" en la primera pasada
(`04-seguridad.md` §8.6 punto 1): el `.htaccess` apunta a un `.htpasswd` que nadie decía
cómo crear. No hay SSH en este encargo (§7), así que no hay `htpasswd` en el servidor —
se genera **en local** y se sube el archivo ya hecho.

**Procedimiento, probado en esta sesión con el mismo binario que usó `security-engineer`
para su laboratorio** (`G:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\htpasswd.exe`):

```
G:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\htpasswd.exe -c -m -b "C:\ruta\local\htpasswd-pachaviva.txt" pachaviva-demo "LA_CONTRASENA_QUE_ANYERSON_ELIJA_AQUI"
```

- `-c` crea el archivo (solo la primera vez — si ya existe y se agrega un segundo usuario,
  quitar `-c` o se sobrescribe).
- `-m` genera hash **`apr1` (MD5 de Apache)**, no bcrypt (`-B`) — es el formato más portable
  entre Apache y LiteSpeed; probé ambos formatos en esta sesión y los dos funcionan en el
  Apache local, pero `apr1` es la opción segura si no se puede confirmar de antemano qué
  soporta el hosting.
- El usuario `pachaviva-demo` es un nombre de ejemplo razonable — no es un requisito, se
  puede usar otro.
- **La contraseña la elige y la conoce Anyerson.** No la genero yo ni la escribo en este
  documento: es exactamente el mismo criterio que C-3 opción (b) de `04-seguridad.md`
  (nunca una clave que quede escrita en un archivo que puede terminar commiteado). Si hace
  falta una generada al azar, `openssl rand -base64 18` en PowerShell (con Git for Windows
  instalado) o el generador de contraseñas de cualquier gestor.

Verificación del formato antes de subir (debe empezar por `usuario:$apr1$`):

```
Get-Content "C:\ruta\local\htpasswd-pachaviva.txt"
```

**Subida** (comando 4 de §9, a `/home/limaview/tour-word-data/.htpasswd` — la misma
carpeta que el SQLite, ver §4):

```
curl.exe -s -T "C:\ruta\local\htpasswd-pachaviva.txt" "ftp://ftp.limaviewtours.com/tour-word-data/.htpasswd" --user "<FTP_USER: fuera del repo>:<FTP_PASS: fuera del repo>"
```

`tour-word-data/` cuelga directamente de la raíz que da el FTP (que aterriza en
`/home/limaview/`, confirmado en §3) — no de `public_html`.

**Alternativa sin este artefacto, si Anyerson la prefiere:** cPanel trae **Directory
Privacy** sobre la carpeta `tour-word/admin` (una vez que exista, requiere que el código ya
esté subido), que genera su propio `.htpasswd` y su propia sección de autenticación sin
tocar el `.htaccess` a mano. **Si se usa esa vía, hay que quitar por completo el bloque de
`SetEnvIf`/`mod_auth_basic` de §5.1** — dejar los dos activos a la vez duplica la
autenticación de forma impredecible (cPanel inserta su propio bloque `<Location>` o
`.htaccess` de Directory Privacy, que puede chocar con el `AuthUserFile` de arriba). Elegir
una vía, no las dos.

**Cómo se comprueba que el `.htpasswd` (corrección 3) y el marcador de `AuthUserFile`
(corrección 4) quedaron cerrados** — los dos se verifican con el mismo par de pruebas,
porque un fallo en cualquiera de los dos se manifiesta igual:

1. **Sin credenciales:** `curl.exe -s -o "NUL" -w "%{http_code}" "https://www.limaviewtours.com/tour-word/admin"`
   → debe dar `401`. Si da `500`, el `AuthUserFile` sigue con el marcador
   `RUTA_ABSOLUTA_PENDIENTE_DE_CONFIRMAR_CON_PWD_VER_COMANDO_1_DE_SECCION_9` sin
   reemplazar, o la ruta real está mal, o el archivo no se subió — con el bloque
   corregido, cualquiera de los tres casos falla cerrado (nadie entra), pero sigue sin
   estar listo para mostrarle el panel a la clienta.
2. **Con las credenciales reales** (usuario y contraseña del paso de creación del
   `.htpasswd`): `curl.exe -s -u "pachaviva-demo:LA_CONTRASENA_REAL" -o "NUL" -w "%{http_code}" "https://www.limaviewtours.com/tour-word/admin"`
   → debe dar `200`. Si sigue dando `401` con las credenciales correctas, el `.htpasswd`
   subido no coincide con el que se generó (usuario, hash, o se subió el archivo de
   prueba en vez del real).

Solo con **las dos** pruebas — sin credenciales rechaza, con credenciales acepta — quedan
cerradas la 3 y la 4 a la vez. Una sola no alcanza: un `401` constante también lo daría un
`AuthUserFile` roto que nunca deja pasar a nadie, ni siquiera con la clave correcta.

### 5.2 Exclusión en el `.htaccess` VIVO de `/public_html/` (anfitrión, Lima View)

**Corrección importante, medida por `security-engineer` en su re-verificación
(`04-seguridad.md` §8.4 punto 3): probablemente no haga falta tocar este archivo.**
Midió, simulando el `.htaccess` del anfitrión en su laboratorio, que **`/tour-word/`
funciona igual con y sin esta línea de exclusión** — porque el `.htaccess` hijo de
`/tour-word/` declara su propio `RewriteEngine On` y **sustituye, no hereda**, las
reglas de reescritura del padre (salvo que el padre tenga `RewriteOptions Inherit`, que
no es el caso aquí). El catch-all del anfitrión (`RewriteRule ^(.*)$ /limaprogramacion/$1 [L]`)
nunca llega a actuar sobre `/tour-word/` porque el hijo ya resolvió la petición antes.

**Consecuencia práctica: esta línea pasa a ser Plan B, no un paso obligatorio.** Es el
único paso de todo el despliegue capaz de tumbar `limaviewtours.com` entero con un error
de sintaxis (§8 lo señala también). Si el `.htaccess` propio de §5.1 basta —y la
medición de `security-engineer` dice que basta—, **la forma más segura de proceder es no
tocar el archivo del otro cliente en absoluto**: se prueba `/tour-word/es` después de
subir el código y el `.htaccess` propio (paso 5 de §8), y **solo si no responde** se
recurre a esta sección como plan B. El orden del despliegue (§8) queda actualizado con
este criterio.

**Si de todas formas hace falta usarla** (plan B), el procedimiento sigue siendo el
mismo, con respaldo fechado primero (§8, paso 3) y la línea en el lugar correcto: va
**después de TODOS los bloqueos existentes** (IPs, deny de `.env`/`.git`,
anti-WordPress, filtro de user-agent) e **inmediatamente antes** del catch-all hacia
`/limaprogramacion/public/`. Si sube por encima de un bloqueo escrito como `RewriteRule`,
ese bloqueo deja de proteger `/tour-word/` (la bandera `[L]` corta el resto de reglas
para esa petición — medido: el deny de `.env` vivo es un `RewriteRule`, no un `<Files>`,
ver `04-seguridad.md` §3.1-3.2). **Aviso de `security-engineer`: no cambiar `[L]` por
`[END]`** — `[END]` detendría también el procesamiento del `.htaccess` del
subdirectorio y rompería la demo.

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

No tengo el contenido literal de ese archivo (no hay acceso de lectura al servidor en
esta sesión — ver §3). Si hace falta usarla, Anyerson debe editarlo a mano viendo el
archivo real, pegando esta línea en el lugar exacto que describe la nota de arriba, no
al principio del archivo aunque sea el instinto natural.

**Cómo se comprueba si hace falta plan B o no (antes de decidir tocar el archivo):**
después del paso 5 de §8 (código + `.htaccess` propio ya subidos), probar
`https://www.limaviewtours.com/tour-word/es` por contenido (mismo criterio de §9 — buscar
el título real de la portada, no un código `200`). Si responde con la demo, **el plan B no
hace falta — no tocar el archivo del anfitrión** y saltar directo al resto del checklist
de §9. Si no responde (cae al catch-all hacia `/limaprogramacion/`, o a un 404), recién
ahí aplicar la línea de exclusión de arriba, con el respaldo fechado hecho antes.

---

## 6. Lista de exclusión de la subida masiva del código (app + `vendor`)

**Esto lo cubre el documento pero no lo ejecuta como comandos `curl` uno por uno**: la
app completa (con `vendor/`, cientos de archivos) la sube Anyerson con su cliente FTP
habitual (arrastrar carpeta), no con `curl` fila por fila — eso sí sería el "script con
bucle" que el propio encargo prohíbe. Lo que **sí** es code numerado en §8 son los pasos
sensibles de seguridad: sondeo, respaldo, los dos `.htaccess`, el SQLite y la
verificación, que es donde han estado los incidentes reales del equipo, no en la
transferencia masiva de código.

**La carpeta que se arrastra NO debe incluir:**

```
.git/
.claude/
node_modules/
tests/
docs/
.env                          (se escribe a mano en el servidor, nunca se sube)
.env.example                  (no hace falta en producción, y delata nombres de variables)
database/database.sqlite      (es el residuo del 1-sep — NUNCA este archivo, ver §1)
database/database.sqlite-wal
database/database.sqlite-shm
storage/logs/*
bootstrap/cache/*.php         (ver §4, punto 6: congela config vieja si viaja cacheado)
.phpunit.result.cache
```

**Sí debe incluir**, además del código: `vendor/` completo (no hay SSH para correr
`composer install` en el servidor — ver §7), `public/build/` con los assets ya
compilados (`npm run build` corrido en local **después** de `php artisan view:cache`,
para que Tailwind no purgue clases de vistas compiladas — orden que ya causó una
regresión de ~48KB de CSS perdido en otro proyecto del estudio).

---

## 7. El vacío de SSH: cómo correr `config:cache` sin shell en el servidor

**No hay credenciales SSH en este encargo, solo FTP.** El paso "`.env` con
`IS_STAGING_MIRROR=true` + `config:cache` en el mismo paso" (pedido explícito, y motivo
ya reproducido por QA: sin recachear, la config vieja queda en efecto en silencio)
requiere ejecutar `artisan` en el servidor. Dos caminos, en orden de preferencia:

**(a) Si el hosting ofrece Terminal SSH o "Setup Node.js/PHP App" con consola en cPanel**
(muchos planes GoDaddy/cPanel lo tienen bajo "Terminal" o "Advanced"): usar eso
directamente — `php artisan config:cache && php artisan route:cache`, tal cual, es más
limpio y no deja rastro. **Confirmar esto es el primer paso de Anyerson, antes de tocar
nada** (revisar el panel de cPanel).

**(b) Si no hay Terminal**, un runner de un solo uso, protegido por token, que se sube,
se dispara una vez desde el navegador (o con `curl`), y **se borra en el mismo minuto**:

```php
<?php
// deploy-runner-CAMBIAR-ESTE-TOKEN.php -- SUBIR, DISPARAR UNA VEZ, BORRAR.
// No queda instalado: es un artefacto de un solo uso.
// UBICACION CORREGIDA tras la re-verificacion de security-engineer
// (04-seguridad.md SS8.6 punto 3): el .htaccess de SS5.1 reescribe TODO lo que
// no empieza por public/ hacia public/ -- medido: pedir este archivo en la
// raiz de tour-word/ lo sirve desde public/ si existe ahi, o cae al 404 de
// Laravel si solo existe en la raiz. Por eso va DENTRO de public/, con los
// require ajustados un nivel mas arriba.
$token = 'CAMBIAR_POR_UN_TOKEN_ALEATORIO_DE_32_CARACTERES_ANTES_DE_SUBIR';
if (($_GET['token'] ?? '') !== $token) {
    http_response_code(404);
    exit('Not found');
}
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->call('config:clear');
$kernel->call('config:cache');
$kernel->call('route:cache');
echo "OK: config:cache y route:cache ejecutados.\n";
echo $kernel->output();
```

Va en **`/public_html/tour-word/public/deploy-runner-<token>.php`** — dentro de `public/`,
no en la raíz de `tour-word/` (corrección de ubicación; antes decía la raíz y ahí la
reescritura del `.htaccess` se lo come, medido por `security-engineer`). Los dos `require`
ya están ajustados a `../vendor/autoload.php` y `../bootstrap/app.php` para esa ubicación
un nivel más adentro. Generar el token real antes de subir (`openssl rand -hex 16` o
similar) — **no dejar el marcador tal cual**. Dispararlo una vez por HTTP
(`https://www.limaviewtours.com/tour-word/deploy-runner-<token>.php?token=<token>` — la
URL pública no lleva `/public/`, eso es solo la ruta física en el servidor; el `.htaccess`
lo resuelve igual que con cualquier otro archivo de `public/`), confirmar el `OK` en la
respuesta, y borrarlo por FTP en el mismo minuto: un runner de config sin autenticación de
sesión que quede público es una puerta que nadie pidió.

**Cómo se comprueba que esta corrección quedó cerrada:** la respuesta HTTP al disparar el
runner debe contener literalmente `OK: config:cache y route:cache ejecutados.` — no un 404
de Laravel (que indicaría que cayó al front controller, el defecto original) ni un 404 del
propio archivo (que indicaría que se subió en el lugar viejo). Si no aparece ese `OK`,
`config:cache` no corrió y `IS_STAGING_MIRROR=true` puede no estar tomando efecto — no
seguir con el resto del checklist hasta confirmar esto.

---

## 8. Orden del despliegue

1. **Confirmar con Anyerson si hay Terminal SSH/cPanel disponible** (§7a vs §7b).
2. **Comando `PWD` por FTP** (comando 1 de §9) para obtener la ruta absoluta real del
   `home` y completar **los dos** marcadores que dependen de ella: `DB_DATABASE` en el
   `.env` de §4 y `AuthUserFile` en el `.htaccess` de §5.1 — ya confirmado que la cuenta
   alcanza el `home` completo (§3), solo falta el nombre de usuario exacto.
3. **Generar el `.htpasswd` en local** (§5.1bis) con `htpasswd.exe -c -m`, verificar su
   formato (`usuario:$apr1$...`), y subirlo a la carpeta fuera del docroot confirmada en
   el paso 2 (comando 4 de §9) — **o**, si Anyerson prefiere, decidir usar Directory
   Privacy de cPanel y quitar el bloque de auth de §5.1 antes de continuar.
4. **Respaldo fechado del `.htaccess` vivo de `/public_html/`** — fuera del docroot, en
   la máquina de Anyerson, **por si hace falta el paso 9** (comando 2 de §9). Se hace
   igual aunque el plan sea no tocar el archivo: si el paso 8 muestra que sí hace falta,
   ya está listo; si no hace falta, no se usa y no se pierde nada por haberlo hecho.
5. **Humo del sitio anfitrión ANTES de cualquier cambio** — línea base para comparar
   después (comando 3 de §9).
6. **Crear `/public_html/tour-word/`** y subir la app completa (código + `vendor/` +
   `public/build/` ya compilado), con la lista de exclusión de §6. **En este mismo
   paso**, el `.htaccess` de §5.1 (ya con los dos marcadores resueltos) sube junto con el
   resto — el subdirectorio no debe quedar público ni un segundo sin su `noindex` y sin
   sus denies.
7. **Crear la carpeta fuera del docroot y subir ahí el SQLite verificado** (§1-§2) — por
   ejemplo `/home/<usuario-confirmado-en-el-paso-2>/tour-word-data/pachaviva.sqlite`,
   nunca dentro de `public_html`. Esta ruta debe coincidir exactamente con el
   `DB_DATABASE` que se escriba en el paso 8.
8. **Escribir el `.env` de §4 a mano** en el servidor (editor de cPanel File Manager o
   `curl -T` subiendo el archivo ya armado — nunca arrastrado desde la carpeta local),
   con el `DB_DATABASE` ya completado con la ruta real del paso 2.
9. **`config:clear` + `config:cache` + `route:cache`, en ese orden, en el mismo paso**
   que el `.env` (§7b, runner ya movido a `public/`). Sin este paso, `IS_STAGING_MIRROR=true`
   puede no tomar efecto.
10. **Probar `/tour-word/es` por contenido (§5.2).** `security-engineer` midió que el
    `.htaccess` propio de §5.1 basta por sí solo — el hijo sustituye las reglas del padre,
    así que el catch-all del anfitrión nunca llega a actuar. **Si responde con la demo,
    saltar el paso 11 — no tocar el archivo del anfitrión.** Solo si no responde (cae al
    catch-all o a un 404), seguir al paso 11 con el respaldo ya hecho en el paso 4.
11. **(Plan B, solo si el paso 10 lo exige) Pegar la línea de exclusión en el `.htaccess`
    vivo de `/public_html/`** (§5.2), en el lugar exacto indicado — mismo despliegue que
    el paso 6, no una ventana después: si el subdirectorio ya responde antes de que la
    exclusión bloquee el filtro/anti-WP, hay ventana de exposición.
12. **Humo inmediato del sitio anfitrión otra vez**, no solo del subdirectorio nuevo —
    home, una ficha de tour, flujo de reserva (comando de §9). Si algo cambió respecto
    al paso 5, y se usó el paso 11, revertir esa línea de inmediato.
13. **Checklist post-deploy completo de §9 + las siete condiciones de humo de §8.8 de
    `04-seguridad.md`**, todos los ítems, afirmando sobre contenido — nunca sobre código
    de respuesta (el hosting devuelve `200` con una página-desafío anti-bot en cualquier
    URL, incluidas las inexistentes) y con la prueba de navegador real para la CSP
    (ningún `curl` la detecta).
14. **Borrar el runner de §7b** si se usó, en el mismo minuto que terminó de correr.
15. **Hacerlo fuera de la ventana comercial de Lima View** (paso no técnico, pero
    igual de real — recomendación de `04-seguridad.md` §3.4). Se vuelve aún más relevante
    si el paso 10 obliga a usar el plan B del paso 11.

---

## 9. Comandos para Anyerson

**Un comando por línea, cada uno empieza literalmente por `curl`, con los valores
reales dentro, en PowerShell (`curl.exe`, sin `&&`).** Antes de correrlos, crea la
carpeta local donde se guardan respaldos y capturas (no es un comando `curl`, es una
sola vez):

```
New-Item -ItemType Directory -Force "$env:USERPROFILE\Desktop\pacha-viva-deploy"
```

**Nota de contenido verificado en esta sesión** (una sola lectura, para que el humo de
abajo tenga con qué comparar): la home real de Lima View hoy trae
`<title>Tours en Lima — Experiencias inolvidables | Lima View Tours</title>` y el texto
"Lima View Tours" varias veces en 381 KB de HTML — **eso es lo que el humo de abajo
busca**, no un código `200`. Si en cualquier momento la respuesta baja a ~12 KB con
`<title>One moment, please...</title>` o `<title>Loader</title>`, es la página-desafío
anti-bot del hosting (medida también por `security-engineer` en `04-seguridad.md` §0):
**no es un sitio caído, pero tampoco es una medición válida — repetir más tarde, no
declarar nada con esa respuesta**.

### Antes de tocar nada

**1.** ~~Sondear la ruta absoluta con `PWD` por FTP~~ — **ya no hace falta, la ruta está
resuelta: `/home/limaview/`.** Se dejó este aviso para que nadie pierda tiempo si intenta
repetir el comando original:

```
curl.exe -s "ftp://ftp.limaviewtours.com/" --user "<FTP_USER: fuera del repo>:<FTP_PASS: fuera del repo>" -Q "PWD" -v
```

**Este comando no sirve en este servidor** — el FTP está enjaulado para `PWD` y responde
`257 "/" is your current location`, la ruta relativa a la jaula, no la del sistema de
archivos real. El PM obtuvo la ruta real (`/home/limaview/`) leyendo
`/public_html/error_log`, que registra rutas absolutas del sistema en sus trazas de error
— **el método a repetir si esto se vuelve a necesitar en otro hosting es ese, no `PWD`.**
Con la ruta ya confirmada, `DB_DATABASE` y `AuthUserFile` en §4 y §5.1 ya tienen el valor
real (`/home/limaview/tour-word-data/...`) — no queda ningún marcador que reemplazar. Las
subidas de los comandos 4 (`.htpasswd`) y la del SQLite (paso 7 de §8) crean la carpeta
`tour-word-data/` la primera vez que suban algo ahí, si el cliente FTP lo permite
(`--ftp-create-dirs` con `curl`, o crearla a mano una vez desde el cliente FTP habitual).

**2.** Respaldo fechado del `.htaccess` vivo del anfitrión, fuera del docroot (a tu
Desktop, no a `public_html`) — **se hace siempre, por si hace falta el plan B del
comando 6**, aunque §5.2 dice que probablemente no haga falta usarlo:

```
curl.exe -s -o "$env:USERPROFILE\Desktop\pacha-viva-deploy\htaccess-limaviewtours-backup-2026-09-18.txt" "ftp://ftp.limaviewtours.com/public_html/.htaccess" --user "<FTP_USER: fuera del repo>:<FTP_PASS: fuera del repo>"
```

**3.** Humo del anfitrión ANTES del cambio — guarda el home real para comparar después:

```
curl.exe -s -L -o "$env:USERPROFILE\Desktop\pacha-viva-deploy\home-limaview-ANTES.html" "https://www.limaviewtours.com/"
```

Verificación de esa captura (contenido, no código):

```
curl.exe -s -L "https://www.limaviewtours.com/" | Select-String -Pattern "Lima View Tours" -Quiet
```

Debe devolver `True`. Si devuelve `False` o la respuesta pesa ~12 KB, es la página-desafío
(§0 de `04-seguridad.md`) — repetir en un rato, no seguir con el despliegue sobre esa base.

**4.** Subir el `.htpasswd` generado en local (§5.1bis) a `/home/limaview/tour-word-data/.htpasswd`
(`--ftp-create-dirs` crea `tour-word-data/` si aún no existe):

```
curl.exe -s --ftp-create-dirs -T "C:\ruta\local\htpasswd-pachaviva.txt" "ftp://ftp.limaviewtours.com/tour-word-data/.htpasswd" --user "<FTP_USER: fuera del repo>:<FTP_PASS: fuera del repo>"
```

### Subida (después de que el código + `.htaccess` de §5.1 con los marcadores ya
### resueltos + SQLite ya estén en su lugar por el cliente FTP habitual de Anyerson,
### según el orden de §8)

**5.** El subdirectorio nuevo sirve Pacha Viva — busca el título real de la portada, no
un `200`. **Este mismo comando decide si hace falta el plan B del comando 6** (paso 10
de §8): si da `True`, no tocar el `.htaccess` del anfitrión.

```
curl.exe -s -L "https://www.limaviewtours.com/tour-word/es" | Select-String -Pattern "Vive lo mejor de Per" -Quiet
```

Debe devolver `True`. `False` significa que no está sirviendo la app — puede ser la
página-desafío (repetir más tarde), o que sí hace falta el plan B del comando 6, o un
`500` de PHP (revisar `error_log` de cPanel).

### Plan B — SOLO si el comando 5 dio `False` por el motivo correcto (no por la
### página-desafío). Ver §5.2: la medición de `security-engineer` dice que esto
### probablemente no haga falta.

**6.** Subir el `.htaccess` del host ya editado a mano con la línea de exclusión de §5.2
en el lugar correcto (reemplaza el archivo completo, por eso el respaldo del comando 2
es obligatorio antes). Después de este comando, repetir el comando 5 para confirmar que
ahora sí da `True`:

```
curl.exe -s -T "$env:USERPROFILE\Desktop\pacha-viva-deploy\htaccess-limaviewtours-EDITADO.txt" "ftp://ftp.limaviewtours.com/public_html/.htaccess" --user "<FTP_USER: fuera del repo>:<FTP_PASS: fuera del repo>"
```

### Verificación post-deploy (todas por contenido, nunca por código de respuesta)

**7.** `.env` y SQLite NO descargables — con control positivo obligatorio en la misma
corrida (si el control no da lo esperado, los 403/404 de abajo no prueban nada):

```
curl.exe -s -o "NUL" -w "control /tour-word/es -> %{http_code}`n" "https://www.limaviewtours.com/tour-word/es"
curl.exe -s -o "NUL" -w ".env -> %{http_code}`n" "https://www.limaviewtours.com/tour-word/.env"
curl.exe -s -o "NUL" -w "database/database.sqlite -> %{http_code}`n" "https://www.limaviewtours.com/tour-word/database/database.sqlite"
curl.exe -s -o "NUL" -w "database/ (listado) -> %{http_code}`n" "https://www.limaviewtours.com/tour-word/database/"
curl.exe -s -o "NUL" -w "storage/logs/laravel.log -> %{http_code}`n" "https://www.limaviewtours.com/tour-word/storage/logs/laravel.log"
curl.exe -s -o "NUL" -w "composer.lock -> %{http_code}`n" "https://www.limaviewtours.com/tour-word/composer.lock"
curl.exe -s -o "NUL" -w ".git/config -> %{http_code}`n" "https://www.limaviewtours.com/tour-word/.git/config"
```

El control (`/tour-word/es`) debe dar `200`. Todos los demás deben dar `403` o `404`.
**Si el control no da `200`, ninguno de los otros seis resultados significa nada — hay
que repetir la corrida entera**, probablemente porque cayó en la página-desafío. Un `200`
en cualquiera de los demás (sobre todo `.env`) significa que la corrección 1 del bloque
de auth de §5.1 no quedó bien pegada — volver a §5.1 y comparar contra el bloque
corregido literal antes de seguir.

**8.** Cabecera `X-Robots-Tag`, con `always` — probar en una URL que sí existe y en una
que da error, porque `Header always set` es justo lo que debe sobrevivir al error:

```
curl.exe -sI "https://www.limaviewtours.com/tour-word/es"
curl.exe -sI "https://www.limaviewtours.com/tour-word/no-existe-esta-ruta"
```

Buscar `X-Robots-Tag: noindex, nofollow, noarchive` en **las dos** respuestas. Si falta
en la segunda, el `always` no se aplicó y la capa 1 de M-1 no está cerrando lo que se
supone que cierra — no es un OK parcial, es un fallo.

**9.** El panel de Filament pide credenciales sin ellas (corrección 1, bloque de auth):

```
curl.exe -s -o "NUL" -w "panel sin credenciales -> %{http_code}`n" "https://www.limaviewtours.com/tour-word/admin"
```

Debe dar `401`, no `200` ni `302` hacia el formulario de login de Filament, y **tampoco
`500`** (que indicaría el `.htpasswd` del comando 4 mal subido o el marcador de
`AuthUserFile` sin resolver). Un `200`/`302` significa que el bloque sigue siendo el
original con el defecto medido por `security-engineer`.

**10.** El panel SÍ deja entrar con las credenciales reales (cierra a la vez la
corrección 3 — el `.htpasswd` existe y es el correcto — y la 4 — el marcador de
`AuthUserFile` quedó bien resuelto). Sustituir `pachaviva-demo` y la contraseña por los
valores reales usados al generar el `.htpasswd` en el comando del §5.1bis:

```
curl.exe -s -u "pachaviva-demo:LA_CONTRASENA_REAL_USADA_AL_GENERAR_EL_HTPASSWD" -o "NUL" -w "panel con credenciales -> %{http_code}`n" "https://www.limaviewtours.com/tour-word/admin"
```

Debe dar `200`. Si da `401` con las credenciales correctas, el `.htpasswd` subido no
coincide con el que se generó.

**11.** Humo del anfitrión DESPUÉS del cambio — mismo criterio que el comando 3,
comparado contra el "ANTES" guardado. Correr esto **siempre**, se haya usado el plan B
del comando 6 o no:

```
curl.exe -s -L "https://www.limaviewtours.com/" | Select-String -Pattern "Lima View Tours" -Quiet
```

Debe seguir devolviendo `True`. Si pasa a `False` y se usó el plan B, es la señal de que
el `.htaccess` editado en el comando 6 rompió el sitio del anfitrión — ejecutar el
rollback de §10 de inmediato, no esperar a terminar el resto del checklist. Si pasa a
`False` sin haber usado el plan B, algo más está mal y hay que investigar antes de seguir
publicando nada.

**12.** `sitemap.xml` de la demo debe estar neutralizado (esperado `404`, la app de este
lote no expone sitemap si `is_staging_mirror` está activo):

```
curl.exe -s -o "NUL" -w "sitemap tour-word -> %{http_code}`n" "https://www.limaviewtours.com/tour-word/sitemap.xml"
```

**13. (No es `curl`, es navegador real — la CSP no la detecta ningún comando de esta
lista).** Abrir `https://www.limaviewtours.com/tour-word/es` en un navegador, abrir la
consola de desarrollador, y confirmar: **ninguna violación de CSP** (ninguna línea con
`Refused to evaluate a string as JavaScript` ni con fuentes bloqueadas), y que el slider
del hero avanza, la galería abre, el menú móvil despliega y el selector de moneda cambia
los precios — los cuatro con un clic real, no con la sola carga de la página. Si algo de
esto falla, es el punto 2 de §8.7 de `04-seguridad.md` (CSP) — no un problema que arregle
`curl`.

### Lo que NO cubre esta lista, y por qué

- **El flujo de reserva completo del sitio de Lima View (el real, no la demo).** Enviar
  su formulario requiere el token CSRF y las cookies de una sesión real de navegador —
  no es reproducible con `curl` de forma honesta sin fabricar un chequeo que parezca
  automático y en realidad no pruebe nada. Es un paso manual: abrir el navegador,
  completar una reserva de prueba en `limaviewtours.com` (no en `/tour-word/`) después
  del paso 9, y confirmar que llega igual que antes del despliegue.
- **Si las reglas anti-WordPress y el filtro de escáneres del `.htaccess` vivo cubren
  subdirectorios.** `security-engineer` no lo pudo medir por la página-desafío (§3.3 de
  `04-seguridad.md`) y yo tampoco tengo forma de medirlo sin el contenido literal del
  archivo. La réplica de §5.1(a-d) dentro de `/tour-word/.htaccess` es la mitigación que
  no depende de resolver esta duda.

---

## 10. Rollback

**Commit de vuelta:** no aplica un commit específico — **no se hizo ningún commit en
esta sesión**. El árbol de trabajo de `lote-1-sistema-diseno` tiene un cambio más que al
principio de esta ronda: `database/seeders/DemoCatalogImageSeeder.php`, el fix de
`backend-laravel` para el defecto de §2.1 (no es mío, pero vive en el mismo árbol) — 19
archivos modificados sin commitear + `docs/rediseno-2026/` y
`tests/Feature/StagingMirrorNoindexTest.php` sin trackear, verificado por `git status`
antes y después de esta sesión, ver §2.4. Revertir código no es el vector de rollback
aquí: el vector es **deshacer los archivos subidos por FTP**.

**Migración destructiva:** no hay ninguna. El SQLite de la demo es un archivo nuevo y
autocontenido — no toca ninguna base de datos existente de Lima View ni de Pacha Viva en
otro entorno. Borrar el archivo no dañado nada fuera de sí mismo.

**Procedimiento de reversión, en orden:**

1. **Restaurar el `.htaccess` vivo del anfitrión** desde el respaldo del paso 2 de §9
   (`htaccess-limaviewtours-backup-2026-09-18.txt`) — esto por sí solo hace que
   `/tour-word/` vuelva a caer en el catch-all hacia `/limaprogramacion/`, indistinguible
   de que nunca existió, sin tocar nada más:
   ```
   curl.exe -s -T "$env:USERPROFILE\Desktop\pacha-viva-deploy\htaccess-limaviewtours-backup-2026-09-18.txt" "ftp://ftp.limaviewtours.com/public_html/.htaccess" --user "<FTP_USER: fuera del repo>:<FTP_PASS: fuera del repo>"
   ```
2. **Confirmar con el humo del paso 9** (`Lima View Tours` vuelve a `True`) que el
   anfitrión quedó como antes.
3. **Borrar `/public_html/tour-word/` completo** por FTP (código y `.htaccess` propio) —
   puede hacerse después del paso 1 sin apuro, porque ya no es alcanzable públicamente.
   **El SQLite vive fuera del docroot** (`/home/<usuario>/tour-word-data/`, §4 y §9) y se
   borra aparte, en un paso propio — no está dentro de `tour-word/` para empezar.
4. **Riesgo de datos al revertir:** el único dato que existe SOLO en el SQLite de la
   demo son los mensajes que alguien haya mandado por el formulario de contacto de
   Pacha Viva mientras estuvo publicada (nombre, correo, teléfono, mensaje, IP — PII
   real bajo Ley 29733, ver `04-seguridad.md` §4.4). Si se borra el archivo sin
   descargarlo antes, esos mensajes se pierden. Decisión a tomar por Anyerson antes de
   borrar `tour-word-data/`: descargar `pachaviva.sqlite` a `pacha-viva-deploy\` antes de
   borrarlo, por si hay que responder a alguien que escribió durante la demo.
5. Nada de esto afecta la base de datos MySQL local de desarrollo (`pachaviva`) ni
   ningún otro entorno: el SQLite de la demo siempre fue un archivo aislado.

---

## Estado de las seis correcciones de la re-verificación de seguridad

`security-engineer` re-auditó el paquete montando dos Apache 2.4 reales, midió que el
bloque de auth original no protegía nada y que la CSP rompía el sitio, y **RECHAZÓ**
(`04-seguridad.md` §8.9) con seis correcciones concretas, dueño `deployer` en las seis.
Estado de cada una en este documento:

| # | Corrección | Estado | Cómo se comprueba (ver también §5, §9) |
|---|---|---|---|
| 1 | Bloque de auth (`SetEnvIf`/`RequireAny`, sin directivas 2.2) | **Aplicado** en §5.1, literal — el bloque medido por `security-engineer` en dos instancias Apache 2.4 | Comandos 9 y 10 de §9: sin credenciales → `401`; con credenciales → `200`; y comando 7: `.env`/`composer.lock` → `403` en la misma corrida |
| 2 | CSP corregida (`unsafe-eval`, `fonts.googleapis.com`/`fonts.gstatic.com`) | **Aplicado** en §5.1 | Comando 13 de §9 — **prueba de navegador real**, ningún `curl` la detecta: sin violaciones de CSP en consola, slider/galería/menú/moneda responden a un clic real |
| 3 | `.htpasswd` con procedimiento, o Directory Privacy | **Aplicado**: procedimiento completo en §5.1bis, probado con el mismo `htpasswd.exe` que usó `security-engineer` en su laboratorio (formato `apr1`, verificado el formato de salida). Alternativa de Directory Privacy documentada como plan B | Comando 10 de §9 (panel entra con las credenciales reales) — cierra 3 y 4 a la vez |
| 4 | `<usuario_cpanel>` del `AuthUserFile`, resuelto sin marcador mudo | **Aplicado**: usa el mismo marcador ruidoso que `DB_DATABASE` (`RUTA_ABSOLUTA_PENDIENTE_DE_CONFIRMAR_CON_PWD_VER_COMANDO_1_DE_SECCION_9`), se resuelve con la misma salida del comando `PWD`, en el mismo paso (§4 y §8 paso 2) | Comandos 9 y 10 de §9 — con el bloque corregido, una ruta sin resolver da `500` (falla cerrado), no acceso abierto |
| 5 | Runner de §7b movido a donde la reescritura no se lo coma | **Aplicado**: va en `public/deploy-runner-<token>.php`, con los dos `require` ajustados a `../vendor/` y `../bootstrap/` | La respuesta HTTP al dispararlo debe contener literalmente `OK: config:cache y route:cache ejecutados.` — un 404 significa que sigue en el lugar viejo |
| 6 | Réplica del bloqueo por IP del anfitrión | **Aplicado**: el PM descargó el `.htaccess` vivo (lectura, sí pasa el clasificador) y trajo el contenido real. §5.1 ya trae las 5 IPs bloqueadas, las rutas de CMS/Joomla ampliadas (`wp-json`, `administrator/`, `media/system/`, `language/en-GB/`, `elementor`, `wp-content/plugins/`) y los user-agents reales (`ALittle Client`, `visionheight`, `nhs-data`/`fraud-research`, `nuclei`, `Xpanse`/`Cortex-Xpanse`), con nota explícita de que es una **foto del 18/09/2026, no un espejo en vivo** | Revisión manual: las 5 IPs, las rutas y los user-agents del bloque de §5.1 coinciden literal contra la lista que trajo el PM — verificado en esta edición |

**Las seis correcciones quedan aplicadas y documentadas.** `security-engineer` dijo que
con los seis puntos resueltos sobre el papel no hace falta una tercera pasada de
auditoría, pero **sí hace falta que alguien ejecute las comprobaciones de §9 (y las siete
de `04-seguridad.md` §8.8) sobre el servidor real y deje el resultado por escrito** —
midió en Apache, el hosting es LiteSpeed, y eso es justo lo que ninguna sesión de
preparación puede sustituir.

## Pendientes

Con la corrección 6 cerrada, quedan **cinco pendientes — ninguno mío, todos de Anyerson o
del servidor real**:

1. **Comando `PWD` por FTP** (comando 1 de §9) para resolver los dos marcadores
   (`DB_DATABASE` y `AuthUserFile`) — confirmado que el SQLite puede salir del docroot
   (§3), pero el nombre de usuario exacto no existe hasta que alguien lo pida.
2. **Confirmar si hay Terminal SSH/cPanel** (§7a) — decide Anyerson, determina si hace
   falta el runner de un solo uso (§7b, ya movido a `public/`). Declarado como pendiente
   de él, no mío.
3. **Fecha de baja de la demo** — decide Anyerson con la clienta, anotada, incluyendo el
   borrado del SQLite (`tour-word-data/`) y la reversión de la línea del `.htaccess` vivo
   si se usó el plan B (recomendación no bloqueante de `04-seguridad.md` §7). Declarado
   como pendiente de él.
4. **Ejecutar el checklist de §9 sobre el servidor real y dejar el resultado por
   escrito** — `security-engineer` midió todo en Apache de laboratorio; el hosting es
   LiteSpeed y ninguna medición de esta sesión reemplaza esa corrida.
5. **Mantener la réplica de IPs/rutas/user-agents del `.htaccess` vivo al día** —
   la lista de §5.1 es una foto del 18/09/2026. Si Leo agrega IPs u otras reglas a su
   archivo más adelante, la réplica del subdirectorio no se entera sola; alguien tiene
   que volver a compararla cuando eso pase.

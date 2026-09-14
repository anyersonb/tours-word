# Auditoría de seguridad — Pacha Viva, lote 3

- **Fecha:** 2026-09-14
- **Rama / HEAD auditado:** `lote-1-sistema-diseno` @ `903e901`
- **Entorno:** PHP 8.2.1 (`/g/laragon/bin/php/php8.2.1/php.exe`), Laravel 12.69, Filament 4.12, MySQL local,
  Apache de Laragon (`http://tours-word.test`) + servidor embebido (`127.0.0.1:8123`).
- **Alcance:** lo que **introdujo el lote 3**. Lo anterior sólo se menciona si el lote lo empeoró o si la
  auditoría cambió la conclusión previa.
- **El auditor no arregla.** Ningún hallazgo está aplicado. Cada uno lleva el remedio y a quién le toca.

## Integridad del árbol y sondas

El árbol estaba congelado y **sigue congelado**: el único archivo del proyecto que esta auditoría escribió es
este informe.

```
$ git status --porcelain
?? docs/lote-3/qa-2026-09-14.md          <- de QA, no mío
?? docs/lote-3/seguridad-2026-09-14.md   <- este informe
```

Se plantaron **4 sondas** (fuera de git, en `storage/`, que está en `.gitignore`) y **las 4 están retiradas**:

| Sonda | Dónde estuvo | Para qué | Estado |
|---|---|---|---|
| `audit-probe.php` | `storage/app/public/` | ver si Apache **ejecuta** PHP bajo `/storage/` | **borrada** |
| `audit-probe.png` | `storage/app/public/` | PNG válido con PHP anexado, servido como `image/png` | **borrada** |
| `audit-probe.svg` | `storage/app/public/` | ver con qué `Content-Type` se sirve un SVG | **borrada** |
| `.audit-probe.txt` | `storage/app/private/livewire-tmp/` | ver si `livewire-tmp` es legible sin firma | **borrada** |

```
$ find . -path ./vendor -prune -o -path ./node_modules -prune -o -iname '*audit-probe*' -print
(sin salida)

$ find storage/app/public -type f
storage/app/public/.gitignore

$ find storage/app/private -type f
storage/app/private/.gitignore
storage/app/private/livewire-tmp/TQ4jOunTxwVzRPv881fhPFpxQ28C2a-metac2NyYXRjaF9xYV9waG90by5qcGc=-.jpg
```

> Ese `.jpg` de `livewire-tmp` **no es mío**: es del 2026-09-02 18:53, anterior a esta sesión (resto de una
> prueba de QA). Lo dejo donde está porque el árbol está congelado y borrarlo no me corresponde. Ver O-3.

Los PoC (scripts PHP y archivos de ataque) viven en el scratchpad de sesión, fuera del proyecto.

---

# VEREDICTO

## **NO APTO PARA PRODUCCIÓN.** Apto para seguir trabajando.

Dos cosas bloquean el despliegue, y **ninguna de las dos es "el código está mal escrito"**:

1. **F-1 (Alto, anónimo, hoy):** el lote convirtió `robots.txt` en ruta dinámica construida con el `Host` de la
   petición. Es explotable hoy por cualquiera y arrastra consigo `sitemap.xml`, `canonical`, `hreflang`,
   `og:url` y **la URL de los assets JS**.
2. **F-2 (Alto, trampa de despliegue):** `/storage/` es una **zona de ejecución de PHP** y el lote metió dos
   campos de subida nuevos dentro de ella. Hoy no es explotable porque la validación aguanta — lo verifiqué,
   no lo asumí — pero la barrera es **una sola** y no hay nada detrás.

El resto son medios, bajos y observaciones.

## Lo que el lote hizo BIEN (verificado, no asumido)

No son elogios de relleno: son tres vectores que esperaba encontrar abiertos y están cerrados con prueba.

- **JSON-LD: cerrado.** 18/18 payloads de ruptura de `<script>` sin materializar un solo elemento. Ver V-1.
- **Los repetidores de galería nuevos SÍ heredan el remedio de subida.** No se lo saltaron. Ver F-2, parte B.
- **`is_admin` no es asignable en masa, y no hay ruta de registro.** Ver V-2.

---

# Clasificación por quién lo explota

| # | Hallazgo | Sev. | Anónimo hoy | Exige panel | Día del despliegue |
|---|---|---|---|---|---|
| F-1 | `Host` reflejado en `robots.txt`/`sitemap`/`canonical`/`hreflang`/`og:url`/assets | **Alto** | **SÍ** | — | agravante |
| F-2 | `/storage/` ejecuta PHP: barrera única entre el panel y la ejecución remota | **Alto** | no | sí (como cadena) | **SÍ** |
| F-3 | 500 no controlado en el catálogo nuevo con un parámetro de tipo array | **Medio** | **SÍ** | — | — |
| F-4 | Subida temporal de Livewire sin restricción de tipo; contenida sólo por `FILESYSTEM_DISK=local` | **Medio** | no | sí | **SÍ** |
| O-1 | Slug de destino/experiencia sin validación de unicidad | Bajo | — | sí | — |
| O-2 | `CONTACT_NOTIFY_EMAIL` vacío → cae en un dominio ajeno (confirmado, sigue vivo) | Medio | — | — | **SÍ** |
| O-3 | Restos de `livewire-tmp` sin recolectar | Bajo | no | — | — |

---

# A. Explotable HOY por un visitante anónimo

## F-1 · [ALTO] El `Host` de la petición se refleja sin validar en toda la superficie de URLs absolutas

**Ubicación:** `app/Http/Controllers/RobotsController.php:41` (`url('/sitemap.xml')`). Por el mismo mecanismo:
`app/Http/Controllers/SitemapController.php`, `resources/views/components/layout.blade.php:79` (`url()->current()`
→ canonical / og:url) y `:85-106` (hreflang), y todo `route()`/`asset()` de las vistas.

**Causa raíz:** no hay `TrustHosts`. `bootstrap/app.php:14-20` sólo registra el alias `locale`; no hay
`->trustHosts(...)`. Sin él, `url()` toma el host de la cabecera `Host` **de la petición**.
**`APP_URL` no protege aquí:** Laravel sólo cae a `APP_URL` cuando no hay request (CLI, colas), nunca cuando
hay una request con un `Host` hostil.

### Prueba (control positivo y caso hostil en la misma corrida)

```
$ curl -s http://127.0.0.1:8123/robots.txt
User-agent: *
Disallow: /admin

Sitemap: http://127.0.0.1:8123/sitemap.xml          <-- CONTROL POSITIVO

$ curl -s -H 'Host: evil.example.com' http://127.0.0.1:8123/robots.txt
User-agent: *
Disallow: /admin

Sitemap: http://evil.example.com/sitemap.xml        <-- ENVENENADO

$ curl -s -H 'Host: evil.example.com' http://127.0.0.1:8123/sitemap.xml | grep -ao 'https\?://[^<]*'
http://evil.example.com/es
http://evil.example.com/es/nosotros
http://evil.example.com/es/contacto
http://evil.example.com/en

$ curl -s -H 'Host: evil.example.com' http://127.0.0.1:8123/es/ \
    | grep -aoiE '<link[^>]*(canonical|alternate)[^>]*>|<meta[^>]*og:url[^>]*>'
<link rel="canonical" href="http://evil.example.com/es">
<link rel="alternate" hreflang="es" href="http://evil.example.com/es">
<link rel="alternate" hreflang="en" href="http://evil.example.com/en">
<link rel="alternate" hreflang="x-default" href="http://evil.example.com/es">
<meta property="og:url" content="http://evil.example.com/es">

$ curl -s -H 'Host: evil.example.com' http://127.0.0.1:8123/es/ | grep -aoE '(href|src)="http[^"]*"' | sort -u | head -3
href="http://evil.example.com/build/assets/app-BL0yHsjR.css"
href="http://evil.example.com/build/assets/app-CgyniXpf.js"
href="http://evil.example.com/en"
```

### Trampa de medición (no repetirla)

La primera pasada se hizo contra Apache:

```
$ curl -s -H 'Host: evil.example.com' http://tours-word.test/robots.txt
<html><head><title>404 Not Found</title>...
```

Eso **parece** "está protegido" y **no lo está**. Ese 404 lo devolvió el *vhost* de Laragon al no encontrar un
`ServerName` coincidente, **antes de que PHP corriera**. En un hosting con vhost catch-all (lo normal en cPanel
compartido) ese filtro no existe. La medición válida es la del servidor embebido, que es la de arriba.

### Lo que hoy SÍ está bien

`X-Forwarded-Host` **no** se honra, porque no hay proxies de confianza configurados:

```
$ curl -s -H 'X-Forwarded-Host: evil.example.com' http://127.0.0.1:8123/robots.txt
Sitemap: http://tours-word.test/sitemap.xml     <-- ignorado, correcto
```

Esto **deja de ser cierto** el día que alguien añada `->trustProxies(at: '*')` para poner el sitio detrás de
Cloudflare o un balanceador. Entonces el vector se abre aunque el `ServerName` esté bien puesto.

### Impacto

1. **Rastreo/SEO.** `robots.txt` y `sitemap.xml` son los dos primeros documentos que pide un rastreador. Una
   petición con `Host` ajeno hace que el sitio se autodeclare en otro dominio. Para una **marca nueva sin nada
   indexado**, el daño no es "perder posiciones": es que la primera indexación se la lleve otro.
2. **Envenenamiento de caché.** El día que haya CDN o caché de página (lo esperable en un catálogo de tours),
   **una sola** petición con `Host` hostil puede quedar cacheada y servirse a usuarios reales con
   `app-CgyniXpf.js` apuntando al dominio del atacante. Eso ya no es SEO: es JavaScript de terceros ejecutándose
   en la página de la agencia, con el formulario de contacto y sus datos personales dentro.
3. **Correo.** Cualquier futuro correo transaccional que use `url()`/`route()` heredará el host envenenado.

### Remediación (no aplicada)

En `bootstrap/app.php`, dentro de `->withMiddleware()`:

```php
$middleware->trustHosts(at: fn () => [parse_url(config('app.url'), PHP_URL_HOST)], subdomains: true);
```

Con eso un `Host` fuera de la lista responde 400 y `url()` no puede envenenarse. Complementos para el día del
despliegue: `ServerName`/`ServerAlias` estrictos y un vhost por defecto que rechace hosts desconocidos. Si más
adelante hay proxy delante, `trustProxies` con la IP/rango concreto — **nunca `'*'`** — y mantener `trustHosts`.

**Le toca a:** `backend-laravel`.

---

## F-3 · [MEDIO] Un parámetro de filtro con forma de array tumba el catálogo nuevo (500 anónimo)

**Ubicación:** `app/Http/Controllers/TourController.php:35-56` — `$request->query('destino')` y
`$request->query('experiencia')` se pasan sin normalizar a `where("slug->{$locale}", $valor)`.

```
$ curl -o /dev/null -w '%{http_code}\n' 'http://127.0.0.1:8123/es/tours?destino=cusco'      -> 200   (control)
$ curl -o /dev/null -w '%{http_code}\n' 'http://127.0.0.1:8123/es/tours?destino[]=cusco'    -> 500
$ curl -o /dev/null -w '%{http_code}\n' 'http://127.0.0.1:8123/es/tours?experiencia[]=1'    -> 500
$ curl -o /dev/null -w '%{http_code}\n' 'http://127.0.0.1:8123/es/tours?page[]=2'           -> 200   (paginador sí aguanta)

$ curl -s 'http://127.0.0.1:8123/es/tours?destino[]=cusco' | grep -ao 'Array to string conversion'
Array to string conversion
```

**Impacto.** No hay inyección SQL: el valor va por *binding* y el `{locale}` está acotado por la restricción de
ruta `[A-Za-z-]+` más el 404 del middleware, así que la ruta JSON no es manipulable (verificado en V-3). Lo
que hay es un error no controlado, anónimo y trivial de disparar: 958 KB de página de error por petición
(con `APP_DEBUG=true`, hoy, **traza completa con rutas del servidor**), ruido en logs y un 500 que un rastreador
puede encontrar solo si alguien enlaza mal el filtro. En producción con `APP_DEBUG=false` se queda en un 500
genérico, pero sigue siendo un 500 que no debería existir.

**Remediación:** normalizar antes de filtrar, p. ej.
`$destinationSlug = $request->string('destino')->toString() ?: null;` (`string()` colapsa el array a cadena
vacía en vez de reventar), o validar los dos filtros con `['nullable','string','alpha_dash','max:140']` y
descartar lo que no cumpla. **Le toca a:** `backend-laravel`.

---

# B. Exige estar autenticado en el panel

## F-2 · [ALTO] `/storage/` es zona de ejecución de PHP: la única barrera contra ejecución remota es la validación de subida

Este es el hallazgo que más importa entender bien, porque **se arregla en dos sitios distintos** según de dónde
venga cada eslabón. Lo separo.

### Parte A — El eslabón de la máquina/servidor: ¿ejecuta PHP? **SÍ. Y viaja al servidor.**

```
$ cp png_php.png  storage/app/public/audit-probe.png
$ cp plain.php    storage/app/public/audit-probe.php     # contiene: <?php echo "PACHA-RCE-PLAIN"; ?>
$ cp evil_svg.jpg storage/app/public/audit-probe.svg

$ curl -i http://tours-word.test/storage/audit-probe.php | head -1
HTTP/1.1 200 OK
$ curl -s http://tours-word.test/storage/audit-probe.php
PACHA-RCE-PLAIN                                   <-- EJECUTADO, no descargado

$ curl -o /dev/null -w 'http=%{http_code} type=%{content_type}\n' http://tours-word.test/storage/audit-probe.png
http=200 type=image/png
$ curl -o /dev/null -w 'http=%{http_code} type=%{content_type}\n' http://tours-word.test/storage/audit-probe.svg
http=200 type=image/svg+xml                       <-- un SVG aquí sería XSS en el propio origen
```

**¿Es esto una rareza de Laragon o viaja con el proyecto?** Viaja con el proyecto. Desglose honesto:

- El enlace `public/storage → storage/app/public` **lo crea el proyecto**: `artisan storage:link` está
  documentado como paso de instalación en `docs/lote-2/README.md:21`. No es de Laragon.
- `public/.htaccess` es el de Laravel de serie y **no restringe nada**: `Options -MultiViews -Indexes`, y la
  regla `RewriteCond %{REQUEST_FILENAME} !-f` hace que **todo archivo real** bajo el docroot — incluido
  `public/storage/loquesea.php` — lo sirva Apache directamente, sin pasar por Laravel.
- **No existe ningún `.htaccess` dentro del directorio de subidas**:
  `find storage public/storage -name '.htaccess'` → sin salida.
- Que un `.php` bajo el docroot se ejecute es el comportamiento **por defecto** de Apache/LiteSpeed y de
  cualquier cPanel compartido, no una configuración particular de esta máquina.

**Lo que NO puedo verificar y declaro sin probar:** este proyecto todavía no tiene servidor. No puedo comprobar
si el hosting final seguirá el symlink (`Options +FollowSymLinks` / `SymLinksIfOwnerMatch`) ni si tendrá el
handler de PHP activo en ese directorio. Lo medido aquí es Apache 2.4.54 con PHP 8.2.1 en esta máquina. Lo que
sí afirmo con evidencia es que **el proyecto no trae nada que lo impida**, así que hay que asumir que en el
hosting se comporta igual hasta que alguien mida lo contrario allí.

### Parte B — El eslabón del código: ¿los repetidores nuevos heredan el remedio? **SÍ. No se lo saltaron.**

Esta era la pregunta que podía convertir todo esto en un crítico. La respuesta es que no.

Los dos formularios nuevos del lote (`DestinationForm`, `ExperienceForm`) tienen **dos** campos de subida cada
uno (imagen de portada + repetidor de galería) y **los cuatro** pasan por
`App\Filament\Support\SecureImageUpload::configure()`:

```
  DestinationForm.php    FileUpload::make = 2 | SecureImageUpload::configure = 2   TODOS cubiertos
  ExperienceForm.php     FileUpload::make = 2 | SecureImageUpload::configure = 2   TODOS cubiertos
```

Y el remedio **funciona de verdad**. Batería con archivos reales en disco (no `UploadedFile::fake()`, que
escribe 0 bytes y daría un falso verde), bajo `runningUnitTests() = false` — comprobado explícitamente, porque
en modo test el MIME de Livewire sale del *nombre* del archivo y toda la prueba sería mentira:

```
runningUnitTests() = false

ARCHIVO            MIME (finfo)     VALIDACION   ESPERADO   EXT. EN DISCO
----------------------------------------------------------------------------
ok.jpg             image/jpeg       ACEPTAR      ACEPTAR    jpg
ok.png             image/png        ACEPTAR      ACEPTAR    png
png_php.png        image/png        ACEPTAR      ACEPTAR    png      <- PNG válido con PHP anexado
evil_svg.jpg       image/svg+xml    RECHAZAR     RECHAZAR   --       <- SVG con <script>, extensión mentida
evil_gifphp.png    image/gif        RECHAZAR     RECHAZAR   --       <- políglota GIF+PHP
evil_html.webp     text/html        RECHAZAR     RECHAZAR   --
plain.php          text/x-php       RECHAZAR     RECHAZAR   --

RESULTADO: la lista blanca se comporta como lista blanca (0 discrepancias)
```

**Control negativo — ¿mi prueba sabe fallar?** Sí: los mismos dos archivos contra lo que habría puesto
`->image()` (que es lo que el proyecto tenía antes):

```
  evil_svg.jpg      con ->image(): ACEPTA   <-- por esto no basta ->image()
  evil_gifphp.png   con ->image(): ACEPTA
```

Cadena de evidencia del lado del servidor, para que no quede como acto de fe:

- `acceptedFileTypes()` **sí** emite regla de servidor: `vendor/filament/forms/src/Components/BaseFileUpload.php:262-266`
  registra `"mimetypes:{$types}"`.
- La regla `mimetypes` se resuelve contra el **contenido**, no el nombre:
  `vendor/livewire/livewire/src/Features/SupportFileUploads/TemporaryUploadedFile.php:66-90` →
  `detectMimeTypeFromContents()` → `FinfoMimeTypeDetector` sobre los primeros 64 KB.
- La extensión en disco sale del MIME detectado, nunca del cliente:
  `app/Filament/Support/SecureImageUpload.php:40-47`, `match()` cerrado con `default => 'bin'`, nombre
  `Str::ulid()` (sin travesía de ruta posible desde el nombre original).

`TourForm.php:187-215` mantiene su copia inline en vez de usar el helper (documentado a propósito en el
docblock de `SecureImageUpload`). La lógica es **idéntica** — la comparé línea a línea — pero son dos copias
de la misma regla de seguridad que ahora hay que recordar cambiar dos veces.

### Entonces, ¿qué severidad tiene?

**Alto, no crítico, y hoy no explotable.** No hay ejecución remota hoy: el eslabón B aguanta. Lo que hay es un
diseño de **barrera única**: entre un usuario del panel y la ejecución de código en el servidor hay exactamente
un `match()` y una regla `mimetypes`, sin nada detrás. Cualquiera de estas cosas rompe la cadena entera:

- un campo de subida futuro que use `->image()` o se olvide del helper (ya pasó una vez en este proyecto);
- un cambio en `TourForm` que no se replique en `SecureImageUpload` o al revés (son dos copias);
- `FILESYSTEM_DISK=public` en el `.env` de producción (ver F-4);
- un fallo en `finfo` del PHP del hosting.

Y el lote 3 **amplió** esa superficie: metió dos campos de subida nuevos dentro de la zona de ejecución.

### Remediación (no aplicada), por orden de valor

1. **Quitar la zona de ejecución** — es lo que convierte "subida mal validada" en "ejecución remota". En el
   `.htaccess` del directorio servido, o mejor en la configuración del vhost para `public/storage`:
   `php_flag engine off` (o `<FilesMatch "\.(php|phtml|pht|phar|html|htm|svg)$"> Require all denied </FilesMatch>`).
   Si el hosting es LiteSpeed/cPanel, el equivalente es `RemoveHandler .php .phtml` + denegar por `FilesMatch`.
   Esto hay que **versionarlo en el repo**, no dejarlo como paso manual del despliegue: un paso manual es
   exactamente el fallo que ya documentó el docblock de `RobotsController` sobre el `robots.txt` estático.
2. **Unificar las dos copias:** que `TourForm` use `SecureImageUpload::configure()`. Hoy son dos
   implementaciones de la misma regla de seguridad. La suite de `TourImageUploadSecurityTest` seguiría siendo
   válida y pasaría a proteger también al helper.
3. **Defensa en profundidad:** `Content-Type: application/octet-stream` +
   `Content-Disposition: attachment` + `X-Content-Type-Options: nosniff` para todo lo servido desde el
   directorio de subidas, o servir las imágenes por una ruta de Laravel en vez de por el symlink.

**Le toca a:** `backend-laravel` (puntos 1 y 2). El punto 1 debe quedar además en el checklist de `deployer`.

---

## F-4 · [MEDIO] La subida *temporal* de Livewire no restringe el tipo, y sólo la contiene una variable de entorno

**Medido:**

```
livewire.temporary_file_upload.disk  = NULL     -> disco efectivo: local
raiz del disco local                 = storage/app/private        (FUERA del docroot)
reglas de la subida temporal         = NULL     -> por defecto ['required','file','max:12288']
raiz del disco public                = storage/app/public         (DENTRO del docroot vía symlink)
```

Es decir: el endpoint `livewire/upload-file` acepta **cualquier tipo de archivo** (la lista blanca de imágenes
sólo actúa al guardar el formulario, no al subir el temporal). Hoy eso está **contenido**, y lo verifiqué:

```
$ curl -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8123/storage/livewire-tmp/.audit-probe.txt        -> 403
$ curl -o /dev/null -w '%{http_code}\n' -X PUT --data 'x' http://127.0.0.1:8123/storage/livewire-tmp/pwn.txt -> 403
$ curl -o /dev/null -w '%{http_code}\n' 'http://127.0.0.1:8123/storage/../../.env'                          -> 404
```

La ruta `storage.local` exige firma porque el disco `local` no declara `visibility` y
`Illuminate\Filesystem\ServeFile::hasValidSignature()` trata la ausencia como `private`. La travesía de ruta la
corta Flysystem (`PathTraversalDetected` → 404).

**El riesgo es el día del despliegue.** `FILESYSTEM_DISK=public` en el `.env` de producción — un cambio que
alguien hace de buena fe cuando "las imágenes no se ven" — mueve `livewire-tmp` a `storage/app/public`, que
está dentro del docroot y **ejecuta PHP** (F-2 parte A). A partir de ahí, cualquier usuario del panel sube un
`.php` por el endpoint temporal, que no valida tipo, y tiene ejecución remota sin necesidad siquiera de guardar
el formulario.

**Remediación:** fijar explícitamente el disco temporal en un `config/livewire.php` propio
(`'temporary_file_upload' => ['disk' => 'local', 'rules' => ['required','file','max:12288']]`) para que no
dependa de `FILESYSTEM_DISK`, y dejar `FILESYSTEM_DISK=local` documentado como requisito en el `.env.example`
y en el checklist de `deployer`. Con el punto 1 de F-2 aplicado, este vector también se cierra solo.

**Le toca a:** `backend-laravel` + `deployer`.

---

## O-1 · [BAJO] El slug de destino y experiencia no valida unicidad

`app/Filament/Resources/Destinations/Schemas/DestinationForm.php:40-44` y su gemelo de Experiencias declaran
`->rule('alpha_dash')` y `maxLength(140)`, pero **no** la validación de unicidad que sí tiene
`TourForm.php:74-80` vía `Tour::slugTaken()`.

Como el lote 3 estrenó la resolución pública por slug
(`ResolvesBySlugByLocale::findBySlugForLocale()` → `->first()`), dos destinos con el mismo slug hacen que uno
**quede inalcanzable en silencio**: publicado en el CMS, invisible en el sitio. No es un agujero de seguridad;
es integridad de contenido, y lo anoto porque nace de un cambio de este lote.

**Remediación:** replicar el patrón de `Tour::slugTaken()` en `Destination` y `Experience`.
**Le toca a:** `backend-laravel`. (Si además hay que decidir el comportamiento de producto ante el choque, eso
lo decide Anyerson, no yo.)

---

## O-3 · [BAJO] Restos de subidas temporales sin recolectar

`storage/app/private/livewire-tmp/` conserva un archivo del 2026-09-02. No es alcanzable sin firma (probado
arriba), así que el riesgo es de higiene y de espacio en disco, no de exposición. Conviene confirmar que el
recolector de Livewire corre (depende de que haya *scheduler* activo en producción, cosa que hoy no existe).

---

# C. Sólo importa el día del despliegue

- **F-2 punto 1** (bloquear ejecución de PHP en el directorio de subidas) — **bloqueante**.
- **F-1** (`trustHosts` + `ServerName` estricto) — **bloqueante**.
- **F-4** (fijar `FILESYSTEM_DISK=local` y el disco temporal de Livewire).
- **O-2 · `CONTACT_NOTIFY_EMAIL`: CONFIRMADO, SIGUE VIVO.** `.env` tiene `CONTACT_NOTIFY_EMAIL=` (vacío) y
  `MAIL_FROM_ADDRESS="hello@example.com"`. `config/contact.php:20` hace
  `env('CONTACT_NOTIFY_EMAIL') ?: env('MAIL_FROM_ADDRESS', 'hello@example.com')`. Con un SMTP real, **cada
  mensaje del formulario** — nombre, correo, teléfono, texto libre y la IP asociada — se enviaría a un dominio
  de terceros. El `?:` (no `??`) significa que una cadena vacía también cae al respaldo, así que dejar la
  variable declarada pero vacía **no** protege. Remedio: que `config/contact.php` **falle cerrado** si
  `CONTACT_NOTIFY_EMAIL` está vacío en producción, en vez de elegir un destinatario por su cuenta.
- Ya documentado y confirmado sin cambios: `APP_DEBUG=true`, `APP_ENV=local`, `APP_URL=http://127.0.0.1:8000`,
  sin cabeceras de seguridad, sin HTTPS forzado, cookie de sesión sin `Secure`, `SESSION_ENCRYPT=false`.
  El admin `dev@pachaviva.test` es el **único** usuario de la base local y debe **borrarse**, no rotarse.
  Consentimiento de Ley 29733 contra una política de privacidad que no existe.
- `preventFilePathTampering()` sigue desactivado, pero **mi medición cambia la lectura previa**: la ruta
  `/storage/{path}` del disco `local` **sí exige firma** (403 verificado), porque ese disco no declara
  `visibility`. El riesgo residual de esa opción es menor de lo que sugería la nota anterior.

---

# D. Verificaciones que dieron limpio (con prueba)

## V-1 · JSON-LD: no se puede romper el bloque `<script>`

`resources/views/components/seo/breadcrumb-jsonld.blade.php:44` y `faq-jsonld.blade.php:34` emiten con
`JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP`. Renderizando los **componentes reales** con
`Blade::render()` y midiendo con `DOMDocument` (contar elementos materializados, no `strpos`):

```
=== SUJETO: x-seo.breadcrumb-jsonld ===        === SUJETO: x-seo.faq-jsonld ===
  breakout-clasico     img=0 script=1  OK        (mismos 9 payloads, mismo resultado)
  breakout-espaciado   img=0 script=1  OK
  breakout-mayusculas  img=0 script=1  OK
  comilla-doble        img=0 script=1  OK
  comentario-html      img=0 script=1  OK
  cdata                img=0 script=1  OK
  unicode-escapado     img=0 script=1  OK        (U+2028)
  barra-sola           img=0 script=1  OK
  ampersand-entidad    img=0 script=1  OK

RESULTADO: 0 breakouts sobre 18 casos
```

**Control negativo — ¿el instrumento sabe fallar?** Los mismos payloads con `json_encode` sin las banderas
`JSON_HEX_*` (y con `JSON_UNESCAPED_SLASHES`, que es como se reabre este agujero en la vida real):

```
  breakout-clasico     img=1 script=1  detecta el breakout (instrumento OK)
  breakout-espaciado   img=1 script=1  detecta el breakout (instrumento OK)
  cdata                img=1 script=1  detecta el breakout (instrumento OK)
```

Y el JSON sigue siendo válido y fiel, que es lo otro que hay que comprobar (escapar rompiendo el dato no sirve):

```
  json_decode: VALIDO
  name recuperado: '</script><img src=x onerror=alert(1)>'
  ¿idéntico al original? SI
  emitido: ..."name":"<\/script><img src=x oner...
```

**Nota de diseño que conviene conservar:** las migas se construyen con `route()`
(`destinations/show.blade.php:13-16`), no con texto del CMS, así que no hay `href` controlable por la clienta
que pueda acabar como `javascript:` en `x-ui.breadcrumbs`. El día que una miga tome su `href` del CMS, eso deja
de ser cierto.

## V-2 · `is_admin` y acceso al panel

```
fill([... is_admin=>true])   -> is_admin = NULL      (bloqueado)
isFillable('is_admin')       -> false
new User([... is_admin=>1])  -> NULL
$fillable = ["name","email","password"]

usuarios en la BD local (pachaviva):
  #1  Dev Local   dev@pachaviva.test   is_admin=true
```

`User::canAccessPanel()` (`app/Models/User.php:52-55`) exige `is_admin`. No hay ruta de registro ni
`UserResource` (`route:list` completo revisado: sólo `admin/login`, `admin/logout` y los recursos del CMS). El
login de Filament **sí** trae límite de intentos de serie:
`Filament\Auth\Pages\Login` → `tooManyAttempts($rateLimitingKey, maxAttempts: 5)`.
`POST /contacto` tiene `throttle:5,10` (`routes/web.php`, `config/contact.php:36-38`).

## V-3 · Resolución por slug, 301 del histórico e idioma

```
/es/tours            -> 200      /pt-br/tours   -> 404     /es-ES/tours -> 404
/en/tours            -> 200      /xx/tours      -> 404     /e'/tours    -> 404
/ES/tours            -> 301  (normalización a minúsculas, conserva path y query)

/es/tours/no-existe            -> 404
/es/tours/%27%20OR%201=1--     -> 404      (sin error de SQL: el valor va por binding)
/es/tours/..%2F..%2F.env       -> 404
/es/tours/%00                  -> 404
/es/destinos/cusco%27          -> 404
```

- **Inyección por el parámetro de ruta: no.** El `{locale}` que se interpola en la ruta JSON
  (`where("slug->{$locale}", ...)`) está acotado por la restricción de ruta `[A-Za-z-]+` **y** por el 404 de
  `SetLocaleFromUrl` contra `config('cms.active_locales')`. El `{slug}` va siempre por *binding*.
- **Redirección abierta por el 301 del histórico: no.** `TourController::redirectFromHistory()` usa
  `redirect()->route(...)`, que trata el slug como segmento de ruta y nunca como host. Probado con siete slugs
  hostiles — los siete se quedan dentro del sitio:

```
  '//evil.com'          -> http://.../es/tours///evil.com                 host=127.0.0.1   contenido
  'https://evil.com'    -> http://.../es/tours/https://evil.com           host=127.0.0.1   contenido
  "\r\nLocation: ..."   -> http://.../es/tours/%0D%0ALocation:%20https... host=127.0.0.1   contenido
  '%2F%2Fevil.com'      -> http://.../es/tours/%2F%2Fevil.com             host=127.0.0.1   contenido
```

  Además los tres formularios validan el slug con `alpha_dash`, que ya bloquea `/`, `:` y `\` en origen
  (`TourForm.php:73`, `DestinationForm.php:44`, `ExperienceForm.php:44`).
  *Matiz:* la cabecera `Location` sí lleva el host de la petición, así que F-1 la alcanza — pero el atacante
  sólo se redirige a sí mismo, salvo que lo combine con caché envenenada.
- **Enumeración de contenido no publicado: no.** Tanto `findBySlugForLocale()` como la búsqueda del histórico
  aplican el scope `published()`.
- **Locale arbitrario contra las columnas JSON traducibles: contenido.** Sólo llegan locales de
  `active_locales`. Y aunque llegara uno inventado, Spatie devuelve cadena vacía o cae al respaldo, sin error:
  `getTranslation('name','zz-ZZ',false) = ''` / `getTranslation('name','zz-ZZ',true) = 'Cusco'`.
  La escritura sólo ocurre por los formularios, que iteran `config('cms.active_locales')`.

> **Defecto funcional que vi de paso y NO es mío:** el controlador recibe el segmento **crudo** de la URL, no
> el locale canónico. Para `pt-br` eso consultaría `slug->pt-br` cuando la clave JSON es `slug->pt_BR`. Hoy es
> inerte porque PT-BR no está activo, pero se despierta el día que se active. **Lo derivo a `anyerson-qa` y
> `backend-laravel`**: no es superficie de ataque y no me toca a mí dictaminarlo.

## V-4 · Dependencias

```
$ composer audit           -> No security vulnerability advisories found.
$ npm audit --omit=dev     -> found 0 vulnerabilities
```

## V-5 · Otras salidas sin escapar

Los `{!! !!}` que quedan en las vistas (`components/ui/*.blade.php`, `contact.blade.php`, `tours/show.blade.php`)
son **iconos SVG del sistema de diseño**, pasados como props literales desde el Blade, no desde el CMS. El
contenido del CMS sale siempre por `{{ }}`: título, itinerario (`tours/show.blade.php:160-190`),
`meta_title`/`meta_description` (`layout.blade.php:118-138`) y textos alternativos.
Único punto a vigilar a futuro: `contact.blade.php:270-279` mete tres `Setting` de redes sociales en `href`
(`social_instagram_url` y compañía). `{{ }}` escapa el HTML pero **no** bloquea el esquema `javascript:`. Es
previo al lote 3 y exige panel, así que no lo cuento como hallazgo del lote; conviene añadir
`->url()` + validación de esquema `https://` en `Configuracion` cuando se toque esa página.

---

# E. Lo que NO verifiqué (declarado, no asumido)

- **Comportamiento del hosting real.** No hay servidor todavía. No puedo medir si seguirá symlinks ni si tendrá
  el handler de PHP activo bajo `public/storage`, ni qué vhost por defecto tendrá ante un `Host` desconocido.
  Todo lo de la sección C hay que **re-verificar en el servidor** el día del despliegue.
- **La suite existente de subidas.** No la ejecuté ni la di por buena: construí mi propia batería con archivos
  reales precisamente porque `UploadedFile::fake()` escribe 0 bytes y el MIME de Livewire en modo test sale del
  nombre. Mi conclusión sobre F-2 parte B **no depende** de esa suite. Si alguien quiere saber si esos tests
  valen, eso es un encargo para `anyerson-qa`, no para mí.
- **Flujo real de subida por el navegador.** Probé la cadena de validación del servidor (regla `mimetypes`,
  detección finfo, función de nombrado) con archivos reales, no arrastrando un archivo en el panel con
  Playwright. Es la capa que decide; el paso por el navegador no la cambia.
- **`Setting::get()` cacheado.** No audité la invalidación de caché de ajustes; no es superficie de seguridad.

---

# Lista accionable

**Bloquea producción**

1. `trustHosts` en `bootstrap/app.php` + `ServerName`/`ServerAlias` estrictos — **F-1** — `backend-laravel` + `deployer`.
2. Desactivar la ejecución de PHP en el directorio de subidas, **versionado en el repo** — **F-2 punto 1** — `backend-laravel`, verificado por `deployer`.
3. `CONTACT_NOTIFY_EMAIL` real, y que `config/contact.php` falle cerrado si está vacío — **O-2** — `backend-laravel`.
4. Borrar el usuario `dev@pachaviva.test`; `APP_DEBUG=false`, `APP_ENV=production`, `APP_URL` real, cookie `Secure` — `deployer`.

**Antes de seguir ampliando el CMS**

5. Unificar `TourForm` con `SecureImageUpload::configure()`: hoy son dos copias de la misma regla de seguridad — **F-2 punto 2** — `backend-laravel`.
6. Normalizar los filtros `destino`/`experiencia` del catálogo — **F-3** — `backend-laravel`.
7. Fijar el disco temporal de Livewire sin depender de `FILESYSTEM_DISK` — **F-4** — `backend-laravel`.

**Recomendado**

8. Unicidad de slug en Destino y Experiencia — **O-1** — `backend-laravel`.
9. Cabeceras de seguridad (CSP, `X-Content-Type-Options: nosniff`, `X-Frame-Options`, `Referrer-Policy`) — `deployer`.
10. Validar esquema `https://` en las URLs de redes sociales de `Configuracion` — `backend-laravel`.
11. Publicar la política de privacidad que el formulario ya exige aceptar (Ley 29733) — decisión de Anyerson/cliente.

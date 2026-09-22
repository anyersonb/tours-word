# 15 · URLs de imágenes del catálogo en el despliegue por subdirectorio

**Agente:** `backend-laravel` · **Fecha:** 2026-09-22 · **Rama:** `lote-1-sistema-diseno`

## 1. El diagnóstico: un único punto de origen, y por qué se rompe en subdirectorio

Todas las URL de imágenes del catálogo (tarjetas de tour, galerías, cabeceras de
destino/experiencia, mosaico de la home, equipo) pasan por el **mismo y único**
camino: `Storage::disk('public')->url($path)`, invocado desde seis accessors —
nunca se construye la URL a mano en ninguna vista.

| Accessor | Archivo |
|---|---|
| `TourImage::url()` / `TourImage::src` | `app/Models/TourImage.php:47` |
| `DestinationImage::url()` / `::src` | `app/Models/DestinationImage.php:47` |
| `ExperienceImage::url()` / `::src` | `app/Models/ExperienceImage.php:47` |
| `Destination::coverImageUrl()` | `app/Models/Destination.php:103` |
| `Experience::coverImageUrl()` | `app/Models/Experience.php:93` |
| `TeamMember::photoUrl()` | `app/Models/TeamMember.php:68` |

Ese `Storage::disk('public')->url()` a su vez resuelve por una sola línea:
`config/filesystems.php`, clave `disks.public.url`. Antes de este cambio estaba
fija: `'url' => '/storage'` — deliberadamente **raíz-relativa**, a propósito
desde el lote 1 (Defecto Alto del CRO, cubierto por
`tests/Feature/PublicDiskRelativeUrlTest.php`): así se evita el bug anterior
de armar la URL desde `APP_URL`, que rompía en silencio si `APP_URL` apuntaba a
un host que no resuelve.

El `og:image` de la home/layout (`resources/views/components/layout.blade.php:99`)
no pasa por aquí: usa `asset('images/site/og-default.jpg')`, un archivo
estático bajo `public/images/`, y nadie en el proyecto pasa hoy un `ogImage`
por tour — así que ese camino ya funciona bien en subdirectorio (el `asset()`
de Laravel sí resuelve contra la base real de la petición) y no requería tocarlo.

**Por qué el valor fijo `/storage` rompe en `limaviewtours.com/tour-word/`:**
una URL raíz-relativa como `/storage/tours/foo.webp` el navegador la resuelve
contra el **origen**, no contra el directorio de la página — pierde el
segmento `/tour-word` por completo. Y aunque lo llevara, el
`.htaccess` propio de la app (`docs/rediseno-2026/06-deploy.md` §5.1, regla 2)
bloquea con `[F]` cualquier ruta que empiece por `storage/` en la raíz de la
app — es la regla que protege `storage/app`, `storage/framework`, etc. Solo
`.../public/storage/...` esquiva esa regla y llega al directorio real
(`public/storage`, copia física de 187 archivos, confirmado por FTP). Medido
en el propio encargo: `/tour-word/storage/tour-camino-inca-800.webp` → 403;
`/tour-word/public/storage/tour-camino-inca-800.webp` → 200.

## 2. El cambio: una variable de entorno, con default que no toca nada

**Archivo tocado:** `config/filesystems.php`, disco `public`:

```php
'url' => env('PUBLIC_STORAGE_URL') ?: '/storage',
```

Deliberadamente **no** `env('PUBLIC_STORAGE_URL', '/storage')`. Con la forma
de dos argumentos, una clave *presente pero vacía* en el `.env`
(`PUBLIC_STORAGE_URL=` sin nada después — exactamente el estado en que puede
quedar un `.env` tocado a mano por FTP) devuelve la cadena vacía `''`, no el
default, y las URL de imagen quedarían rotas en silencio. Con `?:`, tanto la
ausencia total de la variable como la variable declarada-vacía caen al mismo
default `/storage` — idéntico al comportamiento de hoy. Verificado con un test
dedicado (§4).

Sin la variable declarada (local, y la futura producción real de Pacha Viva en
la raíz de su propio dominio): cero cambio de comportamiento.

## 3. Cómo se activa en el servidor

En el `.env` del despliegue de demo (`limaviewtours.com/tour-word/`), agregar
esta línea — no existe hoy en ese `.env` (ver `06-deploy.md` §4, que no la
tenía porque el defecto no estaba diagnosticado cuando se escribió):

```dotenv
PUBLIC_STORAGE_URL=/tour-word/public/storage
```

**Orden, igual que `IS_STAGING_MIRROR` (mismo punto de falla ya documentado en
este proyecto):** la línea va en el `.env` **antes** de correr
`config:cache`. Si se cachea sin ella, o con el nombre mal escrito, la config
vieja (`/storage`) queda congelada hasta el próximo `config:clear` +
`config:cache` — un fallo silencioso, no uno que avise.

No hace falta ninguna otra variable, ni tocar `.htaccess`, ni el
`public/storage` del servidor, ni tocar el `og:image` (no usa este disco).

## 4. Mediciones antes/después

Medido con `php artisan tinker` (no `artisan serve`: ese comando no propaga
`APP_URL` al proceso hijo y esta configuración no depende de
`Request::root()` en absoluto — es pura config, así que el riesgo señalado en
el encargo no aplica aquí, pero se midió con tinker para no dar por sentado
nada).

**Antes / local, sin `PUBLIC_STORAGE_URL` (estado real del `.env` local, sin
tocar):**
```
config('filesystems.disks.public.url')                                 = /storage
Storage::disk('public')->url('tours/tour-camino-inca-800.webp')        = /storage/tours/tour-camino-inca-800.webp
```
Idéntico al valor que producía el código antes del cambio.

**Después / simulando el subdirectorio** (`PUBLIC_STORAGE_URL` inyectada solo
para esta medición, sin tocar el `.env` real; primer intento con Git Bash
reescribió la ruta a un path de Windows por su auto-conversión de rutas
POSIX-Windows — repetido con `MSYS_NO_PATHCONV=1` para obtener el valor
literal):
```
config('filesystems.disks.public.url')                                 = /tour-word/public/storage
Storage::disk('public')->url('tours/tour-camino-inca-800.webp')        = /tour-word/public/storage/tours/tour-camino-inca-800.webp
```

## 5. Tests

Archivo nuevo: `tests/Feature/PublicStorageUrlSubdirectoryOverrideTest.php` (3
tests, 7 aserciones). No repite la cobertura de default ya existente en
`PublicDiskRelativeUrlTest`; agrega:
- Control negativo: `env('KEY', '/storage')` con la clave presente-vacía
  devuelve `''` (demuestra por qué **no** se usó ese patrón).
- La fórmula real (`env('PUBLIC_STORAGE_URL') ?: '/storage'`) para los tres
  estados: ausente, presente-vacía, presente-con-valor.
- Con el override activo, `Destination::coverImageUrl()` y `TourImage::url()`
  emiten el prefijo completo, literal (incluye una falsación explícita contra
  que se pierda el segmento `/public`).

**Suite completa**, corrida contra MySQL local (`pachaviva_test`, como exige
`phpunit.xml`; el MySQL local estaba caído al empezar esta sesión — se levantó
el `mysqld` de Laragon para poder correr la suite real, sin tocar ninguna
base de datos de contenido):

```
346 tests, 1514 assertions — OK
```

Línea base previa: 343/1507. Los +3 tests / +7 aserciones son los nuevos de
este cambio; cero regresiones.

## 6. Pendiente para quien ejecute el despliegue real

Este documento no sube nada ni modifica `06-deploy.md`. Cuando se prepare el
`.env` real de `06-deploy.md` §4, agregar ahí la línea de `PUBLIC_STORAGE_URL`
de la §3 de este documento, en el mismo paso que `IS_STAGING_MIRROR` y
`config:cache` (§7 de ese documento).

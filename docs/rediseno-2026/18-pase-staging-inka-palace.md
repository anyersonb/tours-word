# 18 · Pase a staging — Home Inka Palace (PDF) + hoja de marca oficial

**Fecha:** 2026-11-18 · **Rama:** `main` (`f84c610`) · **Destino:** `https://www.limaviewtours.com/tour-word/es` (Basic Auth)
**Autorización:** Anyerson en la conversación ("súbelo a staging", "valida que en staging esté todo ok", "avanza").
**Estado:** SUBIDO y verificado por hash. Render en staging PENDIENTE (falta la clave del Basic Auth y un MCP de navegador).

## Hallazgo previo
El servidor no estaba en `ccf4681`: el 12–13/10 se subió por FTP, sin commit, un rebrand "INKA PALACE Travel"
con la hoja de marca oficial. Se rescató en la rama local `staging-2026-10-13` (`bb51232` + `6073dd5`) =
copia exacta de lo que había en el servidor antes de este pase. `main` lo integró (merge `35ca864`).

## Qué se subió (FTP explícito TLS, host `paul.ihost1002.com` — el certificado no cubre ftp.limaviewtours.com)
- Fase 1 · 39 archivos estáticos: `public/build/assets/*` nuevos, `public/fonts/poppins/*`, `public/img/**`.
- Fase 2 · 55 archivos: app, config (`brand.php`), lang, routes, resources (vistas, css, js).
- Fase 3 · `bootstrap/cache/config.php` y `routes-v7.php` **renombrados** a `*.bak-20261118` (la ruta `home`
  pasó a controlador y `config/brand.php` es nuevo; sin caché Laravel lee config y rutas en vivo).
- Al final `public/build/manifest.json` (CSS `app-C1U5UtqD.css`, JS `app-BAakh1VB.js`).
- Verificación: los 95 archivos del servidor son idénticos por SHA-1 a los locales.
- No se borró nada (fuentes Inter/Bodoni/Public Sans viejas siguen en el servidor, sin uso). `.env` sin cambios
  (`APP_NAME="INKA PALACE Travel"`, `IS_STAGING_MIRROR=true`). Sin migraciones nuevas.

## Humo
| Check | Resultado |
|---|---|
| Lima View prod `/es` antes/después | 200 · 309.861 b / 200 · 309.867 b ✅ |
| `/tour-word/es` sin credenciales | 401 ✅ |
| Render de la demo con auth | ⏳ SIN VERIFICAR |
| OPcache reset | ⏳ pendiente (lo corre Anyerson) |

## Rollback
1. Subir por FTP, desde la rama `staging-2026-10-13`, cada archivo de la lista
   (`git diff --name-status staging-2026-10-13 f84c610`, excluyendo docs/tests) — los "A" se pueden dejar.
2. Subir el manifest previo (`C:\laragon\www\_rollbacks\tour-word-20261118\manifest-servidor-antes.json`,
   apunta a `app-CMIOx4lq.css`/`app-CjmmD5Mk.js`, que siguen en el servidor).
3. Renombrar `bootstrap/cache/*.bak-20261118` a su nombre original y resetear OPcache.

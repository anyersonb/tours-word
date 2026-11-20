# 24 · Pase a staging — Home fiel al PDF (paleta del diseño)

**Fecha:** 2026-11-20 (madrugada) · **Rama:** `main` (`62f61b1`, sin push) · **Destino:** `https://www.limaviewtours.com/tour-word/es` (Basic Auth)
**Autorización:** Anyerson en la conversación ("ponte modo nocturno y sube a staging te autorizo").

## Qué entra
- `4594930` (admin con marca + CRO mobile, que no se había subido) + `62f61b1` (Home fiel al PDF).
- Decisiones de Anyerson (2026-11-19): **colores del PDF** (dejan de mandar los de la hoja de marca) y **FAQ con textos de ejemplo creíbles**; testimonios/ratings ocultos hasta tener los reales.
- Cadena: ojo pasada 1 (`21-…`) → programación + maquetación → QA inventario (`22-…`, GO condicional) + ojo pasada 2 (`23-…`, listo como demo) → vuelta 2 (R1/A1/A2/M1-M3/B-01/B-02/B-05/B-06) → 385 tests en verde.

## Subida (FTPS, `paul.ihost1002.com`, `/public_html/tour-word`)
- Comparación previa por hash: el servidor estaba exactamente en `f84c610` + el `.htaccess` del 19-nov (= `4594930`). Nada que rescatar.
- 147 archivos (`git diff --diff-filter=AM f84c610 62f61b1` sin docs/tests/factories/scripts) + `app-9wWexg4r.css`, `app-D7hVXUuO.js` y `manifest.json` al final.
- Verificación: **147/147 idénticos por SHA-1** al local.
- Sin migraciones ni paquetes de composer nuevos. El servidor no tiene caché de config ni de rutas (renombradas en el pase 18), así que Laravel lee `config/brand.php` y `routes/web.php` en vivo.
- Gotcha: la conexión FTPS se corta (WinError 10054) tras ~120 transferencias seguidas; reintentar el resto en otra conexión funciona.
- Script: `C:\laragon\www\_rollbacks\twdeploy.py` (verify-ref / verify-local / backup / upload).

## Humo
| Check | Resultado |
|---|---|
| Lima View prod `/es` antes/después | 200 · 313.994 b / 200 · 313.994 b ✅ |
| `/tour-word/es` sin credenciales | 401 ✅ |
| Render de la demo con auth | ⏳ SIN VERIFICAR (falta la clave del Basic Auth) |
| OPcache reset | ⏳ pendiente (lo corre Anyerson) |

## Rollback
Respaldo de los 70 archivos que ya existían: `C:\laragon\www\_rollbacks\tour-word-20261120\servidor-antes\` (+ `lista-subida.txt`).
Subirlos con `twdeploy.py upload` desde esa carpeta. Los 77 nuevos pueden quedarse: el manifest anterior no los referencia.

## Pendiente de la clienta (bloquea "tal cual PDF" en producción)
Fotos originales en alta resolución (sobre todo hero Lima/Ica/Machu Picchu), precios, duración/idiomas y rating de los tours, testimonios y ratings reales, cifras de la banda, validar las 5 respuestas de FAQ.

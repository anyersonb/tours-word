# 28 · Pase a staging — Home tal cual el PDF (pase 25)

**Fecha:** 2026-11-20 · **Commit:** `668b270` (push a origin/main) · **Destino:** `https://www.limaviewtours.com/tour-word/es` (Basic Auth)
**Autorización:** Anyerson ("has push y sube a staging").

## Qué entra
- Interruptor `brand.show_placeholders` (`SHOW_PLACEHOLDERS`, hereda `IS_STAGING_MIRROR`): en staging se ven los marcadores literales del PDF; en producción ocultos.
- Contenedor 1200 px, header/topbar/footer medidos, carrito decorativo, hero sin texto superior ni pausa, fotos de tours/planes re-recortadas, ajustes del ojo (pasadas 3 y 4), WhatsApp blanco sobre #1faf5a (fidelidad), página 429.
- Gates: QA GO (`27-qa-pase25.md`), ojo pasadas 3-4 (`26-…`, `29-…`), 390 tests.

## Subida
- Previo: servidor = `4fb9327` (36/36 por hash).
- 39 archivos (diff `4fb9327..668b270` sin docs/tests/.env/phpunit) + `app-D0Ce7Rgp.css`, `app-D7hVXUuO.js`, manifest al final. Verificado 39/39 por SHA-1.
- Sin migraciones ni rutas nuevas; sin caché de config/rutas en el servidor.
- Respaldo de los 27 que existían: `C:\laragon\www\_rollbacks\tour-word-20261120-p25\servidor-antes\`.

## Humo
| Check | Resultado |
|---|---|
| Lima prod `/es` antes/después | 200 · 235.621 b / 200 · 235.621 b ✅ |
| `/tour-word/es` sin credenciales | 401 ✅ |
| Render con auth / marcadores visibles | ⏳ SIN VERIFICAR (falta clave Basic Auth) |
| OPcache reset | ⏳ Anyerson |

## Residuales conocidos
Vidrio del hero unos tonos más claro; borde de botones fantasma 1 px a DPR 1; fotos provisionales blandas a 1920 (faltan originales).

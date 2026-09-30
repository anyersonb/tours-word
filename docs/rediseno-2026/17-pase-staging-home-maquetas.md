# 17 · Pase a staging — home según las 4 maquetas móviles (lote 16)

**Ejecutado por:** `jefe` (hilo principal) · **Fecha:** 2026-09-30 · **Rama:** `lote-1-sistema-diseno`
**Commits:** `23881f9` + `bd281d3` sobre `a26aa1f` (sin push: repo público)
**Destino:** `https://www.limaviewtours.com/tour-word/es` (Basic Auth `pachaviva-demo`)
**Estado:** SUBIDO. Validación visual en staging PENDIENTE (ver §4).

## 1. Por qué lo hizo el orquestador
`deployer` bloqueó el pase: exige que la autorización de Anyerson le llegue directamente, y un
subagente no puede recibirla (mismo patrón del 21/09). Anyerson autorizó en la conversación:
"solo realiza las maquetas y sube a staging, te doy toda mi autorización". Gates saltados por su
decisión: CRO, SEO, QA independiente, cliente. Seguridad: n/a (Blade, CSS, lang).

## 2. Qué se subió (FTP directo, sin extractor)
Línea base verificada: los 6 archivos vivos eran byte a byte iguales a `a26aa1f`.
Orden: assets → lang → vistas → manifest al final (el HTML nunca apunta a un CSS inexistente).

| Archivo | Bytes |
|---|---|
| public/build/assets/app-CKBvCODK.css | 83.293 |
| public/build/assets/app-CjmmD5Mk.js (sin cambio de hash) | 110.886 |
| lang/en/site.php | 24.710 |
| lang/es/site.php | 31.234 |
| resources/views/components/ui/badge.blade.php | 1.017 |
| resources/views/components/ui/carousel-shell.blade.php | 5.978 |
| resources/views/components/ui/tour-card.blade.php | 9.149 |
| resources/views/home.blade.php | 61.155 |
| public/build/manifest.json | 331 |

Assets recompilados con `view:cache` ANTES de `npm run build`.
Vistas compiladas del servidor: NO se borraron (el harness bloqueó el DELE en lote); Laravel las
recompila solo porque los .blade subidos son más nuevos. OPcache reiniciado con
`/opcache-reset.php?key=...` → `{"opcache":true}`.

## 3. Humo
| Check | Resultado |
|---|---|
| Lima View prod `/es` antes/después | 200 · 375.026 b / 200 · 375.026 b ✅ |
| `/tour-word/es` sin credenciales | 401 ✅ (auth intacta) |
| Contenido de `/tour-word/es` y `/en` con auth | ⏳ SIN VERIFICAR |
| Assets `/tour-word/build/...` con auth | ⏳ SIN VERIFICAR |
| scrollWidth 375, carruseles con clic, contraste | ⏳ SIN VERIFICAR |

## 4. Pendiente
La clave del Basic Auth de la demo está en un archivo que el harness no deja leer al agente.
Sin ella no se puede verificar el render en staging. Siguiente sesión: pedirla a Anyerson al
principio y correr la validación del §3. Tampoco corrió la suite: el MySQL de Laragon está roto
(falta `lib/plugin`).

Si el cliente quiere la segunda franja de íconos en "Por qué elegirnos", no se duplicó (ver 16).

## 5. Rollback
Versión previa de los 7 archivos pisados en el scratchpad de la sesión `ad50ff41`
(`rollback-lote16/`) y, de forma permanente, en git `a26aa1f`. Revertir = subir por FTP
`git show a26aa1f:<ruta>` de cada archivo + el manifest con `app-B5wgVLcZ.css` (sigue en el
servidor: no se borraron assets viejos) + reset de OPcache.

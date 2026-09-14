# Perú Local (nombre provisional) — sitio de agencia de turismo

Repositorio del proyecto. Al 01/09/2026 está en el **lote 0** (descubrimiento e identidad
provisional): todavía no hay aplicación, solo los entregables de arranque.

- El plan completo (18 pantallas públicas, 12 módulos de CMS, 6 lotes, 30–38 días de trabajo
  efectivo) vive en un artifact interno. Es **interno**: no se envía a la clienta con precios
  ni notas de otros proyectos.
- El contexto que necesitan los agentes está en `.claude/proyecto/00-contexto.md`.
- Los entregables por lote van en `docs/lote-N/`.

## Estado

| Lote | Qué entrega | Estado |
|---|---|---|
| 0 | Identidad provisional, nombres con dominio libre, checklist a la clienta | en curso |
| 1 | Sistema de diseño + Home/Nosotros/Contacto en staging noindex | bloqueado (faltan mockups) |
| 2 | Laravel 12 limpio, contrato de datos, CMS de catálogo | **listo, sin desplegar** — ver `docs/lote-2/` |
| 3 | Resto de pantallas públicas | pendiente |
| 4 | Reservas y cobro (PEN + USD) | pendiente |
| 5 | SEO, EN/PT y salida a producción | pendiente |

Los seis estados de validación corren al cierre de **cada** lote, no solo al final.

## Despliegue: verificar el día que exista un servidor real

F-2 (docs/lote-3/seguridad-2026-09-14.md, Alto). `storage/app/public/.htaccess`
desactiva la ejecución de PHP dentro del directorio de subidas (alcanzable
directo desde la web por el symlink `public/storage`, creado por
`artisan storage:link`) — verificado en local: un `.php` subido ahí a mano
pasó de ejecutarse (200, resultado de una suma) a bloquearse (403), y un
`.png` legítimo sigue sirviéndose sin cambios.

Un `.htaccess` **no protege si el hosting lo ignora**. El día que exista el
servidor real, antes de publicar:

- **Confirmar que `AllowOverride` está activo** para `storage/app/public/`
  (y que ningún `.htaccess` intermedio lo desactiva) — sin esto, Apache
  ignora el archivo por completo y la protección no existe aunque esté en
  el repo.
- **Si el hosting es LiteSpeed** (típico en cPanel compartido): LiteSpeed
  dice soportar `.htaccess` estilo Apache, pero esto **no fue probado**
  contra una instancia real — repetir la prueba de arriba (subir un `.php`
  de prueba, pedir su URL, confirmar que NO se ejecuta, borrarlo) antes de
  dar por buena la protección.
- **Si el hosting corre PHP-FPM detrás de un proxy** (nginx delante de
  Apache, o LiteSpeed con su propio PHP handler), las reglas de
  `RemoveHandler`/`FilesMatch` de un `.htaccess` pueden no alcanzar al
  proxy — puede hacer falta la regla equivalente en la configuración del
  servidor web, fuera del alcance de este repositorio.
- Repetir también la verificación de `FILESYSTEM_DISK=local` (F-4): un
  `.env` de producción con `FILESYSTEM_DISK=public` movería el directorio
  temporal de Livewire dentro de esta misma zona, sin la validación de tipo
  que sí tiene el formulario.

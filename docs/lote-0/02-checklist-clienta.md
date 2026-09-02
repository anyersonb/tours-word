# Lote 0 — Información a pedirle a la clienta

Cada fila dice **qué lote bloquea** si no llega. Los marcados como "producción" no impiden
avanzar, pero sí impiden publicar el sitio.

## Bloquean producción (sin esto no se publica)

| Dato | Para qué | Estado |
|---|---|---|
| Razón social exacta y **RUC** | Pie de página, comprobantes, términos y condiciones | pendiente |
| **RNAVT / registro MINCETUR** | Sello y número en el sitio, como agencia de viajes formal | pendiente |
| Cuenta o pasarela de cobro (PayPal, Culqi, Izipay, transferencia) con sus credenciales de **sandbox** primero | Lote 4: sin sandbox no se puede probar una reserva de punta a punta | pendiente |
| **Política de cancelación y reembolso**, escrita por ella | Pantalla legal y correos de reserva. No la inventamos nosotros | pendiente |
| Afiche/aviso **ESNNA** y **Libro de Reclamaciones** — confirmar con su contador si le aplican y en qué forma | Obligatorios habituales de un negocio turístico en Perú | pendiente |
| Política de privacidad y tratamiento de datos personales | Formularios de contacto y reserva | pendiente |

## Bloquean el lote 1 (diseño)

| Dato | Para qué | Estado |
|---|---|---|
| **Los 3 mockups** en su archivo original | Replicarlos pixel-perfect. Hoy no los tenemos en disco | **pendiente — pedir ya** |
| Logo actual en vectorial (AI, SVG, EPS o PDF), si existe | Hoy solo hay un boceto en los mockups | pendiente |
| Aprobación del **nombre** (ver `01-nombres.md`) | Todo lo demás cuelga del nombre: dominio, logotipo, correos | **pendiente — decide Anyerson y luego la clienta** |

## Bloquean el lote 2 y 3 (catálogo y contenido)

| Dato | Para qué | Estado |
|---|---|---|
| Lista real de tours: nombre, duración, qué incluye y qué no, punto de encuentro, precio en **PEN y USD** | El catálogo. Con dos monedas desde el inicio hacen falta los dos precios, o el tipo de cambio | pendiente |
| **Tipo de cambio**: ¿lo fija ella en el CMS o se toma de una API? | Decisión abierta del proyecto | **pendiente — decide Anyerson** |
| Fotos originales en alta (no de banco, no de Google) | Sin fotos propias el sitio no se distingue de la competencia | pendiente |
| Zonas o puntos de recogida, si aplica | Formulario de reserva | pendiente |
| Textos de "Nosotros": desde cuándo operan, cuántas personas son, qué las diferencia | **Cifras verificables solamente.** Si no hay dato, el campo queda vacío | pendiente |
| Reseñas existentes: enlaces de Google, TripAdvisor | Bloque de reseñas | pendiente |

## Operativos

| Dato | Para qué | Estado |
|---|---|---|
| Correo de contacto y a dónde llegan las reservas | Notificaciones del sitio | pendiente |
| WhatsApp y teléfono, con horario de atención | CTA principal | pendiente |
| Redes sociales activas | Pie de página y datos estructurados | pendiente |
| Hosting y dominio: ¿los tiene o los contratamos? | Define el deploy del lote 5 | pendiente |
| Accesos a Google Analytics / Search Console, si existen | Medición | pendiente |

# Contexto — proyecto de agencia de turismo (nombre provisional "Perú Local")

Clienta nueva, agencia de turismo peruana. Sitio público + CMS, desde cero.
Marca de trabajo: AnyersonDev. Idioma de los entregables: español.

## Decisiones de arranque (cerradas por Anyerson el 01/09/2026)

- **Motor**: instalación limpia de **Laravel 12**. Los módulos de Lima View se traen como
  **código, nunca la base de datos**. Ya pasó tres veces que datos de un proyecto aparecieron
  publicados en otro; no se clona la BD bajo ninguna circunstancia.
- **Idiomas**: **español desde el día uno**, con la arquitectura multiidioma ya puesta
  (Setting + lang files + metas por URL e idioma). **EN y PT entran en el lote 5.**
  No dejar dos agentes editando el mismo lang file en paralelo.
- **Moneda**: **PEN + USD desde el inicio** (decisión de Anyerson, contra la recomendación de
  arrancar con soles solos). `Money::site()` es la **fuente única**; ningún número de moneda
  cableado en vistas. **ABIERTO**: cómo se fija el tipo de cambio (valor editable en el CMS vs.
  API). El lote 4 debe probar el cobro en **las dos** monedas.

- **Nombre**: **Pacha Viva** (elegido por Anyerson el 01/09/2026; reemplaza a "Peru Local",
  cuyo .com esta registrado). **EN REVISION**: la prueba de colision hecha esa misma noche
  encontro que "Pacha" y "Viva" son tokens muy usados por agencias peruanas, asi que el nombre
  comparte el defecto que se le criticaba a "Peru Local". Ver la ronda 2 de
  `docs/lote-0/01-nombres.md`: decide Anyerson. Dominios: `pachaviva.com` y `pachavivaperu.com`,
  libres al 01/09 y **sin comprar**. Antes de comprar: INDECOPI, `.pe` en NIC.pe a mano, redes,
  y aprobacion de la clienta.
- **Tipo de cambio PEN/USD**: **valor fijo editable en el CMS** (Configuracion), no API. El precio
  en USD no debe cambiar solo debajo de una reserva en curso.

## Reglas del proyecto (salen de incidentes reales, no de teoría)

1. Un campo del CMS no está listo hasta que el front lo lee **y** su caché se invalida sin
   limpiar nada a mano.
2. `view:cache` antes de `npm run build`: Tailwind lee las vistas compiladas y sin eso purga
   clases que sí se usan.
3. Nada de cifras inventadas por defecto (años de experiencia, número de clientes). Un default
   que publica un número sin respaldo es un defecto, no un pendiente de configuración.
4. Contenido de muestra visible **como tal**. Si no hay dato real, el campo queda vacío.
5. Al medir en el navegador: scroll instantáneo, no smooth; y medir el fondo real de la sección
   antes de elegir el color del texto.
6. Mientras un validador certifica, **el árbol se congela**.
7. Los validadores no aprueban por captura: clic real y `getBoundingClientRect()`.
8. Playwright es un navegador único y compartido: los agentes que navegan van en serie.
9. La analítica se prueba contra el CSP en producción, no solo en local.
10. Tras tocar el CMS, humo post-deploy pulsando el botón que usa la clienta.

## Marca

Todo el color cuelga de un solo número: `--brand-h: 155` (verde bosque de los mockups).
Motivos gráficos: **geometría abstracta**, no figurativa — a baja opacidad un motivo figurativo
se lee como otra cosa.

## Obligatorios peruanos

RNAVT/MINCETUR, razón social y RUC, cuenta de cobro y política de cancelación **bloquean
producción** (no el arranque). Ver `docs/lote-0/02-checklist-clienta.md`.

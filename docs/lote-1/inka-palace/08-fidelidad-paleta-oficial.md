# 08 · Fidelidad perceptual con la paleta oficial (ojo)

Fecha: 2026-11-18. Comparé `http://127.0.0.1:8000/es/?muestra=1` a 1440 contra `referencia/s00..s10.jpg`. También revisé el Home a 375, y `/es/tours` y `/es/tours/muestra-camino-inca-4-dias` a 1440 y 375.
- Herramienta: un solo Chrome headless (Playwright), con `cookie_consent=all` y `reducedMotion`.
- Comparador: `qa/ojo-pal/index.html` (PDF a la izquierda, render a la derecha), más zooms del logo (DPR 2 y 3).
- **El color ya no se juzga contra el JPEG.** La referencia de color es la hoja oficial: Espresso #3E2F24, Sage #556B52, Champagne Gold #C9A96A, Ivory #FAF7EE.
- No repito los deltas declarados en 06/07: contenedor 1440/40, costuras de las fotos provisionales, `[N]`/`[PRECIO]` y los 2 destinos del footer (dato).

## 1. Estructura, composición y tipografía frente al PDF (1440)
Respecto al 06 hay avances claros. Los títulos de card de tours, planes y razones ya van en Poppins. Ya están el chip de ciudad y el chip "Muestra" (este ya no pisa la flecha). La nota de muestra no tapa el eyebrow. El precio del banner Camino Inca es protagonista. La secuencia de secciones, los wraps de los H2 y la grilla coinciden con el PDF.

Deltas que quedan:
- 🔴 **Tours destacados: el precio dice "[PRECIO]" sin el "S/"**. El PDF dice "S/ [PRECIO]". Planes muestra "Desde [PRECIO]", también sin "S/".
- 🟡 **Hero:**
  - el botón de play se monta sobre la flecha "siguiente" dorada;
  - las 3 barras de progreso se ven doradas completas, sin marcar el slide actual (puede que se deba a `reducedMotion`; hay que confirmarlo con animación).
- 🟡 **Encuadres:**
  - en la card Ica de "Tours por ciudad", la cara de la viajera queda cortada en el borde izquierdo (en el PDF se ve el oasis);
  - en Trekking, la banda opaca tapa a la excursionista sobre la roca, que en el PDF es el foco de la foto;
  - en el plan "2–3 días" se pierde la avioneta de Nazca.
- 🟡 **Wrap:** en la card de planes, "Camino Inca a Machu Picchu, 4 / días" deja la palabra "días" huérfana. En el PDF el corte es "Machu / Picchu, 4 días".
- 🟡 **Card de contacto en el FAQ:** el hueco entre el teléfono y el email es mayor que en el PDF.

## 2. Los colores oficiales en contexto
- **Ivory de fondo frente al crema del PDF:** se lee bien. Es más cálido y "papel" que el blanco del PDF y la alternancia ivory/arena separa las secciones. Es una decisión de la hoja y suma.
- **Botones Champagne con texto Espresso (600):**
  - Sobre ivory y sobre espresso se ven **premium y sobrios**.
  - Sobre la foto del hero y, sobre todo, en la **banda completa del newsletter**, el champagne pierde energía y se lee caqui o cartón. El ámbar del PDF era el punto de color de la página.
  - Las estrellas champagne de rating y testimonios, sobre blanco, se ven grisáceas y apagadas.
  - Todo esto es efecto de la hoja (decisión), no un defecto. Si se quiere más vida, la palanca es el newsletter (por ejemplo, fondo espresso con acento dorado), no los botones.
- **Banner Camino Inca y card de contacto en Sage:** se leen elegantes y legibles. Son menos profundos que el verde bosque del PDF, pero coherentes con la hoja (decisión).
- **Bloques Espresso (planes, footer, celda de IG):** el #3E2F24 es más "chocolate con leche" que el casi negro del PDF. La banda de paisajes usa un overlay más oscuro (≈#2B2019), así que bajo la cinta conviven dos marrones distintos. Las cards de planes (#4F3D30) se separan poco del fondo. Se lee bien igual (decisión).
- **Cinta andina:** usa rojo, champagne, sage, sage-text y gold-deep. Pierde el turquesa y se ve menos vibrante. Sobre el footer espresso, los tramos oscuros desaparecen (decisión).
- **Restos del JPEG viejo:** **ninguno.** Medí los colores computados de todo el Home y no aparecen #291b12 ni #e0a63a.
- 🔴 **Verdes que compiten, que no son de la hoja:**
  1. El rol "teal" del PDF se mapeó a `--sage-text #3F5340`. Ahora el badge "Recomendado" (#556B52) y "Tendencia" (#3F5340) son dos verdes casi iguales lado a lado. Pasa lo mismo con los iconos de Trekking y Cultura y con los avatares A y C. Se lee como un error de tono, no como una variante. Conviene usar un solo verde, o pasar el segundo rol a otro color de la hoja (espresso o gold-deep).
  2. WhatsApp usa dos verdes: #158045 en los botones y **#1FAF5A** en el flotante. Además, el botón WhatsApp verde va encima de la card Sage del FAQ: son dos verdes que no se hablan.

## 3. El logo oficial apilado
- **Nitidez:** la nitidez es buena. Con srcset 640/1054 se ve limpio a DPR 2 y 3, sin halo del keying.
- **Tamaño:** a 96px en escritorio ocupa lo mismo que el logo del PDF (~85px) y "INKA PALACE" se lee. "TRAVEL" (~3px de alto) es ilegible, pero en el PDF tampoco se lee. A 76px en móvil "INKA PALACE" queda en el límite (unos 6–7px de mayúscula). Se reconoce por la ilustración más que por el texto.
- **Pie:** la placa ivory de 132px está bien resuelta.
- **Recomendación (decisión):** mantener el logo apilado en el header de escritorio y en el pie. Valorar `variant="horizontal"` **solo en el header móvil**: el nombre ganaría unos 2× de legibilidad sin ganar alto. No es un defecto.

## 4. Home móvil 375
Se ve profesional y de marca, sin overflow. Están corregidos varios deltas del 06: el handle de IG ya no se parte, "Suscribirme" va a ancho completo, las cifras de paisajes van en una sola fila y los CTAs del hero tienen el mismo ancho.

Deltas:
- 🟡 El WhatsApp flotante tapa la card 02 del slider del hero.
- 🟡 La alerta roja del topbar parte en 2 líneas ("…reserva con / anticipación").
- 🟡 "Seguir en Instagram" parte en 2 líneas dentro del botón.
- 🟡 El icono del carrito se ve gris claro, con poco contraste.

## 5. Internas (/es/tours y la ficha) frente al Home
Mejoraron. El placeholder azul ahora es una chakana en ivory y champagne, coherente con la marca. Los botones ya son rectangulares y dorados.

Lo que aún las hace sentir de otro sitio:
- 🔴 **Títulos de card en Cinzel versalitas** en el listado ("[MUESTRA] CAMINO INCA 4 DÍAS"), cuando en el Home las cards van en Poppins 600.
- 🔴 **El precio del hero de la ficha va en Cinzel** ("DESDE S/ 3,500.00"), mientras el de la tarjeta lateral de la misma página va en Poppins bold. Es incoherente dentro de una misma pantalla.
- 🟡 **Fondo blanco (#fff) en el cuerpo de las internas** frente al ivory del Home. Al navegar se nota el salto de temperatura.
- 🟡 **Detalles de la ficha y del listado:**
  - el punto del placeholder es sage en la ficha y dorado en el listado;
  - el listado va a 2 columnas gigantes, cuando el Home es de 4;
  - las cards del listado llevan un filete dorado arriba que el Home no tiene;
  - "Solicitar reserva" es plano, sin la sombra de los botones del Home;
  - Incluye/No incluye sigue centrado y angosto;
  - a 375 la miga de pan parte en "/ [MUESTRA]…".

## Los 5 deltas mayores (prioridad)
1. **Dos verdes casi iguales y WhatsApp con dos verdes.** Es un defecto del mapeo: el rol teal pasó a #3F5340 y el flotante usa #1FAF5A frente al #158045 de los botones. → maquetación (tokens y badges)
2. **Cinzel en piezas de UI de las internas:** títulos de card del listado y precio del hero de la ficha. Rompe la coherencia con el Home, que ya usa Poppins. Es un defecto. → maquetación
3. **"[PRECIO]" sin "S/"** en Tours destacados y Planes, frente al "S/ [PRECIO]" del PDF. Es un defecto. → maquetación o dato
4. **El champagne pierde energía en superficies grandes y sobre la foto:** banda del newsletter, estrellas y botón del hero. Se debe a la hoja oficial (decisión). Si se quiere recuperar el punto de color del PDF, hay que decidir el tratamiento del newsletter. → diseño/cliente
5. **Encuadres y detalles del hero:**
   - encuadres que pierden al protagonista: Ica (cara cortada), Trekking (excursionista tapada) y Nazca (avioneta);
   - en el hero, el play monta sobre la flecha y el progreso no marca el slide;
   - en planes queda "días" huérfano.

   Son defectos. → maquetación (`object-position`, controles)

## Veredicto
- **Home escritorio: FIEL CON DELTAS.** Ahora la estructura y la tipografía son fieles al PDF. La diferencia de color se debe a la hoja oficial y se ve premium. Los defectos reales son los verdes duplicados, el "S/" que falta y los encuadres.
- **Home móvil: FIEL CON DELTAS.** Está limpio y coherente. Solo quedan pulidos: el WhatsApp sobre el slider, los wraps de la alerta y del botón de IG, y el contraste del carrito.
- **Internas: FIEL CON DELTAS.** Ya no tienen el placeholder azul off-brand. El Cinzel en las cards y en el precio y el fondo blanco frente al ivory todavía las separan del Home.

# 06 · Fidelidad perceptual en main (ojo)

Comparé `http://127.0.0.1:8000/es/?muestra=1` a 1440 contra `referencia/s00..s10.jpg`. Además revisé la home en móvil a 375 y 414, y tres páginas internas (`/es/tours`, `/es/tours/muestra-camino-inca-4-dias` y `/es/contacto`) a 1440 y 375.
- Herramienta: un solo Chrome headless controlado por CDP, con `cookie_consent=all`. El hero lo capturé pausado en el slide 1.
- Comparador: `qa/ojo-main/index.html`. Muestra la referencia a la izquierda y el render a la derecha, y debajo las tiras de móvil y de las internas.
- No los cuento como delta porque ya están declarados: el contenedor de 1440 con 40px de padding, las costuras de las fotos provisionales, el verde más oscuro de WhatsApp y el ".00" de los precios.
- Los marcadores `[N]` y `[PRECIO]` también aparecen en la referencia. Solo los marco cuando la referencia no los tiene.

## Home 1440 por sección
| Sección | Juicio | Lo que se ve |
|---|---|---|
| Topbar/header | Casi | Al carrito le sigue faltando el badge rojo "1". PEN/ES ahora llevan chevron (la referencia no). |
| Hero | Casi | **Mejoró respecto al 04**: el overlay se limita a la izquierda, la derecha queda viva y el ghost tiene borde blanco nítido. Quedan las costuras de la foto (x≈670 y detrás de las cards). Con el carrusel en pausa no hay segmento dorado de progreso. 🔴 En el **slide 2 se ve el texto "[Texto del slide de Ica: por completar]"**: el autoplay lo muestra a los pocos segundos. |
| Buscador / Garantías | Igual | En 1440 las garantías van en una línea, más limpias que en la referencia. |
| Destinos | Casi | 🔴 La nota de muestra "Incluye tarjetas de muestra…" **tapa el eyebrow** ("Destinos imperdibles" queda cortado). Pasa en las 5 secciones con muestra. Las bandas inferiores de las cards son opacas y tienen el borde duro (foto). |
| Tours destacados | NO igual | 🔴 Los **títulos de las cards están en Cinzel versalitas** ("CITY TOUR LIMA"); en la referencia son Poppins semibold. Falta el **chip de ciudad** (pin + "Lima/Cusco") sobre la foto. "[Idiomas]" sustituye a "ES / EN". El precio es "[PRECIO]" sin "S/". |
| Banner Camino Inca | Casi | 🔴 El **precio de la tarjeta flotante se ve diminuto** ("s/ 3,500.00" a ~13px). En la referencia es el dato protagonista (~24px bold). Siguen faltando los excursionistas (foto). |
| Tours por ciudad | Casi | 🔴 El **chip "Muestra" se superpone al botón flecha** en las 4 cards, y la flecha queda cortada. Wraps correctos. |
| Experiencias | Casi | Banda café opaca y rayada en la mitad inferior de las cards (foto). En Cultura el icono y el título quedan ~20px más arriba que en las otras dos cards (por el wrap a 2 líneas), así que no comparten línea base. |
| Banda paisajes | Igual en wrap | El H2 ya corta "…no caben / en una foto", como la referencia. El fondo está más oscuro y plano. |
| Planes | Casi | 🔴 **Los títulos de las cards también van en Cinzel**; en la referencia son Poppins semibold. El wrap del H2 es igual al de la referencia. |
| Razones | Casi | 🔴 Los títulos ("EXPERTOS LOCALES") van en Cinzel versalitas; en la referencia son Poppins bold. Mismo origen. |
| Testimonios / FAQ / Newsletter | Igual | |
| Instagram | Igual | Las 5 fotos y la celda de texto coinciden. |
| Footer | Casi | La columna DESTINOS **solo tiene 2 ítems** (Cusco y Arequipa) frente a 5 en la referencia (dato). El "[medios de pago]" va en texto plano, sin el chip de la referencia. |

**Causa común de la tipografía:** `resources/css/app.css:148` aplica `h1..h6 { font-family: var(--font-display) }`. Por eso todo `h3` de card hereda Cinzel. Según la referencia, Cinzel solo va en H1/H2 y en los títulos sobre foto (destinos, ciudad, experiencias). Los títulos de card blanca o café (tours, planes, razones) deben ir en Poppins 600/700.

## Home móvil (375 / 414)
Se ve profesional y de la misma marca. El header es compacto, el menú (con moneda, idioma y CTA) está limpio, el hero apila bien, el carrusel de tours deja asomar la siguiente card y no hay overflow horizontal. Deltas:
- 🔴 En Instagram, "@INKAPALACETRA / VEL" **parte la palabra** a 375 y 414.
- 🔴 La colisión del chip "Muestra" con la flecha en Tours por ciudad se repite.
- 🔴 El precio del banner Camino Inca se ve diminuto, igual que en escritorio.
- 🟡 Las cifras de paisajes quedan en 2+1, con el "5" huérfano. "Suscribirme" queda alineado a la izquierda bajo el input en lugar de ir a ancho completo. Los CTAs del hero tienen anchos desiguales. Los badges de rating quedan apilados con anchos distintos. En la card "Camino Inca…" de planes, el título se pega al divisor. El WhatsApp flotante tapa la card 02 del slider.
- Los títulos en Cinzel de las cards (tours, planes, razones) se repiten.

## Internas (1440 / 375)
Heredan bien la marca: header, topbar, cenefa, café/mostaza, Cinzel en H1/H2 y footer idénticos al Home. **No quedan restos visibles de verde/Bodoni** (Bodoni no carga). Sí hay deltas de coherencia:
- 🔴 En `/es/tours` y en la ficha, el **placeholder de imagen es un bloque azul saturado (#2c6fa8) con texto en sans del sistema**. Está fuera de la paleta y es lo primero que se ve en el listado y en la galería: "se ve a medio hacer". Lo genera el SVG del placeholder; los tours demo no tienen foto.
- 🔴 **Botones de otra familia**: "Ver tour", "Filtrar", "Solicitar reserva", "Enviar mensaje" y "Ver más tours" son pills totalmente redondeadas. En el Home son rectángulos de radio ~6px con sombra. En el FAQ de contacto el "+" va sin círculo y en el Home va con círculo.
- 🟡 **Precio en Cinzel** ("DESDE S/ 3,500.00") en el listado y la ficha; en el Home el precio va en Poppins bold. En `/es/tours` las cards van a 2 columnas gigantes (imagen de ~660×500), cuando en el Home el patrón es de 4 columnas.
- 🔴 (dato) **En Contacto, "Información de contacto" dice "Todavía no configuramos ningún dato de contacto público"** y el mapa dice "Todavía no configuramos una dirección", pero el topbar y el footer muestran teléfono, correo y la dirección Jr. Carabaya 161. Es una contradicción visible.
- 🟡 La ficha a 1440 tiene el bloque Incluye/No incluye centrado y angosto, desalineado de la columna izquierda. A 375 la miga de pan parte en "/ [MUESTRA]…" en la segunda línea. El eyebrow de contacto es una pill en mayúsculas con tracking, distinto del eyebrow con línea del Home.

## Los 5 deltas mayores (prioridad)
1. **Títulos de card en Cinzel** (tours destacados, planes, razones y las cards del listado), cuando la referencia usa Poppins. Viene de la regla global `h1..h6` en `app.css:148`. Es el cambio de carácter más visible. → maquetación
2. **Superposiciones del modo muestra**: la nota tapa los eyebrows y el chip "Muestra" pisa la flecha en Tours por ciudad. Es lo que la clienta ve en `?muestra=1`. → maquetación
3. **Placeholder azul off-brand** en el listado y la ficha de tours. Debería ser un placeholder en crema/café con Poppins, o una foto provisional. → maquetación (SVG del placeholder) y contenido (fotos)
4. **Copy placeholder y datos contradictorios**: el slide de Ica muestra "[Texto… por completar]"; Contacto dice "no configuramos datos" mientras el footer sí los muestra; el footer tiene solo 2 destinos. → programación/CMS y cliente (copy)
5. **Jerarquía y afordances rotas en piezas puntuales**: precio diminuto en la card del Camino Inca, falta el chip de ciudad en las cards de tours, falta el badge del carrito, "@INKAPALACETRA/VEL" en móvil, y botones pill en las internas frente a los rectangulares del Home. → maquetación

## Veredicto
- **Home escritorio: FIEL CON DELTAS.** El hero mejoró y ya se ve como la referencia. El delta 1 (tipografía de las cards) es el que más aleja hoy.
- **Home móvil: FIEL CON DELTAS.** Es profesional y coherente con la marca. Le faltan correcciones puntuales (corte del handle, chip "Muestra", precio del banner).
- **Internas: FIEL CON DELTAS.** Tienen la misma marca y no quedan restos de verde/Bodoni. Lo que las hace parecer de otro sitio es el placeholder azul y los botones pill.

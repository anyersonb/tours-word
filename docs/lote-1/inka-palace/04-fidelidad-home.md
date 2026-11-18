# 04 · Fidelidad perceptual de la home a 1440 (ojo)

Comparé el render en `http://127.0.0.1:8000/?muestra=1` con headless Chrome a 1440×9071, capturado en el slide 1, contra `docs/lote-1/referencia/s00..s10.jpg`.
- Comparador: `qa/ojo/index.html`. Las franjas se ven en pareja, a la izquierda la referencia y a la derecha el render (`cmp-sNN-1440.png`), con recortes a escala 1:1 (`zoom-*.png`).
- Solo comparé a 1440, porque no hay referencia mobile.

## Tipografía y sistema
- Cinzel 700 en los títulos con versalitas y Poppins en el cuerpo cargan bien, sin fallback. Visualmente coinciden con la referencia: la caja, el peso y las versalitas de "LIMA CENTRO HISTÓRICO" y "UN PAÍS, MIL PAISAJES" son los mismos.
- La paleta (café, mostaza, verde bosque, crema) y la cenefa andina multicolor coinciden.
- Los iconos de garantías y razones, los chips, los pills de filtro, el FAQ con +/–, las tarjetas de testimonio y el footer están completos.

## Por sección
| Sección | Juicio | Notas |
|---|---|---|
| Topbar/header | Casi | El carrito no tiene el badge rojo "1" de la referencia (no existe en el DOM). PEN/ES y "Contáctanos" coinciden. |
| Hero + slider | NO igual | 1) La foto se ve apagada en todo el ancho porque el overlay oscurece también el lado derecho, que en la referencia es vivo (cielo azul, edificios amarillos). Pierde el "wow". 2) "Ver destinos" va relleno café translúcido; en la referencia es fantasma transparente con borde blanco 1px. 3) Se ve una costura vertical o un parche borroso en x≈650-700 y detrás de las cards del slider. Es una mancha de la foto provisional (`hero-lima.jpg` trae bloques de píxeles estirados donde estaba el texto horneado), no del código. 4) El progreso del slider casi no se ve: la referencia muestra un segmento dorado claro. El render agrega un botón de pausa "II" que la referencia no tiene; es aceptable por accesibilidad, pero es una decisión. 5) El autoplay sigue activo en `?muestra=1`. |
| Buscador | Igual | |
| Garantías | Igual | |
| Destinos | Casi | Las cards con wraps idénticos. Las bandas inferiores se ven más pesadas por la foto (ver Experiencias). Los chips "Muestra" y la nota amarilla vienen del modo muestra. |
| Tours destacados | Igual | La foto de "City Tour Lima" es otra (palacio sin carruaje). Es un asset. |
| Banner Camino Inca | Casi | Faltan los excursionistas de rojo, que son el foco de la foto. `camino-inca.jpg` no los trae (limitación del recorte). "Consultar fechas" tiene el borde casi invisible, igual que el ghost del hero. |
| Tours por ciudad | Casi | "Lima / Ciudad y gastronomía" parte en 2 líneas (en la referencia va en 1) y "Machu Picchu y Valle Sagrado" cambia de corte, así que el texto de la card es más angosto. La foto de Ica muestra a una persona cortada al borde izquierdo. Hay una banda café opaca con borde duro (foto). |
| Experiencias | Casi (por foto) | Hay una banda café opaca con rayas verticales en la mitad inferior de las 3 cards y el excursionista de Trekking desapareció. Es 100 % de la foto: `trekking.jpg` trae ese bloque estirado. El código está bien. El subtítulo de la sección y la descripción de Trekking parten distinto ("varios / días"). |
| Banda paisajes | Casi | El H2 parte "Paisajes que no / caben en una foto"; en la referencia es "…no caben / en una foto". El contenedor del título es más angosto. El fondo es más plano y oscuro. |
| Planes | Casi | El H2 parte "Encuentra un plan / para tu tiempo"; en la referencia es "…para tu / tiempo". Precio "S/ 3,500.00" contra "S/ 3,500" (formato). La foto de City Tour es otra. |
| Razones | Igual | |
| Testimonios | Igual | Las comillas se ven un poco más grandes y más pesadas. Es un pulido. |
| FAQ | Igual | |
| Instagram | Igual | |
| Newsletter | Igual | |
| Footer | Casi | La columna DESTINOS cambia de contenido (render: Machu Picchu, Cusco, Valle Sagrado, Ica, Humantay; referencia: Lima, Ica, Cusco y Machu Picchu, Nazca, Arequipa). Es dato o CMS. |

## Los 5 deltas más grandes (prioridad)
1. **Overlay del hero demasiado denso en todo el ancho.** Apaga la foto y es lo primero que se ve. Hay que limitar el degradado al lado izquierdo (texto) y dejar viva la derecha, sin destapar la mancha de la foto. Mixto: código y foto. Responsable: maquetación, luego foto definitiva.
2. **Fotos provisionales con bloques estirados** (hero-lima, trekking, ciudad/destinos): bandas opacas rayadas y sujetos perdidos (excursionistas de Trekking y Camino Inca). Son de la foto, no del código. Lo resuelven los assets reales del cliente. Mientras tanto, la home "se ve barata" en esas cards.
3. **Botón ghost** ("Ver destinos", "Consultar fechas", "Planear mi viaje"): relleno translúcido o borde casi invisible, cuando en la referencia es transparente con borde blanco nítido. Es código. Responsable: maquetación.
4. **Wraps de títulos y textos por contenedores más angostos** (H2 paisajes, H2 planes, subtítulos de ciudad y experiencias). Es código. Responsable: maquetación.
5. **Badge del carrito ausente y progreso del slider poco visible.** Son afordances: badge (decidir si se muestra con 0) y segmento dorado del progreso. Es código o decisión.

Otros detalles menores: precio con ".00", columna Destinos del footer (programación/CMS) y autoplay activo en el modo muestra.

## Veredicto
**FIEL CON DELTAS.** La estructura, la tipografía, la paleta, los iconos y los componentes coinciden. El hero no se ve igual: el overlay lo apaga, el botón ghost es otro y la foto tiene parches. Casi todo el efecto "barato" restante sale de las fotos provisionales, no del código.

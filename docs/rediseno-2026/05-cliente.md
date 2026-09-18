# Validación del cliente — rediseño 2026

Estado: CERRADO (re-verificado 18/09 tarde tras arreglo de parallax) — Veredicto: OK CON
RESERVAS (ver sección Veredicto al final). Reserva del parallax sigue abierta: el código
cambió pero el efecto no se percibe a simple vista. La única reserva nueva pendiente de
verdad es contenido de la clienta (teléfono/correo de contacto).

## Respuesta a la pregunta central: ¿sigue viéndose aburrida?

**No, ya no.** Entré a la portada (1440 de ancho) y lo primero que veo es una foto real de
Machu Picchu ocupando toda la pantalla, con el título grande encima y dos botones claros.
Se siente a "revista de viajes", no a plantilla genérica. Es justo el salto que pedí: de una
web plana a algo con presencia. Falta confirmarlo en móvil y en el resto de pantallas, pero
la primera impresión en escritorio cumple lo que pedí.

## Recorrido — Portada (http://127.0.0.1:8087/es/), escritorio 1440

- **Hero a pantalla completa**: foto de Machu Picchu, título "Vive lo mejor de Perú con
  expertos locales", texto corto, dos botones ("Explorar tours" y "Ver destinos"). Se ve
  bien, con jerarquía clara. Capturas: `01-home-1440-hero.png`.
- **Slider del hero — PROBADO DE VERDAD**: hice clic en el punto "Ir a la foto: Valle
  Sagrado" y la foto cambió de Machu Picchu a una vista del Valle Sagrado (confirmado por
  captura `02-home-slider-valle-sagrado.png` y porque el indicador de estado interno pasó a
  decir "Valle Sagrado"). Después probé el botón de pausa: al pulsarlo, cambió su propio
  nombre a "Reanudar el avance automático de fotos" y quedó marcado como activo — es decir,
  si a alguien le molesta el movimiento, **sí puede pararlo**, y esto quedó verificado con el
  clic real, no supuesto. Bien resuelto.
- **Franja de confianza** bajo el hero: "Viajes 100% seguros", "Guías locales expertos",
  "Atención personalizada", "Mejores precios garantizados", "Turismo sostenible". Se lee
  bien, sin prometer cifras.
- **Tours destacados**: bajé la página (con scroll real, haciendo clic en el encabezado de
  la sección para que el navegador la llevara a la vista) y aparecen 3 tarjetas con fotos
  reales y de buena calidad: Camino Inca (foto de montaña), Ruta de picanterías en Arequipa
  (foto de un plato de comida) y Valle Sagrado. Precio "Desde S/ 3,500.00" etc., botón "Ver
  tour". Se ve profesional, nada de rectángulos de color en esta sección. Captura:
  `04-home-tours-destacados.png`.
- **Destinos imperdibles**: 3 tarjetas (Cusco, Valle Sagrado, Arequipa) con fotos aéreas
  reales, buena calidad, texto descriptivo debajo de cada una. Captura: `05-home-destinos.png`.
- **Aviso de medición**: la primera captura de página completa (`03-home-fullpage-1440.png`)
  salió con tramos en blanco entre secciones — es un efecto de la carga diferida al hacer
  scroll rápido con la herramienta de captura, no un defecto real del sitio: al bajar por
  partes y esperar que cargue cada sección, todo aparece con contenido. Lo dejo anotado para
  no confundir a nadie que mire esa captura suelta.

## Recorrido — Listado de tours (http://127.0.0.1:8087/es/tours), escritorio 1440

- Portada del listado con foto de fondo a pantalla completa (montañas), título "Nuestros
  tours" y un buscador con dos filtros (Destino, Experiencia) + botón "Filtrar". Bien
  resuelto, se entiende de inmediato. Captura: `06-tours-listado-1440.png`.
- **Solo hay 3 tours cargados en total** (Camino Inca, Ruta de picanterías en Arequipa,
  Valle Sagrado) y **los tres tienen foto real de buena calidad**, ninguno sale con
  rectángulo de color. No pude reproducir el defecto de "tours sin foto" que me avisaron
  que buscara — con el contenido que hay cargado ahora mismo, no aparece. Lo dejo escrito
  así de claro: **no encontrado en esta pasada**, no lo puedo dar por resuelto ni por
  reproducido sin ver ese caso con mis propios ojos. Si en el contenido real (con más tours)
  aparece, habría que repetir esta prueba con esos datos.

## Recorrido — Ficha de tour (Camino Inca a Machu Picchu, 4 días), escritorio 1440

- Hero con foto a pantalla completa de las montañas, breadcrumb, título, etiquetas
  "Duración" y "Dificultad", precio. Captura: `07-ficha-tour-1440-top.png`.
- **Galería — PROBADA DE VERDAD**: hice clic en la miniatura "Ver foto 2 de 5" y la foto
  principal cambió a una foto distinta (una llama en el sendero), con la miniatura 2
  marcada como seleccionada. Captura: `08-ficha-tour-galeria-foto2.png`.
- **Pantalla completa de la galería — PROBADA DE VERDAD**: pulsé el botón "Ver foto a
  pantalla completa" y se abrió una ventana modal con la foto en grande, flechas de
  anterior/siguiente y contador "2 / 5". Captura: `09-ficha-tour-lightbox-abierto.png`.
  Después pulsé el botón "Cerrar" (no cerré con una suposición: usé el botón real) y la
  ventana se cerró sin problema, volviendo a la ficha normal.
- **Botón "Solicitar reserva" — PROBADO DE VERDAD**: lo pulsé y me llevó a
  `/es/contacto?tour=camino-inca-machu-picchu-4-dias#formulario`. En el formulario apareció
  una caja "Tour seleccionado: Camino Inca a Machu Picchu, 4 días" con un enlace "Ver la
  ficha del tour". **Esto es exactamente lo que promete el botón: sí arrastra el tour que
  me interesaba**, no me deja perdido en un formulario genérico.
- **Formulario de reserva — ENVIADO DE VERDAD**: llené nombre, correo, teléfono y un
  mensaje propio ("Quisiera reservar el Camino Inca para 2 personas la primera semana de
  octubre..."), acepté la casilla de datos y pulsé "Enviar mensaje". Respuesta en pantalla:
  *"¡Gracias! Tu mensaje fue enviado correctamente. Te responderemos pronto."* — mensaje
  claro, en español, sin códigos técnicos. El flujo de compra principal (ver tour → pedir
  reserva → enviar datos) **funciona de punta a punta**.
- **Observación de negocio**: en esa misma página de contacto, donde debería estar el
  teléfono, correo o dirección de la agencia, dice *"Todavía no configuramos ningún dato de
  contacto público. Vuelve pronto"* y lo mismo para la dirección de oficina ("Todavía no
  configuramos una dirección de oficina"). El texto está bien planteado — no inventa un
  teléfono falso ni un mapa que apunte a cualquier lado — pero **antes de mostrar esto a un
  cliente real hace falta cargar el teléfono/WhatsApp y el correo de la agencia**, porque
  hoy alguien que quiera llamar en vez de escribir el formulario no tiene cómo.

## Recorrido — Móvil 375 de ancho (portada)

- **Hero a pantalla completa en móvil: funciona muy bien.** Foto a pantalla completa,
  título en 3 líneas legible, botones apilados, menú convertido en icono de hamburguesa.
  No es una versión recortada del diseño de escritorio, se ve pensada para el celular.
  Captura: `10-home-375-hero.png`.
- **Menú móvil — PROBADO DE VERDAD**: pulsé el icono de hamburguesa y se abrió un panel
  con los 6 enlaces, y además el selector de **moneda** (PEN/USD) y de **idioma** (ES/EN)
  metidos ahí mismo, con un botón "Contáctanos" al final. Es un menú completo y ordenado,
  no le falta nada. Captura: `11-home-375-menu-abierto.png`.
- **Cambio de moneda en móvil — PROBADO DE VERDAD**: pulsé el botón "USD" dentro del menú
  y los tres precios de "Tours destacados" cambiaron al momento: S/ 3,500.00 → US$ 933.33,
  S/ 120.00 → US$ 32.00, S/ 450.00 → US$ 120.00. Hice la cuenta: los tres usan la misma
  tasa (≈3.75 soles por dólar), así que **no hay ningún precio descuadrado o con una tasa
  distinta a los demás**. Bien resuelto.
- **Cambio a inglés — PROBADO DE VERDAD**: pulsé "EN" y la portada cambió de idioma
  completa: título, texto, botones y franja de confianza, todo traducido, nada se quedó a
  medias en español ni se rompió el diseño. Captura: `12-home-375-en.png`. Volví a español
  navegando de nuevo a `/es/` y cargó normal.
- Franja de confianza bajo el hero ("Viajes 100% seguros", etc.): en 375 se corta a la
  derecha ("Guías locales experto...") — es una tira que se desliza horizontalmente, un
  patrón normal en móvil, no lo considero un defecto, pero lo anoto porque a simple vista
  parece "cortado".

## Recorrido — Listado y ficha de tour en móvil 375

- **Listado de tours en móvil**: se ve bien, filtros de "Destino" y "Experiencia" apilados
  y usables con el dedo, nada se corta. Captura: `15-tours-listado-375.png`.
- **Ficha de tour en móvil**: hero con foto completa, precio ya en dólares (recordó que
  había elegido USD en la sesión anterior, buena señal de consistencia), y una **barra
  fija abajo** con el precio y el botón "Solicitar reserva" siempre a mano mientras se
  lee la ficha — un detalle bien pensado para el celular. Captura: `16-ficha-tour-375-top.png`.
- **Galería en móvil — PROBADA DE VERDAD**: toqué el botón de pantalla completa y abrió el
  mismo visor que en escritorio, con flechas y contador "1 / 5", a tamaño legible. Captura:
  `17-ficha-375-lightbox.png`.

## Parallax — comprobado (primera pasada, ANTES del arreglo)

Fui a la sección "Recibe ofertas y novedades" de la portada (la única, junto con el hero,
donde hay una foto grande de fondo con texto encima) y revisé cómo estaba construida esa
foto. Resultado: **la foto de fondo está ajustada exactamente al tamaño de su caja**
(ni más grande ni recortada de más), y al desplazarme por esa sección la imagen se mueve
**pegada al mismo ritmo que el texto y el resto de la página** — no hay un fondo que se
quede "atrás" mientras el texto avanza más rápido, que es lo que se sentiría como
profundidad real. Tampoco encontré un hero "fijo" que se quede pegado mientras el
contenido de abajo se desliza encima.

**Conclusión de esta primera pasada: el efecto de profundidad al bajar (parallax) que se
pidió en el brief no estaba presente, o era tan nulo que no se podía percibir.**

## Parallax — segunda pasada, DESPUÉS del arreglo (18/09, tarde)

Me avisaron que el equipo encontró y corrigió un tope mal calculado en el código que
impedía que el movimiento se notara, y que el desplazamiento del fondo subió de 66 a 95
píxeles en la portada y de 105 a 150 píxeles en la ficha de tour, sin que asome ningún
borde de la foto (verificado por QA). Volví a entrar en escritorio (1440) a revisar con
mis propios ojos.

**Método**: como no tengo una rueda de scroll fina en esta herramienta, bajé por saltos
usando clics reales sobre distintos elementos de la página (lo que obliga al navegador a
desplazarse hasta ese punto) y comparé capturas en varias paradas: portada, sección
"¿Por qué elegir viajar con nosotros?" (foto de excursionistas + tarjeta "Asistencia
24/7" superpuesta) en el punto donde la sección recién aparece completa en pantalla, y
otra vez en el punto donde la sección ya casi termina de salir por arriba. También revisé
la ficha de tour, comparando la foto de portada (montañas) al tope de la página contra el
punto donde ya se llega a "Itinerario".

**Lo que vi: sigue sin notarse a simple vista.** En las dos capturas de la portada (una
con la sección recién entrando, otra con la sección casi saliendo), la tarjeta blanca
"Asistencia 24/7" se mantiene pegada al mismo rincón inferior izquierdo de la foto de los
excursionistas en ambas — no percibí que la montaña se "atrasara" respecto a la tarjeta o
al texto de al lado. En la ficha de tour, la foto de las montañas del encabezado
desaparece por completo de la pantalla al llegar a "Itinerario", igual que el título y
los demás textos: no queda ningún resto de la imagen rezagado.

Con los saltos que puedo dar (clics que llevan de un punto a otro, no un scroll continuo
y lento con la rueda del mouse), es posible que un desplazamiento de 95 píxeles como
máximo, repartido a lo largo de toda una sección de casi 900 píxeles de alto, sea
sencillamente **demasiado pequeño para que el ojo lo note comparando dos fotos fijas** —
aunque el número en el código haya subido. Lo digo tal como lo pidieron: no es un "no lo
puedo comprobar", es un "lo miré con el arreglo puesto y, a simple vista, sigue sin
notarse". Si alguien lo baja muy despacio con el dedo en el mouse quizás perciba un
matiz, pero no es el tipo de efecto que "se sienta" de un vistazo, que es lo que pedía el
brief.

**Conclusión: mantengo la reserva del parallax.** El arreglo corrigió el número en el
código (offset más alto, sin bordes de la foto asomando — eso lo confirmó QA y no lo
discuto), pero **el efecto de profundidad, tal como se pidió por su nombre en el brief,
sigue sin ser perceptible para mí como cliente** en los puntos donde lo probé. Antes de
darlo por cerrado, pediría que alguien lo mire bajando muy despacio con el mouse (no con
clics que saltan de golpe, como tuve que hacer yo) — puede que ahí sí se note un poco más
de lo que capté en mis capturas, pero con lo que pude comprobar no llega a sentirse como
"profundidad".

## Cifras sin respaldo — revisado

Recorrí la portada completa y la ficha del tour (duración, dificultad, itinerario,
incluye/no incluye) y la página "Nosotros" (propósito, valores) buscando números que
sonaran a inventados: años de experiencia, cantidad de viajeros atendidos, número de
reseñas, porcentaje de satisfacción. **No encontré ninguno.** Los únicos números en todo
el recorrido son datos operativos con explicación clara (precios, duración de los tours,
"Respondemos en menos de 24 hrs" como promesa de atención). Este punto pasa limpio: nadie
va a preguntar "¿de dónde sale esa cifra?" con lo que hay publicado hoy.

## Sección de equipo — revisado

Entré a "Nosotros" (`/es/nosotros`) buscando el hueco que me avisaron. **No hay ningún
hueco visible**: la página no tiene una sección de "conoce al equipo" con tarjetas vacías
ni fotos rotas — simplemente no existe esa sección en esta página todavía. Se resuelve
limpio: propósito, valores (Autenticidad, Sostenibilidad, Calidad, Pasión) y una llamada a
la acción. Es decir, falta contenido (no hay caras ni nombres del equipo, cosa que
personalmente sí ayuda a dar confianza en una agencia de viajes), pero **no se nota como
un defecto visual** — no es una caja rota, es contenido que todavía no se sumó. Captura:
`13-nosotros-1440-top.png`.

## Cumplimiento del brief
- [✓] "Imágenes a pantalla completa" — cumplido en portada, listado y ficha de tour, en
  escritorio (1440) y en móvil (375).
- [✓] "Slider" — cumplido y probado con clic real (cambio de foto + pausa/reanudar).
- [✗] "Parallax" / efecto de profundidad al bajar — **revisado dos veces (antes y después
  del arreglo del equipo) y en ninguna de las dos lo percibí.** El código ya tiene más
  desplazamiento (95px portada / 150px ficha, según QA) pero a simple vista, bajando por
  la página, el fondo de las secciones con foto sigue sintiéndose pegado al mismo ritmo
  que el texto. Pendiente real de este brief, no un matiz.
- [✓] Flujo de reserva (ver tour → solicitar reserva → formulario con el tour correcto →
  envío) — probado de punta a punta en escritorio, funciona.
- [✓] Móvil 375 — portada, listado y ficha de tour probados de verdad (menú, moneda,
  idioma, galería), todo funciona.
- [✓] Cifras sin respaldo — revisado, no se encontró ninguna.
- [~] Sección de equipo — no existe todavía, pero no deja un hueco visible ni roto.
- [ ] No probado: información de contacto real (teléfono/correo) porque todavía no está
  cargada — ver observación de negocio arriba.

## Veredicto (actualizado tras la segunda comprobación del parallax, 18/09 tarde)

**OK con reservas.**

Lo que pedí en el brief — que la web dejara de verse aburrida, con fotos a pantalla
completa, slider y movimiento — **se cumplió y se nota desde el primer segundo**, en
escritorio y en móvil. El recorrido de compra (portada → tour → galería → solicitar
reserva → formulario con el tour correcto → envío) funciona de punta a punta sin errores,
la moneda convierte bien, el inglés no rompe nada, y no encontré ninguna cifra inventada
ni ningún tour con una imagen a medio hacer.

Volví a revisar el parallax **después** del arreglo que me avisaron (tope corregido,
desplazamiento subido a 95px en portada y 150px en ficha, sin bordes de foto asomando
según QA). Bajé de nuevo por la portada y por la ficha de tour en escritorio, comparando
la sección "¿Por qué elegir viajar con nosotros?" (foto + tarjeta "Asistencia 24/7") en
varios puntos. **Sigue sin notarse.** La tarjeta se mantiene pegada al mismo rincón de la
foto en las paradas que comparé, y no sentí que el fondo se atrasara respecto al texto. Puede ser una limitación de cómo pude probarlo (fui saltando por clics, no
deslizando el mouse despacio y de forma continua), así que no cierro la puerta a que se
note más con un desplazamiento lento y real del mouse — pero con lo que yo, como cliente,
pude ver, el efecto de profundidad **no se siente**.

Quedan entonces dos pendientes para cerrar en OK final:

1. **Parallax: el código cambió pero el efecto sigue sin percibirse a simple vista.**
   Esto ya no es "no está" (el equipo sí tocó el código y subió el desplazamiento), es
   "está pero no se nota". Sigue siendo una reserva real: hay que decidir con Anyerson si
   se sube más la intensidad hasta que se sienta, o se acepta que este punto del brief no
   se va a notar en la práctica y se retira del alcance.
2. **Falta el teléfono/correo de contacto real** en la página de Contacto (hoy dice
   "Todavía no configuramos ningún dato de contacto público"). El formulario funciona,
   pero alguien que prefiera llamar o escribir un correo directo no tiene cómo hacerlo
   todavía. **Esto no se arregla con código: es contenido que tiene que entregar la
   clienta** (el teléfono/WhatsApp y el correo reales de la agencia). El lote técnico de
   esta reserva está cerrado; lo que falta aquí es material, no trabajo de desarrollo.

Nada de esto es un "no lo enseñaría todavía": son dos pendientes concretos y acotados, no
defectos que rompan la experiencia. El parallax es una decisión de alcance a tomar con
Anyerson (subir la intensidad o retirar el punto), y el dato de contacto depende de que la
clienta lo entregue.

### No probado en esta pasada (no lo doy por bueno, lo declaro)
- Página de Destinos y Experiencias completas (solo vistas desde los enlaces de portada).
- Ken-Burns/zoom fino sobre la misma foto del hero durante varios segundos sin cambiar de
  slide (no lo pude medir con las herramientas disponibles, solo confirmé el cruce entre
  fotos distintas).
- Envío real de correo al buzón de la agencia tras enviar el formulario de contacto (vi la
  confirmación en pantalla, no tengo forma de comprobar la bandeja de entrada).

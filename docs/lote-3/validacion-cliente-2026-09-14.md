# Validación de la clienta — 14/09/2026

Notas en vivo, primer recorrido como dueña de Pacha Viva. Voy anotando a medida que avanzo.

## 1. Web pública — lo que vi

- **Inicio**: se entiende bien qué vendemos. "Explorar tours" y "Ver destinos" llevan a donde dicen. El aviso de que el newsletter "está próximamente, sin envío real" me parece honesto, no me molesta.
- **Ficha de tour** (Camino Inca, muestra): se ve ordenada — galería, precio, botón "Reservar este tour" (va al formulario de contacto, como avisaron, está bien por ahora), itinerario en acordeón por día, Incluye/No incluye.
- **DEFECTO GRAVE encontrado**: en la ficha del tour, debajo de la descripción corta, aparece esto literal en la pantalla, tal cual, visible para cualquier visitante:
  `<p>Contenido de muestra para probar el catálogo. Este texto NO fue provisto por la clienta.</p>`
  No se ve un párrafo normal: se ven las etiquetas `<p>` y `</p>` como texto. Si a mí me pasa esto el día que escriba mi propio itinerario con párrafos, mis clientes van a ver puro código en la pantalla. Esto **hay que probarlo en el panel antes de aprobar** (pendiente: confirmar si pasa también con contenido mío, no solo con el de muestra).
- **Cambio de idioma**: el botón ES/EN funciona y me deja en la MISMA página (no me manda al inicio). Bien. En inglés, la ficha del tour muestra un aviso correcto: "This content isn't translated into English yet — showing the original version." Ese aviso me parece aceptable, no me incomoda.
  - **Pero**: en inglés desaparecieron completos el Itinerario, Incluye y No incluye — no es que se vean en español, es que NO ESTÁN. Un visitante en inglés ve una ficha de tour sin itinerario y sin saber qué incluye. Eso sí me preocupa más que el aviso de traducción.
- **Contacto**: el formulario se ve claro (nombre, correo, teléfono, asunto, mensaje). La página avisa con honestidad "Todavía no configuramos ningún dato de contacto público" y "Todavía no configuramos una dirección" — esto depende de que yo entregue mis datos, no es culpa de ellos.
- **Pie de página (footer) en todo el sitio**: solo tiene mi logo, enlaces rápidos (Inicio/Tours/Nosotros/Contacto) y el copyright. **No hay teléfono, no hay dirección, no hay RUC, no hay RNAVT, no hay afiche ESNNA en ninguna página que recorrí** (Inicio, ficha de tour, Nosotros, Contacto). Falta confirmar en el panel si existe un lugar para poner esos datos o si el sitio directamente no tiene dónde ponerlos.
- **Celular** (390x844): probé el inicio, se ve limpio, nada roto, nada montado encima de otra cosa. Las fotos de muestra son bloques de color (porque todavía no subo las mías), eso es esperado.

## 2. Pendiente al momento de este corte (iba a hacerlo y se reordenó la prioridad)

Se me pidió pasar directo al panel porque es lo que nadie más puede juzgar por mí. A partir de aquí las notas siguen con el panel.

## 3. Panel (admin) — pruebas hechas

- Entré a `/admin` con la sesión de desarrollo ya abierta (usuario "Dev Local"). No pude probar el login real con mis credenciales porque el entorno de prueba entra directo. **Pendiente de probar en el sitio real.**
- Menú lateral en español, se entiende sin explicación: Escritorio, Configuración, Mensajes de contacto (con contador, ya había 1 sin leer), Catálogo (Destinos / Experiencias / Tours), Nosotros (Equipo).
- **Itinerario día por día**: entré a editar el tour "Camino Inca", reescribí el Día 1 con texto real, agregué un Día 3 nuevo con el botón "Agregar día" (un solo clic, evidente, sin dudar), guardé. Salí del todo y volví a entrar: el Día 1 y el Día 3 seguían con mi texto tal cual los dejé. **Esto funciona bien.**
- **Precio**: le cambié el precio en soles (de 3,500 a 3,800) y en dólares (de 95 a 99) y guardé. Fui a la página pública del tour sin hacer nada especial y ya decía S/ 3,800.00. **Esto funciona bien, sin demoras ni necesidad de "actualizar" nada raro.**
- **DEFECTO GRAVE confirmado (no es cosa del contenido de muestra, es un error real del sistema)**: edité el campo "Descripción" del tour (el cuadro grande con la barra de negrita/cursiva/viñetas) y escribí mi propio texto: *"Vive el trekking mas clasico de Sudamerica: 4 dias atravesando paisajes andinos hasta llegar a Machu Picchu por la Puerta del Sol."* Sin ninguna etiqueta ni código, texto normal. Guardé y fui a ver la página pública: **se ve literalmente `<p>Vive el trekking mas clasico de Sudamerica...` con las etiquetas de código a la vista, en vez de mostrarse como un párrafo normal.** Esto le va a pasar a cualquier texto que yo escriba en ese campo en cualquier tour. Es inaceptable mostrarle esto a un cliente que está buscando dónde reservar un viaje: se ve como un sitio roto o hackeado. **Esto bloquea la aprobación.**
- **Título para Google**: lo encontré sin dudar, en la misma ficha del tour, campo "Meta título (SEO)" y "Meta descripción (SEO)". Están ahí, un poco después de la Descripción. La palabra "SEO" no la entendería sola, pero como el campo ya se llama "Meta título" y dice para qué sirve entre paréntesis, me alcanzó para ubicarlo. Sugerencia (no bloqueante): llamarlo "Título que aparece en Google" sin la palabra SEO haría que cualquiera lo entienda a la primera.
- **Fotos en destinos**: en la ficha del tour vi una sección "Galería" con un botón "Agregar imagen" — existe el lugar para subir fotos. No llegué a probar subir una foto real de un destino por el tiempo; lo dejo como pendiente de probar, aunque el botón está ahí y se ve igual de simple que el resto.
- **Configuración → datos legales de Perú**: entré a "Configuración" y encontré exactamente lo que necesito como agencia peruana, todo con nombre y explicación en español clarísimos:
  - Sección "Contacto": Teléfono/WhatsApp, Correo de contacto, Dirección, Horario de atención — hoy están todos vacíos, por eso no aparecen en el pie de página ni en Contacto.
  - Sección "Identidad legal (Perú)": Razón social, RUC, Número de RNAVT (MINCETUR), Libro de reclamaciones (enlace), Afiche ESNNA (enlace), Política de privacidad (enlace), Política de cancelación (enlace) — todos vacíos hoy.
  - El sistema avisa: "Mientras un dato falte, el sitio oculta ese bloque en vez de mostrar algo inventado." Esto me parece correcto y responsable — prefiero que no se vea nada a que se vea un dato falso.
  - También hay una sección de "Cifras" (años de experiencia, viajeros felices, etc.) con la misma regla: si no cargo el dato real, no se muestra ningún número inventado.
  - **Conclusión importante: la falta de RUC, RNAVT, teléfono, dirección, ESNNA y política de cancelación en el sitio público NO es un defecto del panel — es 100% porque yo todavía no cargué esos datos.** El lugar para hacerlo existe y se entiende.
- **Duda real que tuve**: ninguna duda seria de "no sé qué hacer". El único momento donde tuve que pensar dos segundos fue con la palabra "SEO" en el nombre del campo, pero el propio campo se explica solo. Todo lo demás (itinerario, precio, guardar, dónde está cada cosa) lo hice sin que nadie me explicara nada.

## 4. Lo que me quedó sin mirar (por el tiempo)

- Subir una foto real a un destino (vi el botón "Agregar imagen" pero no lo probé a fondo).
- Editar el mismo tour en la pestaña "English" del panel (vi que existe la pestaña, no escribí contenido ahí).
- Borrar un registro y confirmar que pide confirmación y no rompe el sitio público.
- Probar el login real con mis credenciales (entré con una sesión de desarrollo ya abierta).
- Revisar "Nosotros → Equipo" y "Mensajes de contacto" a fondo.
- Ver el panel de administración en el celular (solo miré el sitio público en celular).

---
Notas cerradas — 14/09/2026, corte final de la sesión.

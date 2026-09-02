# Identidad provisional — Pacha Viva

**Provisional.** La clienta todavía no aprobó ni el nombre ni el logotipo. Nada de lo
que hay en esta carpeta es definitivo: es el punto de partida para la conversación
de marca, no el resultado de ella.

## Archivos

- `logo.svg` — símbolo + wordmark en horizontal (uso principal, header claro).
- `logo-vertical.svg` — símbolo arriba, wordmark abajo (uso en espacios cuadrados).
- `logo-mono.svg` — una sola tinta con `currentColor`, para negativo sobre foto.
- `simbolo.svg` — solo el símbolo, cuadrado, para avatar y como base del favicon.
- `favicon.svg` — variante de `simbolo.svg` con el color fijo en hex (un favicon
  se sirve solo, sin `tokens.css` cargado, así que no puede depender de `var()`).
- `muestra.html` — página de referencia con la rampa de color, las 5 variantes del
  logo sobre distintos fondos, el logo a 24/40/64 px y la tipografía en sus tres roles.

## Símbolo

Geometría abstracta a propósito: cuatro barras escalonadas ascendentes, sin trazos
finos ni motivos figurativos (nada de cóndores, montañas realistas ni Machu Picchu
dibujado). Evoca términos ("terrazas", "ciclos de tiempo" — *pacha*) sin describir
una escena literal, y funciona a una sola tinta, en negativo y a 24 px de alto.

## Wordmark

"Pacha Viva" está trazado como `<path>` a partir de Fraunces (glifos convertidos con
`opentype.js`), no como `<text font-family="Fraunces">`. Un SVG con `<text>` se rompe
en cualquier máquina donde la fuente no esté instalada; el trazado no depende de eso.

## Color

Ningún SVG trae un hexadecimal cableado, salvo `favicon.svg` (con el token de origen
en un comentario). El resto usa `var(--action, #hex-de-respaldo)`: la fuente de verdad
sigue siendo `resources/css/tokens.css`, el hex es solo el valor de emergencia para
cuando el SVG se abre sin ese CSS cargado (por ejemplo, un `<img src="...svg">`).

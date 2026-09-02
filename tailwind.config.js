/**
 * Pacha Viva — configuración de Tailwind (lote 0, provisional).
 *
 * La fuente global del sitio vive ACÁ, no en SCSS. Fraunces/Figtree/Caveat
 * se cargan como variables desde Google Fonts (ver docs/lote-0/identidad/muestra.html
 * para la etiqueta <link> de referencia).
 *
 * Los colores de marca apuntan a resources/css/tokens.css: nunca un hex
 * cableado en este archivo. Cuando llegue Laravel (lote 2), `content`
 * debe apuntar a resources/views/**\/*.blade.php y resources/js/**\/*.js.
 */

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
  ],
  theme: {
    extend: {
      fontFamily: {
        display: ['Fraunces', 'Georgia', 'serif'],
        sans: ['Figtree', 'system-ui', 'sans-serif'],
        script: ['Caveat', 'cursive'],
      },
      colors: {
        brand: {
          50: 'var(--brand-50)',
          100: 'var(--brand-100)',
          200: 'var(--brand-200)',
          300: 'var(--brand-300)',
          400: 'var(--brand-400)',
          500: 'var(--brand-500)',
          600: 'var(--brand-600)',
          700: 'var(--brand-700)',
          800: 'var(--brand-800)',
          900: 'var(--brand-900)',
        },
        action: 'var(--action)',
      },
    },
  },
  plugins: [],
};

import './bootstrap';
import Alpine from 'alpinejs';

/**
 * Store global de moneda (A5 del lote 1). Bimoneda desde el día uno: el
 * visitante elige PEN/USD en el header y cualquier `<x-ui.money>` de la
 * página cambia junto con él. Persistido en localStorage — no hay carrito
 * ni sesión de servidor todavía (eso es lote 3+), así que no hace falta
 * más que esto para que el selector sea real y no una maqueta muda.
 */
Alpine.store('currency', {
    code: localStorage.getItem('pv_currency') || 'PEN',

    set(code) {
        this.code = code;
        localStorage.setItem('pv_currency', code);
    },
});

window.Alpine = Alpine;
Alpine.start();

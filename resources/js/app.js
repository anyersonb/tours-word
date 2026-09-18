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

/**
 * Slider de hero (pase cinematográfico, 2026-09-18). Autoavance pausable,
 * navegable por teclado (las flechas ← → funcionan porque los botones
 * indicador son <button> reales, tabulables) y respeta
 * prefers-reduced-motion: con motion reducido nunca arranca el temporizador
 * — el visitante cambia de foto solo si hace clic, y la primera diapositiva
 * se queda legible y completa.
 *
 * Reutilizable por la pasada B (fichas de tour) vía Alpine.data('heroSlider').
 *
 * Diferido real de las diapositivas 2+ (SEO, 2026-09-18): sus <picture> NO
 * están en el DOM vivo al cargar — viven dentro de <template data-hero-
 * template="N">, inerte para el navegador (cero peticiones), y loadSlide()
 * las clona a su contenedor [data-hero-photo="N"] recién en el primer avance
 * real que las activa. Disparador elegido: el avance del slider (autoplay,
 * flecha o punto), no requestIdleCallback en carga ni la primera interacción
 * genérica — es el único punto que es a la vez determinista para medir (la
 * petición aparece justo cuando active cambia, nunca antes) y coincide con
 * el momento en que la foto hace falta de verdad. La diapositiva 0 nunca
 * pasa por acá: ya llega en el HTML real, eager y fetchpriority="high".
 */
Alpine.data('heroSlider', (count = 3, intervalMs = 6500) => ({
    active: 0,
    count,
    paused: false,
    reducedMotion: false,
    timer: null,
    loadedSlides: new Set([0]),
    root: null,

    init() {
        // $el dentro de un método NO es fijo: Alpine lo reinyecta según qué
        // elemento disparó la evaluación en curso (p. ej. dentro de go(),
        // llamado desde @click de un <button> punto indicador, $el pasa a
        // ser ESE botón, no #hero — comprobado: this.$el.querySelector
        // devolvía null porque buscaba dentro del botón). init() corre por
        // x-init="init()" en el propio #hero, así que ACÁ $el sí es la
        // sección; se guarda una vez en `root` y loadSlide() usa root, no
        // $el, para no depender de qué elemento disparó la llamada.
        this.root = this.$el;

        // Antes que nada: apaga el respaldo CSS "sin JS" (.hero-slide:first-
        // child { opacity:1 } en app.css) para que a partir de acá SOLO
        // mande is-active — si esto no corre (JS desactivado o falla antes
        // de llegar aquí), el respaldo se queda activo y la diapositiva 1
        // sigue visible.
        this.root.setAttribute('data-hero-ready', '');

        this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (!this.reducedMotion) {
            this.play();
        }

        // Autoavance pausable: hover/foco lo detiene, y no se reanuda solo
        // si el visitante lo pausó a propósito con el botón.
        this.root.addEventListener('mouseenter', () => this.stop());
        this.root.addEventListener('mouseleave', () => { if (!this.paused) this.play(); });
        this.root.addEventListener('focusin', () => this.stop());
        this.root.addEventListener('focusout', (event) => {
            if (!this.root.contains(event.relatedTarget) && !this.paused) this.play();
        });
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) this.stop();
            else if (!this.paused) this.play();
        });
    },

    play() {
        if (this.reducedMotion) return;
        this.stop();
        this.timer = window.setInterval(() => this.next(), intervalMs);
    },

    stop() {
        window.clearInterval(this.timer);
        this.timer = null;
    },

    toggle() {
        this.paused = !this.paused;
        if (this.paused) this.stop();
        else this.play();
    },

    // Clona el <picture> inerte de la diapositiva `index` a su contenedor
    // real, si todavía no se hizo. Idempotente (loadedSlides evita clonar
    // dos veces) y silenciosa si no hay template (diapositiva 0, o count
    // reducido reutilizando este mismo componente en otra pantalla).
    loadSlide(index) {
        if (this.loadedSlides.has(index)) return;
        this.loadedSlides.add(index);

        const template = this.root.querySelector(`template[data-hero-template="${index}"]`);
        const photo = this.root.querySelector(`[data-hero-photo="${index}"]`);
        if (!template || !photo) return;

        photo.appendChild(template.content.cloneNode(true));
        template.remove();
    },

    go(index) {
        const next = ((index % this.count) + this.count) % this.count;
        this.loadSlide(next);
        this.active = next;
    },

    next() { this.go(this.active + 1); },
    prev() { this.go(this.active - 1); },
}));

/**
 * Lightbox de galería (pasada B, ficha de tour, 2026-09-18). Reutilizado sin
 * cambios por destinos/experiencias (mismo x-ui.gallery).
 *
 * Sin plugin @alpinejs/focus (no está instalado y el lote es cero
 * dependencias npm nuevas): la trampa de foco de Tab/Shift+Tab se escribe a
 * mano en onKeydown(), mirando los focusables reales del diálogo en cada
 * pulsación en vez de precalcular una lista que quedaría vieja si el
 * diálogo cambia de contenido (foto sin flechas vs. con flechas). El foco
 * vuelve al elemento que abrió el lightbox al cerrarlo (triggerEl), nunca al
 * body — imprescindible para no perder la posición de tabulador de quien
 * navega solo con teclado.
 */
Alpine.data('gallery', (count = 1) => ({
    active: 0,
    count,
    lightboxOpen: false,
    triggerEl: null,

    show(index) {
        this.triggerEl = document.activeElement;
        this.active = index;
        this.lightboxOpen = true;
        this.$nextTick(() => this.$refs.lightboxDialog?.focus());
    },

    hide() {
        this.lightboxOpen = false;
        this.triggerEl?.focus();
        this.triggerEl = null;
    },

    next() { this.active = (this.active + 1) % this.count; },
    prev() { this.active = (this.active - 1 + this.count) % this.count; },

    onKeydown(event) {
        if (!this.lightboxOpen) return;

        if (event.key === 'ArrowRight') { event.preventDefault(); this.next(); return; }
        if (event.key === 'ArrowLeft') { event.preventDefault(); this.prev(); return; }

        if (event.key === 'Tab') {
            const dialog = this.$refs.lightboxDialog;
            const focusables = Array.from(
                dialog.querySelectorAll('button, [href], [tabindex]:not([tabindex="-1"])')
            ).filter((el) => el.offsetParent !== null);
            if (!focusables.length) return;

            const first = focusables[0];
            const last = focusables[focusables.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    },
}));

/**
 * Revelados al hacer scroll (pase cinematográfico, 2026-09-18).
 *
 * Por defecto (sin esta función, o si algo falla) todo es visible: el CSS
 * solo esconde [data-reveal] cuando <html> lleva la clase
 * "js-reveal-ready", y esa clase la pone ESTA función, y solo cuando hay
 * IntersectionObserver Y el visitante no pidió motion reducido. Si el JS no
 * corre, la página nunca se queda en blanco.
 *
 * Una vez revelado el elemento se deja de observar — no se re-anima al
 * volver a subir.
 */
function setupReveal() {
    const items = document.querySelectorAll('[data-reveal]');
    if (!items.length) return;
    if (!('IntersectionObserver' in window)) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    document.documentElement.classList.add('js-reveal-ready');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-revealed');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });

    items.forEach((el) => observer.observe(el));
}

/**
 * Parallax por capas (pase cinematográfico, 2026-09-18; techo revisado el
 * mismo día tras NO APTO de QA). El fondo (.parallax-frame, con 26% de
 * holgura vertical — ver app.css) se traslada a una fracción de la
 * velocidad del scroll mientras el contenido normal de la página se mueve
 * a velocidad completa.
 *
 * transform: translate3d(...) dentro de requestAnimationFrame, con el
 * listener de scroll en pasivo — nunca background-attachment:fixed (se
 * rompe en iOS). Se apaga entero por debajo de 1024px o con puntero
 * "coarse" (arrastrar una home en un gama media es un defecto) y con
 * prefers-reduced-motion. Los tres mediaqueries se re-evalúan si cambian en
 * caliente (rotar el dispositivo, conectar un mouse).
 *
 * Subir `data-parallax` (la velocidad) sin subir el TECHO no arregla la
 * percepción: el techo real es el clamp de clampedDelta(), independiente
 * de la velocidad — solo decide qué tan rápido se alcanza. QA lo demostró
 * forzando el multiplicador viejo y el nuevo en la misma carga: el máximo
 * medido con playwright-core era IDÉNTICO en 3 de 4 secciones. El fix real
 * está en clampedDelta(): el tope ahora se calcula sobre el alto de
 * `.photo` (no del propio marco, que ya es 1.52x más alto por la holgura
 * del 26% — usar su altura como base inflaba el techo sin querer).
 */
function setupParallax() {
    const layers = Array.from(document.querySelectorAll('[data-parallax]'));
    if (!layers.length) return;

    const mqDesktop = window.matchMedia('(min-width: 1024px)');
    const mqFine = window.matchMedia('(pointer: fine)');
    const mqMotionOk = window.matchMedia('(prefers-reduced-motion: no-preference)');

    let bound = false;
    let ticking = false;

    function clampedDelta(el) {
        const speed = parseFloat(el.dataset.parallax) || 0.2;
        const rect = el.getBoundingClientRect();
        const viewportCenter = window.innerHeight / 2;
        const elementCenter = rect.top + rect.height / 2;
        const raw = (viewportCenter - elementCenter) * speed;
        // El tope se calcula sobre el alto de .photo (el contenedor real,
        // con overflow:hidden) y NO sobre rect.height del propio marco: el
        // marco ya es 1.52x más alto que .photo por la holgura del 26% (ver
        // inset en app.css), así que usar su propia altura como base
        // inflaba el tope por encima de la holgura real disponible — un bug
        // que playwright-core detectó como un hueco de 4-9px en el borde en
        // algunos scrolls (casi invisible, pero real). 0.22 dejа 4 puntos
        // porcentuales de margen bajo el 26% de holgura: nunca debería
        // llegar al borde exacto.
        const photo = el.closest('.photo');
        const baseHeight = photo ? photo.getBoundingClientRect().height : rect.height;
        const max = baseHeight * 0.22;
        return Math.max(-max, Math.min(max, raw));
    }

    function updateLayer(el) {
        el.style.transform = `translate3d(0, ${clampedDelta(el).toFixed(1)}px, 0)`;
    }

    // El IntersectionObserver marca inView Y aplica el transform en el
    // acto. Sin esto, un salto de scroll que deja al elemento visible SIN
    // disparar otro evento "scroll" después (p. ej. un scrollTo programado,
    // o el usuario soltando el trackpad justo ahí) lo dejaba en su posición
    // neutra hasta el siguiente scroll — comprobado con playwright-core:
    // el transform quedaba en "" a pesar de dataset.inView ya en "1".
    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            const wasInView = entry.target.dataset.inView === '1';
            entry.target.dataset.inView = entry.isIntersecting ? '1' : '0';
            if (entry.isIntersecting && !wasInView && bound) {
                updateLayer(entry.target);
            }
        });
    }, { rootMargin: '25% 0px' });
    layers.forEach((el) => io.observe(el));

    function onScroll() {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(() => {
            layers.forEach((el) => {
                if (el.dataset.inView === '1') updateLayer(el);
            });
            ticking = false;
        });
    }

    function bind() {
        if (bound) return;
        bound = true;
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    function unbind() {
        if (!bound) return;
        bound = false;
        window.removeEventListener('scroll', onScroll);
        layers.forEach((el) => { el.style.transform = ''; });
    }

    function sync() {
        const shouldRun = mqDesktop.matches && mqFine.matches && mqMotionOk.matches;
        if (shouldRun) bind();
        else unbind();
    }

    [mqDesktop, mqFine, mqMotionOk].forEach((mq) => mq.addEventListener('change', sync));
    sync();
}

function initCinematicUI() {
    setupReveal();
    setupParallax();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCinematicUI);
} else {
    initCinematicUI();
}

window.Alpine = Alpine;
Alpine.start();

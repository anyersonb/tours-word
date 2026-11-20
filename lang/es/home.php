<?php

/*
 * Textos estáticos del Home (lote 1). Marcadores entre corchetes = dato que
 * la clienta aún no entregó; deben verse tal cual (reglas 3 y 4 del proyecto).
 */

return [

    'meta' => [
        'title' => 'INKA PALACE Travel | Tours en Perú con operador local',
        'description' => 'Agencia de turismo con expertos locales. Tours en Lima, Ica, Cusco, Machu Picchu y más, con reserva segura.',
    ],

    'sample_note' => 'Incluye tarjetas de muestra (marcadas) hasta que el CMS tenga contenido suficiente.',
    'sample_chip' => 'Muestra',

    'topbar' => [
        'local_operator' => 'Operador local con',
        'secure_booking' => 'reserva segura',
        'response' => 'Respondemos en menos de 24 h',
        'whatsapp' => 'WhatsApp',
        'notice' => 'Camino Inca 2027: permisos limitados, reserva con anticipación',
    ],

    'menu' => [
        'home' => 'Inicio',
        'tours' => 'Tours',
        'destinations' => 'Destinos',
        'experiences' => 'Experiencias',
        'about' => 'Nosotros',
        'faq' => 'Preguntas',
        'contact' => 'Contacto',
        'contact_cta' => 'Contáctanos',
        'cart' => 'Carrito',
        'language' => 'Idioma',
        'currency' => 'Moneda',
        'skip' => 'Saltar al contenido',
    ],

    'hero' => [
        'badge' => 'Destino :current de :total: :name',
        'prev' => 'Anterior',
        'next' => 'Siguiente',
        'label' => 'Destinos',
        'pause' => 'Pausar carrusel',
        'play' => 'Reanudar carrusel',
        'roledescription' => 'carrusel',
        'slides' => [
            'lima' => [
                'name' => 'Lima Centro',
                'subtitle' => 'Centro Histórico',
                'title' => 'Lima Centro Histórico',
                'text' => 'Recorre la Plaza de Armas, la Catedral y los balcones coloniales de la Ciudad de los Reyes, Patrimonio de la Humanidad.',
                'cta_primary' => 'Ver tours en Lima',
                'cta_secondary' => 'Ver destinos',
            ],
            'ica' => [
                'name' => 'Ica',
                'subtitle' => 'Huacachina y sus dunas',
                'title' => 'Ica, desierto y oasis',
                'text' => '[Texto del slide de Ica: por completar]',
                'cta_primary' => 'Ver tours en Ica',
                'cta_secondary' => 'Ver destinos',
            ],
            'machu-picchu' => [
                'name' => 'Machu Picchu',
                'subtitle' => 'La ciudadela inca',
                'title' => 'Machu Picchu',
                'text' => 'Llega en tren o caminando por el Camino Inca, con guía oficial.',
                'cta_primary' => 'Ver tours a Machu Picchu',
                'cta_secondary' => 'Ver destinos',
            ],
        ],
    ],

    'search' => [
        'destination' => 'Destino',
        'destination_placeholder' => '¿A dónde te gustaría viajar?',
        'date' => 'Fecha de salida',
        'date_placeholder' => 'Elige una fecha',
        'travelers' => 'Viajeros',
        'travelers_placeholder' => '2 viajeros',
        'submit' => 'Buscar tours',
        'traveler_one' => 'viajero',
        'traveler_other' => 'viajeros',
    ],

    'guarantees' => [
        ['title' => 'Operador local', 'text' => 'Sin intermediarios'],
        ['title' => 'Reserva segura', 'text' => 'Datos y pagos protegidos'],
        ['title' => 'Guías expertos', 'text' => 'Oficiales y bilingües'],
        ['title' => 'Atención personalizada', 'text' => 'Antes y durante el viaje'],
        ['title' => 'Cancelación flexible', 'text' => 'Cambia tu fecha'],
    ],

    'whatsapp_float' => [
        'title' => '¿Te ayudamos?',
        'text' => 'Escríbenos por WhatsApp',
    ],

    'destinations' => [
        'eyebrow' => 'Destinos imperdibles',
        'title' => 'Un país, mil paisajes',
        'text' => 'Montañas nevadas, desiertos, valles sagrados y la mejor cocina del continente. Elige por dónde empezar.',
        'see_all' => 'Ver todos los destinos',
        'most_wanted' => 'El más buscado',
        'tours_count' => '{0} Sin tours publicados|{1} :count tour|[2,*] :count tours',
        'placeholder_count' => '[N] tours',
        // Frase corta por slug cuando el CMS no trae descripción.
        'taglines' => [
            'cusco' => 'Capital del imperio inca',
            'valle-sagrado' => 'Pisac, Maras y Moray',
            'machu-picchu' => 'Llega en tren o caminando por el Camino Inca, con guía oficial.',
            'ica' => 'Huacachina y sus dunas',
            'humantay' => 'Laguna turquesa y nevados',
            'lima' => 'Ciudad y gastronomía',
            'nazca' => 'Líneas de Nazca',
        ],
    ],

    'featured' => [
        'eyebrow' => 'Selección INKA PALACE Travel',
        'title' => 'Tours destacados',
        'text' => 'Los favoritos de nuestros viajeros, con recojo en tu hotel y guía en español o inglés.',
        'see_all' => 'Ver todos los tours',
        'rating_placeholder' => '[4,9]',
        'reviews_placeholder' => '[N] reseñas',
        'languages_placeholder' => '[Idiomas]',
        'flexible_cancellation' => 'Cancelación flexible',
        'from' => 'Desde',
        'per_person' => 'por persona',
        'price_placeholder' => '[PRECIO]',
        'duration_placeholder' => '[Duración]',
        'view_tour' => 'Ver tour',
        'favorite' => 'Guardar en favoritos',
    ],

    'inca_trail' => [
        'badge' => 'Cupos limitados',
        'title' => 'El Camino Inca se agota meses antes',
        'text' => 'Los permisos de ingreso son limitados por día y se venden por orden de reserva. Asegura tu fecha con nosotros y nosotros gestionamos el permiso.',
        'includes' => ['Permiso incluido', 'Porteadores y campamento', 'Retorno en tren'],
        'cta_primary' => 'Reservar mi cupo',
        'cta_secondary' => 'Consultar fechas',
        'price_label' => 'Camino Inca, 4 días, desde',
        // El monto vive en config('brand.inca_trail.price_pen_cents') (un entero no se puede leer con __()).
    ],

    'cities' => [
        'eyebrow' => 'Tu próximo mapa',
        'title' => 'Tours por ciudad',
        'text' => 'Elige una ciudad y descubre todos sus tours.',
        'see_all' => 'Ver todos los destinos',
        // Subtítulo propio de esta sección (PDF); distinto del tagline del mosaico.
        'taglines' => [
            'ica' => 'Desierto y oasis',
            'cusco' => 'Machu Picchu y Valle Sagrado',
            'lima' => 'Ciudad y gastronomía',
            'nazca' => 'Líneas de Nazca',
        ],
    ],

    'experiences' => [
        'eyebrow' => 'Vive Perú',
        'title' => 'Experiencias para vivir Perú',
        'text' => 'Elige cómo quieres recorrerlo: caminando, comiendo o aprendiendo de quienes lo habitan.',
        'taglines' => [
            'trekking' => 'Rutas de altura, de una jornada o de varios días.',
            'gastronomia' => 'Mercados, picanterías y cocinas de casa.',
            'cultura' => 'Sitios arqueológicos, talleres textiles y comunidades.',
        ],
        'links' => [
            'trekking' => 'Ver rutas',
            'gastronomia' => 'Ver sabores',
            'cultura' => 'Ver cultura',
        ],
        'link_default' => 'Ver experiencia',
    ],

    'landscapes' => [
        'eyebrow' => 'Perú se vive caminando',
        'title' => 'Paisajes que no caben en una foto',
        'text' => 'Te llevamos a los lugares que los buses no alcanzan, con guías que crecieron en estas montañas.',
        'cta_primary' => 'Explorar tours',
        'cta_secondary' => 'Planear mi viaje',
        'stats' => [
            ['value' => '[N]+', 'label' => 'viajeros felices'],
            ['value' => '[4,9]', 'label' => 'en Tripadvisor'],
            // "5 destinos" figura en el diseño; confirmar con la clienta.
            ['value' => '5', 'label' => 'destinos en Perú'],
        ],
    ],

    'plans' => [
        'eyebrow' => 'Según tus días',
        'title' => 'Encuentra un plan para tu tiempo',
        'text' => '¿Cuánto tiempo tienes? Te mostramos lo que puedes vivir.',
        'filter_label' => 'Filtrar por duración',
        'filters' => [
            'all' => 'Todos',
            '4h' => '4 h',
            '1dia' => '1 día',
            '2-3dias' => '2–3 días',
            '4mas' => '4+ días',
        ],
        'bucket_kicker' => [
            '4h' => 'Medio día',
            '1dia' => 'Un día completo',
            '2-3dias' => 'Fin de semana',
            '4mas' => 'Viaje largo',
        ],
        'from' => 'Desde',
        'price_placeholder' => '[PRECIO]',
        'view_plans' => 'Ver planes',
        'empty' => '[Planes por duración: pendientes de cargar en el CMS]',
    ],

    'reasons' => [
        'eyebrow' => 'Por qué elegirnos',
        'title' => 'Viaja con quienes conocen el camino',
        'items' => [
            ['title' => 'Expertos locales', 'text' => 'Conocemos cada rincón del Perú y diseñamos cada ruta nosotros mismos.'],
            ['title' => 'Atención personalizada', 'text' => 'Te ayudamos a planificar tu viaje ideal, sin respuestas automáticas.'],
            ['title' => 'Viajes seguros', 'text' => 'Tu seguridad y bienestar son nuestra prioridad en cada salida.'],
            ['title' => 'Asistencia 24/7', 'text' => 'Antes, durante y después de tu viaje, por WhatsApp.'],
        ],
    ],

    'testimonials' => [
        'eyebrow' => 'Opiniones reales',
        'title' => 'Lo que dicen nuestros viajeros',
        // Marcadores visibles: no hay reseñas reales cargadas.
        'items' => [
            ['text' => '[Testimonio de un viajero tomado de Tripadvisor sobre el Camino Inca.]', 'name' => '[Nombre del viajero]', 'meta' => '[País], Camino Inca', 'initial' => 'A'],
            ['text' => '[Testimonio de un viajero tomado de Tripadvisor sobre el Valle Sagrado.]', 'name' => '[Nombre del viajero]', 'meta' => '[País], Valle Sagrado', 'initial' => 'B'],
            ['text' => '[Testimonio de un viajero tomado de Tripadvisor sobre Arequipa.]', 'name' => '[Nombre del viajero]', 'meta' => '[País], Ruta de picanterías', 'initial' => 'C'],
        ],
        'seals' => [
            ['platform' => 'Tripadvisor', 'text' => '[4,9] en Tripadvisor'],
            ['platform' => 'Viator', 'text' => '[4,9] en Viator'],
            ['platform' => 'Google', 'text' => '[4,9] en Google'],
        ],
    ],

    'faq' => [
        'eyebrow' => 'Ayuda',
        'title' => 'Preguntas frecuentes',
        'text' => '¿No encuentras tu respuesta? Escríbenos y te ayudamos.',
        'contact_cta' => 'Hablar por WhatsApp',
        // Textos de ejemplo prudentes (sin políticas duras ni cifras):
        // la clienta debe validarlos o reemplazarlos.
        'items' => [
            ['q' => '¿Cómo reservo un tour?', 'a' => 'Elige tu tour, selecciona la fecha y el número de viajeros y confirma en línea. También puedes reservar escribiéndonos por WhatsApp al :phone.'],
            ['q' => '¿Puedo cancelar o cambiar mi reserva?', 'a' => 'Depende del tour y de la fecha. Antes de pagar te confirmamos por WhatsApp las condiciones de cambio y cancelación de tu reserva, para que decidas con toda la información. Si necesitas cambiar la fecha, escríbenos cuanto antes y vemos las opciones disponibles.'],
            ['q' => '¿El recojo del hotel está incluido?', 'a' => 'Depende del tour. Muchos tours incluyen el recojo en tu hotel y otros salen desde un punto de encuentro. En cada tour te indicamos qué incluye, y al reservar te confirmamos por WhatsApp el lugar y la hora exacta de recojo.'],
            ['q' => '¿En qué idiomas son los tours?', 'a' => 'Atendemos en español e inglés. Si necesitas otro idioma o un guía en un idioma específico, escríbenos por WhatsApp y te confirmamos la disponibilidad para tu fecha.'],
            ['q' => '¿Qué medios de pago aceptan?', 'a' => 'Te indicamos los medios de pago disponibles al confirmar tu reserva, siempre por canales seguros. Escríbenos por WhatsApp y te explicamos cómo pagar antes de que hagas cualquier pago.'],
            ['q' => '¿Con cuánta anticipación debo reservar el Camino Inca?', 'a' => 'Cuanto antes mejor. Los permisos del Camino Inca son limitados por día y se agotan, por eso recomendamos reservar con varios meses de anticipación. Escríbenos con tu fecha ideal y revisamos la disponibilidad.'],
        ],
    ],

    'instagram' => [
        'text' => 'Síguenos y mira cada salida en vivo',
        'cta' => 'Seguir en Instagram',
        'alts' => [
            'Plaza de Armas de Lima de noche',
            'Viajeros en Machu Picchu',
            'Montaña de 7 Colores',
            'Laguna Humantay',
            'Laguna de Huacachina',
        ],
    ],

    'newsletter' => [
        'title' => 'Recibe ofertas y salidas confirmadas',
        'text' => 'Promociones exclusivas y fechas con cupo para tu próximo viaje. Un correo al mes.',
        'placeholder' => 'Ingresa tu correo electrónico',
        'submit' => 'Suscribirme',
        'inactive' => 'Gracias. La suscripción aún no está activa en esta versión del sitio.',
        'invalid' => 'Escribe un correo válido.',
    ],

    'footer' => [
        'about' => 'Agencia de turismo con expertos locales. Diseñamos tours y experiencias auténticas por los destinos más increíbles del Perú.',
        'whatsapp_cta' => 'Escríbenos por WhatsApp',
        'destinations' => 'Destinos',
        'links' => 'Enlaces',
        'contact' => 'Contacto',
        'link_items' => [
            'tours' => 'Tours',
            'experiences' => 'Experiencias',
            'about' => 'Nosotros',
            'faq' => 'Preguntas frecuentes',
            'contact' => 'Contacto',
        ],
        'rights' => 'Todos los derechos reservados.',
        'payments_label' => 'Pagos seguros:',
        'payments_placeholder' => '[medios de pago]',
    ],

];

<?php

return [

    // SEO (lote 1, arreglo A3). Descripción de respaldo cuando una vista no
    // pasa su propia `description` a <x-layout> (hoy es el caso de
    // contact.blade.php, fuera de mi alcance en este lote). Sin cifras ni
    // afirmaciones regulatorias: ver docs/lote-1/00-sistema-diseno.md.
    'seo' => [
        'default_description' => 'Pacha Viva es una agencia de turismo en Cusco, Perú. Diseñamos tours y experiencias auténticas por los destinos del país con expertos locales.',
    ],

    // UI compartida (lote 1, arreglo A1 sobre `carousel-shell.blade.php`).
    // ':position'/':total' se sustituyen en el cliente (Alpine), no acá: son
    // reactivos al slide activo y al conteo real de tarjetas.
    'ui' => [
        'carousel' => [
            'pagination_group' => 'Paginación de :label',
            'go_to_card' => 'Ir a la tarjeta :position de :total',
        ],
    ],

    'nav' => [
        'home' => 'Inicio',
        'tours' => 'Tours',
        'destinations' => 'Destinos',
        'experiences' => 'Experiencias',
        'about' => 'Nosotros',
        'contact' => 'Contacto',
    ],

    'header' => [
        'search' => 'Buscar',
        'search_soon' => 'Buscador — próximamente',
        'currency' => 'Moneda',
        'language' => 'Idioma',
        'language_soon' => 'Próximamente',
        'contact_cta' => 'Contáctanos',
        'open_menu' => 'Abrir menú',
        'close_menu' => 'Cerrar menú',
    ],

    'footer' => [
        'quick_links' => 'Enlaces rápidos',
        'destinations' => 'Destinos',
        'information' => 'Información',
        'contact' => 'Contacto',
        'privacy_policy' => 'Política de privacidad',
        'cancellation_policy' => 'Política de cancelación',
        'complaints_book' => 'Libro de Reclamaciones',
        'faq' => 'Preguntas frecuentes',
        'rights' => 'Todos los derechos reservados.',
    ],

    // Home (lote 1, etapa B). Copy de marketing genérico, sin cifras ni
    // reseñas: eso sale de Setting/DB (ver home.blade.php), no de acá.
    'home' => [
        // S-12 (auditoría SEO 02/09): el title de Home no puede caer al
        // fallback de solo-marca de layout.blade.php — es el title de mayor
        // valor del sitio. Contenido final a validar con la clienta/Anyerson.
        'meta' => [
            'title' => 'Agencia de turismo en Cusco, Perú',
            'description' => 'Agencia de turismo en Cusco, Perú. Diseñamos experiencias auténticas e inolvidables en los destinos más increíbles del país, guiadas por expertos locales.',
        ],

        'hero' => [
            'title_before' => 'Vive lo mejor',
            'title_highlight' => 'de Perú',
            'title_after' => 'con expertos locales',
            'subtitle' => 'Diseñamos experiencias auténticas e inolvidables en los destinos más increíbles del Perú.',
            'cta_primary' => 'Explorar tours',
            'cta_secondary' => 'Ver destinos',
            'photo_alt' => 'Viajera contemplando el paisaje andino',
            'trust' => [
                'safe' => 'Viajes 100% seguros',
                'guides' => 'Guías locales expertos',
                'personalized' => 'Atención personalizada',
                'prices' => 'Mejores precios garantizados',
                'sustainable' => 'Turismo sostenible',
            ],
        ],

        'featured_tours' => [
            'title' => 'Tours destacados',
            'cta' => 'Ver todos los tours',
        ],

        'destinations' => [
            'title' => 'Destinos imperdibles',
            'cta' => 'Ver todos',
        ],

        'why_us' => [
            'title_before' => '¿Por qué elegir viajar con',
            'title_highlight' => 'nosotros',
            'title_after' => '?',
            'photo_alt' => 'Pareja de viajeros revisando un mapa',
            'features' => [
                ['title' => 'Expertos locales', 'description' => 'Conocemos cada rincón del Perú.'],
                ['title' => 'Atención personalizada', 'description' => 'Te ayudamos a planificar tu viaje ideal.'],
                ['title' => 'Viajes seguros', 'description' => 'Tu seguridad y bienestar son nuestra prioridad.'],
                ['title' => 'Mejores experiencias', 'description' => 'Creamos recuerdos que duran toda la vida.'],
            ],
            'assistance_title' => 'Asistencia 24/7',
            'assistance_description' => 'Te acompañamos antes, durante y después de tu viaje.',
        ],

        'experiences' => [
            'title' => 'Experiencias únicas',
            'cta' => 'Ver todas',
        ],

        'newsletter' => [
            'title' => 'Recibe ofertas y novedades',
            'description' => 'Suscríbete a nuestro boletín y recibe promociones exclusivas para tu próximo viaje.',
            'photo_alt' => 'Viajero con los brazos abiertos frente a la montaña',
            'email_label' => 'Correo electrónico',
            'email_placeholder' => 'Ingresa tu correo electrónico',
            'submit' => 'Suscribirme',
            'unavailable' => 'Newsletter — próximamente, sin envío real todavía',
        ],

        'empty' => [
            'tours' => 'Muy pronto vas a encontrar acá nuestros tours destacados.',
            'destinations' => 'Muy pronto vas a encontrar acá nuestros destinos.',
            'experiences' => 'Muy pronto vas a encontrar acá nuestras experiencias.',
        ],
    ],

    // Contacto (lote 1, etapa C). Sin cifras/datos de contacto cableados:
    // teléfono, correo, dirección y redes salen de Setting (ver
    // contact.blade.php). El formulario no persiste (contact_messages es
    // lote 3): envío deshabilitado, mismo patrón que el newsletter de Home.
    'contacto' => [
        'breadcrumb' => [
            'home' => 'Inicio',
            'current' => 'Contacto',
        ],

        'hero' => [
            'eyebrow' => 'Estamos para ayudarte',
            'title_before' => 'Hablemos de tu próxima',
            'title_highlight' => 'aventura',
            'subtitle' => '¿Tienes dudas sobre nuestros tours o destinos? Nuestro equipo de expertos locales está listo para ayudarte a planificar una experiencia inolvidable en Perú.',
            'photo_alt' => 'Viajera contemplando el paisaje andino',
            'attributes' => [
                ['title' => 'Atención rápida', 'description' => 'Respondemos en menos de 24 hrs.'],
                ['title' => 'Asesoría personalizada', 'description' => 'Te ayudamos a crear el viaje ideal para ti.'],
                ['title' => 'Reserva segura', 'description' => 'Tu información está protegida.'],
            ],
        ],

        'form' => [
            'title' => 'Envíanos un mensaje',
            'description' => 'Completa el formulario y te responderemos lo antes posible.',
            'name_label' => 'Nombre completo',
            'name_placeholder' => 'Ingresa tu nombre',
            'email_label' => 'Correo electrónico',
            'email_placeholder' => 'Ingresa tu correo',
            'phone_label' => 'Teléfono / WhatsApp',
            'phone_placeholder' => 'Ej. +51 987 654 321',
            'subject_label' => 'Asunto',
            'subject_placeholder' => 'Selecciona un asunto',
            'subject_options' => [
                'reserva' => 'Reserva de un tour',
                'consulta' => 'Consulta general',
                'modificacion' => 'Modificar una reserva',
                'otro' => 'Otro',
            ],
            'message_label' => 'Mensaje',
            'message_placeholder' => 'Cuéntanos cómo podemos ayudarte...',
            'privacy_pre' => 'Acepto la',
            'privacy_link' => 'política de privacidad',
            'privacy_pending' => 'política de privacidad (en preparación por la clienta)',
            'privacy_post' => 'y el tratamiento de mis datos.',
            'submit' => 'Enviar mensaje',
            'unavailable' => 'Formulario de contacto — próximamente, sin envío real todavía',
        ],

        'info' => [
            'title' => 'Información de contacto',
            'phone_title' => 'Teléfono / WhatsApp',
            'email_title' => 'Correo electrónico',
            'address_title' => 'Dirección',
            'social_title' => 'Síguenos',
            'social_instagram' => 'Síguenos en Instagram',
            'social_facebook' => 'Síguenos en Facebook',
            'social_youtube' => 'Síguenos en YouTube',
            'social_whatsapp' => 'Escríbenos por WhatsApp',
            'empty' => 'Todavía no configuramos ningún dato de contacto público. Vuelve pronto.',
        ],

        'faq' => [
            'title' => 'Preguntas frecuentes',
            'items' => [
                [
                    'question' => '¿Cómo puedo reservar un tour?',
                    'answer' => 'Escríbenos por WhatsApp o correo con el tour y las fechas que te interesan; nuestro equipo te confirma la disponibilidad y te guía en el pago.',
                ],
                [
                    'question' => '¿Cuáles son los métodos de pago?',
                    'answer' => 'Aceptamos transferencia, tarjeta y pago en línea. Te confirmamos las opciones disponibles al coordinar tu reserva.',
                ],
                [
                    'question' => '¿Puedo personalizar un tour?',
                    'answer' => 'Sí. Cuéntanos qué buscas y adaptamos el itinerario, la duración o el grupo a tu medida.',
                ],
                [
                    'question' => '¿Qué incluye el precio de los tours?',
                    'answer' => 'Cada tour detalla lo que incluye en su propia ficha (transporte, guía, entradas, etc.). Revisa la ficha del tour o pregúntanos directamente.',
                ],
                [
                    'question' => '¿Cuál es la política de cancelación?',
                    'answer' => 'Todavía no publicamos esta política — la clienta debe redactarla y aprobarla antes de habilitar reservas en línea. Mientras tanto, contáctanos directamente si necesitas cancelar o reprogramar.',
                ],
            ],
        ],

        'help' => [
            'title' => '¿Necesitas ayuda inmediata?',
            'description' => 'Escríbenos por WhatsApp y conversa con nuestro equipo en tiempo real.',
            'cta' => 'Chatear por WhatsApp',
        ],

        'map' => [
            'title' => '¿Dónde estamos?',
            'placeholder_alt' => 'Mapa referencial — pendiente de dirección real',
            'visit_us' => 'Visítanos en nuestra oficina',
            'cta' => 'Ver en Google Maps',
            'cta_new_tab' => '(se abre en una pestaña nueva)',
            'missing' => 'Todavía no configuramos una dirección. En cuanto la clienta la confirme, vas a poder ver cómo llegar acá.',
        ],
    ],

    // Nosotros (lote 1, etapa D). D3: el copy de "purpose" y "values" es
    // plausible pero lo escribió un generador — queda como contenido de
    // arranque PENDIENTE DE APROBACIÓN de la clienta (ver
    // docs/lote-1/00-sistema-diseno.md §12). Dos afirmaciones concretas
    // (impacto en comunidades/medio ambiente, "sostenibilidad") son
    // verificables sobre la empresa, no adjetivos de relleno: marcadas
    // abajo. Cifras de "Nuestro equipo" y estadísticas NO viven acá — salen
    // de TeamMember/Setting, nunca de este archivo (D1/D2).
    'nosotros' => [
        'meta' => [
            'description' => 'Pacha Viva es una agencia de turismo en Cusco, Perú, que conecta viajeros con lo auténtico del país de la mano de expertos locales.',
        ],

        'breadcrumb' => [
            'home' => 'Inicio',
            'current' => 'Nosotros',
        ],

        'hero' => [
            'title' => 'Nosotros',
            'tagline' => 'Conectamos viajeros con lo auténtico del Perú',
            // PENDIENTE clienta: afirma "impacto positivo en las comunidades
            // locales y en el medio ambiente" — verificable, no relleno.
            'description' => 'Somos un equipo de apasionados por el Perú, comprometidos a brindar experiencias auténticas, responsables y memorables que generan un impacto positivo en las comunidades locales y en el medio ambiente.',
            'photo_alt' => 'Grupo de viajeros celebrando con los brazos en alto en la Montaña de 7 Colores',
        ],

        'purpose' => [
            'title' => 'Nuestro propósito',
            // PENDIENTE clienta (copy de arranque, no redactado por la
            // clienta todavía).
            'paragraph_1' => 'Conectar viajeros con la esencia del Perú a través de experiencias únicas, sostenibles y de la mano de expertos locales que conocen y aman su tierra.',
            // PENDIENTE clienta: afirma "impacto positivo en las comunidades
            // y en el medio ambiente" — verificable, no relleno.
            'paragraph_2' => 'Creemos en un turismo responsable que genera un impacto positivo en las comunidades y en el medio ambiente.',
            'signature' => 'Equipo Pacha Viva',
            'photo_alt' => 'Vista aérea de los andenes circulares de Moray al atardecer',
        ],

        'values' => [
            'title' => 'Nuestros valores',
            // PENDIENTE clienta (copy de arranque). "Sostenibilidad" promete
            // cuidado del medio ambiente y las culturas locales: verificable,
            // no adjetivo de relleno.
            'items' => [
                ['title' => 'Autenticidad', 'description' => 'Ofrecemos experiencias reales y auténticas.'],
                ['title' => 'Sostenibilidad', 'description' => 'Promovemos el cuidado del medio ambiente y las culturas locales.'],
                ['title' => 'Calidad', 'description' => 'Brindamos servicios de alta calidad con atención personalizada.'],
                ['title' => 'Pasión', 'description' => 'Amamos lo que hacemos y lo compartimos en cada viaje.'],
            ],
        ],

        'team' => [
            'title' => 'Nuestro equipo',
            'description' => 'Contamos con guías locales expertos, profesionales en turismo y un equipo comprometido en hacer de tu viaje una experiencia inolvidable.',
        ],

        'cta' => [
            'title' => '¿Listo para vivir la mejor experiencia en Perú?',
            'description' => 'Déjanos ser parte de tu próxima aventura.',
            'button' => 'Explorar tours',
            'photo_alt' => 'Viajero contemplando Machu Picchu desde el mirador al amanecer',
        ],
    ],

];

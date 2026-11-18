<?php

/*
 * Textos estáticos del Home (lote 1), inglés. Paridad de claves 1:1 con
 * lang/es/home.php (LangKeyParityTest). Marcadores entre corchetes = dato
 * que la clienta aún no entregó; deben verse tal cual (reglas 3 y 4).
 */

return [

    'meta' => [
        'title' => 'Inka Palace Travel | Peru tours with a local operator',
        'description' => 'Travel agency with local experts. Tours in Lima, Ica, Cusco, Machu Picchu and more, with secure booking.',
    ],

    'sample_note' => 'Includes sample cards (marked) until the CMS has enough content.',
    'sample_chip' => 'Sample',

    'topbar' => [
        'local_operator' => 'Local operator with',
        'secure_booking' => 'secure booking',
        'response' => 'We reply in under 24 h',
        'whatsapp' => 'WhatsApp',
        'notice' => 'Inca Trail 2027: limited permits, book early',
    ],

    'menu' => [
        'home' => 'Home',
        'tours' => 'Tours',
        'destinations' => 'Destinations',
        'experiences' => 'Experiences',
        'about' => 'About us',
        'faq' => 'FAQ',
        'contact' => 'Contact',
        'contact_cta' => 'Contact us',
        'cart' => 'Cart',
        'language' => 'Language',
        'currency' => 'Currency',
        'skip' => 'Skip to content',
    ],

    'hero' => [
        'badge' => 'Destination :current of :total: :name',
        'prev' => 'Previous',
        'next' => 'Next',
        'label' => 'Destinations',
        'pause' => 'Pause carousel',
        'play' => 'Resume carousel',
        'roledescription' => 'carousel',
        'slides' => [
            'lima' => [
                'name' => 'Lima Center',
                'subtitle' => 'Historic Center',
                'title' => 'Lima Historic Center',
                'text' => 'Walk the Plaza de Armas, the Cathedral and the colonial balconies of the City of Kings, a World Heritage Site.',
                'cta_primary' => 'See tours in Lima',
                'cta_secondary' => 'See destinations',
            ],
            'ica' => [
                'name' => 'Ica',
                'subtitle' => 'Huacachina and its dunes',
                'title' => 'Ica, desert and oasis',
                'text' => '[Ica slide text: pending]',
                'cta_primary' => 'See tours in Ica',
                'cta_secondary' => 'See destinations',
            ],
            'machu-picchu' => [
                'name' => 'Machu Picchu',
                'subtitle' => 'The Inca citadel',
                'title' => 'Machu Picchu',
                'text' => 'Arrive by train or hike the Inca Trail, with an official guide.',
                'cta_primary' => 'See tours to Machu Picchu',
                'cta_secondary' => 'See destinations',
            ],
        ],
    ],

    'search' => [
        'destination' => 'Destination',
        'destination_placeholder' => 'Where would you like to travel?',
        'date' => 'Departure date',
        'date_placeholder' => 'Pick a date',
        'travelers' => 'Travelers',
        'travelers_placeholder' => '2 travelers',
        'submit' => 'Search tours',
        'traveler_one' => 'traveler',
        'traveler_other' => 'travelers',
    ],

    'guarantees' => [
        ['title' => 'Local operator', 'text' => 'No middlemen'],
        ['title' => 'Secure booking', 'text' => 'Protected data and payments'],
        ['title' => 'Expert guides', 'text' => 'Official and bilingual'],
        ['title' => 'Personal attention', 'text' => 'Before and during your trip'],
        ['title' => 'Flexible cancellation', 'text' => 'Change your date'],
    ],

    'whatsapp_float' => [
        'title' => 'Need help?',
        'text' => 'Message us on WhatsApp',
    ],

    'destinations' => [
        'eyebrow' => 'Unmissable destinations',
        'title' => 'One country, a thousand landscapes',
        'text' => 'Snowy mountains, deserts, sacred valleys and the best cuisine on the continent. Choose where to start.',
        'see_all' => 'See all destinations',
        'most_wanted' => 'Most wanted',
        'tours_count' => '{0} No published tours|{1} :count tour|[2,*] :count tours',
        'placeholder_count' => '[N] tours',
        'taglines' => [
            'cusco' => 'Capital of the Inca empire',
            'valle-sagrado' => 'Pisac, Maras and Moray',
            'machu-picchu' => 'Arrive by train or hike the Inca Trail, with an official guide.',
            'ica' => 'Huacachina and its dunes',
            'humantay' => 'Turquoise lake and snowy peaks',
            'lima' => 'City and gastronomy',
            'nazca' => 'Nazca Lines',
        ],
    ],

    'featured' => [
        'eyebrow' => 'Inka Palace Travel selection',
        'title' => 'Featured tours',
        'text' => "Our travelers' favorites, with hotel pickup and a guide in Spanish or English.",
        'see_all' => 'See all tours',
        'rating_placeholder' => '[4,9]',
        'reviews_placeholder' => '[N] reviews',
        'languages_placeholder' => '[Languages]',
        'flexible_cancellation' => 'Flexible cancellation',
        'from' => 'From',
        'per_person' => 'per person',
        'price_placeholder' => '[PRICE]',
        'duration_placeholder' => '[Duration]',
        'view_tour' => 'View tour',
        'favorite' => 'Save to favorites',
    ],

    'inca_trail' => [
        'badge' => 'Limited spots',
        'title' => 'The Inca Trail sells out months ahead',
        'text' => 'Entry permits are limited per day and sold in order of booking. Secure your date with us and we handle the permit.',
        'includes' => ['Permit included', 'Porters and camp', 'Train back'],
        'cta_primary' => 'Book my spot',
        'cta_secondary' => 'Check dates',
        'price_label' => 'Inca Trail, 4 days, from',
        // El monto vive en config('brand.inca_trail.price_pen_cents').
    ],

    'cities' => [
        'eyebrow' => 'Your next map',
        'title' => 'Tours by city',
        'text' => 'Pick a city and discover all its tours.',
        'see_all' => 'See all destinations',
        'taglines' => [
            'ica' => 'Desert and oasis',
            'cusco' => 'Machu Picchu and the Sacred Valley',
            'lima' => 'City and gastronomy',
            'nazca' => 'Nazca Lines',
        ],
    ],

    'experiences' => [
        'eyebrow' => 'Live Peru',
        'title' => 'Experiences to live Peru',
        'text' => 'Choose how to explore it: on foot, eating, or learning from the people who live there.',
        'taglines' => [
            'trekking' => 'High-altitude routes, one day or several.',
            'gastronomia' => 'Markets, picanterías and home kitchens.',
            'cultura' => 'Archaeological sites, textile workshops and communities.',
        ],
        'links' => [
            'trekking' => 'See routes',
            'gastronomia' => 'See flavors',
            'cultura' => 'See culture',
        ],
        'link_default' => 'See experience',
    ],

    'landscapes' => [
        'eyebrow' => 'Peru is best lived on foot',
        'title' => "Landscapes that don't fit in a photo",
        'text' => "We take you to places buses don't reach, with guides who grew up in these mountains.",
        'cta_primary' => 'Explore tours',
        'cta_secondary' => 'Plan my trip',
        'stats' => [
            ['value' => '[N]+', 'label' => 'happy travelers'],
            ['value' => '[4,9]', 'label' => 'on Tripadvisor'],
            // "5 destinos" figura en el diseño; confirmar con la clienta.
            ['value' => '5', 'label' => 'destinations in Peru'],
        ],
    ],

    'plans' => [
        'eyebrow' => 'By your days',
        'title' => 'Find a plan for your time',
        'text' => 'How much time do you have? Here is what you can experience.',
        'filter_label' => 'Filter by duration',
        'filters' => [
            'all' => 'All',
            '4h' => '4 h',
            '1dia' => '1 day',
            '2-3dias' => '2–3 days',
            '4mas' => '4+ days',
        ],
        'bucket_kicker' => [
            '4h' => 'Half day',
            '1dia' => 'Full day',
            '2-3dias' => 'Weekend',
            '4mas' => 'Long trip',
        ],
        'from' => 'From',
        'price_placeholder' => '[PRICE]',
        'view_plans' => 'See plans',
        'empty' => '[Plans by duration: pending upload in the CMS]',
    ],

    'reasons' => [
        'eyebrow' => 'Why choose us',
        'title' => 'Travel with people who know the way',
        'items' => [
            ['title' => 'Local experts', 'text' => 'We know every corner of Peru and design each route ourselves.'],
            ['title' => 'Personal attention', 'text' => 'We help you plan your ideal trip, with no automated replies.'],
            ['title' => 'Safe travel', 'text' => 'Your safety and well-being are our priority on every departure.'],
            ['title' => '24/7 assistance', 'text' => 'Before, during and after your trip, via WhatsApp.'],
        ],
    ],

    'testimonials' => [
        'eyebrow' => 'Real opinions',
        'title' => 'What our travelers say',
        // Marcadores visibles: no hay reseñas reales cargadas.
        'items' => [
            ['text' => '[Traveler testimonial from Tripadvisor about the Inca Trail.]', 'name' => '[Nombre del viajero]', 'meta' => '[Country], Inca Trail', 'initial' => 'A'],
            ['text' => '[Traveler testimonial from Tripadvisor about the Sacred Valley.]', 'name' => '[Nombre del viajero]', 'meta' => '[Country], Sacred Valley', 'initial' => 'B'],
            ['text' => '[Traveler testimonial from Tripadvisor about Arequipa.]', 'name' => '[Nombre del viajero]', 'meta' => '[Country], Picantería food trail', 'initial' => 'C'],
        ],
        'seals' => [
            ['platform' => 'Tripadvisor', 'text' => '[4,9] on Tripadvisor'],
            ['platform' => 'Viator', 'text' => '[4,9] on Viator'],
            ['platform' => 'Google', 'text' => '[4,9] on Google'],
        ],
    ],

    'faq' => [
        'eyebrow' => 'Help',
        'title' => 'Frequently asked questions',
        'text' => "Can't find your answer? Write to us and we will help.",
        'contact_cta' => 'Talk on WhatsApp',
        'items' => [
            ['q' => 'How do I book a tour?', 'a' => 'Pick your tour, choose the date and number of travelers and confirm online. You can also book by messaging us on WhatsApp at +51 959 173 246.'],
            ['q' => 'Can I cancel or change my booking?', 'a' => '[Answer pending: cancellation policy]'],
            ['q' => 'Is hotel pickup included?', 'a' => '[Answer pending]'],
            ['q' => 'What languages are the tours in?', 'a' => '[Answer pending]'],
            ['q' => 'Which payment methods do you accept?', 'a' => '[Answer pending]'],
            ['q' => 'How far ahead should I book the Inca Trail?', 'a' => '[Answer pending]'],
        ],
    ],

    'instagram' => [
        'text' => 'Follow us and see every departure live',
        'cta' => 'Follow on Instagram',
        'alts' => [
            'Plaza de Armas in Lima at night',
            'Travelers at Machu Picchu',
            'Rainbow Mountain',
            'Humantay Lake',
            'Huacachina lagoon',
        ],
    ],

    'newsletter' => [
        'title' => 'Get offers and confirmed departures',
        'text' => 'Exclusive promotions and dates with availability for your next trip. One email a month.',
        'placeholder' => 'Enter your email address',
        'submit' => 'Subscribe',
        'inactive' => 'Thank you. Subscription is not active yet in this version of the site.',
        'invalid' => 'Enter a valid email address.',
    ],

    'footer' => [
        'about' => "Travel agency with local experts. We design authentic tours and experiences across Peru's most incredible destinations.",
        'whatsapp_cta' => 'Message us on WhatsApp',
        'destinations' => 'Destinations',
        'links' => 'Links',
        'contact' => 'Contact',
        'link_items' => [
            'tours' => 'Tours',
            'experiences' => 'Experiences',
            'about' => 'About us',
            'faq' => 'FAQ',
            'contact' => 'Contact',
        ],
        'rights' => 'All rights reserved.',
        'payments_label' => 'Secure payments:',
        'payments_placeholder' => '[payment methods]',
    ],

];

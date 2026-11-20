<?php

/*
|--------------------------------------------------------------------------
| Marca: INKA PALACE Travel
|--------------------------------------------------------------------------
|
| Datos de marca y contacto leídos del diseño del Home (lote 1). Un valor
| NULL significa "sin dato": la vista no debe inventarlo ni mostrar un
| enlace vacío. Los textos largos viven en lang/es/home.php.
|
*/

return [

    'name' => 'INKA PALACE Travel',

    'tagline' => 'Agencia de turismo con expertos locales',

    /*
    | Interruptor ÚNICO de marcadores ([N], [PRECIO], [4,9]...). Con true se
    | muestran TAL CUAL el PDF de diseño (revisión con la clienta); con false
    | (producción) App\Support\Placeholder los oculta. SHOW_PLACEHOLDERS manda;
    | sin él, hereda IS_STAGING_MIRROR (el espejo de staging lo trae en true).
    | Sin ninguno de los dos: false.
    */
    'show_placeholders' => filter_var(env('SHOW_PLACEHOLDERS', env('IS_STAGING_MIRROR', false)), FILTER_VALIDATE_BOOL),

    'logo' => 'images/brand/inka-palace-logo.png',

    'contact' => [
        'phone' => '+51 959 173 246',
        // Solo dígitos, formato wa.me (código de país + número).
        'whatsapp' => '51959173246',
        'whatsapp_url' => 'https://wa.me/51959173246',
        'email' => 'maribel@inkapalacetravel.com',
        'address' => 'Jr. Carabaya 161, Lima',
        'website' => 'www.inkapalacetravel.com',
        'website_url' => 'https://www.inkapalacetravel.com',
        'instagram_handle' => '@inkapalacetravel',
    ],

    'manager' => [
        'name' => 'Maribel Condori Gonzales',
        'role' => 'Gerente General',
        'initials' => 'MC',
    ],

    // URL NULL = la clienta aún no entregó el enlace; no inventar.
    'social' => [
        'tripadvisor' => null,
        'viator' => null,
        'youtube' => null,
        'instagram' => null,
        'facebook' => null,
        'tiktok' => null,
    ],

    /*
    | Mapa slug (destino o experiencia) -> imagen provisional bajo public/.
    | Sin coincidencia, la tarjeta se muestra sin foto (fondo de marca).
    */
    'images' => [
        'lima' => 'img/provisional/lima.jpg',
        'ica' => 'img/provisional/ica.jpg',
        'cusco' => 'img/provisional/cusco.jpg',
        'valle-sagrado' => 'img/provisional/valle-sagrado.jpg',
        'machu-picchu' => 'img/provisional/machu-picchu.jpg',
        'humantay' => 'img/provisional/humantay.jpg',
        'nazca' => 'img/provisional/nazca.jpg',
        'arequipa' => 'img/provisional/arequipa.jpg',
        'trekking' => 'img/provisional/trekking.jpg',
        'gastronomia' => 'img/provisional/gastronomia.jpg',
        'cultura' => 'img/provisional/cultura.jpg',
    ],

    /*
    | Columna "Destinos" del footer según el PDF (orden fijo). Cada ítem usa la
    | ficha del CMS si existe un destino publicado con ese slug; si no, enlaza al
    | listado. El componente footer debe leer esta lista (pendiente de maquetación).
    */
    'footer_destinations' => [
        ['key' => 'lima', 'name' => 'Lima'],
        ['key' => 'ica', 'name' => 'Ica'],
        ['key' => 'cusco', 'name' => 'Cusco y Machu Picchu'],
        ['key' => 'nazca', 'name' => 'Nazca'],
        ['key' => 'arequipa', 'name' => 'Arequipa'],
    ],

    // Banner Camino Inca. Monto en céntimos de PEN (350000 = S/ 3,500.00).
    // DATO DEL PDF: confirmar con la clienta antes de publicar.
    'inca_trail' => [
        'price_pen_cents' => 350000,
    ],

    // Imágenes fijas de secciones (rutas bajo public/).
    'fixed_images' => [
        'camino_inca' => 'img/provisional/camino-inca.jpg',
        'paisajes' => 'img/provisional/paisajes.jpg',
        'plan_nazca' => 'img/provisional/plan-nazca.jpg',
        // Fotos de las tarjetas de PLAN (PDF s05, 281.5x200): encuadre propio, distinto al de la tarjeta de tour.
        'plans' => [
            'city-tour-lima' => 'img/provisional/plan-city-tour-lima.jpg',
            'montana-7-colores' => 'img/provisional/plan-montana-7-colores.jpg',
        ],
        'tours' => [
            'city-tour-lima' => 'img/provisional/tour-city-tour-lima.jpg',
            'laguna-humantay' => 'img/provisional/tour-laguna-humantay.jpg',
            'montana-7-colores' => 'img/provisional/tour-montana-7-colores.jpg',
            'machu-picchu' => 'img/provisional/tour-machu-picchu.jpg',
        ],
        'instagram' => [
            'img/provisional/ig-1.jpg',
            'img/provisional/ig-2.jpg',
            'img/provisional/ig-3.jpg',
            'img/provisional/ig-4.jpg',
            'img/provisional/ig-5.jpg',
            'img/provisional/ig-6.jpg',
        ],
    ],

    /*
    | Hero: 3 slides fijos. Los textos (título, descripción, CTA) están en
    | lang/es/home.php -> hero.slides.{slug}. Aquí solo van las imágenes.
    */
    'hero' => [
        'slides' => [
            ['slug' => 'lima', 'image' => 'img/provisional/hero-lima.jpg', 'thumb' => 'img/provisional/thumb-lima.jpg'],
            ['slug' => 'ica', 'image' => 'img/provisional/hero-ica.jpg', 'thumb' => 'img/provisional/thumb-ica.jpg'],
            ['slug' => 'machu-picchu', 'image' => 'img/provisional/hero-machu-picchu.jpg', 'thumb' => 'img/provisional/thumb-machu-picchu.jpg'],
        ],
    ],

];

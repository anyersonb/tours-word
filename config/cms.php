<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Idiomas del catálogo
    |--------------------------------------------------------------------------
    |
    | "locales" es el universo completo que el ESQUEMA soporta (columnas JSON
    | traducibles en Tour, Destination, Experience, TourImage). "active_locales"
    | es el subconjunto que el panel de Filament expone como pestañas editables
    | HOY. Español está activo desde el lote 2; EN y PT-BR ya tienen columna
    | pero se activan recién en el lote 5 (ver docs/lote-2/00-contrato-datos.md).
    | Migrar contenido a traducible más tarde es carísimo: por eso el esquema
    | ya soporta los tres idiomas aunque el panel solo edite uno.
    |
    */

    'locales' => [
        'es' => 'Español',
        'en' => 'English',
        'pt_BR' => 'Português (Brasil)',
    ],

    // Alcance corregido por Anyerson el 2026-09-10: el sitio es SOLO español
    // e inglés; PT-BR queda fuera del proyecto (se deja inerte en "locales"
    // porque el esquema y LocalePrefixRoutingTest ya lo referencian).
    // Inglés activado el 2026-09-14 (lote i18n): lang/en/site.php,
    // contact-form.php y validation.php ya tienen paridad de claves 1:1 con
    // español (ver el diff de claves del reporte del lote), así que "/en/"
    // ya no muestra claves crudas.
    'active_locales' => ['es', 'en'],

    /*
    |--------------------------------------------------------------------------
    | Contenido de muestra del catálogo
    |--------------------------------------------------------------------------
    |
    | true mientras tours/destinos/experiencias siguen sembrados por
    | DemoTourSeeder (lote 3): fuerza "noindex" en las fichas de destino y
    | experiencia, independientemente de si el contenido está traducido,
    | porque publicar un "Cusco" o "Trekking" de muestra en un motor de
    | búsqueda sería indexar contenido inventado. Extraído a config (en vez
    | de quedar cableado como `:noindex="true"` en el Blade) exactamente por
    | la misma razón que "active_locales" ya es config: es el único lugar que
    | hay que tocar para levantarlo cuando la clienta cargue contenido real
    | -- a partir de ahí, el noindex de cada ficha depende SOLO de si esa
    | ficha tiene traducción real al locale de la URL (ver "indexación del
    | inglés" del lote SEO, resources/views/destinations/show.blade.php y
    | resources/views/experiences/show.blade.php).
    |
    | ALCANCE REAL desde 7d25bf0: ya no es solo "las fichas del catálogo".
    | components/layout.blade.php hace `$isNoindex = $noindex ||
    | config('cms.catalog_demo_content')`, así que mientras esto esté en
    | true el sitio ENTERO va noindex -- home, nosotros y contacto
    | incluidos, en los dos locales activos. Por eso también cuelgan de
    | aquí, vía App\Support\Indexability, sitemap.xml (que si no anunciaría
    | justo esas páginas noindex: señal contradictoria) y la directiva
    | "Sitemap:" de robots.txt. Bajar esta bandera devuelve las tres cosas a
    | la vez, sin tocar código. Lo que NO cuelga de aquí y no debe colgar
    | nunca: el permiso de rastreo de robots.txt, porque poder descargar la
    | página es condición necesaria para que el noindex se llegue a leer.
    |
    */

    'catalog_demo_content' => true,

    /*
    |--------------------------------------------------------------------------
    | Moneda
    |--------------------------------------------------------------------------
    |
    | Monedas soportadas desde el día uno. El tipo de cambio real vive en la
    | tabla settings (clave "exchange_rate_pen_usd"), editable en el panel;
    | esto solo declara qué monedas existen y sus símbolos de despliegue.
    | Ningún componente debe cablear "S/" o "$" fuera de App\Support\Money.
    |
    */

    'currencies' => [
        'PEN' => ['symbol' => 'S/', 'name' => 'Sol peruano'],
        'USD' => ['symbol' => 'US$', 'name' => 'Dólar estadounidense'],
    ],

    'default_currency' => 'PEN',

];

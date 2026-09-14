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

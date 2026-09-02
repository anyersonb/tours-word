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

    'active_locales' => ['es'],

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

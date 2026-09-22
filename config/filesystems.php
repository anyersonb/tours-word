<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            // Relative URL a propósito (lote 1, ronda 2, Defecto Alto del
            // CRO / S-04 del SEO): Destination/Experience::coverImageUrl(),
            // TeamMember::photoUrl() y TourImage::url() resuelven todas por
            // este disco. Armarla desde env('APP_URL') hacía que cualquier
            // imagen del CMS se rompiera en silencio en cuanto APP_URL
            // apuntara a un host que no resuelve (medido: ERR_NAME_NOT_RESOLVED,
            // naturalWidth 0) -- y seguiría rompiendo con cualquier futuro
            // desajuste www/no-www. "/storage" nunca es absoluta, así que el
            // navegador siempre la resuelve contra el host real que sirvió
            // la página.
            //
            // PUBLIC_STORAGE_URL (docs/rediseno-2026/15-urls-imagenes-subdirectorio.md):
            // único punto de ajuste para el despliegue de demo en el subdirectorio
            // ajeno (limaviewtours.com/tour-word/), donde el .htaccess de la app
            // BLOQUEA el acceso directo a "storage/" en la raíz (regla anti-fuga de
            // App/Config/etc.) y solo "tour-word/public/storage/..." llega al archivo
            // real -- medido: "/tour-word/storage/foo.webp" -> 403, "/tour-word/public/
            // storage/foo.webp" -> 200. `env('PUBLIC_STORAGE_URL') ?: '/storage'`
            // (nunca el segundo argumento de env()) a propósito: si el .env del
            // servidor declara la clave vacía ("PUBLIC_STORAGE_URL=") en vez de
            // omitirla, `?:` la trata igual que ausente y cae al default -- con
            // env('KEY', 'default') una clave presente-pero-vacía se habría quedado
            // en '' (URL rota) en vez de caer al default. Vacío/ausente = el
            // comportamiento de siempre, local y producción real futura en la raíz
            // del dominio, sin tocar nada.
            'url' => env('PUBLIC_STORAGE_URL') ?: '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];

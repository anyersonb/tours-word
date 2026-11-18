<?php

// Front controller de la RAIZ de /tour-word/ (no el de public/).
//
// Motivo: con la app en un subdirectorio y public/ escondido por reescritura,
// SCRIPT_NAME quedaba en /tour-word/public/index.php mientras REQUEST_URI era
// /tour-word/es. Symfony no logra deducir la base con esa pareja, calcula
// baseUrl vacio y el router busca la ruta "tour-word/es", que no existe -> 404
// en todas las paginas, y ademas asset()/Storage::url() generan rutas colgando
// de la raiz del dominio (/storage/tours/...) en vez de /tour-word/storage/...
//
// Con este archivo aqui, SCRIPT_NAME es /tour-word/index.php, la base detectada
// es /tour-word y pathInfo vuelve a ser /es. Los tres require apuntan a esta
// misma carpeta, no a la de arriba.

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

$app->handleRequest(Request::capture());

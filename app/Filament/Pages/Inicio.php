<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard;

/**
 * Inicio del panel: el mismo Dashboard de Filament, solo con el nombre en
 * español llano ("Inicio" en vez de "Escritorio"). Mantiene el slug
 * "dashboard" para que la ruta y los enlaces internos de Filament no cambien.
 * Se registra vía discoverPages(); no declarar Dashboard::class aparte.
 */
class Inicio extends Dashboard
{
    protected static ?string $slug = 'dashboard';

    protected static ?string $title = 'Inicio';

    protected static ?string $navigationLabel = 'Inicio';
}

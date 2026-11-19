<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\Tours\TourResource;
use Filament\Widgets\Widget;

/**
 * Saludo y accesos rápidos del Inicio del panel. Solo lee datos y arma
 * enlaces internos: no escribe nada.
 */
class BienvenidaWidget extends Widget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.bienvenida';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'nombre' => auth()->user()?->name,
            'marca' => config('brand.name'),
            'urlNuevoTour' => TourResource::getUrl('create'),
            'urlMensajes' => ContactMessageResource::getUrl('index'),
            'urlSitio' => url('/'),
        ];
    }
}

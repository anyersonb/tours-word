<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Destinations\DestinationResource;
use App\Filament\Resources\Experiences\ExperienceResource;
use App\Filament\Resources\Tours\TourResource;
use App\Models\Destination;
use App\Models\Experience;
use App\Models\Tour;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * Cuántos tours, experiencias y destinos están publicados (visibles en el
 * sitio) frente al total cargado. Solo lectura.
 */
class CatalogoStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Lo que se ve en tu sitio';

    protected ?string $description = 'Solo se muestran en el sitio los elementos marcados como "Publicado".';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        return [
            $this->stat('Tours publicados', Tour::query(), TourResource::getUrl('index'), 'heroicon-o-map'),
            $this->stat('Experiencias publicadas', Experience::query(), ExperienceResource::getUrl('index'), 'heroicon-o-sparkles'),
            $this->stat('Destinos publicados', Destination::query(), DestinationResource::getUrl('index'), 'heroicon-o-map-pin'),
        ];
    }

    private function stat(string $label, Builder $query, string $url, string $icon): Stat
    {
        $total = (clone $query)->count();
        $publicados = (clone $query)->where('is_published', true)->count();

        return Stat::make($label, $publicados)
            ->description("de {$total} cargados")
            ->descriptionIcon($icon)
            ->url($url);
    }
}

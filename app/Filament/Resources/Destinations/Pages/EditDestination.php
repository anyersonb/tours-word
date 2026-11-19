<?php

namespace App\Filament\Resources\Destinations\Pages;

use App\Filament\Resources\Destinations\DestinationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDestination extends EditRecord
{
    protected static string $resource = DestinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading('¿Eliminar este destino?')
                ->modalDescription('Se borrará para siempre y no se puede deshacer. ' . 'Si solo quieres ocultarlo del sitio, mejor desactiva "Publicado".')
                ->modalSubmitActionLabel('Sí, eliminar')
                ->modalCancelActionLabel('No, volver'),
        ];
    }
}

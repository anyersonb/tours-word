<?php

namespace App\Filament\Resources\Experiences\Pages;

use App\Filament\Resources\Experiences\ExperienceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExperience extends EditRecord
{
    protected static string $resource = ExperienceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading('¿Eliminar esta experiencia?')
                ->modalDescription('Se borrará para siempre y no se puede deshacer. ' . 'Si solo quieres ocultarla del sitio, mejor desactiva "Publicado".')
                ->modalSubmitActionLabel('Sí, eliminar')
                ->modalCancelActionLabel('No, volver'),
        ];
    }
}

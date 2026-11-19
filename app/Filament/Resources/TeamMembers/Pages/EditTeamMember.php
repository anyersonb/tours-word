<?php

namespace App\Filament\Resources\TeamMembers\Pages;

use App\Filament\Resources\TeamMembers\TeamMemberResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTeamMember extends EditRecord
{
    protected static string $resource = TeamMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading('¿Eliminar a este integrante del equipo?')
                ->modalDescription('Se borrará para siempre y no se puede deshacer. ' . 'Si solo quieres ocultarlo del sitio, mejor desactiva "Publicado".')
                ->modalSubmitActionLabel('Sí, eliminar')
                ->modalCancelActionLabel('No, volver'),
        ];
    }
}

<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContactMessage extends EditRecord
{
    protected static string $resource = ContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading('¿Eliminar este mensaje?')
                ->modalDescription('Se borrará para siempre y no se puede deshacer. ' . 'Si ya lo atendiste, mejor cambia su estado a "Atendido".')
                ->modalSubmitActionLabel('Sí, eliminar')
                ->modalCancelActionLabel('No, volver'),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Enums\ContactMessageStatus;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Los 5 mensajes de contacto más recientes, con acceso directo a cada uno.
 * Solo lectura (el estado se cambia dentro del mensaje).
 */
class UltimosMensajesWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Últimos mensajes de contacto';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => ContactMessage::query()->latest()->limit(5))
            ->paginated(false)
            ->columns([
                TextColumn::make('created_at')
                    ->label('Recibido')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('name')
                    ->label('Nombre'),
                TextColumn::make('tour_title')
                    ->label('Tour consultado')
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (ContactMessageStatus $state): string => $state->label())
                    ->color(fn (ContactMessageStatus $state): string => $state->color()),
            ])
            ->recordActions([
                Action::make('abrir')
                    ->label('Abrir')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ContactMessage $record): string => ContactMessageResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('Todavía no hay mensajes')
            ->emptyStateDescription('Cuando alguien escriba desde el formulario de contacto del sitio, aparecerá aquí.');
    }
}

<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Enums\ContactMessageStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Recibido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subject')
                    ->label('Asunto')
                    ->wrap()
                    ->formatStateUsing(fn (?string $state): string => $state ? __('site.contacto.form.subject_options.'.$state) : ''),
                // DEF-A: instantánea del tour, buscable. Sin esto la bandeja
                // no distingue una solicitud de reserva del Camino Inca de
                // una consulta genérica.
                TextColumn::make('tour_title')
                    ->label('Tour consultado')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable()
                    ->limit(40)
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (ContactMessageStatus $state): string => $state->label())
                    ->color(fn (ContactMessageStatus $state): string => $state->color()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(ContactMessageStatus::options()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalHeading('¿Eliminar los elementos seleccionados?')
                        ->modalDescription('Se borrarán para siempre y no se puede deshacer. ' . 'Si ya los atendiste, mejor cambia su estado a "Atendido".')
                        ->modalSubmitActionLabel('Sí, eliminar')
                        ->modalCancelActionLabel('No, volver'),
                ]),
            ]);
    }
}

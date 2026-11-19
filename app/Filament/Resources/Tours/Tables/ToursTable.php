<?php

namespace App\Filament\Resources\Tours\Tables;

use App\Enums\TourDifficulty;
use App\Models\Tour;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ToursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('order')
            ->reorderable('order')
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable(query: fn ($query, string $search) => $query->where('title->es', 'like', "%{$search}%"))
                    ->limit(40),
                TextColumn::make('destination.name')
                    ->label('Destino')
                    ->limit(24)
                    ->toggleable(),
                TextColumn::make('difficulty')
                    ->label('Dificultad')
                    ->badge()
                    ->toggleable()
                    ->formatStateUsing(fn (?TourDifficulty $state) => $state?->label()),
                TextColumn::make('price_pen_cents')
                    ->label('PEN')
                    ->formatStateUsing(fn (Tour $record) => $record->priceInPen()->format())
                    ->sortable(),
                TextColumn::make('price_usd_cents')
                    ->label('USD')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn (Tour $record) => $record->priceInUsd()->format())
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->label('Destacado')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->boolean(),
                IconColumn::make('is_published')
                    ->label('Publicado')
                    ->boolean(),
                TextColumn::make('order')
                    ->label('Orden')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Publicado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo publicados')
                    ->falseLabel('Solo ocultos'),
                TernaryFilter::make('is_featured')
                    ->label('Destacado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo destacados')
                    ->falseLabel('Sin destacar'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalHeading('¿Eliminar los elementos seleccionados?')
                        ->modalDescription('Se borrarán para siempre y no se puede deshacer. ' . 'Si solo quieres ocultarlos del sitio, desactiva "Publicado" en cada uno.')
                        ->modalSubmitActionLabel('Sí, eliminar')
                        ->modalCancelActionLabel('No, volver'),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Destinations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DestinationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('order')
            ->reorderable('order')
            ->columns([
                ImageColumn::make('cover_image_path')
                    ->label('Imagen')
                    ->disk('public'),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(query: fn ($query, string $search) => $query->where('name->es', 'like', "%{$search}%")),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(query: fn ($query, string $search) => $query->where('slug->es', 'like', "%{$search}%")),
                TextColumn::make('tours_count')
                    ->label('Tours')
                    ->counts('tours'),
                IconColumn::make('is_published')
                    ->label('Publicado')
                    ->boolean(),
                TextColumn::make('order')
                    ->label('Orden')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Publicado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo publicados')
                    ->falseLabel('Solo ocultos'),
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

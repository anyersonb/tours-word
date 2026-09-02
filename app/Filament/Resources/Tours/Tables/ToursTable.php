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
                    ->label('Destino'),
                TextColumn::make('difficulty')
                    ->label('Dificultad')
                    ->badge()
                    ->formatStateUsing(fn (?TourDifficulty $state) => $state?->label()),
                TextColumn::make('price_pen_cents')
                    ->label('PEN')
                    ->formatStateUsing(fn (Tour $record) => $record->priceInPen()->format())
                    ->sortable(),
                TextColumn::make('price_usd_cents')
                    ->label('USD')
                    ->formatStateUsing(fn (Tour $record) => $record->priceInUsd()->format())
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->label('Destacado')
                    ->boolean(),
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
                    ->label('Publicado'),
                TernaryFilter::make('is_featured')
                    ->label('Destacado'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

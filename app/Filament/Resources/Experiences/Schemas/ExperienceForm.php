<?php

namespace App\Filament\Resources\Experiences\Schemas;

use App\Filament\Support\SecureImageUpload;
use App\Filament\Support\TranslatableTabs;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ExperienceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Imagen de portada')
                    ->schema([
                        SecureImageUpload::configure(
                            FileUpload::make('cover_image_path')->label('Imagen'),
                            'experiences'
                        ),
                    ]),
                TranslatableTabs::make(fn (string $locale) => [
                    TextInput::make("name.{$locale}")
                        ->label('Nombre')
                        ->required($locale === 'es')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $state, callable $set, callable $get) use ($locale) {
                            if (blank($get("slug.{$locale}"))) {
                                $set("slug.{$locale}", Str::slug($state));
                            }
                        })
                        ->maxLength(120),
                    TextInput::make("slug.{$locale}")
                        ->label('Slug')
                        ->required($locale === 'es')
                        ->maxLength(140)
                        ->rule('alpha_dash'),
                    Textarea::make("description.{$locale}")
                        ->label('Descripción')
                        ->rows(3),
                    TextInput::make("cover_image_alt.{$locale}")
                        ->label('Texto alternativo de la imagen'),
                    // Defecto 3 (auditoria CRO/SEO): sin esto, <title> y
                    // "meta description" de la ficha caian al nombre/
                    // descripcion tal cual (vacios cuando la clienta aun no
                    // los llena, caso real: Trekking). Mismo patron que
                    // meta_title/meta_description de Tour (ver TourForm).
                    TextInput::make("meta_title.{$locale}")
                        ->label('Meta título (SEO)')
                        ->helperText('Si se deja vacío, se usa el nombre de la experiencia.')
                        ->maxLength(160),
                    Textarea::make("meta_description.{$locale}")
                        ->label('Meta descripción (SEO)')
                        ->helperText('Si se deja vacío, se usa la descripción de la experiencia.')
                        ->rows(2)
                        ->maxLength(320),
                ]),
                Section::make('Galería')
                    ->schema([
                        Repeater::make('gallery')
                            ->relationship('gallery')
                            ->label('')
                            ->schema([
                                SecureImageUpload::configure(
                                    FileUpload::make('path')->label('Imagen')->required(),
                                    'experiences'
                                ),
                                ...collect(config('cms.active_locales'))
                                    ->map(fn (string $locale) => TextInput::make("alt.{$locale}")
                                        ->label("Texto alternativo ({$locale})"))
                                    ->all(),
                                TextInput::make('order')
                                    ->label('Orden')
                                    ->numeric()
                                    ->default(0),
                            ])
                            ->orderColumn('order')
                            ->addActionLabel('Agregar imagen')
                            ->defaultItems(0)
                            ->maxItems(20)
                            ->collapsible()
                            ->columns(1),
                    ]),
                Section::make('Publicación')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Publicado')
                            ->default(false),
                        TextInput::make('order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ]),
            ]);
    }
}

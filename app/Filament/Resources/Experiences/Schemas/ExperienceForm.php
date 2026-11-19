<?php

namespace App\Filament\Resources\Experiences\Schemas;

use App\Filament\Support\SecureImageUpload;
use App\Filament\Support\TranslatableTabs;
use App\Models\Experience;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
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
                        )->helperText('JPG, PNG o WebP, máximo 4 MB. Mejor en formato horizontal y de al menos 1600 píxeles de ancho.'),
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
                        ->helperText('Es la parte final de la dirección web (por ejemplo, /experiencias/nombre-del-elemento). Se completa sola a partir del nombre; solo letras minúsculas, números y guiones.')
                        ->rule('alpha_dash')
                        // O-1 (docs/lote-3/seguridad-2026-09-14.md, Bajo):
                        // mismo fix que DestinationForm -- el catálogo
                        // público resuelve por slug (ResolvesBySlugByLocale),
                        // así que dos experiencias con el mismo slug para
                        // el mismo idioma dejan a una inalcanzable en
                        // silencio.
                        ->rules([
                            fn (?Model $record) => function (string $attribute, $value, \Closure $fail) use ($locale, $record) {
                                if (filled($value) && Experience::slugTaken($locale, $value, $record?->getKey())) {
                                    $fail("Ya existe otra experiencia con este slug para el idioma \"{$locale}\".");
                                }
                            },
                        ]),
                    Textarea::make("description.{$locale}")
                        ->label('Descripción')
                        ->rows(3),
                    TextInput::make("cover_image_alt.{$locale}")
                        ->label('Texto alternativo de la imagen')
                        ->helperText('Describe la imagen en una frase corta. Ayuda a Google y a personas que usan lector de pantalla.'),
                    // Defecto 3 (auditoria CRO/SEO): sin esto, <title> y
                    // "meta description" de la ficha caian al nombre/
                    // descripcion tal cual (vacios cuando la clienta aun no
                    // los llena, caso real: Trekking). Mismo patron que
                    // meta_title/meta_description de Tour (ver TourForm).
                    TextInput::make("meta_title.{$locale}")
                        ->label('Título que aparece en Google')
                        ->helperText('Si se deja vacío, se usa el nombre de la experiencia.')
                        ->maxLength(160),
                    Textarea::make("meta_description.{$locale}")
                        ->label('Descripción que aparece en Google')
                        ->helperText('Si se deja vacío, se usa la descripción de la experiencia.')
                        ->rows(2)
                        ->maxLength(320),
                ]),
                Section::make('Galería')
                    ->description('Hasta 20 imágenes (JPG, PNG o WebP, máximo 4 MB cada una). Arrastra para cambiar el orden.')
                    ->schema([
                        Repeater::make('gallery')
                            ->relationship('gallery')
                            ->label('Galería')
                            ->hiddenLabel()
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
                            ->default(false)
                            ->helperText('Si lo desactivas, deja de verse en el sitio, pero no se borra: puedes volver a activarlo cuando quieras.'),
                        TextInput::make('order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Número más bajo = aparece primero. Para reordenar más fácil, arrastra las filas en la lista.'),
                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\TeamMembers\Schemas;

use App\Filament\Support\SecureImageUpload;
use App\Filament\Support\TranslatableTabs;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TeamMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Foto')
                    ->schema([
                        SecureImageUpload::configure(
                            FileUpload::make('photo_path')->label('Foto')->required(),
                            'team'
                        )->helperText('JPG, PNG o WebP, máximo 4 MB. Mejor una foto vertical o cuadrada, con el rostro bien visible.'),
                    ]),

                TranslatableTabs::make(fn (string $locale) => [
                    TextInput::make("name.{$locale}")
                        ->label('Nombre')
                        ->required($locale === 'es')
                        ->maxLength(120),
                    TextInput::make("role.{$locale}")
                        ->label('Rol')
                        ->placeholder('Ej: Guía oficial de turismo')
                        ->required($locale === 'es')
                        ->maxLength(120),
                    Textarea::make("description.{$locale}")
                        ->label('Descripción breve')
                        ->rows(3)
                        ->maxLength(500),
                    TextInput::make("photo_alt.{$locale}")
                        ->label('Texto alternativo de la foto')
                        ->helperText('Describe la imagen en una frase corta. Ayuda a Google y a personas que usan lector de pantalla.'),
                ]),

                Section::make('Redes sociales')
                    ->columns(3)
                    ->schema([
                        TextInput::make('instagram_url')
                            ->label('Instagram')
                            ->url()
                            ->helperText('Enlace completo, ej: https://www.instagram.com/usuario')
                            ->maxLength(255),
                        TextInput::make('facebook_url')
                            ->label('Facebook')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('whatsapp_url')
                            ->label('WhatsApp')
                            ->url()
                            ->maxLength(255)
                            ->helperText('Enlace completo, ej: https://wa.me/51999999999'),
                    ]),

                Section::make('Publicación')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Publicado')
                            ->default(false)
                            ->helperText('Si lo desactivas, esta persona deja de verse en la página Nosotros, pero no se borra.'),
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

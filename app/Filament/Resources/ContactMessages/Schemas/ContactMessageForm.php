<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use App\Enums\ContactMessageStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Everything except "status" is disabled: this is a lead inbox, not a
 * content-editing form. The client reads what the visitor sent and moves it
 * through the attention workflow (Nuevo → En proceso → Atendido).
 */
class ContactMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Mensaje recibido')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Nombre')->disabled(),
                        TextInput::make('email')->label('Correo')->disabled(),
                        TextInput::make('phone')->label('Teléfono / WhatsApp')->disabled(),
                        TextInput::make('subject')
                            ->label('Asunto')
                            ->disabled()
                            ->formatStateUsing(fn (?string $state): string => $state ? __('site.contacto.form.subject_options.'.$state) : ''),
                        Textarea::make('message')
                            ->label('Mensaje')
                            ->disabled()
                            ->columnSpanFull()
                            ->rows(4),
                        DateTimePicker::make('privacy_consent_at')
                            ->label('Consentimiento aceptado el')
                            ->disabled(),
                        TextInput::make('ip_address')->label('IP de origen')->disabled(),
                    ]),

                Section::make('Atención')
                    ->schema([
                        Select::make('status')
                            ->label('Estado')
                            ->options(ContactMessageStatus::options())
                            ->required(),
                    ]),
            ]);
    }
}

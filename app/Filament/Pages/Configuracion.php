<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;

/**
 * The ONLY place in the CMS where the PEN/USD exchange rate is edited. A
 * fixed value the client sets by hand — never fetched from an external API.
 * App\Support\Money::exchangeRate() reads exactly what's saved here.
 */
class Configuracion extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.configuracion';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Configuración';

    protected static ?string $title = 'Configuración';

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'exchange_rate_pen_usd' => Setting::get(Money::EXCHANGE_RATE_SETTING_KEY),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Moneda')
                    ->description('Tipo de cambio fijo, editable acá. NUNCA se consulta a una API externa. Todo formateo y conversión en el sitio pasa por App\\Support\\Money, que lee este valor.')
                    ->schema([
                        TextInput::make('exchange_rate_pen_usd')
                            ->label('Soles (PEN) por 1 dólar (USD)')
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->required()
                            ->helperText('Ejemplo: si 1 USD equivale a S/ 3.75, escribe 3.75.'),
                    ]),
                // Filament 4 has no `<x-filament-panels::form.actions>` Blade
                // component (that was v3). Actions are a first-class Schema
                // component instead — placing it inside this same schema
                // renders the submit button inside the <form wire:submit="save">
                // from the view, which is all `Action::submit()` needs: it
                // just outputs <button type="submit">.
                Actions::make($this->getFormActions())
                    ->alignment(Alignment::Start)
                    ->key('form-actions'),
            ])
            ->statePath('data');
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set(
            Money::EXCHANGE_RATE_SETTING_KEY,
            (float) $data['exchange_rate_pen_usd'],
            'float',
            'moneda',
        );

        Notification::make()
            ->title('Configuración guardada')
            ->success()
            ->send();
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
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
 * The ONLY place in the CMS where the client can write the Setting values
 * that resources/views/** reads. Before this fix (lote-1 audit, defect
 * bloqueante), this page exposed a single field (the exchange rate) while
 * home.blade.php, nosotros.blade.php, contact.blade.php and the footer read
 * a dozen more Setting keys that had no admin UI at all — the only way to
 * fill them was tinker. See docs/lote-1/02-fixes-backend-2026-09-02.md.
 *
 * Every key in TEXT_FIELDS/STAT_FIELDS below was found by grepping every
 * `Setting::get('...')` call under resources/views (footer, contact,
 * stats-strip, mincetur-badge). company_ruc, company_legal_name,
 * contact_schedule are the only ones added ahead of a front consumer:
 * company_ruc/company_legal_name are already read by the footer, and
 * contact_schedule is rendered by the new "Horario de atención" card added
 * to contact.blade.php in this same fix.
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

    /**
     * Plain string/URL settings. Value => Setting "group" column.
     *
     * @var array<string, string>
     */
    private const TEXT_FIELDS = [
        'contact_phone' => 'contacto',
        'contact_email' => 'contacto',
        'contact_address' => 'contacto',
        'contact_schedule' => 'contacto',
        'company_legal_name' => 'legal',
        'company_ruc' => 'legal',
        'rnavt_number' => 'legal',
        'complaints_book_url' => 'legal',
        'esnna_poster_url' => 'legal',
        'privacy_policy_url' => 'legal',
        'cancellation_policy_url' => 'legal',
        'social_instagram_url' => 'redes',
        'social_facebook_url' => 'redes',
        'social_youtube_url' => 'redes',
    ];

    /**
     * Numeric settings that must never default to (or persist as) a
     * publishable 0. Empty means NULL, always — see Setting::set().
     *
     * @var array<string, string>
     */
    private const STAT_FIELDS = [
        'stat_years_experience' => 'cifras',
        'stat_happy_travelers' => 'cifras',
        'stat_tours_completed' => 'cifras',
        'stat_destinations_count' => 'cifras',
    ];

    public function mount(): void
    {
        $data = [
            'exchange_rate_pen_usd' => Setting::get(Money::EXCHANGE_RATE_SETTING_KEY),
        ];

        foreach (array_keys(self::TEXT_FIELDS) as $key) {
            $data[$key] = Setting::get($key);
        }

        foreach (array_keys(self::STAT_FIELDS) as $key) {
            $data[$key] = Setting::get($key);
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contacto')
                    ->description('Se muestran en el pie de página y en la página de Contacto del sitio.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('contact_phone')
                            ->label('Teléfono / WhatsApp')
                            ->tel()
                            ->maxLength(30)
                            ->helperText('Con código de país, ej: +51 999 999 999. El mismo número arma el botón de WhatsApp del sitio.'),
                        TextInput::make('contact_email')
                            ->label('Correo de contacto')
                            ->email()
                            ->maxLength(255)
                            ->helperText('El correo que ve el visitante, no el que recibe los avisos de mensajes nuevos.'),
                        TextInput::make('contact_address')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->helperText('Se usa también para armar el botón "Cómo llegar" hacia Google Maps.'),
                        Textarea::make('contact_schedule')
                            ->label('Horario de atención')
                            ->rows(2)
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->helperText('Ej: Lunes a viernes, 9:00 a 18:00. Se muestra en la página de Contacto.'),
                    ]),

                Section::make('Identidad legal (Perú)')
                    ->description('Datos obligatorios para operar como agencia de viajes en Perú. Mientras un dato falte, el sitio oculta ese bloque en vez de mostrar algo inventado.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('company_legal_name')
                            ->label('Razón social')
                            ->maxLength(255),
                        TextInput::make('company_ruc')
                            ->label('RUC')
                            ->rule('regex:/^\d{11}$/')
                            ->maxLength(11)
                            ->helperText('11 dígitos, sin espacios ni guiones.'),
                        TextInput::make('rnavt_number')
                            ->label('Número de RNAVT (MINCETUR)')
                            ->maxLength(50)
                            ->helperText('Registro Nacional de Agencias de Viajes y Turismo. Obligatorio para operar en Perú.'),
                        TextInput::make('complaints_book_url')
                            ->label('Libro de reclamaciones (enlace)')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('esnna_poster_url')
                            ->label('Afiche ESNNA (enlace)')
                            ->url()
                            ->maxLength(255)
                            ->helperText('Explotación Sexual de Niñas, Niños y Adolescentes: afiche informativo obligatorio para agencias de turismo.'),
                        TextInput::make('privacy_policy_url')
                            ->label('Política de privacidad (enlace)')
                            ->url()
                            ->maxLength(255)
                            ->helperText('Se enlaza también desde la casilla de consentimiento del formulario de Contacto.'),
                        TextInput::make('cancellation_policy_url')
                            ->label('Política de cancelación (enlace)')
                            ->url()
                            ->maxLength(255),
                    ]),

                Section::make('Redes sociales')
                    ->columns(3)
                    ->schema([
                        TextInput::make('social_instagram_url')->label('Instagram')->url()->maxLength(255),
                        TextInput::make('social_facebook_url')->label('Facebook')->url()->maxLength(255),
                        TextInput::make('social_youtube_url')->label('YouTube')->url()->maxLength(255),
                    ]),

                Section::make('Cifras (Home y Nosotros)')
                    ->description('Deja un campo en blanco si todavía no tienes el dato real: nunca se publica un cero. Cada cifra aparece sola en el sitio en cuanto la cargas.')
                    ->columns(4)
                    ->schema([
                        TextInput::make('stat_years_experience')->label('Años de experiencia')->numeric()->minValue(0),
                        TextInput::make('stat_happy_travelers')->label('Viajeros felices')->numeric()->minValue(0),
                        TextInput::make('stat_tours_completed')->label('Tours realizados')->numeric()->minValue(0),
                        TextInput::make('stat_destinations_count')->label('Destinos')->numeric()->minValue(0),
                    ]),

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

        foreach (self::TEXT_FIELDS as $key => $group) {
            $value = $data[$key] ?? null;

            Setting::set($key, filled($value) ? $value : null, 'string', $group);
        }

        foreach (self::STAT_FIELDS as $key => $group) {
            $value = $data[$key] ?? null;

            Setting::set($key, filled($value) ? (int) $value : null, 'integer', $group);
        }

        Notification::make()
            ->title('Configuración guardada')
            ->success()
            ->send();
    }
}

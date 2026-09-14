<?php

namespace App\Filament\Resources\Tours\Schemas;

use App\Enums\TourDifficulty;
use App\Filament\Support\SecureImageUpload;
use App\Filament\Support\TranslatableTabs;
use App\Models\Destination;
use App\Models\Experience;
use App\Models\Tour;
use App\Support\Money;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TourForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('General')
                    ->columns(2)
                    ->schema([
                        Select::make('destination_id')
                            ->label('Destino')
                            ->relationship('destination', modifyQueryUsing: fn ($query) => $query->orderBy('order'))
                            ->getOptionLabelFromRecordUsing(fn (Destination $record) => $record->name)
                            ->searchable()
                            ->preload(),
                        Select::make('difficulty')
                            ->label('Dificultad')
                            ->options(TourDifficulty::class),
                        Toggle::make('is_featured')
                            ->label('Destacado')
                            ->default(false),
                        Toggle::make('is_published')
                            ->label('Publicado')
                            ->default(false)
                            ->helperText('Solo un tour publicado queda visible para el catálogo público.'),
                        TextInput::make('order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ]),

                TranslatableTabs::make(fn (string $locale) => [
                    TextInput::make("title.{$locale}")
                        ->label('Título')
                        ->required($locale === 'es')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $state, callable $set, callable $get) use ($locale) {
                            if (blank($get("slug.{$locale}"))) {
                                $set("slug.{$locale}", Str::slug($state));
                            }
                        })
                        ->maxLength(160),
                    TextInput::make("slug.{$locale}")
                        ->label('Slug')
                        ->required($locale === 'es')
                        ->maxLength(180)
                        ->rule('alpha_dash')
                        ->rules([
                            fn (?Model $record) => function (string $attribute, $value, \Closure $fail) use ($locale, $record) {
                                if (filled($value) && Tour::slugTaken($locale, $value, $record?->getKey())) {
                                    $fail("Ya existe otro tour con este slug para el idioma \"{$locale}\".");
                                }
                            },
                        ])
                        ->helperText('Cambiarlo aquí guarda el slug anterior en el historial para poder redirigir más adelante.'),
                    Textarea::make("summary.{$locale}")
                        ->label('Resumen')
                        ->rows(2)
                        ->maxLength(300),
                    // Defecto 1 (auditoria cliente, 2026-09-14): la toolbar
                    // completa por defecto de RichEditor incluye tablas,
                    // adjuntar archivos (imagenes embebidas) y alineacion --
                    // mucha mas superficie de la que
                    // App\Support\Html\RichTextSanitizer sanea. Se restringe
                    // aqui a exactamente lo que el sanitizador permite: si
                    // se agrega un boton, hay que agregar su etiqueta/
                    // atributo alla tambien, o el formato de la clienta se
                    // pierde en silencio en el sitio publico.
                    RichEditor::make("description.{$locale}")
                        ->label('Descripción')
                        ->toolbarButtons([
                            ['bold', 'italic', 'underline', 'strike', 'link'],
                            ['h2', 'h3'],
                            ['blockquote', 'bulletList', 'orderedList'],
                            ['undo', 'redo'],
                        ]),
                    TextInput::make("duration_label.{$locale}")
                        ->label('Duración')
                        ->placeholder('Ej: 4 días / 3 noches'),
                    TextInput::make("meeting_point.{$locale}")
                        ->label('Punto de encuentro'),
                    // No ->separator(): with one set, TagsInput dehydrates by
                    // *joining* the array into a delimited string (meant for
                    // plain string columns). inclusions/exclusions are
                    // translatable JSON ARRAY columns, so the native array
                    // state must be kept as-is.
                    TagsInput::make("inclusions.{$locale}")
                        ->label('Qué incluye'),
                    TagsInput::make("exclusions.{$locale}")
                        ->label('Qué no incluye'),
                    // Structured (title + description) unlike
                    // inclusions/exclusions, so it needs a Repeater instead
                    // of a TagsInput -- same translatable-JSON-array dot
                    // path mechanism ("itinerary.{$locale}"), Spatie's
                    // HasTranslations::attributesToArray() exposes it as
                    // itinerary => [locale => [items...]] for the form to
                    // fill from, and Model::fill() re-assembles it back on
                    // save (see App\Models\Tour).
                    Repeater::make("itinerary.{$locale}")
                        ->label('Itinerario')
                        ->schema([
                            TextInput::make('title')
                                ->label('Título del día')
                                ->required()
                                ->maxLength(160),
                            // Defecto 1 (auditoria cliente, 2026-09-14):
                            // mismo criterio y misma toolbar restringida que
                            // description.{locale} arriba -- contenido
                            // narrativo puro (un dia del itinerario), sin
                            // ningun rol de respaldo de meta description,
                            // asi que no hay razon para tratarlo distinto de
                            // la descripcion del tour.
                            RichEditor::make('description')
                                ->label('Descripción')
                                ->toolbarButtons([
                                    ['bold', 'italic', 'underline', 'strike', 'link'],
                                    ['h2', 'h3'],
                                    ['blockquote', 'bulletList', 'orderedList'],
                                    ['undo', 'redo'],
                                ])
                                ->required(),
                        ])
                        ->addActionLabel('Agregar día')
                        ->defaultItems(0)
                        ->collapsible()
                        ->columns(1),
                    TextInput::make("meta_title.{$locale}")
                        ->label('Título que aparece en Google')
                        ->maxLength(160),
                    Textarea::make("meta_description.{$locale}")
                        ->label('Descripción que aparece en Google')
                        ->rows(2)
                        ->maxLength(320),
                ]),

                Section::make('Precio')
                    ->columns(2)
                    ->schema([
                        TextInput::make('price_pen_cents')
                            ->label('Precio en soles (PEN)')
                            ->prefix('S/')
                            ->numeric()
                            ->required()
                            ->default(0)
                            // Column is unsignedInteger, max 4294967295 cents.
                            // Without these the form lets -150 or 99999999
                            // through and MySQL throws SQLSTATE[22003] → 500
                            // instead of a validation message (audit B-1).
                            ->minValue(0)
                            ->maxValue(42949672)
                            ->validationMessages([
                                'min' => 'El precio no puede ser negativo.',
                                'max' => 'El precio no puede superar S/ 42,949,672.',
                            ])
                            ->formatStateUsing(fn (?int $state) => $state === null ? null : Money::pen($state)->decimal())
                            ->dehydrateStateUsing(fn ($state) => Money::parseToCents($state)),
                        TextInput::make('price_usd_cents')
                            ->label('Precio en dólares (USD)')
                            ->prefix('US$')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(42949672)
                            ->validationMessages([
                                'min' => 'El precio no puede ser negativo.',
                                'max' => 'El precio no puede superar US$ 42,949,672.',
                            ])
                            ->formatStateUsing(fn (?int $state) => $state === null ? null : Money::usd($state)->decimal())
                            ->dehydrateStateUsing(fn ($state) => Money::parseToCents($state)),
                    ]),

                Section::make('Experiencias')
                    ->schema([
                        CheckboxList::make('experiences')
                            ->label('')
                            ->relationship('experiences')
                            ->getOptionLabelFromRecordUsing(fn (Experience $record) => $record->name)
                            ->columns(3),
                    ]),

                Section::make('Galería')
                    ->schema([
                        Repeater::make('images')
                            ->relationship('images')
                            ->label('')
                            ->schema([
                                // F-2 part 2 (docs/lote-3/seguridad-2026-09-14.md,
                                // Alto): this used to be its own inline copy
                                // of the same MIME whitelist + server-detected
                                // extension logic as App\Filament\Support\
                                // SecureImageUpload -- two implementations of
                                // one security rule that had already
                                // diverged the moment SecureImageUpload was
                                // introduced (this field predates it, see
                                // that class's docblock). Unified so there is
                                // exactly one place this rule can be changed
                                // — and exactly one place it can be forgotten.
                                SecureImageUpload::configure(
                                    FileUpload::make('path')->label('Imagen'),
                                    'tours'
                                )->required(),
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
            ]);
    }
}

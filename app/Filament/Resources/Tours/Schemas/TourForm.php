<?php

namespace App\Filament\Resources\Tours\Schemas;

use App\Enums\TourDifficulty;
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
                    RichEditor::make("description.{$locale}")
                        ->label('Descripción'),
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
                    TextInput::make("meta_title.{$locale}")
                        ->label('Meta título (SEO)')
                        ->maxLength(160),
                    Textarea::make("meta_description.{$locale}")
                        ->label('Meta descripción (SEO)')
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
                                FileUpload::make('path')
                                    ->label('Imagen')
                                    // ->image() only adds `mimetypes:image/*`,
                                    // which accepts image/svg+xml (inline
                                    // <script>). A closed whitelist of the
                                    // three formats the site actually serves
                                    // is what the security audit
                                    // (docs/lote-2/seguridad-2026-09-01.md,
                                    // M-1) asked for.
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(4096)
                                    ->disk('public')
                                    ->directory('tours')
                                    // The extension on disk must come from the
                                    // MIME type Laravel detects on the server
                                    // (finfo over the real file bytes), never
                                    // from the client-supplied original
                                    // extension: that's how a GIF polyglot
                                    // renamed evil.pht or evil.html got served
                                    // from the same origin as a script.
                                    ->getUploadedFileNameForStorageUsing(
                                        fn ($file) => Str::ulid().'.'.match ($file->getMimeType()) {
                                            'image/jpeg' => 'jpg',
                                            'image/png' => 'png',
                                            'image/webp' => 'webp',
                                            default => 'bin',
                                        }
                                    )
                                    ->required(),
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

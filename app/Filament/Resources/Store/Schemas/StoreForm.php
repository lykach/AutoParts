<?php

namespace App\Filament\Resources\Store\Schemas;

use App\Filament\Forms\Components\PhoneInput;
use App\Models\Store;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class StoreForm
{
    protected static function cleanAdditionalLocalization(array $state): array
    {
        $mainCurrencyId = $state['currency_id'] ?? null;
        $addCurrencies = data_get($state, 'settings.localization.additional_currency_ids', []);

        if (is_array($addCurrencies)) {
            $addCurrencies = array_values(array_filter(
                $addCurrencies,
                fn ($id) => (string) $id !== (string) $mainCurrencyId
            ));

            data_set($state, 'settings.localization.additional_currency_ids', $addCurrencies);
        }

        $mainLang = $state['default_language'] ?? null;
        $addLangs = data_get($state, 'settings.localization.additional_languages', []);

        if (is_array($addLangs)) {
            $addLangs = array_values(array_filter(
                $addLangs,
                fn ($code) => (string) $code !== (string) $mainLang
            ));

            data_set($state, 'settings.localization.additional_languages', $addLangs);
        }

        return $state;
    }

    protected static function inheritedNotice(string $overrideKey): Section
    {
        return Section::make('Успадкування')
            ->compact()
            ->description('Цей блок успадковується від головного магазину (перемкни Override у вкладці "Основне").')
            ->visible(function ($get) use ($overrideKey) {
                return (bool) $get('inherit_defaults')
                    && ! (bool) $get('is_main')
                    && ! (bool) $get("settings.overrides.$overrideKey");
            });
    }

    public static function configure(Schema $schema): Schema
    {
        $disabledIfInherited = fn ($get, string $overrideKey) =>
            ((bool) $get('inherit_defaults') && ! (bool) $get('is_main') && ! (bool) $get("settings.overrides.$overrideKey"));

        $defaultCurrencyId = fn () => (int) (\App\Models\Currency::query()->where('is_default', true)->value('id') ?? 1);
        $defaultLanguageCode = fn () => (string) (\App\Models\Language::query()->where('is_default', true)->value('code') ?? 'uk');

        $lockedForMain = fn ($get) => (bool) $get('is_main');

        $hasAnotherMainStore = fn (?Store $record): bool =>
            Store::query()
                ->where('is_main', true)
                ->when($record?->id, fn ($q) => $q->where('id', '!=', $record->id))
                ->exists();

        $hasAnyMainStore = fn (?Store $record): bool =>
            Store::query()
                ->where('is_main', true)
                ->when($record?->id, fn ($q) => $q->where('id', '!=', $record->id))
                ->exists()
            || (bool) $record?->is_main;

        return $schema
            ->columns(1)
            ->components([
                Tabs::make('StoreTabs')
                    ->contained(false)
                    ->tabs([

                        Tab::make('Основне')->schema([
                            Grid::make(['default' => 1, 'lg' => 3])->schema([
                                Grid::make(1)
                                    ->columnSpan(['default' => 1, 'lg' => 2])
                                    ->schema([

                                        Section::make('Ідентифікація')
                                            ->columns(['default' => 1, 'md' => 2])
                                            ->schema([
                                                Toggle::make('is_active')
                                                    ->label('Активний')
                                                    ->default(true),

                                                Toggle::make('is_main')
                                                    ->label('Головний магазин')
                                                    ->default(function (?Store $record) use ($hasAnyMainStore) {
                                                        return ! $hasAnyMainStore($record);
                                                    })
                                                    ->live()
                                                    ->disabled(function ($get, ?Store $record) use ($hasAnyMainStore, $hasAnotherMainStore) {
                                                        if ((bool) $record?->is_main) {
                                                            return true;
                                                        }

                                                        if (! $hasAnyMainStore($record)) {
                                                            return true;
                                                        }

                                                        return $hasAnotherMainStore($record) && ! (bool) $get('is_main');
                                                    })
                                                    ->helperText(function ($get, ?Store $record) use ($hasAnyMainStore, $hasAnotherMainStore) {
                                                        if ((bool) $record?->is_main) {
                                                            return 'Це головний магазин. У системі має бути один головний магазин.';
                                                        }

                                                        if (! $hasAnyMainStore($record)) {
                                                            return 'Поки що головного магазину немає, тому перший магазин обов’язково має бути головним.';
                                                        }

                                                        if ($hasAnotherMainStore($record) && ! (bool) $get('is_main')) {
                                                            return 'Головний магазин вже існує. Може бути тільки один.';
                                                        }

                                                        return null;
                                                    })
                                                    ->afterStateHydrated(function ($state, $set, ?Store $record) use ($hasAnyMainStore, $defaultCurrencyId, $defaultLanguageCode) {
                                                        if ((bool) $record?->is_main || ! $hasAnyMainStore($record)) {
                                                            $set('is_main', true);
                                                            $set('type', 'main');
                                                            $set('parent_id', null);
                                                            $set('inherit_defaults', false);
                                                            $set('currency_id', $defaultCurrencyId());
                                                            $set('default_language', $defaultLanguageCode());
                                                            $set('timezone', 'Europe/Kyiv');
                                                        } elseif (! (bool) $record?->is_main && blank($state)) {
                                                            $set('is_main', false);
                                                            $set('type', 'branch');
                                                        }
                                                    })
                                                    ->afterStateUpdated(function (?bool $state, $set) use ($defaultCurrencyId, $defaultLanguageCode) {
                                                        if ($state) {
                                                            $set('parent_id', null);
                                                            $set('type', 'main');
                                                            $set('inherit_defaults', false);

                                                            $set('currency_id', $defaultCurrencyId());
                                                            $set('default_language', $defaultLanguageCode());
                                                            $set('timezone', 'Europe/Kyiv');

                                                            $set('settings.overrides', []);
                                                        } else {
                                                            $set('inherit_defaults', true);
                                                            $set('type', 'branch');
                                                        }
                                                    }),

                                                Toggle::make('inherit_defaults')
                                                    ->label('Успадковувати налаштування від головного')
                                                    ->default(true)
                                                    ->live()
                                                    ->disabled(function ($get, ?Store $record) use ($hasAnyMainStore) {
                                                        return (bool) $get('is_main') || ! $hasAnyMainStore($record);
                                                    })
                                                    ->helperText(function (?Store $record) use ($hasAnyMainStore) {
                                                        return ! $hasAnyMainStore($record)
                                                            ? 'Поки немає головного магазину, успадковувати нічого — спочатку потрібно створити головний.'
                                                            : null;
                                                    }),

                                                Select::make('type')
                                                    ->label('Тип')
                                                    ->options(function (?Store $record) use ($hasAnyMainStore) {
                                                        if ((bool) $record?->is_main || ! $hasAnyMainStore($record)) {
                                                            return [
                                                                'main' => 'Головний',
                                                            ];
                                                        }

                                                        return [
                                                            'branch' => 'Філія',
                                                        ];
                                                    })
                                                    ->default(function (?Store $record) use ($hasAnyMainStore) {
                                                        return ((bool) $record?->is_main || ! $hasAnyMainStore($record))
                                                            ? 'main'
                                                            : 'branch';
                                                    })
                                                    ->required()
                                                    ->disabled(true)
                                                    ->dehydrated()
                                                    ->validationMessages([
                                                        'required' => 'Оберіть тип магазину.',
                                                    ])
                                                    ->helperText(function (?Store $record) use ($hasAnyMainStore) {
                                                        if ((bool) $record?->is_main || ! $hasAnyMainStore($record)) {
                                                            return 'Перший або єдиний головний магазин має тип "Головний".';
                                                        }

                                                        return 'Оскільки головний магазин уже існує, новий магазин може бути тільки філією.';
                                                    }),

                                                Select::make('parent_id')
                                                    ->label('Батьківський магазин')
                                                    ->options(function (?Store $record) {
                                                        return Store::query()
                                                            ->where('is_main', true)
                                                            ->when($record?->id, fn ($q) => $q->where('id', '!=', $record->id))
                                                            ->orderBy('sort_order')
                                                            ->pluck('name_uk', 'id')
                                                            ->all();
                                                    })
                                                    ->searchable()
                                                    ->preload()
                                                    ->disabled(function ($get, ?Store $record) use ($hasAnyMainStore) {
                                                        return (bool) $get('is_main') || ! $hasAnyMainStore($record);
                                                    })
                                                    ->helperText(function ($get, ?Store $record) use ($hasAnyMainStore) {
                                                        if (! $hasAnyMainStore($record)) {
                                                            return 'Поки немає головного магазину — філію створити не можна.';
                                                        }

                                                        if ((bool) $get('is_main')) {
                                                            return 'Для головного магазину батьківський магазин не використовується.';
                                                        }

                                                        return 'Для філії вибирається головний магазин.';
                                                    }),

                                                TextInput::make('code')
                                                    ->label('Внутрішній код')
                                                    ->maxLength(50),

                                                TextInput::make('sort_order')
                                                    ->label('Сортування')
                                                    ->numeric()
                                                    ->placeholder('авто')
                                                    ->helperText('Якщо не вказати — поставиться автоматично (max+10).')
                                                    ->default(fn () => ((int) (Store::query()->max('sort_order') ?? 0)) > 0
                                                        ? ((int) Store::query()->max('sort_order') + 10)
                                                        : 100
                                                    ),
                                            ]),

                                        Section::make('Спадкування по секціях (Overrides)')
                                            ->description('Для філій: якщо спадкування увімкнено — обирай які блоки будуть СВОЇ, а решта успадкується.')
                                            ->columns(['default' => 1, 'md' => 3])
                                            ->visible(fn ($get) => ! (bool) $get('is_main'))
                                            ->dehydrated(fn ($get) => ! (bool) $get('is_main'))
                                            ->schema([
                                                Toggle::make('settings.overrides.working_hours')->label('Свій графік')->default(false),
                                                Toggle::make('settings.overrides.contacts')->label('Свої контакти')->default(false),
                                                Toggle::make('settings.overrides.seo')->label('Своє SEO')->default(false),
                                                Toggle::make('settings.overrides.legal')->label('Свої юридичні')->default(false),
                                                Toggle::make('settings.overrides.delivery')->label('Своя доставка/оплата')->default(false),
                                                Toggle::make('settings.overrides.stock_sources')->label('Свої склади')->default(false),
                                            ]),

                                        Section::make('Назва')
                                            ->schema([
                                                TextInput::make('name_uk')
                                                    ->label('Назва магазину')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->validationMessages([
                                                        'required' => 'Вкажи назву магазину.',
                                                    ])
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (?string $state, $set, ?Store $record) {
                                                        if (! filled($state)) {
                                                            return;
                                                        }

                                                        if (filled($record?->slug)) {
                                                            return;
                                                        }

                                                        $set('slug', Str::slug($state));
                                                    }),

                                                TextInput::make('slug')
                                                    ->label('Slug')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->unique(ignoreRecord: true)
                                                    ->validationMessages([
                                                        'required' => 'Вкажи slug.',
                                                        'unique' => 'Такий slug уже існує.',
                                                    ]),
                                            ]),

                                        Section::make('Країна та адреса')
                                            ->columns(['default' => 1, 'md' => 2])
                                            ->schema([
                                                Select::make('country_id')
                                                    ->label('Країна')
                                                    ->searchable()
                                                    ->preload()
                                                    ->options(fn () => \App\Models\Country::query()
                                                        ->where('is_active', true)
                                                        ->orderBy('sort_order')
                                                        ->pluck('name_uk', 'id')
                                                        ->all()
                                                    )
                                                    ->columnSpanFull(),

                                                TextInput::make('region')
                                                    ->label('Область / Регіон')
                                                    ->maxLength(120),

                                                TextInput::make('city')
                                                    ->label('Місто')
                                                    ->maxLength(120),

                                                TextInput::make('postal_code')
                                                    ->label('Індекс')
                                                    ->maxLength(30),

                                                TextInput::make('address_line1')
                                                    ->label('Вулиця, будинок')
                                                    ->maxLength(255)
                                                    ->columnSpanFull(),

                                                TextInput::make('address_line2')
                                                    ->label('Квартира/офіс/поверх (опційно)')
                                                    ->maxLength(255)
                                                    ->columnSpanFull(),

                                                Textarea::make('address_note')
                                                    ->label('Примітка до адреси')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ]),

                                        Section::make('Локалізація')->schema([
                                            Select::make('currency_id')
                                                ->label('Валюта (основна)')
                                                ->searchable()
                                                ->preload()
                                                ->options(fn () => \App\Models\Currency::query()
                                                    ->where('is_active', true)
                                                    ->orderByDesc('is_default')
                                                    ->orderBy('code')
                                                    ->pluck('code', 'id')
                                                    ->all()
                                                )
                                                ->disabled($lockedForMain)
                                                ->helperText(fn ($get) => (bool) $get('is_main')
                                                    ? 'Для Головного магазину змінюється у модулі валют (заблоковано тут).'
                                                    : null
                                                )
                                                ->columnSpanFull(),

                                            Select::make('default_language')
                                                ->label('Мова за замовчуванням')
                                                ->searchable()
                                                ->preload()
                                                ->options(fn () => \App\Models\Language::query()
                                                    ->where('is_active', true)
                                                    ->orderByDesc('is_default')
                                                    ->orderBy('code')
                                                    ->pluck('name_uk', 'code')
                                                    ->all()
                                                )
                                                ->disabled($lockedForMain)
                                                ->helperText(fn ($get) => (bool) $get('is_main')
                                                    ? 'Для Головного магазину змінюється у модулі мов (заблоковано тут).'
                                                    : null
                                                )
                                                ->columnSpanFull(),

                                            TextInput::make('timezone')
                                                ->label('Часовий пояс')
                                                ->maxLength(64)
                                                ->placeholder('Europe/Kyiv')
                                                ->columnSpanFull(),

                                            Select::make('settings.localization.additional_currency_ids')
                                                ->label('Додаткові валюти (опційно)')
                                                ->multiple()
                                                ->searchable()
                                                ->preload()
                                                ->options(function ($get) {
                                                    $mainId = $get('currency_id');

                                                    return \App\Models\Currency::query()
                                                        ->where('is_active', true)
                                                        ->when($mainId, fn ($q) => $q->where('id', '!=', $mainId))
                                                        ->orderByDesc('is_default')
                                                        ->orderBy('code')
                                                        ->pluck('code', 'id')
                                                        ->all();
                                                })
                                                ->mutateDehydratedStateUsing(function ($state, $get) {
                                                    if (! is_array($state)) {
                                                        return [];
                                                    }

                                                    $main = $get('currency_id');

                                                    return array_values(array_filter($state, fn ($id) => (string) $id !== (string) $main));
                                                })
                                                ->columnSpanFull(),

                                            Select::make('settings.localization.additional_languages')
                                                ->label('Додаткові мови (опційно)')
                                                ->multiple()
                                                ->searchable()
                                                ->preload()
                                                ->options(function ($get) {
                                                    $main = $get('default_language');

                                                    return \App\Models\Language::query()
                                                        ->where('is_active', true)
                                                        ->when($main, fn ($q) => $q->where('code', '!=', $main))
                                                        ->orderByDesc('is_default')
                                                        ->orderBy('code')
                                                        ->pluck('name_uk', 'code')
                                                        ->all();
                                                })
                                                ->mutateDehydratedStateUsing(function ($state, $get) {
                                                    if (! is_array($state)) {
                                                        return [];
                                                    }

                                                    $main = $get('default_language');

                                                    return array_values(array_filter($state, fn ($code) => (string) $code !== (string) $main));
                                                })
                                                ->columnSpanFull(),
                                        ]),
                                    ]),

                                Section::make('Медіа')
                                    ->columnSpan(['default' => 1, 'lg' => 1])
                                    ->schema([
                                        FileUpload::make('logo')
                                            ->label('Логотип')
                                            ->disk('public')
                                            ->directory('stores')
                                            ->visibility('public')
                                            ->image()
                                            ->imageEditor()
                                            ->maxSize(4096),

                                        FileUpload::make('cover_image')
                                            ->label('Обкладинка / банер')
                                            ->disk('public')
                                            ->directory('stores')
                                            ->visibility('public')
                                            ->image()
                                            ->imageEditor()
                                            ->maxSize(8192),
                                    ]),
                            ]),
                        ]),

                        Tab::make('SEO')->schema([
                            static::inheritedNotice('seo'),

                            Section::make('SEO: Базові мета-теги')
                                ->columns(['default' => 1, 'md' => 3])
                                ->schema([
                                    TextInput::make('footer_title_uk')->label('Назва в підвалі / бренд (uk)')->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                    TextInput::make('footer_title_en')->label('Назва в підвалі / бренд (en)')->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                    TextInput::make('footer_title_ru')->label('Назва в підвалі / бренд (ru)')->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),

                                    TextInput::make('h1_uk')->label('H1 (uk)')->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                    TextInput::make('h1_en')->label('H1 (en)')->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                    TextInput::make('h1_ru')->label('H1 (ru)')->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),

                                    TextInput::make('meta_title_uk')->label('Meta title (uk)')->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                    TextInput::make('meta_title_en')->label('Meta title (en)')->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                    TextInput::make('meta_title_ru')->label('Meta title (ru)')->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),

                                    Textarea::make('meta_description_uk')->label('Meta description (uk)')->rows(3)->maxLength(320)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                    Textarea::make('meta_description_en')->label('Meta description (en)')->rows(3)->maxLength(320)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                    Textarea::make('meta_description_ru')->label('Meta description (ru)')->rows(3)->maxLength(320)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),

                                    TextInput::make('meta_keywords_uk')->label('Meta keywords (uk)')->maxLength(500)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                    TextInput::make('meta_keywords_en')->label('Meta keywords (en)')->maxLength(500)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                    TextInput::make('meta_keywords_ru')->label('Meta keywords (ru)')->maxLength(500)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                ]),

                            Section::make('SEO: Соцмережі (OpenGraph)')
                                ->columns(['default' => 1, 'md' => 3])
                                ->schema([
                                    TextInput::make('og_title_uk')->label('OG title (uk)')->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                    TextInput::make('og_title_en')->label('OG title (en)')->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                    TextInput::make('og_title_ru')->label('OG title (ru)')->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),

                                    Textarea::make('og_description_uk')->label('OG description (uk)')->rows(3)->maxLength(320)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                    Textarea::make('og_description_en')->label('OG description (en)')->rows(3)->maxLength(320)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                    Textarea::make('og_description_ru')->label('OG description (ru)')->rows(3)->maxLength(320)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),

                                    FileUpload::make('og_image')
                                        ->label('OG image (1200x630 рекомендовано)')
                                        ->disk('public')
                                        ->directory('stores/og')
                                        ->visibility('public')
                                        ->image()
                                        ->imageEditor()
                                        ->maxSize(8192)
                                        ->disabled(fn ($get) => $disabledIfInherited($get, 'seo'))
                                        ->columnSpanFull(),
                                ]),

                            Section::make('SEO: Технічні налаштування')
                                ->columns(['default' => 1, 'md' => 2])
                                ->schema([
                                    TextInput::make('canonical_url')->label('Canonical URL')->url()->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                    Select::make('robots')->label('Robots')->options([
                                        null => 'За замовчуванням',
                                        'index,follow' => 'index,follow',
                                        'noindex,follow' => 'noindex,follow',
                                        'index,nofollow' => 'index,nofollow',
                                        'noindex,nofollow' => 'noindex,nofollow',
                                    ])->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                ]),

                            Section::make('SEO: Schema / JSON (advanced)')
                                ->schema([
                                    KeyValue::make('seo')->label('seo (JSON)')->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                ]),
                        ]),

                        Tab::make('Контакти')->schema([
                            static::inheritedNotice('contacts'),

                            Grid::make(['default' => 1, 'lg' => 2])->schema([
                                Section::make('Основні контакти')->schema([
                                    TextInput::make('email')->label('Email')->email()->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'contacts')),
                                    TextInput::make('website_url')->label('Сайт')->url()->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'contacts')),

                                    Repeater::make('additional_emails')
                                        ->label('Додаткові email')
                                        ->disabled(fn ($get) => $disabledIfInherited($get, 'contacts'))
                                        ->defaultItems(0)
                                        ->schema([
                                            Grid::make(['default' => 1, 'md' => 2])->schema([
                                                TextInput::make('label')->label('Мітка')->maxLength(50),
                                                TextInput::make('email')->label('Email')->email()->required()->maxLength(255),
                                            ]),
                                        ])
                                        ->reorderable()
                                        ->collapsible(),
                                ]),

                                Section::make('Телефони')->schema([
                                    Repeater::make('phones')
                                        ->label('Телефони')
                                        ->disabled(fn ($get) => $disabledIfInherited($get, 'contacts'))
                                        ->defaultItems(0)
                                        ->schema([
                                            Grid::make(['default' => 1, 'md' => 13])->schema([
                                                TextInput::make('label')->label('Мітка')->maxLength(50)->columnSpan(3),
                                                PhoneInput::make('number')->label('Номер')->required()->columnSpan(7),

                                                Toggle::make('is_primary')
                                                    ->label('Основний')
                                                    ->inline(false)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, $set, $get) {
                                                        if (! $state) {
                                                            return;
                                                        }

                                                        $current = (string) ($get('number') ?? '');
                                                        $phones = $get('../../phones');

                                                        if (! is_array($phones)) {
                                                            return;
                                                        }

                                                        foreach ($phones as $k => $p) {
                                                            $num = (string) (($p['number'] ?? ''));

                                                            if ($num !== '' && $num !== $current) {
                                                                $phones[$k]['is_primary'] = false;
                                                            }
                                                        }

                                                        $set('../../phones', $phones);
                                                    })
                                                    ->columnSpan(2),
                                            ]),
                                        ])
                                        ->reorderable()
                                        ->collapsible(),
                                ]),
                            ]),
                        ]),

                        Tab::make('Графік')->schema([
                            static::inheritedNotice('working_hours'),

                            Section::make('Регулярний графік роботи')
                                ->description('Базовий щотижневий графік магазину.')
                                ->schema([
                                    Repeater::make('working_hours.days')
                                        ->label('Дні тижня')
                                        ->disabled(fn ($get) => $disabledIfInherited($get, 'working_hours'))
                                        ->default(function () {
                                            return [
                                                ['day' => 'mon', 'is_closed' => false, 'intervals' => [['from' => '09:00', 'to' => '18:00']], 'note' => null],
                                                ['day' => 'tue', 'is_closed' => false, 'intervals' => [['from' => '09:00', 'to' => '18:00']], 'note' => null],
                                                ['day' => 'wed', 'is_closed' => false, 'intervals' => [['from' => '09:00', 'to' => '18:00']], 'note' => null],
                                                ['day' => 'thu', 'is_closed' => false, 'intervals' => [['from' => '09:00', 'to' => '18:00']], 'note' => null],
                                                ['day' => 'fri', 'is_closed' => false, 'intervals' => [['from' => '09:00', 'to' => '18:00']], 'note' => null],
                                                ['day' => 'sat', 'is_closed' => true, 'intervals' => [], 'note' => null],
                                                ['day' => 'sun', 'is_closed' => true, 'intervals' => [], 'note' => null],
                                            ];
                                        })
                                        ->schema([
                                            Grid::make(['default' => 1, 'md' => 4])->schema([
                                                Select::make('day')->label('День')->options([
                                                    'mon' => 'Пн',
                                                    'tue' => 'Вт',
                                                    'wed' => 'Ср',
                                                    'thu' => 'Чт',
                                                    'fri' => 'Пт',
                                                    'sat' => 'Сб',
                                                    'sun' => 'Нд',
                                                ])->required(),

                                                Toggle::make('is_closed')
                                                    ->label('Вихідний')
                                                    ->live()
                                                    ->afterStateUpdated(fn ($s, $set) => $s ? $set('intervals', []) : null),

                                                TextInput::make('note')
                                                    ->label('Примітка')
                                                    ->maxLength(255)
                                                    ->columnSpan(['default' => 1, 'md' => 2]),
                                            ]),

                                            Repeater::make('intervals')
                                                ->label('Інтервали')
                                                ->visible(fn ($get) => ! (bool) $get('is_closed'))
                                                ->defaultItems(1)
                                                ->schema([
                                                    Grid::make(['default' => 1, 'md' => 2])->schema([
                                                        TextInput::make('from')
                                                            ->label('З')
                                                            ->mask('99:99')
                                                            ->required()
                                                            ->maxLength(5),

                                                        TextInput::make('to')
                                                            ->label('До')
                                                            ->mask('99:99')
                                                            ->required()
                                                            ->maxLength(5),
                                                    ]),
                                                ])
                                                ->reorderable()
                                                ->collapsible(),
                                        ])
                                        ->reorderable(false)
                                        ->collapsible(),
                                ]),

                            Section::make('Святкові дні та винятки')
                                ->description('Разові виключення з регулярного графіка: свята, скорочені дні, переноси, спеціальні години роботи. Для постійних свят можна увімкнути "Повторювати щороку" — тоді система братиме той самий день і місяць щороку автоматично.')
                                ->schema([
                                    Repeater::make('working_exceptions')
                                        ->label('Винятки')
                                        ->disabled(fn ($get) => $disabledIfInherited($get, 'working_hours'))
                                        ->defaultItems(0)
                                        ->collapsed()
                                        ->itemLabel(function (array $state): ?string {
                                            $title = trim((string) ($state['title'] ?? ''));
                                            $date = $state['date'] ?? null;
                                            $type = $state['type'] ?? null;
                                            $repeat = (bool) ($state['repeat_annually'] ?? false);

                                            $typeLabel = match ($type) {
                                                'holiday' => 'Свято',
                                                'special' => 'Спецграфік',
                                                'closed' => 'Зачинено',
                                                default => 'Виняток',
                                            };

                                            $dateLabel = null;

                                            if ($date) {
                                                try {
                                                    $carbon = Carbon::parse($date);
                                                    $dateLabel = $repeat
                                                        ? 'щороку ' . $carbon->format('d.m')
                                                        : $carbon->format('d.m.Y');
                                                } catch (\Throwable $e) {
                                                    $dateLabel = $date;
                                                }
                                            }

                                            if ($title !== '' && $dateLabel) {
                                                return "{$typeLabel}: {$title} ({$dateLabel})";
                                            }

                                            if ($title !== '') {
                                                return "{$typeLabel}: {$title}";
                                            }

                                            if ($dateLabel) {
                                                return "{$typeLabel}: {$dateLabel}";
                                            }

                                            return $repeat ? "{$typeLabel}: щорічний" : $typeLabel;
                                        })
                                        ->schema([
                                            Grid::make(['default' => 1, 'md' => 4])->schema([
                                                Select::make('type')
                                                    ->label('Тип')
                                                    ->options([
                                                        'holiday' => 'Святковий день',
                                                        'special' => 'Спеціальний графік',
                                                        'closed' => 'Повністю зачинено',
                                                    ])
                                                    ->default('holiday')
                                                    ->required(),

                                                DatePicker::make('date')
                                                    ->label('Дата')
                                                    ->native(false)
                                                    ->displayFormat('d.m.Y')
                                                    ->required()
                                                    ->helperText(fn ($get) => (bool) $get('repeat_annually')
                                                        ? 'Рік зберігається як базовий, але в роботі буде використовуватись лише день і місяць.'
                                                        : 'Разова дата для цього винятку.'
                                                    ),

                                                Toggle::make('repeat_annually')
                                                    ->label('Повторювати щороку')
                                                    ->default(false)
                                                    ->live()
                                                    ->helperText('Наприклад: 01.01, 08.03, 25.12'),

                                                Toggle::make('is_closed')
                                                    ->label('Не працює цього дня')
                                                    ->default(true)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, $set, $get) {
                                                        if ($state) {
                                                            $set('intervals', []);
                                                            return;
                                                        }

                                                        $intervals = $get('intervals');
                                                        if (! is_array($intervals) || empty($intervals)) {
                                                            $set('intervals', [['from' => '09:00', 'to' => '18:00']]);
                                                        }
                                                    }),
                                            ]),

                                            Grid::make(['default' => 1, 'md' => 2])->schema([
                                                TextInput::make('title')
                                                    ->label('Назва / причина')
                                                    ->maxLength(255)
                                                    ->placeholder('Напр. Різдво, Великдень, інвентаризація')
                                                    ->columnSpanFull(),

                                                TextInput::make('note')
                                                    ->label('Примітка для менеджера / сайту')
                                                    ->maxLength(255)
                                                    ->placeholder('Напр. Самовивіз недоступний')
                                                    ->columnSpanFull(),
                                            ]),

                                            Repeater::make('intervals')
                                                ->label('Інтервали роботи у цей день')
                                                ->visible(fn ($get) => ! (bool) $get('is_closed'))
                                                ->defaultItems(1)
                                                ->schema([
                                                    Grid::make(['default' => 1, 'md' => 2])->schema([
                                                        TextInput::make('from')
                                                            ->label('З')
                                                            ->mask('99:99')
                                                            ->required()
                                                            ->maxLength(5),

                                                        TextInput::make('to')
                                                            ->label('До')
                                                            ->mask('99:99')
                                                            ->required()
                                                            ->maxLength(5),
                                                    ]),
                                                ])
                                                ->reorderable()
                                                ->collapsible(),
                                        ])
                                        ->addActionLabel('Додати виняток / свято')
                                        ->reorderable()
                                        ->collapsible(),
                                ]),
                        ]),

                        Tab::make('Юридичні')->schema([
                            static::inheritedNotice('legal'),

                            Grid::make(['default' => 1, 'lg' => 2])->schema([
                                Section::make('Реквізити')->schema([
                                    TextInput::make('company_name')->label('Назва компанії')->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'legal')),
                                    TextInput::make('edrpou')->label('ЄДРПОУ')->maxLength(20)->disabled(fn ($get) => $disabledIfInherited($get, 'legal')),
                                    TextInput::make('vat')->label('VAT/ІПН')->maxLength(30)->disabled(fn ($get) => $disabledIfInherited($get, 'legal')),
                                    Textarea::make('legal_address')->label('Юридична адреса')->rows(3)->disabled(fn ($get) => $disabledIfInherited($get, 'legal')),
                                ]),

                                Section::make('Додаткові налаштування (extra)')
                                    ->description('Службові/дрібні параметри. Не чіпає overrides.')
                                    ->schema([
                                        KeyValue::make('settings.extra')->label('settings.extra'),
                                    ]),
                            ]),
                        ]),
                    ])
                    ->mutateDehydratedStateUsing(fn (array $state) => static::cleanAdditionalLocalization($state)),
            ]);
    }
}
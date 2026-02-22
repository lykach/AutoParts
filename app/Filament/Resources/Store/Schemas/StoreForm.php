<?php

namespace App\Filament\Resources\Store\Schemas;

use App\Filament\Forms\Components\PhoneInput;
use App\Models\Store;
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
    /**
     * Нормалізує priority для pivot-рядків у вигляді 10/20/30...
     * Важливо: ключі в Repeater можуть бути UUID (рядки), тому ніколи не використовуємо $i + 1.
     */
    protected static function normalizeStockSourcePriorities($state): array
    {
        if (! is_array($state)) {
            return [];
        }

        $pos = 1;
        foreach ($state as $key => $row) {
            if (! is_array($row)) {
                continue;
            }

            $state[$key]['priority'] = $pos * 10;
            $pos++;
        }

        return $state;
    }

    public static function configure(Schema $schema): Schema
    {
        $disabledIfInherited = fn ($get, string $overrideKey) =>
            ((bool) $get('inherit_defaults') && ! (bool) $get('is_main') && ! (bool) $get("settings.overrides.$overrideKey"));

        return $schema
            ->columns(1)
            ->components([
                Tabs::make('StoreTabs')
                    ->contained(false)
                    ->tabs([
                        // =====================================================
                        // ОСНОВНЕ
                        // =====================================================
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
                                                    ->live()
                                                    ->afterStateUpdated(function (?bool $state, $set) {
                                                        if ($state) {
                                                            $set('parent_id', null);
                                                            $set('type', 'main');
                                                            $set('inherit_defaults', false);
                                                        } else {
                                                            $set('inherit_defaults', true);
                                                        }
                                                    }),

                                                Toggle::make('inherit_defaults')
                                                    ->label('Успадковувати налаштування від головного')
                                                    ->helperText('Якщо увімкнено — філія бере значення з головного, коли поле тут порожнє (NULL).')
                                                    ->default(true)
                                                    ->live()
                                                    ->disabled(fn ($get) => (bool) $get('is_main')),

                                                Select::make('type')
                                                    ->label('Тип')
                                                    ->options([
                                                        'main' => 'Головний',
                                                        'branch' => 'Філія',
                                                        'warehouse' => 'Склад',
                                                        'pickup' => 'Пункт видачі',
                                                        'office' => 'Офіс',
                                                        'online' => 'Онлайн',
                                                    ])
                                                    ->default('branch')
                                                    ->required(),

                                                Select::make('parent_id')
                                                    ->label('Батьківський магазин')
                                                    ->options(fn () => Store::query()->orderBy('sort_order')->pluck('name_uk', 'id')->all())
                                                    ->searchable()
                                                    ->preload()
                                                    ->disabled(fn ($get) => (bool) $get('is_main')),

                                                TextInput::make('code')
                                                    ->label('Внутрішній код')
                                                    ->maxLength(50),

                                                TextInput::make('sort_order')
                                                    ->label('Сортування')
                                                    ->numeric()
                                                    ->default(100),
                                            ]),

                                        Section::make('Спадкування по секціях (Overrides)')
                                            ->description('Якщо спадкування увімкнено — обирай які блоки будуть СВОЇ, а решта успадкується.')
                                            ->columns(['default' => 1, 'md' => 3])
                                            ->schema([
                                                Toggle::make('settings.overrides.working_hours')->label('Свій графік')->default(false),
                                                Toggle::make('settings.overrides.delivery')->label('Своя доставка/оплата')->default(false),
                                                Toggle::make('settings.overrides.contacts')->label('Свої контакти')->default(false),
                                                Toggle::make('settings.overrides.seo')->label('Своє SEO')->default(false),
                                                Toggle::make('settings.overrides.legal')->label('Свої юридичні')->default(false),
                                                Toggle::make('settings.overrides.stock_sources')->label('Свої склади')->default(false),
                                            ]),

                                        Section::make('Назви')
                                            ->columns(['default' => 1, 'md' => 3])
                                            ->schema([
                                                TextInput::make('name_uk')
                                                    ->label('Назва (uk)')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (?string $state, $set, ?Store $record) {
                                                        if (! filled($state)) return;
                                                        if (filled($record?->slug)) return;
                                                        $set('slug', Str::slug($state));
                                                    }),

                                                TextInput::make('name_en')->label('Name (en)')->maxLength(255),
                                                TextInput::make('name_ru')->label('Название (ru)')->maxLength(255),

                                                TextInput::make('short_name_uk')->label('Коротка назва (uk)')->maxLength(255),
                                                TextInput::make('short_name_en')->label('Short name (en)')->maxLength(255),
                                                TextInput::make('short_name_ru')->label('Короткое название (ru)')->maxLength(255),

                                                TextInput::make('slug')
                                                    ->label('Slug')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->unique(ignoreRecord: true)
                                                    ->columnSpan(['default' => 1, 'md' => 2]),
                                            ]),

                                        Grid::make(['default' => 1, 'lg' => 2])->schema([
                                            Section::make('Країна / валюта')
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
                                                        ),

                                                    Select::make('currency_id')
                                                        ->label('Валюта')
                                                        ->searchable()
                                                        ->preload()
                                                        ->options(fn () => \App\Models\Currency::query()
                                                            ->where('is_active', true)
                                                            ->orderByDesc('is_default')
                                                            ->orderBy('code')
                                                            ->pluck('code', 'id')
                                                            ->all()
                                                        ),
                                                ]),

                                            Section::make('Локалізація')->schema([
                                                TextInput::make('timezone')
                                                    ->label('Часовий пояс')
                                                    ->maxLength(64)
                                                    ->placeholder('Europe/Uzhgorod'),

                                                Select::make('default_language')
                                                    ->label('Мова за замовчуванням')
                                                    ->options(['uk' => 'uk', 'en' => 'en', 'ru' => 'ru']),
                                            ]),
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

                        // =====================================================
                        // СКЛАДИ / НАЯВНІСТЬ
                        // =====================================================
                        Tab::make('Склади/Наявність')->schema([
                            Section::make('Джерела наявності для цього магазину')
                                ->description('Пріоритет: 10/20/30… (перераховується автоматично перед збереженням і для UX при зміні).')
                                ->schema([
                                    Repeater::make('stockSourceLinks')
                                        ->label('Склади / джерела')
                                        ->relationship('stockSourceLinks')
                                        ->disabled(fn ($get) => $disabledIfInherited($get, 'stock_sources'))
                                        ->defaultItems(0)
                                        ->reorderable()
                                        ->collapsible()

                                        // ✅ 100% стабільно: нормалізація перед збереженням (dehydrate)
                                        ->mutateDehydratedStateUsing(fn ($state) => static::normalizeStockSourcePriorities($state))

                                        // ✅ UX: також перераховуємо після змін (без string+int і без циклів)
                                        ->afterStateUpdated(function ($state, $set) {
                                            if (! is_array($state)) return;

                                            $normalized = static::normalizeStockSourcePriorities($state);

                                            // ставимо тільки якщо реально різниця, щоб не ловити зайві тригери
                                            if ($normalized !== $state) {
                                                $set('stockSourceLinks', $normalized);
                                            }
                                        })

                                        ->schema([
                                            Grid::make(['default' => 1, 'md' => 4])->schema([
                                                Select::make('stock_source_id')
                                                    ->label('Джерело')
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->options(fn () => \App\Models\StockSource::query()
                                                        ->where('is_active', true)
                                                        ->orderBy('sort_order')
                                                        ->pluck('name', 'id')
                                                        ->all()
                                                    ),

                                                Toggle::make('is_active')
                                                    ->label('Активно')
                                                    ->default(true),

                                                TextInput::make('priority')
                                                    ->label('Пріоритет')
                                                    ->numeric()
                                                    ->default(100)
                                                    ->helperText('Авто: 10/20/30… (менше = важливіше).'),

                                                Toggle::make('pickup_available')
                                                    ->label('Самовивіз')
                                                    ->default(false),
                                            ]),

                                            Grid::make(['default' => 1, 'md' => 4])->schema([
                                                TextInput::make('min_delivery_days')
                                                    ->label('Доставка (мін. днів)')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->maxValue(365)
                                                    ->placeholder('0'),

                                                TextInput::make('max_delivery_days')
                                                    ->label('Доставка (макс. днів)')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->maxValue(365)
                                                    ->placeholder('3')
                                                    ->rule(function ($get) {
                                                        $min = $get('min_delivery_days');
                                                        if ($min === null || $min === '') return null;
                                                        return 'gte:min_delivery_days';
                                                    })
                                                    ->helperText('Максимум має бути ≥ мінімуму.'),

                                                TextInput::make('markup_percent')
                                                    ->label('Націнка (%)')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->maxValue(9999.99)
                                                    ->placeholder('0')
                                                    ->suffix('%'),

                                                TextInput::make('lead_time_days')
                                                    ->label('Lead time (дні)')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->maxValue(365)
                                                    ->placeholder('0-14'),
                                            ]),

                                            Grid::make(['default' => 1, 'md' => 4])->schema([
                                                TextInput::make('cutoff_time')
                                                    ->label('Cutoff (HH:MM)')
                                                    ->mask('99:99')
                                                    ->maxLength(5)
                                                    ->placeholder('16:00')
                                                    ->rule('regex:/^(?:[01]\d|2[0-3]):[0-5]\d$/')
                                                    ->helperText('Формат 00:00–23:59'),

                                                TextInput::make('price_multiplier')
                                                    ->label('Множник ціни')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->placeholder('1.0000')
                                                    ->helperText('1.05 = +5% поверх базової'),

                                                TextInput::make('extra_fee')
                                                    ->label('Дод. збір')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->placeholder('0.00'),

                                                TextInput::make('min_order_amount')
                                                    ->label('Мін. сума')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->placeholder('0.00'),
                                            ]),

                                            Grid::make(['default' => 1, 'md' => 2])->schema([
                                                TextInput::make('note')
                                                    ->label('Примітка')
                                                    ->maxLength(255),

                                                KeyValue::make('settings')
                                                    ->label('settings')
                                                    ->helperText('Дрібні параметри (API codes, правила, винятки).'),
                                            ]),
                                        ]),
                                ]),
                        ]),

                        // =====================================================
                        // КОНТАКТИ
                        // =====================================================
                        Tab::make('Контакти')->schema([
                            Grid::make(['default' => 1, 'lg' => 2])->schema([
                                Section::make('Основні контакти')->schema([
                                    TextInput::make('email')
                                        ->label('Email')
                                        ->email()
                                        ->maxLength(255)
                                        ->disabled(fn ($get) => $disabledIfInherited($get, 'contacts')),

                                    TextInput::make('website_url')
                                        ->label('Сайт')
                                        ->url()
                                        ->maxLength(255)
                                        ->disabled(fn ($get) => $disabledIfInherited($get, 'contacts')),

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
                                            Grid::make(['default' => 1, 'md' => 12])->schema([
                                                TextInput::make('label')
                                                    ->label('Мітка')
                                                    ->maxLength(50)
                                                    ->columnSpan(3),

                                                PhoneInput::make('number')
                                                    ->label('Номер')
                                                    ->required()
                                                    ->columnSpan(7),

                                                Toggle::make('is_primary')
                                                    ->label('Основний')
                                                    ->inline(false) // ✅ label над toggle
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

                        // =====================================================
                        // ГРАФІК
                        // =====================================================
                        Tab::make('Графік')->schema([
                            Section::make('Графік роботи')->schema([
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
                                                'mon' => 'Пн','tue' => 'Вт','wed' => 'Ср','thu' => 'Чт','fri' => 'Пт','sat' => 'Сб','sun' => 'Нд',
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
                                                    TextInput::make('from')->label('З')->mask('99:99')->required()->maxLength(5),
                                                    TextInput::make('to')->label('До')->mask('99:99')->required()->maxLength(5),
                                                ]),
                                            ])
                                            ->reorderable()
                                            ->collapsible(),
                                    ])
                                    ->reorderable(false)
                                    ->collapsible(),
                            ]),
                        ]),

                        // =====================================================
                        // Доставка (скорочено)
                        // =====================================================
                        Tab::make('Доставка')->schema([
                            Section::make('Методи оплати / доставки / послуги')
                                ->schema([
                                    Repeater::make('payment_methods')
                                        ->label('Методи оплати')
                                        ->disabled(fn ($get) => $disabledIfInherited($get, 'delivery'))
                                        ->defaultItems(0)
                                        ->schema([
                                            Grid::make(['default' => 1, 'md' => 3])->schema([
                                                TextInput::make('code')->label('Code')->maxLength(50),
                                                TextInput::make('title')->label('Назва')->maxLength(255)->required(),
                                                Toggle::make('is_active')->label('Активно')->default(true),
                                            ]),
                                        ])
                                        ->reorderable()
                                        ->collapsible(),

                                    Repeater::make('delivery_methods')
                                        ->label('Методи доставки')
                                        ->disabled(fn ($get) => $disabledIfInherited($get, 'delivery'))
                                        ->defaultItems(0)
                                        ->schema([
                                            Grid::make(['default' => 1, 'md' => 3])->schema([
                                                TextInput::make('code')->label('Code')->maxLength(50),
                                                TextInput::make('title')->label('Назва')->maxLength(255)->required(),
                                                Toggle::make('is_active')->label('Активно')->default(true),
                                            ]),
                                        ])
                                        ->reorderable()
                                        ->collapsible(),
                                ]),
                        ]),

                        // =====================================================
                        // SEO (скорочено)
                        // =====================================================
                        Tab::make('SEO')->schema([
                            Section::make('Meta')->columns(['default' => 1, 'md' => 3])->schema([
                                TextInput::make('meta_title_uk')->label('Meta title (uk)')->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                TextInput::make('meta_title_en')->label('Meta title (en)')->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                                TextInput::make('meta_title_ru')->label('Meta title (ru)')->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                            ]),
                            Section::make('seo JSON')->schema([
                                KeyValue::make('seo')->label('seo')->disabled(fn ($get) => $disabledIfInherited($get, 'seo')),
                            ]),
                        ]),

                        // =====================================================
                        // Юридичні
                        // =====================================================
                        Tab::make('Юридичні')->schema([
                            Grid::make(['default' => 1, 'lg' => 2])->schema([
                                Section::make('Реквізити')->schema([
                                    TextInput::make('company_name')->label('Назва компанії')->maxLength(255)->disabled(fn ($get) => $disabledIfInherited($get, 'legal')),
                                    TextInput::make('edrpou')->label('ЄДРПОУ')->maxLength(20)->disabled(fn ($get) => $disabledIfInherited($get, 'legal')),
                                    TextInput::make('vat')->label('VAT/ІПН')->maxLength(30)->disabled(fn ($get) => $disabledIfInherited($get, 'legal')),
                                    Textarea::make('legal_address')->label('Юридична адреса')->rows(3)->disabled(fn ($get) => $disabledIfInherited($get, 'legal')),
                                ]),
                                Section::make('Settings JSON')->schema([
                                    KeyValue::make('settings')->label('settings'),
                                ]),
                            ]),
                        ]),
                    ]),
            ]);
    }
}

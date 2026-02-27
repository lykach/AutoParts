<?php

namespace App\Filament\Resources\StockSource\Schemas;

use App\Filament\Forms\Components\PhoneInput;
use App\Models\Currency;
use App\Models\StockSource;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class StockSourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('StockSourceTabs')
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
                                                Toggle::make('is_active')->label('Активне')->default(true),

                                                TextInput::make('sort_order')
                                                    ->label('Сортування')
                                                    ->numeric()
                                                    ->placeholder('Авто')
                                                    ->helperText('Якщо пусто — встановиться автоматично')
                                                    ->dehydrateStateUsing(fn ($state) => filled($state) ? (int) $state : null),

                                                TextInput::make('name')
                                                    ->label('Назва')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->columnSpanFull(),

                                                TextInput::make('code')
                                                    ->label('Код (унікальний)')
                                                    ->helperText('Якщо не вкажеш — згенерується автоматично.')
                                                    ->maxLength(64)
                                                    ->unique(ignoreRecord: true),

                                                Select::make('type')
                                                    ->label('Тип')
                                                    ->required()
                                                    ->default('own_warehouse')
                                                    ->options(StockSource::typeOptions())
                                                    ->native(false),

                                                Select::make('default_currency_code')
                                                    ->label('Валюта за замовчуванням')
                                                    ->required()
                                                    ->native(false)
                                                    ->default('UAH')
                                                    ->options(fn () => Currency::query()
                                                        ->where('is_active', true)
                                                        ->orderByDesc('is_default')
                                                        ->orderBy('code')
                                                        ->pluck('code', 'code')
                                                        ->all()
                                                    ),
                                            ]),

                                        Section::make('Доставка за замовчуванням')
                                            ->columns(['default' => 1, 'md' => 3])
                                            ->schema([
                                                Select::make('delivery_unit')
                                                    ->label('Одиниці')
                                                    ->native(false)
                                                    ->options(StockSource::deliveryUnitOptions())
                                                    ->default('days')
                                                    ->required(),

                                                TextInput::make('delivery_min')
                                                    ->label('Від')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->placeholder('—')
                                                    ->helperText('Число в обраних одиницях'),

                                                TextInput::make('delivery_max')
                                                    ->label('До')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->placeholder('—'),
                                            ]),

                                        Section::make('Контакти')
                                            ->columns(['default' => 1, 'md' => 2])
                                            ->schema([
                                                TextInput::make('contact_name')->label('Контакт')->maxLength(255),
                                                PhoneInput::make('phone')->label('Телефон'),
                                                TextInput::make('email')->label('Email')->email()->maxLength(255),
                                                TextInput::make('website_url')->label('Сайт')->url()->maxLength(255),
                                            ]),

                                        Section::make('Адреса')
                                            ->columns(['default' => 1, 'md' => 2])
                                            ->schema([
                                                TextInput::make('country')->label('Країна')->maxLength(100),
                                                TextInput::make('city')->label('Місто')->maxLength(100),

                                                TextInput::make('address_line1')
                                                    ->label('Вулиця')
                                                    ->maxLength(255)
                                                    ->columnSpanFull(),
                                            ]),

                                        Section::make('Примітка')->schema([
                                            Textarea::make('note')->label('Примітка')->rows(3),
                                        ]),
                                    ]),

                                Section::make('Опціонально')
                                    ->columnSpan(['default' => 1, 'lg' => 1])
                                    ->schema([
                                        FileUpload::make('settings.logo')
                                            ->label('Лого (опц.)')
                                            ->disk('public')
                                            ->directory('stock-sources')
                                            ->visibility('public')
                                            ->image()
                                            ->imageEditor()
                                            ->maxSize(4096),
                                    ]),
                            ]),
                        ]),
                    ]),
            ]);
    }
}
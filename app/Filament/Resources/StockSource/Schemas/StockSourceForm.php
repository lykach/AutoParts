<?php

namespace App\Filament\Resources\StockSource\Schemas;

use App\Filament\Forms\Components\PhoneInput;
use App\Models\Currency;
use App\Models\StockSource;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
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
                                                Toggle::make('is_active')
                                                    ->label('Активне')
                                                    ->default(true),

                                                TextInput::make('sort_order')
                                                    ->label('Сортування')
                                                    ->numeric()
                                                    ->default(100),

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

                                                TextInput::make('min_order_default_qty')
                                                    ->label('Мін. замовлення (шт, дефолт)')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('—'),

                                                // ✅ NEW
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
                                                    )
                                                    ->helperText('Підставляється по замовчуванню в позиції складу (stock_items), але там можна змінити.'),
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
                                                TextInput::make('region')->label('Область')->maxLength(100),
                                                TextInput::make('city')->label('Місто')->maxLength(100),
                                                TextInput::make('postal_code')->label('Індекс')->maxLength(20),

                                                TextInput::make('address_line1')
                                                    ->label('Адреса 1')->maxLength(255)->columnSpanFull(),
                                                TextInput::make('address_line2')
                                                    ->label('Адреса 2')->maxLength(255)->columnSpanFull(),

                                                TextInput::make('lat')->label('Lat')->numeric(),
                                                TextInput::make('lng')->label('Lng')->numeric(),
                                            ]),

                                        Section::make('Налаштування')
                                            ->schema([
                                                KeyValue::make('settings')->label('settings'),
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

<?php
namespace App\Filament\Resources\Currencies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->schema([ // ✅ ->schema() замість ->components()
                TextInput::make('code')
                    ->label('Символьний код')
                    ->placeholder('UAH')
                    ->required()
                    ->maxLength(3)
                    ->unique(ignoreRecord: true)
                    ->helperText('3-літерний код валюти (наприклад: UAH, USD, EUR)')
                    ->columnSpan(1),
                
                TextInput::make('iso_code')
                    ->label('Код ISO (цифровий)')
                    ->placeholder('980')
                    ->maxLength(3)
                    ->numeric()
                    ->helperText('Цифровий код валюти за ISO 4217')
                    ->columnSpan(1),
                
                TextInput::make('symbol')
                    ->label('Символ валюти')
                    ->placeholder('₴')
                    ->required()
                    ->maxLength(10)
                    ->helperText('Символ для відображення (₴, $, €)')
                    ->columnSpan(1),
                
                TextInput::make('short_name_uk')
                    ->label('Коротке ім\'я (Українська)')
                    ->placeholder('грн')
                    ->maxLength(10)
                    ->helperText('Скорочена назва українською')
                    ->columnSpan(1),
                
                TextInput::make('short_name_en')
                    ->label('Коротке ім\'я (English)')
                    ->placeholder('UAH')
                    ->maxLength(10)
                    ->helperText('Скорочена назва англійською')
                    ->columnSpan(1),
                
                TextInput::make('short_name_ru')
                    ->label('Коротке ім\'я (Русский)')
                    ->placeholder('грн')
                    ->maxLength(10)
                    ->helperText('Скорочена назва російською')
                    ->columnSpan(1),
                
                TextInput::make('rate')
                    ->label('Курс до головної валюти')
                    ->numeric()
                    ->default(1.0000)
                    ->minValue(0.0001)
                    ->step(0.0001)
                    ->required()
                    ->helperText('Для головної валюти курс завжди 1.0')
                    ->disabled(fn (callable $get) => $get('is_default') === true) // ✅ callable
                    ->columnSpan(1),
                
                Toggle::make('is_default')
                    ->label('Головна валюта магазину')
                    ->helperText('Головна валюта має курс 1.0 та завжди активна')
                    ->live() // ✅ live замість reactive
                    ->columnSpan(1),
                
                Toggle::make('is_active')
                    ->label('Активна на сайті')
                    ->default(true)
                    ->helperText('Тільки активні валюти доступні покупцям')
                    ->disabled(fn (callable $get) => $get('is_default') === true) // ✅ callable
                    ->columnSpan(1),
            ]);
    }
}
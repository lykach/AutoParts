<?php
namespace App\Filament\Resources\Languages\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Основна інформація')
                    ->schema([
                        TextInput::make('code')
                            ->label('Код мови')
                            ->placeholder('uk')
                            ->required()
                            ->maxLength(5)
                            ->unique(ignoreRecord: true)
                            ->helperText('ISO 639-1 код мови (uk, en, ru)')
                            ->regex('/^[a-z]{2,5}$/')
                            ->validationMessages([
                                'regex' => 'Код має містити лише малі латинські літери (2-5 символів)',
                            ])
                            ->columnSpanFull(),
                        
                        TextInput::make('name_uk')
                            ->label('Назва (Українська)')
                            ->placeholder('Українська')
                            ->required()
                            ->maxLength(100)
                            ->columnSpan(1),
                        
                        TextInput::make('name_en')
                            ->label('Назва (English)')
                            ->placeholder('Ukrainian')
                            ->maxLength(100)
                            ->columnSpan(1),
                        
                        TextInput::make('name_ru')
                            ->label('Назва (Русский)')
                            ->placeholder('Украинская')
                            ->maxLength(100)
                            ->columnSpan(1),
                        
                        Toggle::make('is_default')
                            ->label('Головна мова сайту')
                            ->helperText('Головна мова завжди активна та не може бути видалена')
                            ->live()
                            ->columnSpan(1),
                        
                        Toggle::make('is_active')
                            ->label('Активна на сайті')
                            ->default(true)
                            ->helperText('Тільки активні мови доступні користувачам')
                            ->disabled(fn (callable $get) => $get('is_default') === true)
                            ->columnSpan(2),
                    ])
                    ->columns(3),
                
                Section::make('TecDoc Інтеграція')
                    ->description('Параметри для роботи з TecDoc базою даних')
                    ->schema([
                        TextInput::make('lng_id')
                            ->label('TecDoc Language ID')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(999)
                            ->placeholder('48')
                            ->helperText('ID мови в TecDoc базі (uk: 48, en: 4, ru: 16)')
                            ->columnSpan(1),
                        
                        TextInput::make('lng_codepage')
                            ->label('TecDoc Codepage')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(9999)
                            ->placeholder('1251')
                            ->helperText('Кодова сторінка для TecDoc (1251 для uk/ru, 1252 для en)')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
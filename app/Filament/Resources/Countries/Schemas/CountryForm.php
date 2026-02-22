<?php

namespace App\Filament\Resources\Countries\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function make(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->schema([
                Section::make('Основна інформація')
                    ->columns(3)
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Код (TecDoc)')
                            ->required()
                            ->maxLength(4)
                            ->unique(ignoreRecord: true)
                            ->placeholder('UA')
                            ->helperText('Код країни в TecDoc системі')
                            ->columnSpan(1),

                        TextInput::make('iso_code_2')
                            ->label('ISO Alpha-2')
                            ->maxLength(2)
                            ->placeholder('UA')
                            ->unique(ignoreRecord: true)
                            ->helperText('ISO 3166-1 alpha-2 код')
                            ->columnSpan(1),

                        TextInput::make('iso_code_3')
                            ->label('ISO Alpha-3')
                            ->maxLength(3)
                            ->placeholder('UKR')
                            ->unique(ignoreRecord: true)
                            ->helperText('ISO 3166-1 alpha-3 код')
                            ->columnSpan(1),

                        TextInput::make('iso_code_numeric')
                            ->label('ISO Numeric')
                            ->numeric()
                            ->placeholder('804')
                            ->helperText('ISO 3166-1 числовий код')
                            ->columnSpan(1),

                        TextInput::make('currency_code')
                            ->label('Код валюти')
                            ->maxLength(3)
                            ->placeholder('UAH, EUR, USD')
                            ->helperText('Основна валюта країни')
                            ->columnSpan(1),

                        TextInput::make('sort_order')
                            ->label('Порядок сортування')
                            ->numeric()
                            ->default(0)
                            ->helperText('Порядок відображення в списках')
                            ->columnSpan(1),

                        TextInput::make('name_uk')
                            ->label('Назва (Українська)')
                            ->maxLength(255)
                            ->required()
                            ->placeholder('Україна')
                            ->columnSpan(3),

                        TextInput::make('name_en')
                            ->label('Назва (English)')
                            ->maxLength(255)
                            ->placeholder('Ukraine')
                            ->columnSpan(3),

                        TextInput::make('name_ru')
                            ->label('Назва (Русский)')
                            ->maxLength(255)
                            ->placeholder('Украина')
                            ->columnSpan(3),

                        FileUpload::make('flag_image')
                            ->label('Прапор країни')
                            ->image()
                            ->disk('public')
                            ->directory('flags')
                            ->visibility('public')
                            ->imagePreviewHeight('100')
                            ->maxSize(1024)
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->helperText('WEBP, PNG або JPG. Макс 1MB')
                            ->columnSpan(3),

                        Toggle::make('is_active')
                            ->label('Активна на сайті')
                            ->default(true)
                            ->helperText('Тільки активні країни доступні на сайті')
                            ->inline(false)
                            ->columnSpan(1),

                        Toggle::make('is_group')
                            ->label('Група країн')
                            ->helperText('Позначка для групи країн у TecDoc')
                            ->inline(false)
                            ->columnSpan(1),
                    ]),

                Section::make('TecDoc Інтеграція')
                    ->description('Параметри для роботи з TecDoc базою даних')
                    ->columns(1)
                    ->columnSpan(1)
                    ->schema([
                        Placeholder::make('tecdoc_code_view')
                            ->label('TecDoc Country Code')
                            ->content(fn ($get) => (string) ($get('code') ?: '—')),

                        Placeholder::make('iso_code_2_view')
                            ->label('ISO Alpha-2')
                            ->content(fn ($get) => (string) ($get('iso_code_2') ?: '—')),
                    ]),
            ]);
    }
}

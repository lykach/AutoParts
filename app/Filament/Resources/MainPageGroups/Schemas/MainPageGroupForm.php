<?php

namespace App\Filament\Resources\MainPageGroups\Schemas;

use App\Models\MainPageGroup;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MainPageGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Назва блоку')
                    ->schema([

                        TextInput::make('caption_uk')
                            ->label('Назва (Українська)')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('caption_en')
                            ->label('Назва (English)')
                            ->maxLength(255),

                        TextInput::make('caption_ru')
                            ->label('Назва (Русский)')
                            ->maxLength(255),

                    ])
                    ->columns(3),

                Section::make('Налаштування')
                    ->schema([

                        TextInput::make('sort')
                            ->label('Сортування')
                            ->numeric()
                            ->required()
                            ->default(fn (): int => ((int) MainPageGroup::query()->max('sort')) + 1)
                            ->helperText('Автоматично. Можна змінити вручну.'),

                        Toggle::make('show_caption')
                            ->label('Показувати заголовок')
                            ->default(true),

                        Toggle::make('is_active')
                            ->label('Активний')
                            ->default(true),

                    ])
                    ->columns(3),

            ]);
    }
}
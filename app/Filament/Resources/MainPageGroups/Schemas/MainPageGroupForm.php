<?php

namespace App\Filament\Resources\MainPageGroups\Schemas;

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
                Section::make('Основне')
                    ->schema([
                        TextInput::make('caption')
                            ->label('Назва блоку')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('sort')
                            ->label('Сортування')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Toggle::make('show_caption')
                            ->label('Показувати заголовок')
                            ->default(true),

                        Toggle::make('is_active')
                            ->label('Активний')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
<?php

namespace App\Filament\Resources\Permissions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PermissionForm
{
    public static function make(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->schema([
                Section::make('Право доступу')
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Системна назва (slug)')
                            ->required()
                            ->maxLength(150)
                            ->unique(ignoreRecord: true)
                            ->helperText('Напр: users.view, users.update, orders.view, prices.edit'),

                        TextInput::make('description')
                            ->label('Опис')
                            ->maxLength(255)
                            ->placeholder('Що саме дозволяє це право?')
                            ->helperText('Цей текст буде показуватись в RoleForm при виборі permission'),

                        TextInput::make('guard_name')
                            ->label('Guard')
                            ->default('web')
                            ->disabled()
                            ->dehydrated(true),
                    ]),

                Section::make('Пояснення')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('note')
                            ->label('Формат')
                            ->disabled()
                            ->dehydrated(false)
                            ->default('<module>.<action> | напр: orders.create'),
                    ]),
            ]);
    }
}

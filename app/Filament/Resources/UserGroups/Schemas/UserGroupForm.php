<?php

namespace App\Filament\Resources\UserGroups\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;

class UserGroupForm
{
    public static function make(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->schema([
                // ✅ Основні налаштування (ліва колонка)
                Section::make('Параметри групи')
                    ->description(
                        'Визначте назву та фінансові умови для цієї категорії клієнтів'
                    )
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Назва групи')
                            ->placeholder('напр. СТО «Захід», Оптовий клієнт, VIP')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('discount_percent')
                                    ->label('Знижка')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(99)
                                    ->step(0.1)
                                    ->suffix('%')
                                    ->prefixIcon('heroicon-m-arrow-trending-down')
                                    ->helperText(
                                        'Відсоток, який буде віднято від роздрібної ціни'
                                    ),

                                TextInput::make('markup_percent')
                                    ->label('Націнка')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(500)
                                    ->step(0.1)
                                    ->suffix('%')
                                    ->prefixIcon('heroicon-m-arrow-trending-up')
                                    ->helperText(
                                        'Додатковий відсоток до вартості (якщо потрібно)'
                                    ),
                            ]),
                    ]),

                // ✅ Мета-інформація (права колонка)
                Section::make('Мета-інформація')
                    ->columnSpan(1)
                    ->schema([
                        Placeholder::make('users_count')
                            ->label('Кількість учасників')
                            ->content(fn ($record) =>
                                $record
                                    ? $record->users()->count() . ' осіб'
                                    : '0 осіб'
                            ),

                        Placeholder::make('created_at')
                            ->label('Дата створення')
                            ->content(fn ($record) =>
                                $record
                                    ? $record->created_at->format('d.m.Y H:i')
                                    : 'Зараз'
                            ),

                        Placeholder::make('updated_at')
                            ->label('Останнє оновлення')
                            ->content(fn ($record) =>
                                $record
                                    ? $record->updated_at->diffForHumans()
                                    : '—'
                            ),
                    ])
                    ->collapsible(),
            ]);
    }
}

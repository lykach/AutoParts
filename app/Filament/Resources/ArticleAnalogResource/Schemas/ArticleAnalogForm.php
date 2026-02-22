<?php

namespace App\Filament\Resources\ArticleAnalogResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ArticleAnalogForm
{
    public static function configure(Schema $schema): Schema
    {
        $norm = fn ($state) => mb_strtoupper(trim((string) $state));

        return $schema->components([
            Section::make('Пара артикулів')
                ->description('Значення нормалізуються (trim + UPPERCASE) — щоб не плодити дублі.')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('type')
                            ->label('Тип')
                            ->options([
                                'cross' => 'Крос (дозволений аналог)',
                                'anti'  => 'Антикрос (заборонена пара)',
                            ])
                            ->default('cross')
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Активний')
                            ->default(true),

                        TextInput::make('manufacturer_article')
                            ->label('Виробник артикула')
                            ->required()
                            ->maxLength(128)
                            ->autocomplete(false)
                            ->dehydrateStateUsing($norm),

                        TextInput::make('article')
                            ->label('Артикул')
                            ->required()
                            ->maxLength(128)
                            ->autocomplete(false)
                            ->dehydrateStateUsing($norm),

                        TextInput::make('manufacturer_analog')
                            ->label('Виробник аналога')
                            ->required()
                            ->maxLength(128)
                            ->autocomplete(false)
                            ->dehydrateStateUsing($norm),

                        TextInput::make('analog')
                            ->label('Аналог')
                            ->required()
                            ->maxLength(128)
                            ->autocomplete(false)
                            ->dehydrateStateUsing($norm),
                    ]),
                ]),
        ]);
    }
}

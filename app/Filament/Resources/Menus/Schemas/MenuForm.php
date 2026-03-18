<?php

namespace App\Filament\Resources\Menus\Schemas;

use App\Enums\MenuLocation;
use App\Models\Menu;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Основне')
                ->schema([
                    Grid::make(1)->schema([
                        TextInput::make('name')
                            ->label('Назва')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get, $record) {
                                $currentCode = (string) ($get('code') ?? '');

                                if (blank($currentCode) && filled($state)) {
                                    $set(
                                        'code',
                                        Menu::generateUniqueCode(
                                            (string) $state,
                                            $record?->getKey()
                                        )
                                    );
                                }
                            })
                            ->columnSpan(5),

                        TextInput::make('code')
                            ->label('Код')
                            ->maxLength(255)
                            ->helperText('Можна не заповнювати — згенерується автоматично. Для системних меню код заблокований.')
                            ->disabled(fn ($record) => (bool) $record?->is_system)
                            ->dehydrated(fn ($record) => ! $record?->is_system)
                            ->dehydrateStateUsing(function ($state, callable $get, $record) {
                                $state = trim((string) $state);

                                if (filled($state)) {
                                    return Menu::generateUniqueCode(Str::slug($state), $record?->getKey());
                                }

                                return Menu::generateUniqueCode(
                                    (string) $get('name'),
                                    $record?->getKey()
                                );
                            })
                            ->unique(ignoreRecord: true)
                            ->columnSpan(4),

                        Select::make('location')
                            ->label('Розташування')
                            ->options(MenuLocation::options())
                            ->required()
                            ->native(false)
                            ->columnSpan(3),

                        TextInput::make('sort')
                            ->label('Сортування')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->columnSpan(3),

                        Toggle::make('is_active')
                            ->label('Активне')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(3),

                        Toggle::make('is_system')
                            ->label('Системне меню')
                            ->helperText('Системні меню не можна видаляти, а їхній code захищений.')
                            ->default(false)
                            ->inline(false)
                            ->disabled(fn ($record) => (bool) $record?->is_system)
                            ->dehydrated(fn ($record) => ! $record?->is_system)
                            ->columnSpan(3),

                        Placeholder::make('items_info')
                            ->label('Пункти меню')
                            ->content(fn ($record) => $record ? (string) $record->items()->count() : '0')
                            ->columnSpan(3),
                    ]),
                ]),
        ]);
    }
}
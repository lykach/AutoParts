<?php

namespace App\Filament\Resources\Manufacturers\Schemas;

use App\Models\Country;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ManufacturerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Основне')
                    ->columns(1)
                    ->schema([
                        TextInput::make('name')
                            ->label('Назва виробника')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                if (!$get('slug')) {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),

                        TextInput::make('short_name')
                            ->label('Коротка назва')
                            ->helperText('Напр.: BOSCH, VAG, ZF. Не обовʼязково.')
                            ->maxLength(80)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                $value = trim((string) $state);
                                $set('short_name', $value !== '' ? $value : null);
                            }),

                        TextInput::make('slug')
                            ->label('Slug (для URL)')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Використовується для /{lang}/brands/{slug}')
                            ->unique(ignoreRecord: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('slug', Str::slug((string) $state));
                            }),

                        FileUpload::make('logo')
                            ->label('Логотип')
                            ->image()
                            ->disk('public')
                            ->directory('manufacturers')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(2048)
                            ->helperText('Зберігається в storage/public/manufacturers')
                            ->columnSpanFull(),

                        Select::make('country_id')
                            ->label('Країна')
                            ->options(fn () => Country::query()->orderBy('name_uk')->pluck('name_uk', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->placeholder('—'),

                        Toggle::make('is_oem')
                            ->label('Оригінальний виробник (OEM)')
                            ->helperText('Позначай, якщо бренд є OEM/оригіналом.')
                            ->default(false),

                        Toggle::make('is_active')
                            ->label('Активний')
                            ->default(true),

                        TextInput::make('sort_order')
                            ->label('Порядок')
                            ->numeric()
                            ->nullable()
                            ->helperText('Якщо не вказати — виставиться автоматично (max + 1).')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                // якщо поле стерли — збережемо null, щоб модель авто-поставила порядок
                                $set('sort_order', ($state === null || $state === '') ? null : (int) $state);
                            }),

                        TextInput::make('website_url')
                            ->label('Сайт виробника')
                            ->placeholder('https://example.com')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('catalog_url')
                            ->label('Зовнішній каталог')
                            ->placeholder('https://catalog.example.com')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Section::make('Внутрішні посилання (генеруються автоматично)')
                    ->description('Варіант B: посилання НЕ зберігаємо в БД — завжди будуємо з slug.')
                    ->schema([
                        Placeholder::make('internal_urls')
                            ->label('Посилання на бренд на сайті')
                            ->content(function (callable $get) {
                                $slug = trim((string) $get('slug'));

                                if ($slug === '') {
                                    return "UK: —\nEN: —\nRU: —";
                                }

                                return "UK: /uk/brands/{$slug}\nEN: /en/brands/{$slug}\nRU: /ru/brands/{$slug}";
                            }),
                    ]),

                Tabs::make('Опис')
                    ->tabs([
                        Tabs\Tab::make('Українська')
                            ->schema([
                                Textarea::make('description_uk')
                                    ->label('Опис (UK)')
                                    ->rows(6)
                                    ->maxLength(5000),
                            ]),
                        Tabs\Tab::make('English')
                            ->schema([
                                Textarea::make('description_en')
                                    ->label('Description (EN)')
                                    ->rows(6)
                                    ->maxLength(5000),
                            ]),
                        Tabs\Tab::make('Русский')
                            ->schema([
                                Textarea::make('description_ru')
                                    ->label('Описание (RU)')
                                    ->rows(6)
                                    ->maxLength(5000),
                            ]),
                    ]),
            ]);
    }
}
<?php

namespace App\Filament\Resources\Menus\RelationManagers;

use App\Enums\MenuItemType;
use App\Models\MenuItem;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenuItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Пункти меню';

    protected function getParentOptions(?int $ignoreId = null): array
    {
        return MenuItem::query()
            ->where('menu_id', $this->getOwnerRecord()->id)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(function (MenuItem $item) {
                $label = $item->title_uk
                    ?: $item->title_en
                    ?: $item->title_ru
                    ?: ('[#' . $item->id . '] Без назви');

                return [$item->id => $label];
            })
            ->all();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Основне')
                ->schema([
                    Grid::make(1)->schema([
                        Select::make('parent_id')
                            ->label('Батьківський пункт')
                            ->options(fn ($record) => $this->getParentOptions($record?->id))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('— Кореневий пункт —')
                            ->columnSpan(4),

                        Select::make('type')
                            ->label('Тип')
                            ->options(MenuItemType::options())
                            ->default(MenuItemType::Page->value)
                            ->required()
                            ->live()
                            ->native(false)
                            ->columnSpan(4),

                        TextInput::make('sort')
                            ->label('Сортування')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->columnSpan(2),

                        Toggle::make('is_active')
                            ->label('Активний')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(2),
                    ]),
                ]),

            Tabs::make('Назва')
                ->tabs([
                    Tab::make('UK')->schema([
                        TextInput::make('title_uk')
                            ->label('Назва (UK)')
                            ->maxLength(255)
                            ->helperText('Можна не заповнювати, якщо тип = "Сторінка": назва підтягнеться зі сторінки в таблиці.')
                            ->live(onBlur: true),
                    ]),
                    Tab::make('EN')->schema([
                        TextInput::make('title_en')
                            ->label('Назва (EN)')
                            ->maxLength(255),
                    ]),
                    Tab::make('RU')->schema([
                        TextInput::make('title_ru')
                            ->label('Назва (RU)')
                            ->maxLength(255),
                    ]),
                ]),

            Section::make('Ціль')
                ->schema([
                    Select::make('page_id')
                        ->label('Сторінка')
                        ->relationship(
                            name: 'page',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->orderBy('sort')->orderBy('name')
                        )
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->live()
                        ->visible(fn (callable $get) => $get('type') === MenuItemType::Page->value)
                        ->required(fn (callable $get) => $get('type') === MenuItemType::Page->value)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if ($get('type') !== MenuItemType::Page->value) {
                                return;
                            }

                            $currentTitleUk = trim((string) ($get('title_uk') ?? ''));

                            if (filled($currentTitleUk) || blank($state)) {
                                return;
                            }

                            $page = \App\Models\Page::query()->find($state);

                            if (! $page) {
                                return;
                            }

                            $set(
                                'title_uk',
                                $page->title_uk
                                    ?: $page->title_en
                                    ?: $page->title_ru
                                    ?: $page->name
                            );
                        }),

                    TextInput::make('url')
                        ->label('URL')
                        ->placeholder('/delivery-payment або https://example.com')
                        ->visible(fn (callable $get) => $get('type') === MenuItemType::Url->value)
                        ->required(fn (callable $get) => $get('type') === MenuItemType::Url->value),

                    Select::make('category_id')
                        ->label('Категорія')
                        ->relationship(
                            name: 'category',
                            titleAttribute: 'name_uk',
                            modifyQueryUsing: fn ($query) => $query->orderBy('name_uk')
                        )
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->visible(fn (callable $get) => $get('type') === MenuItemType::Category->value)
                        ->required(fn (callable $get) => $get('type') === MenuItemType::Category->value),

                    Select::make('manufacturer_id')
                        ->label('Виробник')
                        ->relationship(
                            name: 'manufacturer',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->orderBy('name')
                        )
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->visible(fn (callable $get) => $get('type') === MenuItemType::Manufacturer->value)
                        ->required(fn (callable $get) => $get('type') === MenuItemType::Manufacturer->value),
                ]),

            Section::make('Додатково')
                ->schema([
                    Grid::make(1)->schema([
                        TextInput::make('icon')
                            ->label('Іконка')
                            ->placeholder('heroicon-o-truck')
                            ->columnSpan(4),

                        TextInput::make('badge_text')
                            ->label('Badge текст')
                            ->maxLength(50)
                            ->columnSpan(3),

                        TextInput::make('badge_color')
                            ->label('Badge колір')
                            ->placeholder('primary / success / warning / danger')
                            ->maxLength(50)
                            ->columnSpan(3),

                        Toggle::make('target_blank')
                            ->label('Відкривати у новій вкладці')
                            ->inline(false)
                            ->columnSpan(2),
                    ]),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('title_uk')
                    ->label('Назва')
                    ->state(function (MenuItem $record): string {
                        if (filled($record->title_uk)) {
                            return $record->title_uk;
                        }

                        if (filled($record->title_en)) {
                            return $record->title_en;
                        }

                        if (filled($record->title_ru)) {
                            return $record->title_ru;
                        }

                        if ($record->type === MenuItemType::Page && $record->page) {
                            return $record->page->title_uk
                                ?: $record->page->title_en
                                ?: $record->page->title_ru
                                ?: $record->page->name;
                        }

                        return 'Без назви';
                    })
                    ->searchable(),

                TextColumn::make('resolved_url')
                    ->label('Посилання')
                    ->state(fn (MenuItem $record): string => $record->resolved_url ?: '—')
                    ->toggleable(),

                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof MenuItemType ? $state->label() : ($state ? MenuItemType::from($state)->label() : '—')),

                TextColumn::make('parent_id')
                    ->label('Батьківський')
                    ->state(function (MenuItem $record): string {
                        if (! $record->parent) {
                            return '—';
                        }

                        return $record->parent->title_uk
                            ?: $record->parent->title_en
                            ?: $record->parent->title_ru
                            ?: ('[#' . $record->parent->id . '] Без назви');
                    }),

                IconColumn::make('target_blank')
                    ->label('New tab')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Активний')
                    ->boolean(),

                TextColumn::make('sort')
                    ->label('Sort')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['menu_id'] = $this->getOwnerRecord()->id;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
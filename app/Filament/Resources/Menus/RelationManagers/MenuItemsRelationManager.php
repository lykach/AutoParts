<?php

namespace App\Filament\Resources\Menus\RelationManagers;

use App\Enums\MenuItemType;
use App\Models\MenuItem;
use App\Models\Page;
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
use Illuminate\Validation\Rules\Unique;

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
                return [$item->id => $item->resolved_title];
            })
            ->all();
    }

    protected function normalizeUrl(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    protected function hasAtLeastOneTitle(callable $get): bool
    {
        return filled(trim((string) ($get('title_uk') ?? '')))
            || filled(trim((string) ($get('title_en') ?? '')))
            || filled(trim((string) ($get('title_ru') ?? '')));
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
                            ->columnSpan(4)
                            ->rule(function ($record) {
                                return function (string $attribute, $value, \Closure $fail) use ($record) {
                                    if (blank($value)) {
                                        return;
                                    }

                                    if ($record && (int) $value === (int) $record->getKey()) {
                                        $fail('Не можна вибрати поточний пункт батьківським.');
                                        return;
                                    }

                                    $parent = MenuItem::query()->find($value);

                                    if (! $parent || (int) $parent->menu_id !== (int) $this->getOwnerRecord()->id) {
                                        $fail('Батьківський пункт має належати до цього ж меню.');
                                    }
                                };
                            }),

                        Select::make('type')
                            ->label('Тип')
                            ->options(MenuItemType::options())
                            ->default(MenuItemType::Page->value)
                            ->required()
                            ->live()
                            ->native(false)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state === MenuItemType::Page->value) {
                                    $set('url', null);
                                    $set('category_id', null);
                                    $set('manufacturer_id', null);
                                    return;
                                }

                                if ($state === MenuItemType::Url->value) {
                                    $set('page_id', null);
                                    $set('category_id', null);
                                    $set('manufacturer_id', null);
                                    return;
                                }

                                if ($state === MenuItemType::Category->value) {
                                    $set('page_id', null);
                                    $set('url', null);
                                    $set('manufacturer_id', null);
                                    return;
                                }

                                if ($state === MenuItemType::Manufacturer->value) {
                                    $set('page_id', null);
                                    $set('url', null);
                                    $set('category_id', null);
                                }
                            })
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
                            ->helperText('Для типу "Сторінка" можна не заповнювати — назва підтягнеться автоматично.')
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
                            if ($get('type') !== MenuItemType::Page->value || blank($state)) {
                                return;
                            }

                            if ($this->hasAtLeastOneTitle($get)) {
                                return;
                            }

                            $page = Page::query()->find($state);

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
                        ->required(fn (callable $get) => $get('type') === MenuItemType::Url->value)
                        ->dehydrateStateUsing(fn ($state) => $this->normalizeUrl($state))
                        ->rule(function (callable $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                if ($get('type') !== MenuItemType::Url->value) {
                                    return;
                                }

                                $value = trim((string) $value);

                                if ($value === '') {
                                    $fail('Для типу "URL" потрібно вказати посилання.');
                                    return;
                                }

                                $isAbsolute = filter_var($value, FILTER_VALIDATE_URL);
                                $isRelative = str_starts_with($value, '/');

                                if (! $isAbsolute && ! $isRelative) {
                                    $fail('URL має починатися з "/" або бути повним посиланням.');
                                }
                            };
                        }),

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
                        ->live()
                        ->visible(fn (callable $get) => $get('type') === MenuItemType::Category->value)
                        ->required(fn (callable $get) => $get('type') === MenuItemType::Category->value)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if ($get('type') !== MenuItemType::Category->value || blank($state)) {
                                return;
                            }

                            if ($this->hasAtLeastOneTitle($get)) {
                                return;
                            }

                            $category = \App\Models\Category::query()->find($state);

                            if (! $category) {
                                return;
                            }

                            $set(
                                'title_uk',
                                $category->name_uk
                                    ?: $category->name_en
                                    ?: $category->name_ru
                                    ?: 'Категорія'
                            );
                        }),

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
                        ->live()
                        ->visible(fn (callable $get) => $get('type') === MenuItemType::Manufacturer->value)
                        ->required(fn (callable $get) => $get('type') === MenuItemType::Manufacturer->value)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if ($get('type') !== MenuItemType::Manufacturer->value || blank($state)) {
                                return;
                            }

                            if ($this->hasAtLeastOneTitle($get)) {
                                return;
                            }

                            $manufacturer = \App\Models\Manufacturer::query()->find($state);

                            if (! $manufacturer) {
                                return;
                            }

                            $set('title_uk', $manufacturer->name ?: 'Виробник');
                        }),
                ]),

            Section::make('Додатково')
                ->schema([
                    Grid::make(1)->schema([
                        TextInput::make('icon')
                            ->label('Іконка')
                            ->placeholder('heroicon-o-truck')
                            ->maxLength(255)
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->prepareData($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->prepareData($data);
    }

    protected function prepareData(array $data): array
    {
        $data['menu_id'] = $this->getOwnerRecord()->id;

        $data['title_uk'] = $this->nullableTrim($data['title_uk'] ?? null);
        $data['title_en'] = $this->nullableTrim($data['title_en'] ?? null);
        $data['title_ru'] = $this->nullableTrim($data['title_ru'] ?? null);
        $data['icon'] = $this->nullableTrim($data['icon'] ?? null);
        $data['badge_text'] = $this->nullableTrim($data['badge_text'] ?? null);
        $data['badge_color'] = $this->nullableTrim($data['badge_color'] ?? null);
        $data['url'] = $this->normalizeUrl($data['url'] ?? null);
        $data['parent_id'] = blank($data['parent_id'] ?? null) ? null : (int) $data['parent_id'];

        $type = $data['type'] ?? MenuItemType::Page->value;

        if ($type === MenuItemType::Page->value) {
            $data['url'] = null;
            $data['category_id'] = null;
            $data['manufacturer_id'] = null;

            if (! $this->hasAnyTitleInData($data) && ! empty($data['page_id'])) {
                $page = Page::query()->find($data['page_id']);

                if ($page) {
                    $data['title_uk'] = $page->title_uk
                        ?: $page->title_en
                        ?: $page->title_ru
                        ?: $page->name;
                }
            }
        }

        if ($type === MenuItemType::Url->value) {
            $data['page_id'] = null;
            $data['category_id'] = null;
            $data['manufacturer_id'] = null;
        }

        if ($type === MenuItemType::Category->value) {
            $data['page_id'] = null;
            $data['url'] = null;
            $data['manufacturer_id'] = null;

            if (! $this->hasAnyTitleInData($data) && ! empty($data['category_id'])) {
                $category = \App\Models\Category::query()->find($data['category_id']);

                if ($category) {
                    $data['title_uk'] = $category->name_uk
                        ?: $category->name_en
                        ?: $category->name_ru
                        ?: 'Категорія';
                }
            }
        }

        if ($type === MenuItemType::Manufacturer->value) {
            $data['page_id'] = null;
            $data['url'] = null;
            $data['category_id'] = null;

            if (! $this->hasAnyTitleInData($data) && ! empty($data['manufacturer_id'])) {
                $manufacturer = \App\Models\Manufacturer::query()->find($data['manufacturer_id']);

                if ($manufacturer) {
                    $data['title_uk'] = $manufacturer->name ?: 'Виробник';
                }
            }
        }

        return $data;
    }

    protected function nullableTrim(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    protected function hasAnyTitleInData(array $data): bool
    {
        return filled($data['title_uk'] ?? null)
            || filled($data['title_en'] ?? null)
            || filled($data['title_ru'] ?? null);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('resolved_title')
                    ->label('Назва')
                    ->searchable(query: function ($query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('title_uk', 'like', "%{$search}%")
                                ->orWhere('title_en', 'like', "%{$search}%")
                                ->orWhere('title_ru', 'like', "%{$search}%");
                        });
                    }),

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
                    ->state(fn (MenuItem $record): string => $record->parent?->resolved_title ?: '—'),

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
                    ->using(function (array $data) {
                        return $this->getRelationship()->create($this->prepareData($data));
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(function (MenuItem $record, array $data) {
                        $record->update($this->prepareData($data));

                        return $record;
                    }),
                DeleteAction::make(),
            ]);
    }
}
<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function schema(): array
    {
        return [
            Section::make('Основна інформація')
                ->schema([
                    SelectTree::make('parent_id')
                        ->label('Батьківська категорія')
                        ->getTreeUsing(function ($record) {
                            return self::buildParentTree($record instanceof Category ? $record : null);
                        })
                        ->enableBranchNode()
                        ->searchable()
                        ->defaultOpenLevel(2)
                        ->placeholder('Коренева категорія')
                        ->emptyLabel('Нічого не знайдено')
                        ->helperText('Якщо не вибирати батька — це буде root категорія. Недоступні категорії одразу вимкнені.')
                        ->live()
                        ->afterStateHydrated(function (callable $set, callable $get, $record) {
                            if ($record instanceof Category && $record->exists) {
                                return;
                            }

                            if (blank($get('order'))) {
                                $set('order', self::getNextOrderForParent($get('parent_id')));
                            }
                        })
                        ->afterStateUpdated(function ($state, callable $set, callable $get, $record) {
                            if ($state) {
                                $parent = Category::find((int) $state);

                                if ($parent && ! $parent->canHaveChildren()) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Помилка')
                                        ->body("Категорія '{$parent->name_uk}' не може мати підкатегорій, бо має товари або характеристики.")
                                        ->send();

                                    $set('parent_id', null);

                                    return;
                                }

                                if ((bool) $get('is_container') === true) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Container скасовано')
                                        ->body('Контейнерна категорія може бути тільки root.')
                                        ->send();

                                    $set('is_container', false);
                                }
                            }

                            if (! ($record instanceof Category && $record->exists)) {
                                $set('order', self::getNextOrderForParent($state));
                            }
                        }),

                    TextInput::make('name_uk')
                        ->label('Назва (Українська)')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if ($get('auto_slug') !== false) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),

                    Checkbox::make('auto_slug')
                        ->label('Автоматичний slug')
                        ->default(true)
                        ->live()
                        ->dehydrated(false),

                    TextInput::make('slug')
                        ->label('URL slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->disabled(fn ($get) => $get('auto_slug') !== false)
                        ->dehydrated(),

                    TextInput::make('tecdoc_id')
                        ->label('TecDoc ID')
                        ->numeric()
                        ->unique(ignoreRecord: true),

                    TextInput::make('order')
                        ->label('Порядок')
                        ->numeric()
                        ->helperText('Для нової категорії підставляється автоматично як наступний номер серед дітей вибраного батька. За потреби можна змінити вручну.'),

                    FileUpload::make('image')
                        ->label('Зображення')
                        ->image()
                        ->directory('categories')
                        ->disk('public')
                        ->visibility('public')
                        ->imageEditor()
                        ->maxSize(2048),

                    Toggle::make('is_active')
                        ->label('Активна')
                        ->default(true),

                    Toggle::make('is_container')
                        ->label('Контейнерна категорія')
                        ->helperText('Container може бути тільки root, може мати дітей, але не може мати товари або характеристики.')
                        ->default(false)
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (! $state) {
                                return;
                            }

                            if ($get('parent_id')) {
                                Notification::make()
                                    ->danger()
                                    ->title('Неможливо')
                                    ->body('Контейнерна категорія може бути тільки root.')
                                    ->send();

                                $set('is_container', false);

                                return;
                            }

                            $id = $get('id');

                            if (! $id) {
                                return;
                            }

                            $cat = Category::find((int) $id);

                            if ($cat && ($cat->hasProducts() || $cat->hasCharacteristics())) {
                                Notification::make()
                                    ->danger()
                                    ->title('Неможливо')
                                    ->body("Категорія '{$cat->name_uk}' має товари або характеристики — container поставити не можна.")
                                    ->send();

                                $set('is_container', false);
                            }
                        }),

                    Toggle::make('is_leaf')
                        ->label('Кінцева (leaf)')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Оновлюється автоматично залежно від наявності дочірніх категорій.'),
                ])
                ->columns(1),

            Tabs::make('Мови')
                ->tabs([
                    Tabs\Tab::make('Українська')
                        ->schema([
                            Textarea::make('description_uk')
                                ->label('Опис')
                                ->rows(3)
                                ->maxLength(1000),

                            TextInput::make('meta_title_uk')
                                ->label('Meta Title')
                                ->maxLength(255),

                            Textarea::make('meta_description_uk')
                                ->label('Meta Description')
                                ->rows(2)
                                ->maxLength(500),
                        ]),

                    Tabs\Tab::make('English')
                        ->schema([
                            TextInput::make('name_en')
                                ->label('Name')
                                ->maxLength(255),

                            Textarea::make('description_en')
                                ->label('Description')
                                ->rows(3)
                                ->maxLength(1000),

                            TextInput::make('meta_title_en')
                                ->label('Meta Title')
                                ->maxLength(255),

                            Textarea::make('meta_description_en')
                                ->label('Meta Description')
                                ->rows(2)
                                ->maxLength(500),
                        ]),

                    Tabs\Tab::make('Русский')
                        ->schema([
                            TextInput::make('name_ru')
                                ->label('Название')
                                ->maxLength(255),

                            Textarea::make('description_ru')
                                ->label('Описание')
                                ->rows(3)
                                ->maxLength(1000),

                            TextInput::make('meta_title_ru')
                                ->label('Meta Title')
                                ->maxLength(255),

                            Textarea::make('meta_description_ru')
                                ->label('Meta Description')
                                ->rows(2)
                                ->maxLength(500),
                        ]),
                ]),
        ];
    }

    protected static function buildParentTree(?Category $record = null): array
    {
        $excludedIds = [];

        if ($record?->exists) {
            $excludedIds[] = (int) $record->id;

            $descendants = $record->descendantIds();

            foreach ($descendants as $descendantId) {
                $excludedIds[] = (int) $descendantId;
            }
        }

        $categories = Category::query()
            ->select([
                'id',
                'parent_id',
                'name_uk',
                'is_container',
                'is_leaf',
                'children_count',
                'products_total_count',
            ])
            ->orderBy('parent_id')
            ->orderBy('order')
            ->orderBy('name_uk')
            ->get();

        $grouped = $categories->groupBy(fn (Category $category) => $category->parent_id ?: 0);

        return self::buildTreeLevel(
            grouped: $grouped,
            parentId: 0,
            excludedIds: $excludedIds,
        );
    }

    protected static function buildTreeLevel(Collection $grouped, int $parentId, array $excludedIds): array
    {
        /** @var Collection<int, Category> $nodes */
        $nodes = $grouped->get($parentId, collect());

        return $nodes
            ->map(function (Category $category) use ($grouped, $excludedIds) {
                $id = (int) $category->id;
                $children = self::buildTreeLevel($grouped, $id, $excludedIds);

                return [
                    'name' => self::formatTreeNodeLabel($category),
                    'value' => (string) $id,
                    'hidden' => in_array($id, $excludedIds, true),
                    'disabled' => ! $category->canHaveChildren() || in_array($id, $excludedIds, true),
                    'children' => $children,
                ];
            })
            ->values()
            ->all();
    }

    protected static function formatTreeNodeLabel(Category $category): string
    {
        $icon = match (true) {
            (bool) $category->is_container => '📦',
            (bool) $category->is_leaf => '📄',
            default => '📁',
        };

        $badges = [];

        if ((bool) $category->is_container) {
            $badges[] = '[container]';
        } elseif ((bool) $category->is_leaf) {
            $badges[] = '[leaf]';
        } else {
            $badges[] = '[parent]';
        }

        if (! $category->canHaveChildren()) {
            $badges[] = '[no-children]';
        }

        $productsCount = (int) ($category->products_total_count ?? 0);

        return trim(sprintf(
            '%s %s (%d) %s',
            $icon,
            $category->name_uk,
            $productsCount,
            implode(' ', $badges),
        ));
    }

    protected static function getNextOrderForParent($parentId): int
    {
        return ((int) Category::query()
            ->where('parent_id', $parentId ?: null)
            ->max('order')) + 1;
    }
}
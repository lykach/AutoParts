<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use App\Models\Product;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Validation\Rule;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Product')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Основне')
                        ->schema([
                            Section::make('Ядро товару')
                                ->schema([
                                    SelectTree::make('category_id')
                                        ->label('Категорія (кінцева)')
                                        ->helperText('Показуємо дерево тільки активних. Обрати можна лише кінцеві (leaf) і не контейнерні.')
                                        ->query(
                                            fn () => Category::query()
                                                ->where('is_active', 1)
                                                ->orderBy('parent_id')
                                                ->orderBy('order'),
                                            'name_uk',
                                            'parent_id'
                                        )
                                        ->parentNullValue(-1)
                                        ->searchable()
                                        ->storeResults()
                                        ->disabledOptions(fn () => self::disabledCategoryIds())
                                        ->required()
                                        ->rules([
                                            Rule::exists('categories', 'id')
                                                ->where('is_active', 1)
                                                ->where('is_leaf', 1)
                                                ->where('is_container', 0),
                                        ]),

                                    Select::make('manufacturer_id')
                                        ->label('Виробник')
                                        ->relationship('manufacturer', 'name')
                                        ->preload()
                                        ->searchable()
                                        ->required(),

                                    TextInput::make('article_raw')
                                        ->label('Артикул (як є)')
                                        ->required()
                                        ->maxLength(128)
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            // ✅ пробіли зберігаємо, але робимо uppercase прямо в полі
                                            $upper = mb_strtoupper((string) $state, 'UTF-8');

                                            // повертаємо значення назад у поле
                                            $set('article_raw', $upper);

                                            // генеруємо очищений артикул для пошуку
                                            $set('article_norm', Product::normalizeArticle($upper));
                                        })
                                        ->helperText('Пробіли зберігаються. Для пошуку використовується "очищений" артикул.'),

                                    TextInput::make('article_norm')
                                        ->label('Артикул (очищений для пошуку)')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->helperText('Автоматично генерується з поля "Артикул (як є)".'),

                                    Toggle::make('is_active')
                                        ->label('Активний')
                                        ->default(true),
                                ])
                                ->columns(2),
                        ]),

                    Tab::make('Переклади')
                        ->schema([
                            Section::make('UK')->schema(self::translationFields('uk'))->columns(2),
                            Section::make('EN')->schema(self::translationFields('en'))->columns(2),
                            Section::make('RU')->schema(self::translationFields('ru'))->columns(2),
                        ]),

                    Tab::make('Доставка / Габарити')
                        ->schema([
                            Section::make('Дані для перевізників (Нова Пошта тощо)')
                                ->description('Одиниці: вага — кг, габарити — см. Необов’язково, але бажано для точного розрахунку доставки.')
                                ->schema([
                                    TextInput::make('weight_kg')
                                        ->label('Вага (кг)')
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.001)
                                        ->helperText('Напр. 0.350'),

                                    TextInput::make('length_cm')
                                        ->label('Довжина (см)')
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.1),

                                    TextInput::make('width_cm')
                                        ->label('Ширина (см)')
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.1),

                                    TextInput::make('height_cm')
                                        ->label('Висота (см)')
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.1),
                                ])
                                ->columns(2),
                        ]),

                    Tab::make('UUID')
                        ->schema([
                            Section::make('UUID товару')
                                ->description('Можна задати вручну. Або увімкни перемикач — тоді UUID буде згенеровано при збереженні, якщо поле пусте.')
                                ->schema([
                                    Toggle::make('uuid_auto')
                                        ->label('Згенерувати UUID автоматично')
                                        ->default(false)
                                        ->dehydrated(false)
                                        ->helperText('Якщо увімкнено і UUID порожній — згенерується при збереженні.'),

                                    TextInput::make('uuid')
                                        ->label('UUID')
                                        ->placeholder('Напр. 550e8400-e29b-41d4-a716-446655440000')
                                        ->maxLength(36)
                                        ->helperText('Якщо перемикач вимкнено і поле пусте — UUID буде пропущено (NULL).')
                                        ->rules([
                                            'nullable',
                                            'uuid',
                                        ]),
                                ])
                                ->columns(2),
                        ]),
                ]),
        ]);
    }

    private static function disabledCategoryIds(): array
    {
        return Category::query()
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->where('is_leaf', 0)
                    ->orWhere('is_container', 1);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public static function translationFields(string $locale): array
    {
        $p = $locale . '_';
        $isUk = $locale === 'uk';

        return [
            TextInput::make($p . 'name')
                ->label("Назва ({$locale})")
                ->maxLength(255)
                ->required($isUk),

            TextInput::make($p . 'slug')
                ->label("Slug ({$locale})")
                ->maxLength(255)
                ->helperText('Якщо залишиш порожнім — згенерується автоматично як {виробник}-{артикул}.'),

            Textarea::make($p . 'short_description')
                ->label("Короткий опис ({$locale})")
                ->columnSpanFull(),

            Textarea::make($p . 'description')
                ->label("Опис ({$locale})")
                ->columnSpanFull(),

            TextInput::make($p . 'meta_title')
                ->label("Meta title ({$locale})")
                ->maxLength(255),

            Textarea::make($p . 'meta_description')
                ->label("Meta description ({$locale})")
                ->columnSpanFull(),
        ];
    }
}
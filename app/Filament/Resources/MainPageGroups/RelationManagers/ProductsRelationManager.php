<?php

namespace App\Filament\Resources\MainPageGroups\RelationManagers;

use App\Models\Category;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Товари в блоці';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    protected function getNextSort(): int
    {
        return ((int) $this->getOwnerRecord()->items()->max('sort')) + 1;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query
                    ->with([
                        'product.translationUk',
                        'product.translationEn',
                        'product.translationRu',
                        'product.category',
                        'product.manufacturer',
                    ])
                    ->orderBy('sort')
                    ->orderBy('id');
            })
            ->defaultSort('sort')
            ->searchPlaceholder('Пошук по артикулу або назві')
            ->searchDebounce('400ms')
            ->columns([
                TextColumn::make('sort')
                    ->label('#')
                    ->sortable()
                    ->alignCenter()
                    ->width('70px'),

                TextColumn::make('product.article_raw')
                    ->label('Артикул')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('product', function (Builder $productQuery) use ($search) {
                            $productQuery
                                ->where('article_raw', 'like', "%{$search}%")
                                ->orWhere('article_norm', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('product.display_name')
                    ->label('Назва')
                    ->wrap()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('product.translations', function (Builder $translationQuery) use ($search) {
                            $translationQuery->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->description(fn (Model $record): string => $record->product?->category?->name_uk ?? '—'),

                TextColumn::make('product.manufacturer.name')
                    ->label('Бренд')
                    ->toggleable(),

                TextColumn::make('product.best_price_uah')
                    ->label('Ціна')
                    ->money('UAH')
                    ->sortable(),

                TextColumn::make('product.best_stock_qty')
                    ->label('Залишок')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('category_id')
                    ->label('Категорія')
                    ->schema([
                        Select::make('category_id')
                            ->label('Категорія')
                            ->placeholder('Всі категорії')
                            ->options(
                                Category::query()
                                    ->where('is_active', true)
                                    ->orderBy('name_uk')
                                    ->pluck('name_uk', 'id')
                                    ->toArray()
                            )
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $categoryId = $data['category_id'] ?? null;

                        if (blank($categoryId)) {
                            return $query;
                        }

                        return $query->whereHas('product', function (Builder $productQuery) use ($categoryId) {
                            $productQuery->where('category_id', $categoryId);
                        });
                    }),

                Filter::make('only_active_products')
                    ->label('Лише активні товари')
                    ->default()
                    ->query(fn (Builder $query): Builder =>
                        $query->whereHas('product', fn (Builder $productQuery) => $productQuery->where('is_active', true))
                    ),
            ])
            ->headerActions([
                Action::make('addProduct')
                    ->label('Додати товар')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Додати товар на головну')
                    ->modalDescription('Почни вводити артикул або назву товару. Пошук працює в реальному часі.')
                    ->modalWidth(Width::ThreeExtraLarge)
                    ->schema([
                        Select::make('product_id')
                            ->label('Товар')
                            ->placeholder('Почни вводити артикул або назву...')
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->searchDebounce(400)
                            ->getSearchResultsUsing(function (string $search): array {
                                return $this->searchProductsForSelect($search);
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                if (blank($value)) {
                                    return null;
                                }

                                return $this->getProductOptionLabel((int) $value);
                            })
                            ->helperText('Пошук по артикулу та назві. ID вводити не потрібно.'),
                    ])
                    ->action(function (array $data): void {
                        $productId = (int) ($data['product_id'] ?? 0);

                        if (! $productId) {
                            Notification::make()
                                ->title('Оберіть товар')
                                ->danger()
                                ->send();

                            return;
                        }

                        $product = Product::query()->find($productId);

                        if (! $product) {
                            Notification::make()
                                ->title('Товар не знайдено')
                                ->danger()
                                ->send();

                            return;
                        }

                        $exists = $this->getOwnerRecord()
                            ->items()
                            ->where('product_id', $product->id)
                            ->exists();

                        if ($exists) {
                            Notification::make()
                                ->title('Цей товар уже доданий у блок')
                                ->warning()
                                ->send();

                            return;
                        }

                        $this->getOwnerRecord()->items()->create([
                            'product_id' => $product->id,
                            'sort' => $this->getNextSort(),
                        ]);

                        Notification::make()
                            ->title('Товар додано до блоку')
                            ->success()
                            ->send();
                    }),

                Action::make('addPopularProductsAuto')
                    ->label('Додати популярні товари автоматично')
                    ->icon('heroicon-o-fire')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Функція буде доступна пізніше')
                    ->modalDescription('Автододавання популярних товарів увімкнемо після запуску модуля продажів або збору переглядів на фронтенді.')
                    ->action(function (): void {
                        Notification::make()
                            ->title('Поки недоступно')
                            ->body('Спочатку потрібно реалізувати продажі або збір переглядів/кліків.')
                            ->warning()
                            ->send();
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([10, 25, 50, 100]);
    }

    protected function searchProductsForSelect(string $search): array
    {
        $search = trim($search);

        if ($search === '') {
            return [];
        }

        return Product::query()
            ->with([
                'translationUk',
                'translationEn',
                'translationRu',
                'category',
                'manufacturer',
            ])
            ->where(function (Builder $query) use ($search) {
                $query
                    ->where('article_raw', 'like', "%{$search}%")
                    ->orWhere('article_norm', 'like', "%{$search}%")
                    ->orWhereHas('translations', function (Builder $translationQuery) use ($search) {
                        $translationQuery->where('name', 'like', "%{$search}%");
                    });
            })
            ->orderByRaw(
                "
                CASE
                    WHEN article_raw = ? THEN 0
                    WHEN article_norm = ? THEN 1
                    WHEN article_raw LIKE ? THEN 2
                    WHEN article_norm LIKE ? THEN 3
                    ELSE 4
                END
                ",
                [$search, $search, "{$search}%", "{$search}%"]
            )
            ->orderByDesc('is_active')
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Product $product): array => [
                $product->id => $this->formatProductOptionLabel($product),
            ])
            ->all();
    }

    protected function getProductOptionLabel(int $productId): ?string
    {
        $product = Product::query()
            ->with([
                'translationUk',
                'translationEn',
                'translationRu',
                'category',
                'manufacturer',
            ])
            ->find($productId);

        if (! $product) {
            return null;
        }

        return $this->formatProductOptionLabel($product);
    }

    protected function formatProductOptionLabel(Product $product): string
    {
        $article = $product->display_article;
        $name = $product->display_name;
        $category = $product->category?->name_uk ?: 'Без категорії';
        $manufacturer = $product->manufacturer?->name ?: 'Без бренду';

        $price = $product->best_price_uah !== null
            ? number_format((float) $product->best_price_uah, 2, '.', ' ') . ' грн'
            : 'без ціни';

        return "[{$article}] {$name} — {$manufacturer} / {$category} / {$price}";
    }
}
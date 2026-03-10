<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\Product;
use App\Models\ProductRelated;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class RelatedProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'relatedLinks';

    protected static ?string $title = 'Супутні товари';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->relatedLinks()->count();
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return 'info';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('related_product_id')
                ->label('Супутній товар')
                ->required()
                ->native(false)
                ->searchable()
                ->searchDebounce(400)
                ->live()
                ->placeholder('Почни вводити артикул або назву...')
                ->getSearchResultsUsing(function (string $search): array {
                    return $this->searchProductsForSelect($search);
                })
                ->getOptionLabelUsing(function ($value): ?string {
                    if (blank($value)) {
                        return null;
                    }

                    return $this->getProductOptionLabel((int) $value);
                })
                ->validationMessages([
                    'required' => 'Оберіть товар.',
                ])
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    if (! filled($state)) {
                        return;
                    }

                    $ownerId = (int) $this->getOwnerRecord()->id;
                    $selectedProductId = (int) $state;
                    $currentRelationId = $get('id');

                    if ($selectedProductId === $ownerId) {
                        Notification::make()
                            ->danger()
                            ->title('Помилка')
                            ->body('Не можна додати товар до самого себе.')
                            ->send();

                        $set('related_product_id', null);

                        return;
                    }

                    $exists = ProductRelated::query()
                        ->where('product_id', $ownerId)
                        ->where('related_product_id', $selectedProductId)
                        ->when(
                            filled($currentRelationId),
                            fn (Builder $query) => $query->whereKeyNot((int) $currentRelationId)
                        )
                        ->exists();

                    if ($exists) {
                        Notification::make()
                            ->warning()
                            ->title('Уже додано')
                            ->body('Цей товар уже є у списку супутніх.')
                            ->send();

                        $set('related_product_id', null);
                    }
                })
                ->helperText('Пошук по артикулу та назві. Дубль і додавання самого себе заборонені.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with([
                    'relatedProduct.translationUk:id,product_id,locale,name',
                    'relatedProduct.translationEn:id,product_id,locale,name',
                    'relatedProduct.translationRu:id,product_id,locale,name',
                    'relatedProduct.category:id,name_uk',
                    'relatedProduct.manufacturer:id,name',
                    'relatedProduct.primaryImage:id,product_id,image_path,is_primary,sort_order',
                ]);
            })
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('relatedProduct.primaryImage.image_path')
                    ->label('')
                    ->disk('public')
                    ->square()
                    ->size(42)
                    ->defaultImageUrl(url('/images/no_image.webp'))
                    ->extraImgAttributes([
                        'loading' => 'lazy',
                        'style' => 'object-fit: contain; background: #fff;',
                    ])
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->alignCenter()
                    ->width('70px'),

                Tables\Columns\TextColumn::make('relatedProduct.article_raw')
                    ->label('Артикул')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('relatedProduct', function (Builder $productQuery) use ($search) {
                            $productQuery
                                ->where('article_raw', 'like', "%{$search}%")
                                ->orWhere('article_norm', 'like', "%{$search}%");
                        });
                    })
                    ->copyable()
                    ->copyMessage('Артикул скопійовано')
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('relatedProduct.display_name')
                    ->label('Назва')
                    ->wrap()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('relatedProduct.translations', function (Builder $translationQuery) use ($search) {
                            $translationQuery->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->description(function (ProductRelated $record): ?string {
                        $parts = [];

                        if (filled($record->relatedProduct?->manufacturer?->name)) {
                            $parts[] = $record->relatedProduct->manufacturer->name;
                        }

                        if (filled($record->relatedProduct?->category?->name_uk)) {
                            $parts[] = $record->relatedProduct->category->name_uk;
                        }

                        return ! empty($parts) ? implode(' / ', $parts) : null;
                    }),

                Tables\Columns\TextColumn::make('relatedProduct.best_price_uah')
                    ->label('Ціна')
                    ->state(function (ProductRelated $record): string {
                        $price = $record->relatedProduct?->best_price_uah;

                        if ($price === null) {
                            return '—';
                        }

                        return number_format((float) $price, 2, '.', ' ') . ' грн';
                    }),

                Tables\Columns\TextColumn::make('relatedProduct.best_stock_qty')
                    ->label('Залишок')
                    ->state(function (ProductRelated $record): string {
                        $qty = $record->relatedProduct?->best_stock_qty;

                        if ($qty === null) {
                            return '—';
                        }

                        return rtrim(rtrim(number_format((float) $qty, 3, '.', ' '), '0'), '.');
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Додати товар')
                    ->mutateDataUsing(function (array $data): array {
                        $data['sort_order'] = $this->getNextSortOrder();

                        return $data;
                    })
                    ->using(function (array $data): Model {
                        $ownerId = (int) $this->getOwnerRecord()->id;
                        $relatedProductId = (int) ($data['related_product_id'] ?? 0);

                        $this->validateRelatedProduct($ownerId, $relatedProductId);

                        return $this->getRelationship()->create($data);
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->mutateDataUsing(function (array $data, Model $record): array {
                        $data['sort_order'] = (int) ($record->sort_order ?? $this->getNextSortOrder());

                        return $data;
                    })
                    ->using(function (Model $record, array $data): Model {
                        $ownerId = (int) $this->getOwnerRecord()->id;
                        $relatedProductId = (int) ($data['related_product_id'] ?? 0);

                        $this->validateRelatedProduct($ownerId, $relatedProductId, (int) $record->getKey());

                        $record->update($data);

                        return $record;
                    }),

                DeleteAction::make(),
            ]);
    }

    protected function getNextSortOrder(): int
    {
        return ((int) $this->getOwnerRecord()->relatedLinks()->max('sort_order')) + 1;
    }

    protected function validateRelatedProduct(int $ownerId, int $relatedProductId, ?int $ignoreId = null): void
    {
        if ($relatedProductId <= 0) {
            throw ValidationException::withMessages([
                'related_product_id' => 'Оберіть товар.',
            ]);
        }

        if ($relatedProductId === $ownerId) {
            Notification::make()
                ->danger()
                ->title('Помилка')
                ->body('Не можна додати товар до самого себе.')
                ->send();

            throw ValidationException::withMessages([
                'related_product_id' => 'Не можна додати товар до самого себе.',
            ]);
        }

        $exists = ProductRelated::query()
            ->where('product_id', $ownerId)
            ->where('related_product_id', $relatedProductId)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            Notification::make()
                ->warning()
                ->title('Уже додано')
                ->body('Цей товар уже є у списку супутніх.')
                ->send();

            throw ValidationException::withMessages([
                'related_product_id' => 'Цей товар уже доданий у супутні.',
            ]);
        }
    }

    protected function searchProductsForSelect(string $search): array
    {
        $search = trim($search);

        if ($search === '') {
            return [];
        }

        $ownerId = (int) $this->getOwnerRecord()->id;

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
            ->mapWithKeys(function (Product $product) use ($ownerId): array {
                $flags = [];

                if ((int) $product->id === $ownerId) {
                    $flags[] = 'САМ ТОВАР';
                }

                $alreadyAdded = ProductRelated::query()
                    ->where('product_id', $ownerId)
                    ->where('related_product_id', $product->id)
                    ->exists();

                if ($alreadyAdded) {
                    $flags[] = 'УЖЕ ДОДАНО';
                }

                return [
                    $product->id => $this->formatProductOptionLabel($product, $flags),
                ];
            })
            ->all();
    }

    protected function getProductOptionLabel(int $productId): ?string
    {
        $ownerId = (int) $this->getOwnerRecord()->id;

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

        $flags = [];

        if ((int) $product->id === $ownerId) {
            $flags[] = 'САМ ТОВАР';
        }

        $alreadyAdded = ProductRelated::query()
            ->where('product_id', $ownerId)
            ->where('related_product_id', $product->id)
            ->exists();

        if ($alreadyAdded) {
            $flags[] = 'УЖЕ ДОДАНО';
        }

        return $this->formatProductOptionLabel($product, $flags);
    }

    protected function formatProductOptionLabel(Product $product, array $flags = []): string
    {
        $article = $product->display_article;
        $name = $product->display_name;
        $category = $product->category?->name_uk ?: 'Без категорії';
        $manufacturer = $product->manufacturer?->name ?: 'Без бренду';

        $price = $product->best_price_uah !== null
            ? number_format((float) $product->best_price_uah, 2, '.', ' ') . ' грн'
            : 'без ціни';

        $flagsText = ! empty($flags) ? ' [' . implode(' | ', $flags) . ']' : '';

        return "[{$article}] {$name} — {$manufacturer} / {$category} / {$price}{$flagsText}";
    }
}
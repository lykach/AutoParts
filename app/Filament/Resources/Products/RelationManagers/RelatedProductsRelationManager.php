<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\Product;
use App\Models\ProductRelated;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RelatedProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'relatedLinks';

    protected static ?string $title = 'Супутні';

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
            Toggle::make('is_active')
                ->label('Активно')
                ->default(true),

            TextInput::make('sort_order')
                ->label('Порядок')
                ->numeric()
                ->default(null)
                ->helperText('Якщо не вказати — стане в кінець автоматично.'),

            Select::make('related_product_id')
                ->label('Супутній товар')
                ->required()
                ->searchable()
                ->preload(false)
                ->getSearchResultsUsing(function (string $search): array {
                    $search = trim($search);

                    return Product::query()
                        ->when($search !== '', function (Builder $q) use ($search) {
                            if (ctype_digit($search)) {
                                $q->where('id', (int) $search)
                                    ->orWhere('article_raw', 'like', "%{$search}%")
                                    ->orWhere('article_norm', 'like', "%{$search}%");
                                return;
                            }

                            $q->where('article_raw', 'like', "%{$search}%")
                                ->orWhere('article_norm', 'like', "%{$search}%")
                                ->orWhereHas('translations', function (Builder $t) use ($search) {
                                    $t->where('name', 'like', "%{$search}%");
                                });
                        })
                        ->with(['translations' => function ($t) {
                            $t->whereIn('locale', ['uk', 'en', 'ru']);
                        }])
                        ->limit(30)
                        ->get()
                        ->mapWithKeys(function (Product $p) {
                            return [$p->id => self::productLabel($p)];
                        })
                        ->all();
                })
                ->getOptionLabelUsing(function ($value): ?string {
                    if (! $value) return null;

                    $p = Product::query()
                        ->with(['translations' => fn ($t) => $t->whereIn('locale', ['uk', 'en', 'ru'])])
                        ->find($value);

                    return $p ? self::productLabel($p) : (string) $value;
                }),

            Textarea::make('note')
                ->label('Примітка')
                ->rows(2)
                ->maxLength(255)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // ✅ підтягуємо супутній товар + переклади + primaryImage
                return $query->with([
                    'relatedProduct' => function ($q) {
                        $q->with([
                            'translations' => fn ($t) => $t->whereIn('locale', ['uk', 'en', 'ru']),
                            'primaryImage:id,product_id,image_path,is_primary,sort_order,is_active',
                        ]);
                    },
                ]);
            })
            ->defaultSort('sort_order', 'asc')
            ->columns([
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->alignCenter(),

                // ✅ Фото супутнього
                Tables\Columns\ImageColumn::make('relatedProduct.primaryImage.image_path')
                    ->label('')
                    ->disk('public')
                    ->square()
                    ->height(44)
                    ->width(44)
                    ->defaultImageUrl(url('/images/no_image.webp'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('relatedProduct.id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('relatedProduct.article_raw')
                    ->label('Артикул')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Артикул скопійовано')
                    ->placeholder('—')
                    ->wrap(),

                Tables\Columns\TextColumn::make('related_name_all')
                    ->label('Назва (UK/EN/RU)')
                    ->state(function ($record) {
                        $p = $record->relatedProduct;
                        if (! $p) return '—';
                        return self::translationsLabel($p);
                    })
                    ->wrap()
                    ->limit(120),

                Tables\Columns\TextColumn::make('note')
                    ->label('Примітка')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Додати супутній')
                    ->mutateFormDataUsing(function (array $data): array {
                        // ✅ якщо порядок не заданий — ставимо в кінець
                        if (($data['sort_order'] ?? null) === null) {
                            $owner = $this->getOwnerRecord();
                            $max = (int) ($owner->relatedLinks()->max('sort_order') ?? 0);
                            $data['sort_order'] = $max + 1;
                        }
                        return $data;
                    }),

                Action::make('bulkAddIds')
                    ->label('Додати ID списком')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->form([
                        Textarea::make('ids')
                            ->label("ID товарів (кожен з нового рядка)")
                            ->rows(10)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $owner = $this->getOwnerRecord();
                        $lines = preg_split("/\r\n|\n|\r/", (string) ($data['ids'] ?? '')) ?: [];
                        $max = (int) ($owner->relatedLinks()->max('sort_order') ?? 0);

                        foreach ($lines as $line) {
                            $id = (int) trim((string) $line);
                            if ($id <= 0) continue;
                            if ($id === (int) $owner->id) continue;

                            $exists = ProductRelated::query()
                                ->where('product_id', $owner->id)
                                ->where('related_product_id', $id)
                                ->exists();

                            if ($exists) continue;

                            $max++;

                            ProductRelated::create([
                                'product_id' => $owner->id,
                                'related_product_id' => $id,
                                'sort_order' => $max,
                                'is_active' => true,
                            ]);
                        }
                    })
                    ->successNotificationTitle('Супутні додано'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    private static function productLabel(Product $p): string
    {
        $parts = [
            '#' . $p->id,
            $p->article_raw ? ('[' . $p->article_raw . ']') : null,
            self::translationsLabel($p),
        ];

        $label = trim(implode(' ', array_filter($parts)));

        return $label !== '' ? $label : ('#' . $p->id);
    }

    private static function translationsLabel(Product $p): string
    {
        $map = [];
        foreach ($p->translations ?? [] as $tr) {
            $loc = (string) ($tr->locale ?? '');
            $name = trim((string) ($tr->name ?? ''));
            if ($loc !== '' && $name !== '') {
                $map[$loc] = $name;
            }
        }

        $uk = $map['uk'] ?? null;
        $en = $map['en'] ?? null;
        $ru = $map['ru'] ?? null;

        $chunks = [];
        if ($uk) $chunks[] = "UK: {$uk}";
        if ($en) $chunks[] = "EN: {$en}";
        if ($ru) $chunks[] = "RU: {$ru}";

        return $chunks ? ('— ' . implode(' | ', $chunks)) : '';
    }
}

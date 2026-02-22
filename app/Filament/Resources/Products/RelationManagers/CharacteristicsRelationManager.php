<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\CharacteristicsProduct;
use App\Models\CharacteristicValue;
use App\Models\ProductCharacteristic;
use App\Models\ProductCharacteristicValue;
use App\Support\CharacteristicValueKey;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CharacteristicsRelationManager extends RelationManager
{
    protected static string $relationship = 'characteristics';
    protected static ?string $title = 'Характеристики (для фільтрів)';

    private function getCharacteristicType(?int $characteristicId): ?string
    {
        if (! $characteristicId) return null;

        return CharacteristicsProduct::query()
            ->whereKey($characteristicId)
            ->value('type');
    }

    private function isCharacteristicMultivalue(?int $characteristicId): bool
    {
        if (! $characteristicId) return false;

        return (bool) (CharacteristicsProduct::query()
            ->whereKey($characteristicId)
            ->value('is_multivalue') ?? false);
    }

    private function getCharacteristicDecimals(?int $characteristicId): int
    {
        if (! $characteristicId) return 0;

        return (int) (CharacteristicsProduct::query()
            ->whereKey($characteristicId)
            ->value('decimals') ?? 0);
    }

    private function allowedCharacteristicOptions(): array
    {
        $product = $this->getOwnerRecord();
        $categoryId = (int) ($product->category_id ?? 0);

        if (! $categoryId) {
            return CharacteristicsProduct::query()
                ->orderBy('name_uk')
                ->pluck('name_uk', 'id')
                ->toArray();
        }

        return CharacteristicsProduct::query()
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId))
            ->orderBy('name_uk')
            ->pluck('name_uk', 'id')
            ->toArray();
    }

    private function nextSortForProduct(int $productId): int
    {
        $max = ProductCharacteristic::query()
            ->where('product_id', $productId)
            ->max('sort');

        return ((int) $max) + 1;
    }

    /**
     * ✅ Sync multi values into product_characteristic_value
     */
    private function syncMultiValues(ProductCharacteristic $record, array $ids): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_values(array_filter($ids, fn ($v) => $v > 0));

        // чистимо старі
        ProductCharacteristicValue::query()
            ->where('product_id', $record->product_id)
            ->where('characteristic_id', $record->characteristic_id)
            ->delete();

        // вставляємо нові у вказаному порядку
        $pos = 1;
        foreach ($ids as $id) {
            ProductCharacteristicValue::create([
                'product_id' => $record->product_id,
                'characteristic_id' => $record->characteristic_id,
                'characteristic_value_id' => $id,
                'position' => $pos++,
                'source' => null,
            ]);
        }

        // для multi ми не тримаємо single id
        $record->forceFill(['characteristic_value_id' => null])->saveQuietly();
    }

    private function createOptionForm(): array
    {
        return [
            Toggle::make('auto_key')
                ->label('Автоключ (value_key)')
                ->default(true)
                ->dehydrated(false)
                ->live(),

            TextInput::make('value_key')
                ->label('Ключ (value_key)')
                ->maxLength(190)
                ->disabled(fn ($get) => (bool) $get('auto_key'))
                ->helperText('Унікальний в межах цієї характеристики.'),

            Textarea::make('value_uk')
                ->label('Значення (UK)')
                ->rows(2)
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set, $get) {
                    if (! $get('auto_key')) return;
                    if (! empty($get('value_key'))) return;
                    $set('value_key', CharacteristicValueKey::fromText($state, $get('value_en')));
                }),

            Textarea::make('value_en')
                ->label('Value (EN)')
                ->rows(2)
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set, $get) {
                    if (! $get('auto_key')) return;
                    if (! empty($get('value_key'))) return;
                    $set('value_key', CharacteristicValueKey::fromText($get('value_uk'), $state));
                }),

            Textarea::make('value_ru')
                ->label('Значение (RU)')
                ->rows(2)
                ->maxLength(255),

            TextInput::make('sort')
                ->label('Порядок')
                ->numeric()
                ->default(0)
                ->helperText('Якщо 0 — буде виставлено автоматично.'),

            Toggle::make('is_active')
                ->label('Активне')
                ->default(true),
        ];
    }

    private function createOptionUsing(array $data, int $cid): ?int
    {
        $auto = (bool) ($data['auto_key'] ?? true);
        $key = trim((string) ($data['value_key'] ?? ''));

        if ($auto && $key === '') {
            $key = CharacteristicValueKey::fromText($data['value_uk'] ?? null, $data['value_en'] ?? null);
        }
        if ($key === '') {
            Notification::make()->danger()->title('Помилка')->body('Не вдалося згенерувати key.')->send();
            return null;
        }

        $existsId = CharacteristicValue::query()
            ->where('characteristic_id', $cid)
            ->where('value_key', $key)
            ->value('id');

        if ($existsId) {
            Notification::make()->warning()->title('Вже існує')->body("Значення з key '{$key}' вже є.")->send();
            return (int) $existsId;
        }

        $sort = (int) ($data['sort'] ?? 0);
        if ($sort === 0) {
            $max = (int) (CharacteristicValue::query()->where('characteristic_id', $cid)->max('sort') ?? 0);
            $sort = $max + 1;
        }

        $cv = CharacteristicValue::create([
            'characteristic_id' => $cid,
            'value_key' => $key,
            'value_uk' => $data['value_uk'] ?? null,
            'value_en' => $data['value_en'] ?? null,
            'value_ru' => $data['value_ru'] ?? null,
            'sort' => $sort,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return (int) $cv->id;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Характеристика')
                ->schema([
                    Select::make('characteristic_id')
                        ->label('Назва характеристики')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(fn () => $this->allowedCharacteristicOptions())
                        ->live()
                        ->rules([
                            fn (?ProductCharacteristic $record) => Rule::unique('product_characteristics', 'characteristic_id')
                                ->where('product_id', $this->getOwnerRecord()->id)
                                ->ignore($record?->id),
                        ])
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('characteristic_value_id', null);
                            $set('characteristic_value_ids', []);
                            $set('value_text_uk', null);
                            $set('value_text_en', null);
                            $set('value_text_ru', null);
                            $set('value_number', null);
                            $set('value_bool', null);
                        }),

                    TextInput::make('sort')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0)
                        ->helperText('Якщо 0 — буде виставлено автоматично.'),
                ])
                ->columns(1),

            Section::make('Значення')
                ->schema([
                    // ✅ SELECT single
                    Select::make('characteristic_value_id')
                        ->label('Значення (select)')
                        ->searchable()
                        ->preload()
                        ->options(function ($get) {
                            $cid = (int) ($get('characteristic_id') ?? 0);
                            if (! $cid) return [];

                            return CharacteristicValue::query()
                                ->where('characteristic_id', $cid)
                                ->where('is_active', true)
                                ->orderBy('sort')
                                ->orderBy('id')
                                ->pluck('value_uk', 'id')
                                ->toArray();
                        })
                        ->visible(fn ($get) =>
                            $this->getCharacteristicType((int) ($get('characteristic_id') ?? 0)) === 'select'
                            && ! $this->isCharacteristicMultivalue((int) ($get('characteristic_id') ?? 0))
                        )
                        ->createOptionForm($this->createOptionForm())
                        ->createOptionUsing(function (array $data, $get) {
                            $cid = (int) ($get('characteristic_id') ?? 0);
                            if (! $cid) {
                                Notification::make()->danger()->title('Помилка')->body('Спочатку вибери характеристику.')->send();
                                return null;
                            }
                            return $this->createOptionUsing($data, $cid);
                        }),

                    // ✅ SELECT multi
                    Select::make('characteristic_value_ids')
                        ->label('Значення (select, multi)')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(function ($get) {
                            $cid = (int) ($get('characteristic_id') ?? 0);
                            if (! $cid) return [];

                            return CharacteristicValue::query()
                                ->where('characteristic_id', $cid)
                                ->where('is_active', true)
                                ->orderBy('sort')
                                ->orderBy('id')
                                ->pluck('value_uk', 'id')
                                ->toArray();
                        })
                        ->visible(fn ($get) =>
                            $this->getCharacteristicType((int) ($get('characteristic_id') ?? 0)) === 'select'
                            && $this->isCharacteristicMultivalue((int) ($get('characteristic_id') ?? 0))
                        )
                        ->formatStateUsing(function ($state, ?ProductCharacteristic $record) {
                            if (! $record) return [];

                            return DB::table('product_characteristic_value')
                                ->where('product_id', $record->product_id)
                                ->where('characteristic_id', $record->characteristic_id)
                                ->orderBy('position')
                                ->pluck('characteristic_value_id')
                                ->map(fn ($v) => (int) $v)
                                ->all();
                        })
                        ->createOptionForm($this->createOptionForm())
                        ->createOptionUsing(function (array $data, $get) {
                            $cid = (int) ($get('characteristic_id') ?? 0);
                            if (! $cid) {
                                Notification::make()->danger()->title('Помилка')->body('Спочатку вибери характеристику.')->send();
                                return null;
                            }
                            return $this->createOptionUsing($data, $cid);
                        })
                        ->helperText('Можна вибрати кілька значень — збережеться у product_characteristic_value.'),

                    TextInput::make('value_number')
                        ->label('Число')
                        ->numeric()
                        ->visible(fn ($get) => $this->getCharacteristicType((int) ($get('characteristic_id') ?? 0)) === 'number')
                        ->helperText(function ($get) {
                            $cid = (int) ($get('characteristic_id') ?? 0);
                            if (! $cid) return null;
                            $dec = $this->getCharacteristicDecimals($cid);
                            return $dec > 0 ? "Знаків після коми: {$dec}" : 'Ціле число';
                        }),

                    Toggle::make('value_bool')
                        ->label('Так/Ні')
                        ->visible(fn ($get) => $this->getCharacteristicType((int) ($get('characteristic_id') ?? 0)) === 'bool'),

                    Tabs::make('Текст (3 мови)')
                        ->tabs([
                            Tabs\Tab::make('UK')->schema([
                                TextInput::make('value_text_uk')->label('Значення (UK)')->maxLength(255),
                            ]),
                            Tabs\Tab::make('EN')->schema([
                                TextInput::make('value_text_en')->label('Value (EN)')->maxLength(255),
                            ]),
                            Tabs\Tab::make('RU')->schema([
                                TextInput::make('value_text_ru')->label('Значение (RU)')->maxLength(255),
                            ]),
                        ])
                        ->visible(fn ($get) => $this->getCharacteristicType((int) ($get('characteristic_id') ?? 0)) === 'text'),
                ])
                ->columns(1),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('characteristic.name_uk')
                    ->label('Характеристика')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('value')
                    ->label('Значення')
                    ->state(function (ProductCharacteristic $record) {
                        $record->loadMissing(['characteristic', 'characteristicValue', 'multiValues.value']);
                        return $record->getDisplayValue('uk');
                    })
                    ->wrap(),

                TextColumn::make('sort')->label('Порядок')->badge()->sortable(),
            ])
            ->defaultSort('sort', 'asc')
            ->headerActions([
                Action::make('addAllFromCategory')
                    ->label('Додати всі характеристики категорії')
                    ->icon('heroicon-o-squares-plus')
                    ->requiresConfirmation()
                    ->action(function () {
                        $product = $this->getOwnerRecord();
                        $category = $product->category;

                        if (! $category) {
                            Notification::make()->warning()->title('Нема категорії')->body('Спочатку вибери категорію товару.')->send();
                            return;
                        }

                        $rows = $category->characteristics()
                            ->withPivot(['sort'])
                            ->get();

                        if ($rows->isEmpty()) {
                            Notification::make()->info()->title('Нема характеристик')->body('Для цієї категорії не підключено характеристик.')->send();
                            return;
                        }

                        $existing = ProductCharacteristic::query()
                            ->where('product_id', $product->id)
                            ->pluck('characteristic_id')
                            ->map(fn ($v) => (int) $v)
                            ->all();

                        $added = 0;
                        $nextSort = $this->nextSortForProduct($product->id);

                        foreach ($rows as $ch) {
                            $cid = (int) $ch->id;
                            if (in_array($cid, $existing, true)) {
                                continue;
                            }

                            $sort = (int) ($ch->pivot->sort ?? 0);
                            if ($sort <= 0) {
                                $sort = $nextSort++;
                            }

                            ProductCharacteristic::create([
                                'product_id' => $product->id,
                                'characteristic_id' => $cid,
                                'sort' => $sort,
                            ]);

                            $added++;
                        }

                        Notification::make()
                            ->success()
                            ->title('Готово')
                            ->body("Додано характеристик: {$added}")
                            ->send();
                    }),

                CreateAction::make()
                    ->label('Додати характеристику')
                    ->mutateFormDataUsing(function (array $data) {
                        if (empty($data['sort']) || (int) $data['sort'] === 0) {
                            $data['sort'] = $this->nextSortForProduct($this->getOwnerRecord()->id);
                        }

                        // якщо multi — single поле не тримаємо
                        $cid = (int) ($data['characteristic_id'] ?? 0);
                        if ($cid && $this->getCharacteristicType($cid) === 'select' && $this->isCharacteristicMultivalue($cid)) {
                            $data['characteristic_value_id'] = null;
                        }

                        return $data;
                    })
                    ->after(function (ProductCharacteristic $record, array $data) {
                        $cid = (int) ($record->characteristic_id ?? 0);
                        if ($cid && $this->getCharacteristicType($cid) === 'select' && $this->isCharacteristicMultivalue($cid)) {
                            $ids = (array) ($data['characteristic_value_ids'] ?? []);
                            $this->syncMultiValues($record, $ids);
                        }
                        Notification::make()->success()->title('Готово')->send();
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        if (array_key_exists('sort', $data) && (int) $data['sort'] === 0) {
                            $data['sort'] = $this->nextSortForProduct($this->getOwnerRecord()->id);
                        }

                        $cid = (int) ($data['characteristic_id'] ?? 0);
                        if ($cid && $this->getCharacteristicType($cid) === 'select' && $this->isCharacteristicMultivalue($cid)) {
                            $data['characteristic_value_id'] = null;
                        }

                        return $data;
                    })
                    ->after(function (ProductCharacteristic $record, array $data) {
                        $cid = (int) ($record->characteristic_id ?? 0);
                        if ($cid && $this->getCharacteristicType($cid) === 'select' && $this->isCharacteristicMultivalue($cid)) {
                            $ids = (array) ($data['characteristic_value_ids'] ?? []);
                            $this->syncMultiValues($record, $ids);
                        }
                        Notification::make()->success()->title('Збережено')->send();
                    }),

                DeleteAction::make()
                    ->after(function (ProductCharacteristic $record) {
                        // чистимо pivot multi
                        ProductCharacteristicValue::query()
                            ->where('product_id', $record->product_id)
                            ->where('characteristic_id', $record->characteristic_id)
                            ->delete();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function ($records) {
                            foreach ($records as $rec) {
                                /** @var ProductCharacteristic $rec */
                                ProductCharacteristicValue::query()
                                    ->where('product_id', $rec->product_id)
                                    ->where('characteristic_id', $rec->characteristic_id)
                                    ->delete();
                            }
                        }),
                ]),
            ])
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(50);
    }
}
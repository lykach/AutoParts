<?php

namespace App\Filament\Resources\Categories\RelationManagers;

use App\Models\Category;
use App\Models\CharacteristicsProduct;
use App\Models\CharacteristicValue;
use App\Models\Product;
use App\Models\ProductCharacteristic;
use App\Models\ProductCharacteristicValue;
use App\Models\ProductDetail;
use App\Support\CharacteristicValueKey;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryCharacteristicsRelationManager extends RelationManager
{
    protected static string $relationship = 'characteristics';
    protected static ?string $title = 'Характеристики категорії';

    /**
     * ✅ Показуємо RelationManager ТІЛЬКИ для кінцевих (leaf) категорій, які НЕ container.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (! $ownerRecord instanceof Category) {
            return false;
        }

        return (bool) ($ownerRecord->is_active ?? true)
            && (bool) ($ownerRecord->is_leaf ?? false)
            && ! (bool) ($ownerRecord->is_container ?? false);
    }

    private function ensureLeafCategory(): bool
    {
        /** @var Category $cat */
        $cat = $this->getOwnerRecord();

        $ok = (bool) ($cat->is_active ?? true)
            && (bool) ($cat->is_leaf ?? false)
            && ! (bool) ($cat->is_container ?? false);

        if (! $ok) {
            Notification::make()
                ->warning()
                ->title('Недоступно')
                ->body('Характеристики можна налаштовувати тільки для активної кінцевої (leaf) категорії, яка НЕ є контейнером.')
                ->send();
        }

        return $ok;
    }

    protected function getTableQuery(): Builder
    {
        $rel = $this->getRelationship();

        if (! $rel instanceof Relation) {
            return CharacteristicsProduct::query()->whereRaw('1=0');
        }

        $pivot = 'category_characteristic';

        return $rel->getQuery()
            ->select('characteristics_products.*')
            ->addSelect([
                "{$pivot}.sort as cc_sort",
                "{$pivot}.is_visible as cc_is_visible",
                "{$pivot}.is_filterable as cc_is_filterable",
            ])
            ->withCount('values');
    }

    private function categoryId(): int
    {
        return (int) $this->getOwnerRecord()->id;
    }

    private function nextSort(): int
    {
        $max = DB::table('category_characteristic')
            ->where('category_id', $this->categoryId())
            ->max('sort');

        return ((int) $max) + 1;
    }

    private function triStateOptions(string $inheritLabel): array
    {
        return [
            '__inherit__' => $inheritLabel,
            '1' => 'Так',
            '0' => 'Ні',
        ];
    }

    private function normalizeTriState($value): ?int
    {
        if ($value === '__inherit__' || $value === null || $value === '') return null;
        if ((string) $value === '1') return 1;
        if ((string) $value === '0') return 0;
        return null;
    }

    private function renderTriState($state): string
    {
        if ($state === null || $state === '') return 'Успадкувати';
        return ((int) $state) === 1 ? 'Так' : 'Ні';
    }

    private function detailNameOptions(): array
    {
        $categoryId = $this->categoryId();

        $rows = DB::table('product_details as d')
            ->join('products as p', 'p.id', '=', 'd.product_id')
            ->where('p.category_id', $categoryId)
            ->select([
                DB::raw("COALESCE(NULLIF(TRIM(d.name_uk),''), NULLIF(TRIM(d.name_en),''), NULLIF(TRIM(d.name_ru),'')) as name_any"),
            ])
            ->whereNotNull(DB::raw("COALESCE(NULLIF(TRIM(d.name_uk),''), NULLIF(TRIM(d.name_en),''), NULLIF(TRIM(d.name_ru),''))"))
            ->distinct()
            ->orderBy('name_any')
            ->pluck('name_any')
            ->all();

        $out = [];
        foreach ($rows as $name) {
            $name = trim((string) $name);
            if ($name === '') continue;
            $out[$name] = $name;
        }

        return $out;
    }

    private function makeCodeFromName(string $name): string
    {
        $slug = Str::slug($name, '_');
        $slug = preg_replace('/_+/', '_', (string) $slug) ?: 'attr';
        $slug = trim($slug, '_');

        if ($slug === '') $slug = 'attr';
        $slug = preg_replace('/[^a-z0-9_]+/', '', $slug) ?: 'attr';

        return $slug;
    }

    private function guessTypeFromValues(array $values): string
    {
        $vals = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $values), fn ($v) => $v !== ''));
        if (empty($vals)) return 'text';

        $num = 0;
        foreach ($vals as $v) {
            $v2 = str_replace([' ', ','], ['', '.'], $v);
            if (preg_match('/^-?\d+(\.\d+)?$/', $v2)) $num++;
        }

        if ($num / max(1, count($vals)) >= 0.8) return 'number';

        return 'select';
    }

    private function splitMaybeMulti(string $value): array
    {
        $value = trim($value);
        if ($value === '') return [];

        $parts = preg_split('/\s*(?:,|;|\||\/)\s*/u', $value) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), fn ($v) => $v !== ''));

        return $parts ?: [$value];
    }

    private function ensureCharacteristicValue(int $characteristicId, string $textUk): ?int
    {
        $textUk = trim($textUk);
        if ($textUk === '') return null;

        $key = CharacteristicValueKey::fromText($textUk, null);
        if ($key === null || $key === '') {
            $key = Str::slug($textUk, '_');
        }
        $key = trim((string) $key);
        if ($key === '') return null;

        $exists = CharacteristicValue::query()
            ->where('characteristic_id', $characteristicId)
            ->where('value_key', $key)
            ->value('id');

        if ($exists) return (int) $exists;

        $maxSort = (int) (CharacteristicValue::query()->where('characteristic_id', $characteristicId)->max('sort') ?? 0);

        $cv = CharacteristicValue::create([
            'characteristic_id' => $characteristicId,
            'value_key' => $key,
            'value_uk' => $textUk,
            'value_en' => null,
            'value_ru' => null,
            'sort' => $maxSort + 1,
            'is_active' => true,
        ]);

        return (int) $cv->id;
    }

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cc_sort')
                    ->label('Порядок')
                    ->badge()
                    ->sortable(query: fn (Builder $q, string $dir) => $q->orderBy('category_characteristic.sort', $dir)),

                TextColumn::make('code')
                    ->label('Код')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name_uk')
                    ->label('Характеристика (UK)')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'text'   => 'Текст',
                        'number' => 'Число',
                        'bool'   => 'Так/Ні',
                        'select' => 'Список',
                        default  => (string) $state,
                    }),

                TextColumn::make('values_count')
                    ->label('Значень')
                    ->badge()
                    ->sortable(),

                TextColumn::make('cc_is_visible')
                    ->label('Видимість (override)')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $this->renderTriState($state)),

                TextColumn::make('cc_is_filterable')
                    ->label('Фільтр (override)')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $this->renderTriState($state)),
            ])
            ->defaultSort('category_characteristic.sort', 'asc')

            ->headerActions([
                Action::make('importFromProductDetails')
                    ->label('Імпортувати з ProductDetail')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn () => $this->ensureLeafCategory())
                    ->modalHeading('Імпорт властивостей (product_details) у характеристики + фільтри')
                    ->form([
                        Select::make('names')
                            ->label('Які назви деталей імпортувати?')
                            ->multiple()
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(fn () => $this->detailNameOptions())
                            ->helperText('Показує унікальні назви product_details по товарах цієї категорії.'),

                        Toggle::make('attach_to_category')
                            ->label('Прикріпити до категорії')
                            ->default(true),

                        Toggle::make('set_filterable')
                            ->label('Зробити фільтром (за замовчуванням)')
                            ->default(true),

                        Toggle::make('set_visible')
                            ->label('Відображати на фронтенді (за замовчуванням)')
                            ->default(true),

                        Toggle::make('allow_multivalue')
                            ->label('Дозволити multi-value (якщо значення розділені комами/слешами)')
                            ->default(false),

                        Toggle::make('overwrite_existing')
                            ->label('Перезаписувати існуючі значення у товарах')
                            ->default(false),
                    ])
                    ->action(function (array $data) {
                        if (! $this->ensureLeafCategory()) return;

                        $categoryId = $this->categoryId();
                        $names = array_values(array_filter((array) ($data['names'] ?? []), fn ($v) => trim((string) $v) !== ''));

                        if (empty($names)) {
                            Notification::make()->warning()->title('Нічого не вибрано')->send();
                            return;
                        }

                        $attach = (bool) ($data['attach_to_category'] ?? true);
                        $defaultFilterable = (bool) ($data['set_filterable'] ?? true);
                        $defaultVisible = (bool) ($data['set_visible'] ?? true);
                        $allowMulti = (bool) ($data['allow_multivalue'] ?? false);
                        $overwrite = (bool) ($data['overwrite_existing'] ?? false);

                        $products = Product::query()
                            ->where('category_id', $categoryId)
                            ->pluck('id')
                            ->map(fn ($v) => (int) $v)
                            ->all();

                        if (empty($products)) {
                            Notification::make()->warning()->title('Нема товарів')->body('У цій категорії немає товарів.')->send();
                            return;
                        }

                        $created = 0;
                        $attached = 0;
                        $filled = 0;

                        DB::beginTransaction();
                        try {
                            foreach ($names as $name) {
                                $name = trim((string) $name);
                                if ($name === '') continue;

                                $vals = DB::table('product_details as d')
                                    ->whereIn('d.product_id', $products)
                                    ->where(function ($q) use ($name) {
                                        $q->where('d.name_uk', $name)
                                          ->orWhere('d.name_en', $name)
                                          ->orWhere('d.name_ru', $name);
                                    })
                                    ->pluck('d.value_uk')
                                    ->all();

                                $guessType = $this->guessTypeFromValues($vals);
                                $code = $this->makeCodeFromName($name);

                                $base = $code;
                                $i = 2;
                                while (CharacteristicsProduct::query()->where('code', $code)->exists()) {
                                    $existing = CharacteristicsProduct::query()->where('code', $code)->first();
                                    if ($existing && trim((string) $existing->name_uk) === $name) break;
                                    $code = $base . '_' . $i;
                                    $i++;
                                }

                                $ch = CharacteristicsProduct::query()->firstOrCreate(
                                    ['code' => $code],
                                    [
                                        'sort' => 0,
                                        'group_uk' => 'Властивості (для картки товару)',
                                        'group_en' => null,
                                        'group_ru' => null,
                                        'name_uk' => $name,
                                        'name_en' => null,
                                        'name_ru' => null,
                                        'type' => $guessType === 'number' ? 'number' : ($guessType === 'select' ? 'select' : 'text'),
                                        'is_multivalue' => ($guessType === 'select') ? $allowMulti : false,
                                        'unit' => null,
                                        'decimals' => 0,
                                        'min_value' => null,
                                        'max_value' => null,
                                        'is_filterable' => $defaultFilterable,
                                        'is_visible' => $defaultVisible,
                                        'is_important' => false,
                                        'synonyms' => null,
                                    ]
                                );

                                if ($ch->wasRecentlyCreated) $created++;

                                if ($attach) {
                                    $exists = DB::table('category_characteristic')
                                        ->where('category_id', $categoryId)
                                        ->where('characteristic_id', (int) $ch->id)
                                        ->exists();

                                    if (! $exists) {
                                        DB::table('category_characteristic')->insert([
                                            'category_id' => $categoryId,
                                            'characteristic_id' => (int) $ch->id,
                                            'sort' => $this->nextSort(),
                                            'is_visible' => null,
                                            'is_filterable' => null,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);
                                        $attached++;
                                    }
                                }

                                $details = ProductDetail::query()
                                    ->whereIn('product_id', $products)
                                    ->where(function ($q) use ($name) {
                                        $q->where('name_uk', $name)
                                          ->orWhere('name_en', $name)
                                          ->orWhere('name_ru', $name);
                                    })
                                    ->get();

                                $byProduct = $details->groupBy('product_id');

                                foreach ($byProduct as $pid => $rows) {
                                    $pid = (int) $pid;

                                    $pc = ProductCharacteristic::query()
                                        ->where('product_id', $pid)
                                        ->where('characteristic_id', (int) $ch->id)
                                        ->first();

                                    if ($pc && ! $overwrite) continue;

                                    if (! $pc) {
                                        $pc = ProductCharacteristic::create([
                                            'product_id' => $pid,
                                            'characteristic_id' => (int) $ch->id,
                                            'sort' => 0,
                                        ]);
                                    }

                                    $val = trim((string) ($rows->first()?->value_uk ?? $rows->first()?->value_en ?? $rows->first()?->value_ru ?? ''));
                                    if ($val === '') continue;

                                    $type = (string) ($ch->type ?? 'text');

                                    if ($type === 'number') {
                                        $v2 = str_replace([' ', ','], ['', '.'], $val);
                                        $num = is_numeric($v2) ? (float) $v2 : null;

                                        $pc->forceFill([
                                            'characteristic_value_id' => null,
                                            'value_text_uk' => null,
                                            'value_text_en' => null,
                                            'value_text_ru' => null,
                                            'value_number' => $num,
                                            'value_bool' => null,
                                        ])->save();

                                        $filled++;
                                        continue;
                                    }

                                    if ($type === 'bool') {
                                        $lower = mb_strtolower($val, 'UTF-8');
                                        $bool = null;
                                        if (in_array($lower, ['1','true','так','yes','y','on'], true)) $bool = true;
                                        if (in_array($lower, ['0','false','ні','no','n','off'], true)) $bool = false;

                                        $pc->forceFill([
                                            'characteristic_value_id' => null,
                                            'value_text_uk' => null,
                                            'value_text_en' => null,
                                            'value_text_ru' => null,
                                            'value_number' => null,
                                            'value_bool' => $bool,
                                        ])->save();

                                        $filled++;
                                        continue;
                                    }

                                    if ($type === 'select') {
                                        $parts = $allowMulti && (bool) $ch->is_multivalue
                                            ? $this->splitMaybeMulti($val)
                                            : [$val];

                                        $ids = [];
                                        foreach ($parts as $p) {
                                            $id = $this->ensureCharacteristicValue((int) $ch->id, $p);
                                            if ($id) $ids[] = $id;
                                        }

                                        if (empty($ids)) continue;

                                        if ((bool) $ch->is_multivalue) {
                                            ProductCharacteristicValue::query()
                                                ->where('product_id', $pid)
                                                ->where('characteristic_id', (int) $ch->id)
                                                ->delete();

                                            $pos = 1;
                                            foreach (array_values(array_unique($ids)) as $id) {
                                                ProductCharacteristicValue::create([
                                                    'product_id' => $pid,
                                                    'characteristic_id' => (int) $ch->id,
                                                    'characteristic_value_id' => (int) $id,
                                                    'position' => $pos++,
                                                    'source' => null,
                                                ]);
                                            }

                                            $pc->forceFill([
                                                'characteristic_value_id' => null,
                                                'value_text_uk' => null,
                                                'value_text_en' => null,
                                                'value_text_ru' => null,
                                                'value_number' => null,
                                                'value_bool' => null,
                                            ])->save();

                                            $filled++;
                                            continue;
                                        }

                                        $pc->forceFill([
                                            'characteristic_value_id' => (int) $ids[0],
                                            'value_text_uk' => null,
                                            'value_text_en' => null,
                                            'value_text_ru' => null,
                                            'value_number' => null,
                                            'value_bool' => null,
                                        ])->save();

                                        $filled++;
                                        continue;
                                    }

                                    $pc->forceFill([
                                        'characteristic_value_id' => null,
                                        'value_text_uk' => $val,
                                        'value_text_en' => null,
                                        'value_text_ru' => null,
                                        'value_number' => null,
                                        'value_bool' => null,
                                    ])->save();

                                    $filled++;
                                }
                            }

                            DB::commit();
                        } catch (\Throwable $e) {
                            DB::rollBack();
                            report($e);

                            Notification::make()
                                ->danger()
                                ->title('Помилка імпорту')
                                ->body($e->getMessage())
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->success()
                            ->title('Готово')
                            ->body("Створено характеристик: {$created}\nПрикріплено до категорії: {$attached}\nЗаповнено значень у товарах: {$filled}")
                            ->send();
                    }),

                Action::make('addCharacteristic')
                    ->label('Додати характеристику')
                    ->icon('heroicon-o-plus-circle')
                    ->visible(fn () => $this->ensureLeafCategory())
                    ->form([
                        Select::make('characteristic_id')
                            ->label('Характеристика')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(fn () => CharacteristicsProduct::query()
                                ->orderBy('name_uk')
                                ->pluck('name_uk', 'id')
                                ->toArray()
                            ),

                        TextInput::make('sort')
                            ->label('Порядок')
                            ->numeric()
                            ->default(fn () => $this->nextSort())
                            ->helperText('Якщо 0/порожньо — буде автоматично.'),

                        Select::make('is_visible')
                            ->label('Видимість (override)')
                            ->options(fn () => $this->triStateOptions('Успадкувати (як у характеристики)'))
                            ->default('__inherit__'),

                        Select::make('is_filterable')
                            ->label('Фільтр (override)')
                            ->options(fn () => $this->triStateOptions('Успадкувати (як у характеристики)'))
                            ->default('__inherit__'),
                    ])
                    ->action(function (array $data) {
                        if (! $this->ensureLeafCategory()) return;

                        $category = $this->getOwnerRecord();
                        $cid = (int) ($data['characteristic_id'] ?? 0);

                        if (! $cid) {
                            Notification::make()->danger()->title('Помилка')->body('Не вибрано характеристику.')->send();
                            return;
                        }

                        $exists = DB::table('category_characteristic')
                            ->where('category_id', $category->id)
                            ->where('characteristic_id', $cid)
                            ->exists();

                        if ($exists) {
                            Notification::make()->warning()->title('Вже існує')->body('Ця характеристика вже прикріплена до категорії.')->send();
                            return;
                        }

                        $sort = (int) ($data['sort'] ?? 0);
                        if ($sort <= 0) $sort = $this->nextSort();

                        $category->characteristics()->attach($cid, [
                            'sort' => $sort,
                            'is_visible' => $this->normalizeTriState($data['is_visible'] ?? null),
                            'is_filterable' => $this->normalizeTriState($data['is_filterable'] ?? null),
                        ]);

                        Notification::make()->success()->title('Додано')->send();
                    }),

                Action::make('normalizeSort')
                    ->label('Нормалізувати порядок')
                    ->icon('heroicon-o-bars-arrow-down')
                    ->visible(fn () => $this->ensureLeafCategory())
                    ->requiresConfirmation()
                    ->action(function () {
                        if (! $this->ensureLeafCategory()) return;

                        $categoryId = $this->categoryId();

                        $ids = DB::table('category_characteristic')
                            ->where('category_id', $categoryId)
                            ->orderBy('sort')
                            ->orderBy('characteristic_id')
                            ->pluck('characteristic_id')
                            ->all();

                        $i = 1;
                        foreach ($ids as $cid) {
                            DB::table('category_characteristic')
                                ->where('category_id', $categoryId)
                                ->where('characteristic_id', (int) $cid)
                                ->update(['sort' => $i]);
                            $i++;
                        }

                        Notification::make()->success()->title('Готово')->send();
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->label('Налаштувати')
                    ->visible(fn () => $this->ensureLeafCategory())
                    ->modalHeading('Налаштування характеристики в категорії')
                    ->form([
                        TextInput::make('pivot.sort')
                            ->label('Порядок')
                            ->numeric()
                            ->formatStateUsing(fn ($state, $record) => $record?->pivot?->sort)
                            ->dehydrated(fn ($state) => $state !== null && trim((string) $state) !== '')
                            ->helperText('Залиш порожнім — порядок не зміниться.'),

                        Select::make('pivot.is_visible')
                            ->label('Видимість (override)')
                            ->options(fn () => $this->triStateOptions('Успадкувати (як у характеристики)'))
                            ->default(fn ($record) => $record->pivot->is_visible === null ? '__inherit__' : (string) (int) $record->pivot->is_visible)
                            ->dehydrated(true),

                        Select::make('pivot.is_filterable')
                            ->label('Фільтр (override)')
                            ->options(fn () => $this->triStateOptions('Успадкувати (як у характеристики)'))
                            ->default(fn ($record) => $record->pivot->is_filterable === null ? '__inherit__' : (string) (int) $record->pivot->is_filterable)
                            ->dehydrated(true),
                    ])
                    ->mutateFormDataUsing(function (array $data, $record) {
                        $currentSort = (int) ($record?->pivot?->sort ?? 0);

                        if (! isset($data['pivot']['sort']) || trim((string) $data['pivot']['sort']) === '') {
                            $data['pivot']['sort'] = $currentSort > 0 ? $currentSort : $this->nextSort();
                        } else {
                            $sort = (int) $data['pivot']['sort'];
                            $data['pivot']['sort'] = $sort > 0 ? $sort : ($currentSort > 0 ? $currentSort : $this->nextSort());
                        }

                        $data['pivot']['is_visible'] = $this->normalizeTriState($data['pivot']['is_visible'] ?? null);
                        $data['pivot']['is_filterable'] = $this->normalizeTriState($data['pivot']['is_filterable'] ?? null);

                        return $data;
                    })
                    ->after(function ($record, array $data) {
                        $record->categories()->updateExistingPivot(
                            $this->categoryId(),
                            [
                                'sort' => (int) ($data['pivot']['sort'] ?? 0),
                                'is_visible' => $data['pivot']['is_visible'],
                                'is_filterable' => $data['pivot']['is_filterable'],
                            ]
                        );

                        Notification::make()->success()->title('Оновлено')->send();
                    }),

                Action::make('detachOne')
                    ->label('Відкріпити')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn () => $this->ensureLeafCategory())
                    ->requiresConfirmation()
                    ->action(function (CharacteristicsProduct $record) {
                        if (! $this->ensureLeafCategory()) return;

                        DB::table('category_characteristic')
                            ->where('category_id', $this->categoryId())
                            ->where('characteristic_id', (int) $record->id)
                            ->delete();

                        Notification::make()->success()->title('Відкріплено')->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('detachSelected')
                        ->label('Відкріпити вибрані')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->visible(fn () => $this->ensureLeafCategory())
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            if (! $this->ensureLeafCategory()) return;

                            $categoryId = $this->categoryId();

                            $ids = collect($records)
                                ->map(function ($r) {
                                    if (is_numeric($r)) return (int) $r;
                                    if (is_array($r) && isset($r['id'])) return (int) $r['id'];
                                    if (is_object($r) && isset($r->id)) return (int) $r->id;
                                    return null;
                                })
                                ->filter()
                                ->values()
                                ->all();

                            if (empty($ids)) {
                                Notification::make()->warning()->title('Нічого не вибрано')->send();
                                return;
                            }

                            DB::table('category_characteristic')
                                ->where('category_id', $categoryId)
                                ->whereIn('characteristic_id', $ids)
                                ->delete();

                            Notification::make()->success()->title('Від’єднано')->send();
                        }),
                ]),
            ])
            ->striped()
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(50);
    }
}
<?php

namespace App\Filament\Resources\CharacteristicsProduct\RelationManagers;

use App\Models\CharacteristicValue;
use App\Support\CharacteristicValueKey;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class ValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'values';
    protected static ?string $title = 'Значення';

    private function ownerType(): string
    {
        return (string) ($this->getOwnerRecord()->type ?? 'text');
    }

    private function ownerDecimals(): int
    {
        return (int) ($this->getOwnerRecord()->decimals ?? 0);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Службове')
                ->schema([
                    Toggle::make('auto_key')
                        ->label('Автоключ (value_key)')
                        ->default(true)
                        ->dehydrated(false)
                        ->live(),

                    TextInput::make('value_key')
                        ->label('Ключ (value_key)')
                        ->helperText('Унікальний в межах цієї характеристики.')
                        ->maxLength(190)
                        ->disabled(fn ($get) => (bool) $get('auto_key'))
                        ->rules([
                            fn (?CharacteristicValue $record) => Rule::unique('characteristic_values', 'value_key')
                                ->where('characteristic_id', $this->getOwnerRecord()->id)
                                ->ignore($record?->id),
                        ]),

                    TextInput::make('sort')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0),

                    Toggle::make('is_active')
                        ->label('Активне')
                        ->default(true),
                ])
                ->columns(2),

            // -------- TEXT / SELECT (3 мови) --------
            Tabs::make('Мови (для text/select)')
                ->tabs([
                    Tabs\Tab::make('Українська')
                        ->schema([
                            Textarea::make('value_uk')
                                ->label('Значення (UA)')
                                ->rows(2)
                                ->maxLength(255)
                                ->required(fn () => in_array($this->ownerType(), ['text', 'select'], true))
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, $get) {
                                    if (! $get('auto_key')) return;
                                    if (! empty($get('value_key'))) return;

                                    $set('value_key', CharacteristicValueKey::fromText($state, $get('value_en')));
                                }),
                        ]),

                    Tabs\Tab::make('English')
                        ->schema([
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
                        ]),

                    Tabs\Tab::make('Русский')
                        ->schema([
                            Textarea::make('value_ru')
                                ->label('Значение (RU)')
                                ->rows(2)
                                ->maxLength(255),
                        ]),
                ])
                ->visible(fn () => in_array($this->ownerType(), ['text', 'select'], true)),

            // -------- NUMBER --------
            Section::make('Число (для type=number)')
                ->schema([
                    TextInput::make('value_number')
                        ->label('Число (value_number)')
                        ->numeric()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            if (! $get('auto_key')) return;
                            if (! empty($get('value_key'))) return;

                            $key = CharacteristicValueKey::fromNumber($state, $this->ownerDecimals());
                            if ($key !== null) {
                                $set('value_key', $key);
                            }
                        }),
                ])
                ->visible(fn () => $this->ownerType() === 'number'),

            // -------- BOOL --------
            Section::make('Так/Ні (для type=bool)')
                ->schema([
                    Toggle::make('value_bool')
                        ->label('Так/Ні (value_bool)')
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            if (! $get('auto_key')) return;
                            if (! empty($get('value_key'))) return;

                            if ($state === null) return;
                            $set('value_key', $state ? '1' : '0');
                        }),
                ])
                ->visible(fn () => $this->ownerType() === 'bool'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('value_uk')
                    ->label('Значення (UA)')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->visible(fn () => in_array($this->ownerType(), ['text', 'select'], true)),

                TextColumn::make('value_key')
                    ->label('Ключ')
                    ->badge()
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('value_number')
                    ->label('Число')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—')
                    ->visible(fn () => $this->ownerType() === 'number'),

                IconColumn::make('value_bool')
                    ->label('Так/Ні')
                    ->boolean()
                    ->toggleable()
                    ->visible(fn () => $this->ownerType() === 'bool'),

                TextColumn::make('sort')
                    ->label('Порядок')
                    ->sortable()
                    ->badge(),

                IconColumn::make('is_active')
                    ->label('Активне')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('sort', 'asc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Статус')
                    ->placeholder('Всі')
                    ->trueLabel('Активні')
                    ->falseLabel('Неактивні'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Додати значення')
                    ->mutateFormDataUsing(fn (array $data) => $this->normalizeTypedData($data)),

                Action::make('bulkAdd')
                    ->label('Додати списком')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->form([
                        Textarea::make('lines')
                            ->label("Список значень (по одному в рядок)")
                            ->helperText($this->bulkHelpText())
                            ->rows(10)
                            ->required(),

                        Toggle::make('overwrite')
                            ->label('Оновлювати існуючі (якщо value_key збігається)')
                            ->default(false),
                    ])
                    ->action(fn (array $data) => $this->bulkAdd($data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateFormDataUsing(fn (array $data) => $this->normalizeTypedData($data)),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(50);
    }

    private function bulkHelpText(): string
    {
        $type = $this->ownerType();

        return match ($type) {
            'number' => "Формат:\nчисло|key(optional)\nПриклади:\n0,009\n2007|2007",
            'bool' => "Формат:\nтак/ні або 1/0 або true/false\nПриклади:\nтак\nні\n1\n0",
            default => "Формат:\nuk|key(optional)|en(optional)|ru(optional)\nПриклад:\nЛіва|left|Left|Левая",
        };
    }

    private function normalizeTypedData(array $data): array
    {
        $type = $this->ownerType();
        $auto = (bool)($data['auto_key'] ?? true);

        // завжди чистимо key якщо є
        if (array_key_exists('value_key', $data) && $data['value_key'] !== null) {
            $data['value_key'] = trim((string) $data['value_key']);
        }

        // якщо auto_key=true і ключ порожній — генеруємо
        if ($auto && empty($data['value_key'])) {
            if ($type === 'number') {
                $key = CharacteristicValueKey::fromNumber($data['value_number'] ?? null, $this->ownerDecimals());
                if ($key !== null) $data['value_key'] = $key;
            } elseif ($type === 'bool') {
                if (array_key_exists('value_bool', $data) && $data['value_bool'] !== null) {
                    $data['value_key'] = $data['value_bool'] ? '1' : '0';
                }
            } else {
                $data['value_key'] = CharacteristicValueKey::fromText($data['value_uk'] ?? null, $data['value_en'] ?? null);
            }
        }

        // “очищення” неактуальних полів
        if ($type === 'number') {
            $data['value_uk'] = null;
            $data['value_en'] = null;
            $data['value_ru'] = null;
            $data['value_bool'] = null;
        } elseif ($type === 'bool') {
            $data['value_uk'] = null;
            $data['value_en'] = null;
            $data['value_ru'] = null;
            $data['value_number'] = null;
        } else {
            $data['value_number'] = null;
            $data['value_bool'] = null;
        }

        return $data;
    }

    private function bulkAdd(array $data): void
    {
        $owner = $this->getOwnerRecord(); // CharacteristicsProduct
        $type = $this->ownerType();
        $overwrite = (bool) ($data['overwrite'] ?? false);

        $lines = preg_split("/\r\n|\n|\r/", trim((string) $data['lines'])) ?: [];

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') continue;

            $payload = [
                'is_active' => true,
                'sort' => 0,
            ];

            if ($type === 'number') {
                $parts = array_map('trim', explode('|', $line));
                $numRaw = $parts[0] ?? null;
                $key = $parts[1] ?? null;

                $key = $key ?: CharacteristicValueKey::fromNumber($numRaw, $this->ownerDecimals());
                if (! $key) { $skipped++; continue; }

                $payload['value_key'] = $key;
                $payload['value_number'] = str_replace(',', '.', (string) $numRaw);
            }
            elseif ($type === 'bool') {
                $key = CharacteristicValueKey::fromBool($line);
                if ($key === null) { $skipped++; continue; }

                $payload['value_key'] = $key;
                $payload['value_bool'] = ($key === '1');
            }
            else {
                $parts = array_map('trim', explode('|', $line));
                $uk = $parts[0] ?? null;
                $key = $parts[1] ?? null;
                $en = $parts[2] ?? null;
                $ru = $parts[3] ?? null;

                if (! $uk) { $skipped++; continue; }

                $payload['value_key'] = $key ?: CharacteristicValueKey::fromText($uk, $en);
                $payload['value_uk'] = $uk;
                $payload['value_en'] = $en;
                $payload['value_ru'] = $ru;
            }

            $q = $owner->values()->where('value_key', $payload['value_key']);
            $exists = $q->exists();

            if ($exists && ! $overwrite) { $skipped++; continue; }

            if ($exists && $overwrite) {
                $q->first()->update($payload);
                $updated++;
                continue;
            }

            $owner->values()->create($payload);
            $created++;
        }

        Notification::make()
            ->success()
            ->title('Готово')
            ->body("Створено: {$created}, Оновлено: {$updated}, Пропущено: {$skipped}")
            ->send();
    }
}
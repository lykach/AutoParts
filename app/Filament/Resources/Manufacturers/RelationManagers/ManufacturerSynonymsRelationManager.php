<?php

namespace App\Filament\Resources\Manufacturers\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ManufacturerSynonymsRelationManager extends RelationManager
{
    protected static string $relationship = 'synonyms';
    protected static ?string $title = 'Синоніми виробника';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('synonym')
                    ->label('Синонім')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->copyable()
                    ->copyMessage('Синонім скопійовано'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Додати 1 синонім')
                    ->modalHeading('Додати синонім')
                    ->form([
                        TextInput::make('synonym')
                            ->label('Синонім')
                            ->required()
                            ->maxLength(255)
                            ->autofocus()
                            ->helperText('Вводь як у прайсах/постачальників. Збережемо у ВЕРХНЬОМУ регістрі.'),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['synonym'] = self::normalizeOne($data['synonym'] ?? '');
                        return $data;
                    })
                    ->action(function (array $data): void {
                        $syn = self::normalizeOne($data['synonym'] ?? '');

                        if ($syn === '') {
                            Notification::make()->warning()->title('Порожнє значення')->send();
                            return;
                        }

                        $exists = $this->getRelationship()
                            ->where('synonym', $syn)
                            ->exists();

                        if ($exists) {
                            Notification::make()
                                ->warning()
                                ->title('Дублікат')
                                ->body("Синонім '{$syn}' вже існує для цього виробника.")
                                ->send();
                            return;
                        }

                        $this->getRelationship()->create(['synonym' => $syn]);

                        Notification::make()
                            ->success()
                            ->title('Синонім додано')
                            ->body("Додано: {$syn}")
                            ->send();
                    }),

                Action::make('bulkAddSynonyms')
                    ->label('Масово додати')
                    ->icon('heroicon-m-clipboard-document-list')
                    ->color('info')
                    ->modalHeading('Масове додавання синонімів')
                    ->modalDescription('Встав список (кожен синонім з нового рядка) або додай як теги. Дублікати будуть пропущені.')
                    ->form([
                        TagsInput::make('tags')
                            ->label('Швидко як теги')
                            ->placeholder('Введи → Enter')
                            ->helperText('Зручно для 5–30 значень.')
                            ->suggestions([])
                            ->dehydrateStateUsing(fn ($state) => is_array($state) ? array_values(array_filter($state)) : []),

                        Textarea::make('list')
                            ->label('Або встав список')
                            ->rows(10)
                            ->placeholder("Напр:\nKNECHT / MAHLE\nKNECHT-MAHLE\nKNECHT FILTER")
                            ->helperText('Розділювач: новий рядок. (Коми/крапка з комою також ок).'),
                    ])
                    ->action(function (array $data): void {
                        $raw = [];

                        $tags = $data['tags'] ?? [];
                        if (is_array($tags)) {
                            $raw = array_merge($raw, $tags);
                        }

                        $list = (string) ($data['list'] ?? '');
                        if (trim($list) !== '') {
                            $list = str_replace(["\r\n", "\r"], "\n", $list);
                            $list = str_replace([';', ','], "\n", $list);
                            $raw = array_merge($raw, explode("\n", $list));
                        }

                        $normalized = [];
                        foreach ($raw as $item) {
                            $syn = self::normalizeOne($item);
                            if ($syn !== '') {
                                $normalized[] = $syn;
                            }
                        }

                        $normalized = array_values(array_unique($normalized));

                        if (empty($normalized)) {
                            Notification::make()
                                ->warning()
                                ->title('Нічого додавати')
                                ->body('Порожній список або всі значення невалідні.')
                                ->send();
                            return;
                        }

                        $existing = $this->getRelationship()
                            ->whereIn('synonym', $normalized)
                            ->pluck('synonym')
                            ->all();

                        $existingMap = array_fill_keys($existing, true);

                        $toInsert = [];
                        $skipped = [];

                        foreach ($normalized as $syn) {
                            if (isset($existingMap[$syn])) {
                                $skipped[] = $syn;
                                continue;
                            }
                            $toInsert[] = $syn;
                        }

                        if (empty($toInsert)) {
                            Notification::make()
                                ->warning()
                                ->title('Все вже є')
                                ->body('Усі введені синоніми вже існують для цього виробника.')
                                ->send();
                            return;
                        }

                        $foreignKeyName = $this->getRelationship()->getForeignKeyName(); // manufacturer_id
                        $ownerKey = $this->getOwnerRecord()->getKey();

                        $now = now();
                        $rows = array_map(fn ($syn) => [
                            $foreignKeyName => $ownerKey,
                            'synonym' => $syn,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ], $toInsert);

                        $this->getRelationship()->insert($rows);

                        $body = 'Додано: ' . count($toInsert);
                        if (!empty($skipped)) {
                            $body .= ' | Пропущено як дублікати: ' . count($skipped);
                            $preview = array_slice($skipped, 0, 15);
                            $body .= "\nДублікати: " . implode(', ', $preview) . (count($skipped) > 15 ? ' …' : '');
                        }

                        Notification::make()
                            ->success()
                            ->title('Готово')
                            ->body($body)
                            ->send();
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Змінити синонім')
                    ->form([
                        TextInput::make('synonym')
                            ->label('Синонім')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['synonym'] = self::normalizeOne($data['synonym'] ?? '');
                        return $data;
                    })
                    ->action(function ($record, array $data): void {
                        $syn = self::normalizeOne($data['synonym'] ?? '');

                        if ($syn === '') {
                            Notification::make()
                                ->warning()
                                ->title('Порожнє значення')
                                ->body('Синонім не може бути порожнім.')
                                ->send();
                            return;
                        }

                        $exists = $this->getRelationship()
                            ->where('synonym', $syn)
                            ->whereKeyNot($record->getKey())
                            ->exists();

                        if ($exists) {
                            Notification::make()
                                ->warning()
                                ->title('Дублікат')
                                ->body("Синонім '{$syn}' вже існує для цього виробника.")
                                ->send();
                            return;
                        }

                        $record->update(['synonym' => $syn]);

                        Notification::make()
                            ->success()
                            ->title('Синонім оновлено')
                            ->send();
                    }),

                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Синонім видалено')
                    ),
            ])
            ->defaultSort('synonym', 'asc');
    }

    private static function normalizeOne(mixed $value): string
    {
        $str = trim((string) $value);
        if ($str === '') {
            return '';
        }

        $str = preg_replace('/\s+/u', ' ', $str) ?? $str;

        return mb_strtoupper($str, 'UTF-8');
    }
}

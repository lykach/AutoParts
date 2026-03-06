<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with([
                    'parent:id,name_uk',
                ]);
            })
            ->defaultSort('path_ids')
            ->columns([
                ImageColumn::make('image')
                    ->label('Фото')
                    ->disk('public')
                    ->square()
                    ->size(40),

                TextColumn::make('name_uk')
                    ->label('Назва')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->badge()
                    ->formatStateUsing(function (?string $state, Category $record): string {
                        $depth = max(0, (int) ($record->depth ?? 0));
                        $indent = str_repeat('— ', min($depth, 8));

                        if ((bool) $record->is_container) {
                            $icon = '🧱';
                        } elseif ((int) $record->depth === 0) {
                            $icon = '🗂';
                        } elseif ((bool) $record->is_leaf && (int) $record->products_total_count > 0) {
                            $icon = '📦';
                        } elseif ((bool) $record->is_leaf) {
                            $icon = '📄';
                        } else {
                            $icon = '📁';
                        }

                        return trim($indent . $icon . ' ' . ($state ?? ''));
                    })
                    ->color(function (Category $record): string {
                        if ((bool) $record->is_container) {
                            return 'info';
                        }

                        if ((int) $record->depth === 0) {
                            return 'primary';
                        }

                        if ((bool) $record->is_leaf && (int) $record->products_total_count > 0) {
                            return 'success';
                        }

                        if ((bool) $record->is_leaf) {
                            return 'gray';
                        }

                        return 'warning';
                    })
                    ->description(function (Category $record): string {
                        $parts = [];

                        $parts[] = 'Шлях: /' . $record->full_url_path;
                        $parts[] = 'ID: ' . $record->id;
                        $parts[] = 'Depth: ' . (int) $record->depth;
                        $parts[] = 'Порядок: ' . (int) $record->order;

                        if ($record->parent?->name_uk) {
                            $parts[] = 'Батько: ' . $record->parent->name_uk;
                        } else {
                            $parts[] = 'Root';
                        }

                        return implode(' • ', $parts);
                    }),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('parent.name_uk')
                    ->label('Батьківська')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Коренева')
                    ->toggleable(),

                TextColumn::make('depth')
                    ->label('Рівень')
                    ->sortable()
                    ->badge()
                    ->color(fn (Category $record): string => match (true) {
                        (int) $record->depth === 0 => 'primary',
                        (int) $record->depth === 1 => 'info',
                        (int) $record->depth === 2 => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('tecdoc_id')
                    ->label('TecDoc')
                    ->sortable()
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('children_count')
                    ->label('Дітей')
                    ->sortable()
                    ->badge()
                    ->color(fn (Category $record): string => (int) $record->children_count > 0 ? 'warning' : 'gray'),

                TextColumn::make('products_direct_count')
                    ->label('Товарів тут')
                    ->sortable()
                    ->badge()
                    ->color(fn (Category $record): string => (int) $record->products_direct_count > 0 ? 'success' : 'gray'),

                TextColumn::make('products_total_count')
                    ->label('Товарів у гілці')
                    ->sortable()
                    ->badge()
                    ->color(fn (Category $record): string => (int) $record->products_total_count > 0 ? 'success' : 'gray'),

                IconColumn::make('is_leaf')
                    ->label('Leaf')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                IconColumn::make('is_container')
                    ->label('Container')
                    ->boolean()
                    ->sortable()
                    ->trueColor('info')
                    ->falseColor('gray'),

                TextColumn::make('path_ids')
                    ->label('Path IDs')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('path_slugs')
                    ->label('Path Slugs')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('order')
                    ->label('Порядок')
                    ->sortable()
                    ->badge(),

                IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean()
                    ->sortable()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Батьківська категорія')
                    ->options(fn () => Category::query()
                        ->orderBy('name_uk')
                        ->pluck('name_uk', 'id')
                        ->toArray())
                    ->searchable()
                    ->placeholder('Всі, крім root'),

                TernaryFilter::make('is_root')
                    ->label('Root')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки root')
                    ->falseLabel('Тільки вкладені')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('parent_id'),
                        false: fn (Builder $query) => $query->whereNotNull('parent_id'),
                        blank: fn (Builder $query) => $query,
                    ),

                TernaryFilter::make('has_products')
                    ->label('Товари в гілці')
                    ->placeholder('Всі')
                    ->trueLabel('Є товари')
                    ->falseLabel('Без товарів')
                    ->queries(
                        true: fn (Builder $query) => $query->where('products_total_count', '>', 0),
                        false: fn (Builder $query) => $query->where('products_total_count', 0),
                        blank: fn (Builder $query) => $query,
                    ),

                TernaryFilter::make('is_active')
                    ->label('Статус')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки активні')
                    ->falseLabel('Неактивні'),

                TernaryFilter::make('is_leaf')
                    ->label('Тип')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки leaf')
                    ->falseLabel('З підкатегоріями'),

                TernaryFilter::make('is_container')
                    ->label('Container')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки container')
                    ->falseLabel('Не container'),
            ])
            ->recordUrl(
                fn (Category $record): string => route('filament.admin.resources.categories.edit', ['record' => $record])
            )
            ->actions([
                Action::make('move_up')
                    ->label('Вгору')
                    ->icon('heroicon-o-arrow-up')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (Category $record) {
                        $record->moveUp();

                        Notification::make()
                            ->success()
                            ->title('Готово')
                            ->body("Категорію '{$record->name_uk}' переміщено вгору.")
                            ->send();
                    }),

                Action::make('move_down')
                    ->label('Вниз')
                    ->icon('heroicon-o-arrow-down')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (Category $record) {
                        $record->moveDown();

                        Notification::make()
                            ->success()
                            ->title('Готово')
                            ->body("Категорію '{$record->name_uk}' переміщено вниз.")
                            ->send();
                    }),

                Action::make('change_parent')
                    ->label('Змінити батька')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning')
                    ->form([
                        SelectTree::make('parent_id')
                            ->label('Новий батько')
                            ->placeholder('Зробити root категорією')
                            ->query(
                                fn () => Category::query()->orderBy('name_uk'),
                                'name_uk',
                                'parent_id'
                            )
                            ->searchable()
                            ->defaultOpenLevel(2)
                            ->emptyLabel('Нічого не знайдено')
                            ->helperText('Порожнє значення = root категорія.')
                            ->disabledOptions(function (SelectTree $component, Category $record) {
                                return array_merge([$record->id], $record->descendantIds());
                            })
                            ->default(fn (Category $record) => $record->parent_id)
                            ->nullable(),
                    ])
                    ->action(function (Category $record, array $data) {
                        try {
                            $newParentId = $data['parent_id'] ?? null;
                            $newParentId = $newParentId ? (int) $newParentId : null;

                            $record->moveToParent($newParentId);

                            Notification::make()
                                ->success()
                                ->title('Готово')
                                ->body("Батьківську категорію для '{$record->name_uk}' оновлено.")
                                ->send();
                        } catch (ValidationException $e) {
                            $msg = collect($e->errors())->flatten()->first() ?: $e->getMessage();

                            Notification::make()
                                ->danger()
                                ->title('Не збережено')
                                ->body((string) $msg)
                                ->persistent()
                                ->send();
                        } catch (\Throwable $e) {
                            report($e);

                            Notification::make()
                                ->danger()
                                ->title('Помилка')
                                ->body($e->getMessage())
                                ->persistent()
                                ->send();
                        }
                    }),

                Action::make('make_root')
                    ->label('Зробити root')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('info')
                    ->hidden(fn (Category $record) => $record->parent_id === null)
                    ->requiresConfirmation()
                    ->action(function (Category $record) {
                        try {
                            $record->makeRoot();

                            Notification::make()
                                ->success()
                                ->title('Готово')
                                ->body("Категорію '{$record->name_uk}' перенесено в root.")
                                ->send();
                        } catch (ValidationException $e) {
                            $msg = collect($e->errors())->flatten()->first() ?: $e->getMessage();

                            Notification::make()
                                ->danger()
                                ->title('Не збережено')
                                ->body((string) $msg)
                                ->persistent()
                                ->send();
                        } catch (\Throwable $e) {
                            report($e);

                            Notification::make()
                                ->danger()
                                ->title('Помилка')
                                ->body($e->getMessage())
                                ->persistent()
                                ->send();
                        }
                    }),

                Action::make('create_child')
                    ->label('Підкатегорія')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn (Category $record) => $record->canHaveChildren())
                    ->url(fn (Category $record) => route('filament.admin.resources.categories.create', [
                        'parent_id' => $record->id,
                    ])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('normalizeSiblingOrder')
                        ->label('Нормалізувати порядок')
                        ->icon('heroicon-o-bars-3-bottom-left')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $parentIds = $records
                                ->map(fn (Category $record) => $record->parent_id)
                                ->unique()
                                ->values();

                            foreach ($parentIds as $parentId) {
                                Category::normalizeSiblingOrder($parentId);
                            }

                            Notification::make()
                                ->success()
                                ->title('Готово')
                                ->body('Порядок у вибраних групах нормалізовано.')
                                ->send();
                        }),

                    BulkAction::make('createSubcategory')
                        ->label('Створити підкатегорію')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            if ($records->count() !== 1) {
                                Notification::make()
                                    ->warning()
                                    ->title('Виберіть одну категорію')
                                    ->body('Підкатегорію можна створити тільки для однієї батьківської категорії.')
                                    ->send();
                                return;
                            }

                            /** @var Category $parent */
                            $parent = $records->first();

                            if (! $parent->canHaveChildren()) {
                                Notification::make()
                                    ->danger()
                                    ->title('Неможливо створити підкатегорію')
                                    ->body("Категорія '{$parent->name_uk}' має товари або характеристики і не може мати підкатегорій.")
                                    ->send();
                                return;
                            }

                            return redirect()->route('filament.admin.resources.categories.create', [
                                'parent_id' => $parent->id,
                            ]);
                        }),

                    BulkAction::make('markContainer')
                        ->label('Зробити container')
                        ->icon('heroicon-o-archive-box')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $done = 0;

                            foreach ($records as $cat) {
                                /** @var Category $cat */
                                if ($cat->parent_id !== null) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Неможливо')
                                        ->body("Категорія '{$cat->name_uk}' не є root.")
                                        ->send();
                                    continue;
                                }

                                if ($cat->hasProducts() || $cat->hasCharacteristics()) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Неможливо')
                                        ->body("Категорія '{$cat->name_uk}' має товари або характеристики.")
                                        ->send();
                                    continue;
                                }

                                $cat->update(['is_container' => true]);
                                $done++;
                            }

                            Notification::make()
                                ->success()
                                ->title('Готово')
                                ->body("Container-прапорець встановлено для {$done} категорій.")
                                ->send();
                        }),

                    BulkAction::make('unmarkContainer')
                        ->label('Зняти container')
                        ->icon('heroicon-o-archive-box-x-mark')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $cat) {
                                /** @var Category $cat */
                                $cat->update(['is_container' => false]);
                            }

                            Notification::make()
                                ->success()
                                ->title('Готово')
                                ->body('Container-прапорець знято.')
                                ->send();
                        }),

                    DeleteBulkAction::make()
                        ->before(function (Collection $records, DeleteBulkAction $action) {
                            foreach ($records as $record) {
                                /** @var Category $record */
                                if ($record->children()->exists()) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Категорія має підкатегорії')
                                        ->body("Неможливо видалити '{$record->name_uk}'.")
                                        ->send();
                                    $action->cancel();
                                    return;
                                }

                                if ($record->hasProducts()) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Категорія має товари')
                                        ->body("Неможливо видалити '{$record->name_uk}'.")
                                        ->send();
                                    $action->cancel();
                                    return;
                                }

                                if ($record->hasCharacteristics()) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Категорія має характеристики')
                                        ->body("Неможливо видалити '{$record->name_uk}'.")
                                        ->send();
                                    $action->cancel();
                                    return;
                                }

                                if ($record->mirrorsAsParent()->exists()) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Категорія використовується як контейнер дзеркал')
                                        ->body("Неможливо видалити '{$record->name_uk}'.")
                                        ->send();
                                    $action->cancel();
                                    return;
                                }

                                if ($record->mirrorsAsSource()->exists()) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Категорія використовується як джерело дзеркал')
                                        ->body("Неможливо видалити '{$record->name_uk}'.")
                                        ->send();
                                    $action->cancel();
                                    return;
                                }
                            }
                        }),
                ]),
            ])
            ->striped()
            ->paginated([50, 100, 'all'])
            ->defaultPaginationPageOption(50);
    }
}
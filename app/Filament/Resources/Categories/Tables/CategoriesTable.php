<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                    ->description(fn (Category $record): string =>
                        'Шлях: /' . $record->full_url_path
                    ),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->toggleable(),

                TextColumn::make('parent.name_uk')
                    ->label('Батьківська')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Коренева')
                    ->toggleable(),

                TextColumn::make('tecdoc_id')
                    ->label('TecDoc')
                    ->sortable()
                    ->badge()
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('children_count')
                    ->label('Підкатегорій')
                    ->counts('children')
                    ->sortable()
                    ->badge()
                    ->color('warning'),

                TextColumn::make('products_count')
                    ->label('Товарів')
                    ->counts('products')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                IconColumn::make('is_container')
                    ->label('Контейнер')
                    ->boolean()
                    ->sortable()
                    ->trueColor('info')
                    ->falseColor('gray'),

                TextColumn::make('order')
                    ->label('Порядок')
                    ->sortable()
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean()
                    ->sortable()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->defaultSort('order', 'asc')
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Батьківська категорія')
                    ->options(function () {
                        return Category::query()
                            ->orderBy('name_uk')
                            ->pluck('name_uk', 'id')
                            ->prepend('Кореневі категорії', -1)
                            ->toArray();
                    })
                    ->searchable()
                    ->placeholder('Всі категорії'),

                TernaryFilter::make('is_active')
                    ->label('Статус')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки активні')
                    ->falseLabel('Неактивні'),

                TernaryFilter::make('is_leaf')
                    ->label('Тип')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки кінцеві')
                    ->falseLabel('З підкатегоріями'),

                TernaryFilter::make('is_container')
                    ->label('Контейнер')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки контейнерні')
                    ->falseLabel('Не контейнерні'),
            ])
            ->recordUrl(
                fn (Category $record): string =>
                    route('filament.admin.resources.categories.edit', ['record' => $record])
            )
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('createSubcategory')
                        ->label('Створити підкатегорію')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->requiresConfirmation(false)
                        ->action(function (Collection $records) {
                            if ($records->count() > 1) {
                                Notification::make()
                                    ->warning()
                                    ->title('Виберіть ОДНУ категорію')
                                    ->body('Можна створити підкатегорію лише для однієї батьківської категорії.')
                                    ->send();
                                return;
                            }

                            $parent = $records->first();

                            if (! $parent->canHaveChildren()) {
                                Notification::make()
                                    ->danger()
                                    ->title('Неможливо створити підкатегорію')
                                    ->body("Категорія '{$parent->name_uk}' має товари і не може мати підкатегорій!")
                                    ->send();
                                return;
                            }

                            return redirect()->route('filament.admin.resources.categories.create', [
                                'parent_id' => $parent->id
                            ]);
                        }),

                    BulkAction::make('markContainer')
                        ->label('Зробити контейнером')
                        ->icon('heroicon-o-archive-box')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $cat) {
                                /** @var Category $cat */
                                if ($cat->hasProducts()) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Неможливо')
                                        ->body("Категорія '{$cat->name_uk}' має товари. Перенесіть товари і повторіть.")
                                        ->send();
                                    continue;
                                }
                                $cat->update(['is_container' => true]);
                            }

                            Notification::make()
                                ->success()
                                ->title('Готово')
                                ->body('Вибрані категорії позначено контейнерними (де це було можливо).')
                                ->send();
                        }),

                    BulkAction::make('unmarkContainer')
                        ->label('Зняти контейнер')
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
                                ->body('Контейнерний прапорець знято.')
                                ->send();
                        }),

                    DeleteBulkAction::make()
                        ->before(function (Collection $records, DeleteBulkAction $action) {
                            foreach ($records as $record) {
                                if ($record->children()->exists()) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Категорія має підкатегорії!')
                                        ->body("Неможливо видалити '{$record->name_uk}'")
                                        ->send();
                                    $action->cancel();
                                    return;
                                }

                                if ($record->hasProducts()) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Категорія має товари!')
                                        ->body("Неможливо видалити '{$record->name_uk}'")
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
<?php

namespace App\Filament\Resources\StockSource\Tables;

use App\Rules\UkrainianPhone;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class StockSourceTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->withCount([
                    'storeLinks',
                    'locations',
                    'items',
                ]);
            })

            // ✅ Drag & Drop reorder
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc')

            ->columns([
                IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('#')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('Назва')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('code')
                    ->label('Код')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Код скопійовано')
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Телефон скопійовано')
                    ->icon('heroicon-o-phone')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state) => UkrainianPhone::format($state))
                    ->toggleable(),

                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->sortable(),

                TextColumn::make('city')
                    ->label('Місто')
                    ->toggleable(),

                TextColumn::make('store_links_count')
                    ->label('Магазинів')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('locations_count')
                    ->label('Складів')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
					
				TextColumn::make('items_count')
                    ->label('Товарів')
                    ->counts('items')
                    ->sortable()
                    ->badge()
                    ->color('success'),	

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                Filter::make('active')
                    ->label('Тільки активні')
                    ->query(fn (Builder $q) => $q->where('is_active', true)),
            ])

            ->actions([
                ActionGroup::make([
                    EditAction::make(),

                    DeleteAction::make()
                        ->disabled(fn ($record) =>
                            (int) ($record->store_links_count ?? 0) > 0
                            || (int) ($record->locations_count ?? 0) > 0
                            || $record->items()->exists()
                        )
                        ->tooltip(fn ($record) => (
                            (int) ($record->store_links_count ?? 0) > 0
                                ? 'Неможливо видалити: джерело використовується в магазинах.'
                                : ((int) ($record->locations_count ?? 0) > 0
                                    ? 'Неможливо видалити: у джерела є склади/локації.'
                                    : ($record->items()->exists()
                                        ? 'Неможливо видалити: у джерелі є залишки (stock_items).'
                                        : null
                                    )
                                )
                        )),
                ])->iconButton(),
            ])

            // ✅ чекбокси + "Відкрити дії" як у CategoriesTable
            ->bulkActions([
                BulkActionGroup::make([

                    BulkAction::make('activateSelected')
                        ->label('Зробити активними')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation(false)
                        ->action(function (Collection $records) {
                            $count = 0;

                            foreach ($records as $record) {
                                if (! $record->is_active) {
                                    $record->update(['is_active' => true]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Готово')
                                ->body("Активовано: {$count}")
                                ->send();
                        }),

                    BulkAction::make('deactivateSelected')
                        ->label('Зробити неактивними')
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $count = 0;

                            foreach ($records as $record) {
                                if ($record->is_active) {
                                    $record->update(['is_active' => false]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Готово')
                                ->body("Деактивовано: {$count}")
                                ->send();
                        }),

                    DeleteBulkAction::make()
                        ->before(function (Collection $records, DeleteBulkAction $action) {
                            foreach ($records as $record) {
                                $hasLinks = $record->storeLinks()->exists() || ((int) ($record->store_links_count ?? 0)) > 0;
                                $hasLocations = $record->locations()->exists() || ((int) ($record->locations_count ?? 0)) > 0;
                                $hasItems = $record->items()->exists();

                                if ($hasLinks || $hasLocations || $hasItems) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Неможливо видалити')
                                        ->body("'{ $record->name }' має привʼязки / склади / залишки.")
                                        ->send();

                                    $action->cancel();
                                    return;
                                }
                            }
                        }),

                ])->label('Відкрити дії'),
            ]);
    }
}
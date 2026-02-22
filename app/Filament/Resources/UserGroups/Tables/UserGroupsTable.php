<?php

namespace App\Filament\Resources\UserGroups\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UserGroupsTable
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Назва групи')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => "ID: {$record->id}")
                    ->copyable()
                    ->copyMessage('Назву скопійовано')
                    ->toggleable(),

                TextColumn::make('discount_percent')
                    ->label('Знижка')
                    ->numeric(1)
                    ->suffix('%')
                    ->badge()
                    ->color(fn ($state) => ((float) $state) > 0 ? 'danger' : 'gray')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('markup_percent')
                    ->label('Націнка')
                    ->numeric(1)
                    ->suffix('%')
                    ->badge()
                    ->color(fn ($state) => ((float) $state) > 0 ? 'success' : 'gray')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('users_count')
                    ->label('Клієнтів')
                    ->counts('users')
                    ->badge()
                    ->color(fn ($state) => ((int) $state) > 0 ? 'primary' : 'gray')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')

            ->filters([
                TernaryFilter::make('has_users')
                    ->label('Клієнти в групі')
                    ->placeholder('Всі')
                    ->trueLabel('Є клієнти')
                    ->falseLabel('Немає клієнтів')
                    ->queries(
                        true: fn (Builder $query) => $query->has('users'),
                        false: fn (Builder $query) => $query->doesntHave('users'),
                    ),

                Filter::make('discount_gt_0')
                    ->label('Знижка > 0%')
                    ->query(fn (Builder $query) => $query->where('discount_percent', '>', 0)),

                Filter::make('markup_gt_0')
                    ->label('Націнка > 0%')
                    ->query(fn (Builder $query) => $query->where('markup_percent', '>', 0)),
            ])

            // Клік по рядку -> сторінка редагування
            ->recordUrl(
                fn ($record) => \App\Filament\Resources\UserGroups\UserGroupResource::getUrl('edit', ['record' => $record->id])
            )

            ->bulkActions([
                BulkActionGroup::make([
                    // Безпечне масове видалення: видаляємо тільки ті групи, де немає користувачів
                    BulkAction::make('safeDelete')
                        ->label('Видалити вибране (без користувачів)')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $blocked = $records->filter(fn ($record) => $record->users()->exists());
                            $allowed = $records->reject(fn ($record) => $record->users()->exists());

                            if ($blocked->isNotEmpty()) {
                                Notification::make()
                                    ->warning()
                                    ->title('Частину груп не видалено')
                                    ->body('Деякі вибрані групи містять користувачів. Спочатку перенесіть їх в іншу групу.')
                                    ->send();
                            }

                            $allowed->each->delete();

                            if ($allowed->isNotEmpty()) {
                                Notification::make()
                                    ->success()
                                    ->title('Групи видалено')
                                    ->body('Вибрані групи без користувачів успішно видалені.')
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Якщо хочеш "звичайний" bulk delete (НЕ рекомендую):
                    // DeleteBulkAction::make()->requiresConfirmation(),
                ]),
            ])

            ->emptyStateIcon('heroicon-o-rectangle-group')
            ->emptyStateHeading('Груп не створено')
            ->emptyStateDescription('Створи першу групу, щоб почати керувати знижками та націнками.')
            ->striped();
    }
}

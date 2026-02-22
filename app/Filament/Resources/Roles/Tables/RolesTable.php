<?php

namespace App\Filament\Resources\Roles\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class RolesTable
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Роль (slug)')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Slug скопійовано'),

                TextColumn::make('description')
                    ->label('Опис')
                    ->searchable()
                    ->wrap()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('users_count')
                    ->label('Користувачів')
                    ->counts('users')
                    ->badge()
                    ->alignCenter()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('permissions_count')
                    ->label('Дозволи')
                    ->counts('permissions')
                    ->badge()
                    ->alignCenter()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')

            // ✅ Клік по рядку -> редагування
            ->recordUrl(fn (Role $record) => \App\Filament\Resources\Roles\RoleResource::getUrl('edit', ['record' => $record->id]))

            // ✅ Bulk actions (safe delete)
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('safeDelete')
                        ->label('Видалити вибране')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        // тільки super-admin бачить bulk delete (додатково до Policy)
                        ->visible(fn (): bool => auth()->user()?->hasRole('super-admin') ?? false)
                        ->action(function (Collection $records): void {
                            $blocked = $records->filter(function (Role $role): bool {
                                // super-admin роль ніколи не видаляємо
                                if ($role->name === 'super-admin') {
                                    return true;
                                }

                                // якщо роль призначена комусь — не видаляємо
                                return $role->users()->exists();
                            });

                            $deletable = $records->reject(function (Role $role): bool {
                                if ($role->name === 'super-admin') {
                                    return true;
                                }

                                return $role->users()->exists();
                            });

                            if ($blocked->isNotEmpty()) {
                                Notification::make()
                                    ->warning()
                                    ->title('Не всі ролі можна видалити')
                                    ->body('Частина ролей або super-admin, або призначена користувачам.')
                                    ->send();
                            }

                            $count = $deletable->count();

                            if ($count > 0) {
                                $deletable->each->delete();

                                Notification::make()
                                    ->success()
                                    ->title("Видалено ролей: {$count}")
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])

            ->emptyStateIcon('heroicon-o-shield-check')
            ->emptyStateHeading('Ролей ще немає')
            ->emptyStateDescription('Створи першу роль для керування доступами.')
            ->striped();
    }
}

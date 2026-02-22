<?php

namespace App\Filament\Resources\Permissions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;

class PermissionsTable
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Permission (slug)')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Permission скопійовано')
                    ->description(fn (Permission $record) => "ID: {$record->id}"),

                TextColumn::make('description')
                    ->label('Опис')
                    ->wrap()
                    ->placeholder('—')
                    ->toggleable(),

                // беремо roles_count з ->withCount(['roles']) у Resource
                TextColumn::make('roles_count')
                    ->label('Ролей')
                    ->badge()
                    ->alignCenter()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->since()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->recordUrl(fn (Permission $record) =>
                \App\Filament\Resources\Permissions\PermissionResource::getUrl('edit', ['record' => $record->id])
            )
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()?->hasRole('super-admin') ?? false),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-key')
            ->emptyStateHeading('Немає прав доступу')
            ->emptyStateDescription('Створи перше permission.')
            ->striped();
    }
}

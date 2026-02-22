<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Rules\UkrainianPhone;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class UsersTable
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([

                // ✅ Аватар
                ImageColumn::make('avatar_url')
                    ->label('Аватар')
                    ->disk('public')
                    ->size(40)
                    ->circular()
                    ->defaultImageUrl(fn (User $record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF')
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('Ім\'я')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (User $record) => $record->email),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email скопійовано')
                    ->icon('heroicon-o-envelope')
                    ->toggleable(),

                // ✅ Телефон з форматуванням
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

                // ✅ Ролі (Spatie Permission) — показуємо description, якщо є
                TextColumn::make('roles_display')
                    ->label('Ролі')
                    ->badge()
                    ->getStateUsing(function (User $record): array {
                        // Повертаємо масив значень для бейджів
                        return $record->roles
                            ->sortBy('name')
                            ->map(fn ($role) => $role->description ?: $role->name)
                            ->values()
                            ->all();
                    })
                    ->color(function ($state): string {
                        // $state тут — текст одного бейджа
                        $s = Str::lower((string) $state);

                        return match (true) {
                            str_contains($s, 'super') => 'danger',
                            $s === 'admin' || str_contains($s, 'адмін') => 'success',
                            $s === 'manager' || str_contains($s, 'менедж') => 'warning',
                            $s === 'user' || str_contains($s, 'корист') => 'gray',
                            default => 'primary',
                        };
                    })
                    ->separator(', ')
                    ->toggleable(),

                // ✅ Група користувача (знижки/націнки)
                TextColumn::make('group.name')
                    ->label('Група')
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->toggleable(),

                IconColumn::make('email_verified_at')
                    ->label('Email підтверджено')
                    ->boolean()
                    ->sortable()
                    ->toggleable()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle'),

                TextColumn::make('created_at')
                    ->label('Зареєстрований')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([

                TernaryFilter::make('email_verified_at')
                    ->label('Email статус')
                    ->placeholder('Всі')
                    ->trueLabel('Email підтверджено')
                    ->falseLabel('Email не підтверджено')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('email_verified_at'),
                        false: fn ($query) => $query->whereNull('email_verified_at'),
                    ),
            ])
            ->recordUrl(
                fn (User $record) => \App\Filament\Resources\Users\UserResource::getUrl('edit', ['record' => $record->id])
            )
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading('Немає користувачів')
            ->emptyStateDescription('Створіть першого користувача, натиснувши кнопку нижче.')
            ->striped();
    }
}

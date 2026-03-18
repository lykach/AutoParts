<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use App\Models\Page;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LogicException;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([SoftDeletingScope::class]))
            ->defaultSort('sort')
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('')
                    ->disk('public')
                    ->square()
                    ->size(40),

                TextColumn::make('name')
                    ->label('Назва')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Page $record): string => '/' . ltrim($record->slug, '/')),

                TextColumn::make('template')
                    ->label('Шаблон')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PageTemplate ? $state->label() : ($state ? PageTemplate::from($state)->label() : '—'))
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PageStatus ? $state->label() : ($state ? PageStatus::from($state)->label() : '—'))
                    ->sortable(),

                IconColumn::make('is_system')
                    ->label('Системна')
                    ->boolean(),

                IconColumn::make('show_in_sitemap')
                    ->label('Sitemap')
                    ->boolean(),

                TextColumn::make('sort')
                    ->label('Sort')
                    ->sortable(),

                TextColumn::make('published_at')
                    ->label('Опубліковано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->since()
                    ->sortable(),

                TextColumn::make('deleted_at')
                    ->label('Видалено')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(PageStatus::options()),

                SelectFilter::make('template')
                    ->label('Шаблон')
                    ->options(PageTemplate::options()),

                TernaryFilter::make('is_system')
                    ->label('Системні сторінки'),

                TernaryFilter::make('trashed')
                    ->label('Видалені')
                    ->queries(
                        true: fn (Builder $query) => $query->onlyTrashed(),
                        false: fn (Builder $query) => $query->withoutTrashed(),
                        blank: fn (Builder $query) => $query->withTrashed(),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                RestoreAction::make(),
                DeleteAction::make()
                    ->visible(fn (Page $record) => ! $record->is_system)
                    ->requiresConfirmation()
                    ->action(function (Page $record): void {
                        try {
                            $record->delete();
                        } catch (LogicException $e) {
                            Notification::make()
                                ->title('Сторінку не можна видалити')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                ForceDeleteAction::make()
                    ->visible(fn (Page $record) => ! $record->is_system),
            ]);
    }
}
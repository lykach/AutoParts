<?php
namespace App\Filament\Resources\Currencies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;

class CurrenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Код валюти')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Код скопійовано')
                    ->badge()
                    ->color('primary'),
                
                TextColumn::make('iso_code')
                    ->label('ISO')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),
                
                TextColumn::make('symbol')
                    ->label('Символ')
                    ->searchable()
                    ->size('lg')
                    ->weight('bold'),
                
                TextColumn::make('short_name_uk')
                    ->label('Назва (UK)')
                    ->searchable()
                    ->placeholder('—'),
                
                TextColumn::make('short_name_en')
                    ->label('Назва (EN)')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),
                
                TextColumn::make('short_name_ru')
                    ->label('Назва (RU)')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),
                
                TextColumn::make('rate')
                    ->label('Курс')
                    ->numeric(decimalPlaces: 4)
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state == 1.0000 ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state) => $state == 1.0000 ? '1.0000 (база)' : $state),
                
                TextColumn::make('rate_updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('Ніколи')
                    ->toggleable(),
                
                IconColumn::make('is_default')
                    ->label('Головна')
                    ->boolean()
                    ->sortable()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                
                IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean()
                    ->sortable()
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('updated_at')
                    ->label('Змінено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_default')
                    ->label('Головна валюта')
                    ->placeholder('Всі валюти')
                    ->trueLabel('Тільки головна')
                    ->falseLabel('Не головна'),
                
                TernaryFilter::make('is_active')
                    ->label('Статус')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки активні')
                    ->falseLabel('Неактивні'),
            ])
            ->recordUrl( // ✅ recordUrl замість recordActions
                fn ($record): string => 
                    route('filament.admin.resources.currencies.edit', ['record' => $record])
            )
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (Collection $records, DeleteBulkAction $action) {
                            if ($records->contains('is_default', true)) {
                                Notification::make()
                                    ->danger()
                                    ->title('Помилка видалення')
                                    ->body('Неможливо видалити головну валюту магазину!')
                                    ->send();
                                
                                $action->cancel();
                            }
                        })
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Валюти видалено')
                                ->body('Вибрані валюти успішно видалено.')
                        ),
                ]),
            ])
            ->defaultSort('is_default', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
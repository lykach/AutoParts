<?php
namespace App\Filament\Resources\Languages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;

class LanguagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Код')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->copyable()
                    ->copyMessage('Код скопійовано'),
                
                TextColumn::make('name_uk')
                    ->label('Назва (UK)')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                TextColumn::make('name_en')
                    ->label('Назва (EN)')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),
                
                TextColumn::make('name_ru')
                    ->label('Назва (RU)')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),
                
                TextColumn::make('lng_id')
                    ->label('TecDoc ID')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->toggleable(),
                
                TextColumn::make('lng_codepage')
                    ->label('Codepage')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                
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
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_default')
                    ->label('Головна мова')
                    ->placeholder('Всі мови')
                    ->trueLabel('Тільки головна')
                    ->falseLabel('Не головна'),
                
                TernaryFilter::make('is_active')
                    ->label('Статус')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки активні')
                    ->falseLabel('Неактивні'),
            ])
            ->recordUrl(
                fn ($record): string => 
                    route('filament.admin.resources.languages.edit', ['record' => $record])
            )
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (Collection $records, DeleteBulkAction $action) {
                            if ($records->contains('is_default', true)) {
                                Notification::make()
                                    ->danger()
                                    ->title('Помилка видалення')
                                    ->body('Неможливо видалити головну мову сайту!')
                                    ->send();
                                
                                $action->cancel();
                            }
                        })
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Мови видалено')
                                ->body('Вибрані мови успішно видалено.')
                        ),
                ]),
            ])
            ->defaultSort('is_default', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
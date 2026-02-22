<?php

namespace App\Filament\Resources\ArticleAnalogImportResource\Tables;

use App\Models\ArticleAnalogImport;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ArticleAnalogImportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Тип')
                    ->formatStateUsing(fn ($state) => $state === 'anti' ? 'Антикроси' : 'Кроси')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'gray' => 'queued',
                        'warning' => 'processing',
                        'success' => 'done',
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'queued' => 'В черзі',
                        'processing' => 'Імпортується',
                        'done' => 'Готово',
                        'failed' => 'Помилка',
                        default => (string) $state,
                    })
                    ->sortable(),

                TextColumn::make('inserted')
                    ->label('Додано')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('skipped')
                    ->label('Пропущено')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('file_name')
                    ->label('Файл')
                    ->formatStateUsing(fn ($state) => $state ?: '—')
                    ->wrap(),
            ])
            ->recordActions([
                Action::make('deleteSourceFile')
                    ->label('Видалити файл')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Видалити файл імпорту?')
                    ->modalDescription('Файл буде видалено з диска, запис лишиться для історії.')
                    ->visible(fn ($record) => ! empty($record->path))
                    ->action(function (ArticleAnalogImport $record) {
                        try {
                            if ($record->path && Storage::disk($record->disk)->exists($record->path)) {
                                Storage::disk($record->disk)->delete($record->path);
                            }

                            $record->update([
                                'path' => null,
                                'file_name' => null,
                            ]);

                            Notification::make()
                                ->title('Файл видалено')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Не вдалося видалити файл')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('error')
                    ->label('Помилка')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn ($record) => ($record->status === 'failed') && ! empty($record->error))
                    ->modalHeading('Помилка імпорту')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Закрити')
                    ->modalContent(fn ($record) => (string) $record->error),

                Action::make('purge')
                    ->label('Прибрати запис')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Видалити запис з історії?')
                    ->modalSubmitActionLabel('Видалити')
                    ->action(fn (ArticleAnalogImport $record) => $record->delete()),
            ])
            ->bulkActions([
                BulkAction::make('purgeRows')
                    ->label('Видалити вибрані записи')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $count = 0;

                        foreach ($records as $record) {
                            /** @var ArticleAnalogImport $record */
                            $record->delete();
                            $count++;
                        }

                        Notification::make()
                            ->title('Готово')
                            ->body("Видалено записів: {$count}")
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

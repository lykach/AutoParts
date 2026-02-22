<?php

namespace App\Filament\Resources\ArticleAnalogExportResource\Tables;

use App\Models\ArticleAnalogExport;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ArticleAnalogExportsTable
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
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cross' => 'Кроси',
                        'anti'  => 'Антикроси',
                        default => 'Всі',
                    })
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'gray' => ['queued', 'deleted'],
                        'warning' => 'processing',
                        'success' => 'done',
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(function ($state, $record) {
                        if (empty($record->path) && in_array($state, ['done', 'deleted'], true)) {
                            return 'Видалено';
                        }

                        return match ($state) {
                            'queued' => 'В черзі',
                            'processing' => 'Генерується',
                            'done' => 'Готово',
                            'failed' => 'Помилка',
                            'deleted' => 'Видалено',
                            default => (string) $state,
                        };
                    })
                    ->sortable(),

                TextColumn::make('rows')
                    ->label('Рядків')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('file_name')
                    ->label('Файл')
                    ->formatStateUsing(fn ($state, $record) => $record->path ? ($state ?: 'export.csv') : '—')
                    ->wrap(),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Скачати')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn ($record) => ($record->status === 'done') && ! empty($record->path))
                    ->url(fn ($record) => Storage::disk($record->disk ?? 'public')->url($record->path), shouldOpenInNewTab: true),

                Action::make('deleteFile')
                    ->label('Видалити файл')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Видалити файл експорту?')
                    ->modalDescription('Файл буде видалено з диска. Запис залишиться для історії.')
                    ->visible(fn ($record) => ! empty($record->path))
                    ->action(function (ArticleAnalogExport $record) {
                        $disk = $record->disk ?? 'public';

                        try {
                            if ($record->path && Storage::disk($disk)->exists($record->path)) {
                                Storage::disk($disk)->delete($record->path);
                            }

                            $record->update([
                                'path' => null,
                                'file_name' => null,
                                'status' => 'deleted',
                                'error' => null,
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

                Action::make('purge')
                    ->label('Прибрати запис')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Видалити запис з історії?')
                    ->modalDescription('Це видалить рядок з БД. Дія незворотня.')
                    ->visible(fn ($record) => $record->status === 'deleted')
                    ->action(fn (ArticleAnalogExport $record) => $record->delete()),

                Action::make('error')
                    ->label('Помилка')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn ($record) => ($record->status === 'failed') && ! empty($record->error))
                    ->modalHeading('Помилка експорту')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Закрити')
                    ->modalContent(fn ($record) => (string) $record->error),
            ])
            ->bulkActions([
                BulkAction::make('deleteSelectedFiles')
                    ->label('Видалити файли (вибрані)')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Видалити вибрані файли?')
                    ->modalDescription('Видаляються тільки файли. Записи лишаються (стануть deleted).')
                    ->action(function (Collection $records) {
                        $deleted = 0;

                        foreach ($records as $record) {
                            /** @var ArticleAnalogExport $record */
                            if (empty($record->path)) {
                                continue;
                            }

                            $disk = $record->disk ?? 'public';

                            try {
                                if (Storage::disk($disk)->exists($record->path)) {
                                    Storage::disk($disk)->delete($record->path);
                                }

                                $record->update([
                                    'path' => null,
                                    'file_name' => null,
                                    'status' => 'deleted',
                                ]);

                                $deleted++;
                            } catch (\Throwable) {
                                // пропускаємо
                            }
                        }

                        Notification::make()
                            ->title('Готово')
                            ->body("Видалено файлів: {$deleted}")
                            ->success()
                            ->send();
                    }),

                BulkAction::make('purgeDeletedRows')
                    ->label('Очистити записи deleted (вибрані)')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Видалити вибрані записи зі статусом deleted?')
                    ->modalDescription('Це видалить рядки з БД (файлів вже немає).')
                    ->action(function (Collection $records) {
                        $count = 0;

                        foreach ($records as $record) {
                            /** @var ArticleAnalogExport $record */
                            if ($record->status !== 'deleted') {
                                continue;
                            }

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

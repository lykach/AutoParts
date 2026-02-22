<?php

namespace App\Jobs;

use App\Models\ArticleAnalogImport;
use App\Models\User;
use App\Services\ArticleAnalogImportService;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportArticleAnalogsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(
        public int $importId,
    ) {}

    public function handle(ArticleAnalogImportService $service): void
    {
        $import = ArticleAnalogImport::query()->findOrFail($this->importId);

        $import->update([
            'status' => 'processing',
            'error' => null,
            'inserted' => 0,
            'skipped' => 0,
        ]);

        $absolutePath = \Storage::disk($import->disk)->path($import->path);

        $result = $service->importCsv($absolutePath, $import->type, (bool) $import->is_active);

        $import->update([
            'status' => 'done',
            'inserted' => (int) ($result['inserted'] ?? 0),
            'skipped' => (int) ($result['skipped'] ?? 0),
        ]);

        $user = User::find($import->user_id);
        if ($user) {
            Notification::make()
                ->title('Імпорт завершено')
                ->body("Додано: {$import->inserted}, пропущено: {$import->skipped}. Деталі у розділі “Імпорти кросів”.")
                ->success()
                ->sendToDatabase($user);
        }
    }

    public function failed(\Throwable $e): void
    {
        $import = ArticleAnalogImport::query()->find($this->importId);

        if ($import) {
            $import->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            $user = User::find($import->user_id);
            if ($user) {
                Notification::make()
                    ->title('Імпорт не вдався')
                    ->body($e->getMessage())
                    ->danger()
                    ->sendToDatabase($user);
            }
        }
    }
}

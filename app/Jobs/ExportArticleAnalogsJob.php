<?php

namespace App\Jobs;

use App\Models\ArticleAnalog;
use App\Models\ArticleAnalogExport;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ExportArticleAnalogsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(
        public int $exportId,
    ) {}

    public function handle(): void
    {
        $export = ArticleAnalogExport::query()->findOrFail($this->exportId);

        $export->update([
            'status' => 'processing',
            'error' => null,
            'rows' => 0,
        ]);

        $disk = $export->disk ?: 'public';
        $dir  = 'exports/article_analogs';

        $fileName = 'article_analogs_' . $export->type
            . ($export->only_active ? '_active' : '')
            . '_' . now()->format('Ymd_His') . '.csv';

        $path = $dir . '/' . $fileName;

        Storage::disk($disk)->makeDirectory($dir);

        $fullPath = Storage::disk($disk)->path($path);
        $out = fopen($fullPath, 'w');

        if ($out === false) {
            throw new RuntimeException("Не вдалося створити файл експорту: {$fullPath}");
        }

        fputcsv($out, [
            'manufacturer_article',
            'article',
            'manufacturer_analog',
            'analog',
            'type',
            'is_active',
        ]);

        $query = ArticleAnalog::query()
            ->select([
                'id',
                'manufacturer_article',
                'article',
                'manufacturer_analog',
                'analog',
                'type',
                'is_active',
            ])
            ->orderBy('id');

        if (in_array($export->type, ['cross', 'anti'], true)) {
            $query->where('type', $export->type);
        }

        if ($export->only_active) {
            $query->where('is_active', true);
        }

        $rowsWritten = 0;

        $query->chunkById(10000, function ($rows) use ($out, &$rowsWritten) {
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->manufacturer_article,
                    $r->article,
                    $r->manufacturer_analog,
                    $r->analog,
                    $r->type,
                    $r->is_active ? 1 : 0,
                ]);
                $rowsWritten++;
            }
        }, column: 'id');

        fclose($out);

        $export->update([
            'status' => 'done',
            'rows' => $rowsWritten,
            'path' => $path,
            'file_name' => $fileName,
        ]);

        $user = User::find($export->user_id);
        if ($user) {
            Notification::make()
                ->title('Експорт готовий')
                ->body("Файл готовий у розділі: Експорти")
                ->success()
                ->sendToDatabase($user);
        }
    }

    public function failed(\Throwable $e): void
    {
        $export = ArticleAnalogExport::query()->find($this->exportId);

        if ($export) {
            $export->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            $user = User::find($export->user_id);
            if ($user) {
                Notification::make()
                    ->title('Експорт не вдався')
                    ->body($e->getMessage())
                    ->danger()
                    ->sendToDatabase($user);
            }
        }
    }
}

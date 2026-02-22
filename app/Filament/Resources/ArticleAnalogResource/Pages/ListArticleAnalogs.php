<?php

namespace App\Filament\Resources\ArticleAnalogResource\Pages;

use App\Filament\Resources\ArticleAnalogExportResource\ArticleAnalogExportResource;
use App\Filament\Resources\ArticleAnalogImportResource\ArticleAnalogImportResource;
use App\Filament\Resources\ArticleAnalogResource\ArticleAnalogResource;
use App\Jobs\ExportArticleAnalogsJob;
use App\Jobs\ImportArticleAnalogsJob;
use App\Models\ArticleAnalog;
use App\Models\ArticleAnalogExport;
use App\Models\ArticleAnalogImport;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListArticleAnalogs extends ListRecords
{
    protected static string $resource = ArticleAnalogResource::class;

    /**
     * /admin/.../article-analogs?type=cross|anti
     */
    public ?string $type = null;

    protected function getQueryString(): array
    {
        return [
            'type' => ['except' => null],
        ];
    }

    protected function getHeaderActions(): array
    {
        // Лічильники (красиво скорочуємо)
        $all   = ArticleAnalog::count();
        $cross = ArticleAnalog::where('type', 'cross')->count();
        $anti  = ArticleAnalog::where('type', 'anti')->count();

        $allFmt   = $this->formatCount($all);
        $crossFmt = $this->formatCount($cross);
        $antiFmt  = $this->formatCount($anti);

        $defaultTypeForTools = in_array($this->type, ['cross', 'anti'], true) ? $this->type : 'cross';

        return [
            // ✅ компактні сегменти-фільтри
            Actions\Action::make('showAll')
                ->label("Всі ({$allFmt})")
                ->icon('heroicon-o-rectangle-stack')
                ->url(static::getResource()::getUrl('index'))
                ->color($this->type === null ? 'primary' : 'gray'),

            Actions\Action::make('showCross')
                ->label("Кроси ({$crossFmt})")
                ->icon('heroicon-o-check-circle')
                ->url(static::getResource()::getUrl('index', ['type' => 'cross']))
                ->color($this->type === 'cross' ? 'success' : 'gray'),

            Actions\Action::make('showAnti')
                ->label("Антикроси ({$antiFmt})")
                ->icon('heroicon-o-no-symbol')
                ->url(static::getResource()::getUrl('index', ['type' => 'anti']))
                ->color($this->type === 'anti' ? 'danger' : 'gray'),

            // ✅ Інструменти в одному меню (не вилазить за екран)
            Actions\ActionGroup::make([
                // ---------- ІМПОРТ (черга + історія) ----------
                Actions\Action::make('import')
                    ->label('Імпорт CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Select::make('type')
                            ->label('Що імпортуємо')
                            ->options([
                                'cross' => 'Кроси',
                                'anti'  => 'Антикроси',
                            ])
                            ->default($defaultTypeForTools)
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Імпортувати як активні')
                            ->default(true),

                        FileUpload::make('file')
                            ->label('CSV файл')
                            ->helperText('Колонки: manufacturer_article, article, manufacturer_analog, analog. З header або без.')
                            ->required()
                            ->disk('local')
                            ->directory('imports/article_analogs')
                            ->acceptedFileTypes(['text/csv', 'text/plain']),
                    ])
                    ->action(function (array $data) {
                        $userId = (int) auth()->id();

                        // 1) записуємо “історію імпорту”
                        $import = ArticleAnalogImport::create([
                            'user_id'    => $userId,
                            'type'       => (string) $data['type'],
                            'is_active'  => (bool) $data['is_active'],
                            'status'     => 'queued',
                            'disk'       => 'local',
                            'path'       => (string) $data['file'],
                            'file_name'  => basename((string) $data['file']),
                        ]);

                        // 2) запускаємо job
                        ImportArticleAnalogsJob::dispatch($import->id);

                        Notification::make()
                            ->title('Імпорт поставлено в чергу')
                            ->body('Деталі та статус дивись у “Імпорти кросів”.')
                            ->success()
                            ->send();
                    }),

                // ---------- ЕКСПОРТ (черга + історія) ----------
                Actions\Action::make('export')
                    ->label('Експорт CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form([
                        Select::make('type')
                            ->label('Експортувати')
                            ->options([
                                'all'   => 'Всі',
                                'cross' => 'Кроси',
                                'anti'  => 'Антикроси',
                            ])
                            ->default(in_array($this->type, ['cross', 'anti'], true) ? $this->type : 'all')
                            ->required(),

                        Toggle::make('only_active')
                            ->label('Тільки активні')
                            ->default(false),
                    ])
                    ->action(function (array $data) {
                        $export = ArticleAnalogExport::create([
                            'user_id'     => (int) auth()->id(),
                            'type'        => (string) ($data['type'] ?? 'all'),
                            'only_active' => (bool) ($data['only_active'] ?? false),
                            'status'      => 'queued',
                            'disk'        => 'public',
                        ]);

                        ExportArticleAnalogsJob::dispatch($export->id);

                        Notification::make()
                            ->title('Експорт поставлено в чергу')
                            ->body('Готовий файл буде у “Експорти кросів”.')
                            ->success()
                            ->send();
                    }),

                // ---------- Швидкі переходи ----------
                Actions\Action::make('openImports')
                    ->label('Імпорти кросів')
                    ->icon('heroicon-o-queue-list')
                    ->url(fn () => ArticleAnalogImportResource::getUrl('index'))
                    ->color('gray'),

                Actions\Action::make('openExports')
                    ->label('Експорти кросів')
                    ->icon('heroicon-o-archive-box')
                    ->url(fn () => ArticleAnalogExportResource::getUrl('index'))
                    ->color('gray'),
            ])
                ->label('Інструменти')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('gray'),

            // ✅ CTA
            Actions\CreateAction::make()
                ->label('Створити')
                ->icon('heroicon-o-plus')
                ->color('success'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        if (in_array($this->type, ['cross', 'anti'], true)) {
            $query->where('type', $this->type);
        }

        return $query;
    }

    private function formatCount(int $n): string
    {
        if ($n >= 1_000_000_000) {
            return rtrim(rtrim(number_format($n / 1_000_000_000, 1, '.', ''), '0'), '.') . 'B';
        }

        if ($n >= 1_000_000) {
            return rtrim(rtrim(number_format($n / 1_000_000, 1, '.', ''), '0'), '.') . 'M';
        }

        if ($n >= 1_000) {
            return rtrim(rtrim(number_format($n / 1_000, 1, '.', ''), '0'), '.') . 'k';
        }

        return (string) $n;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ArticleAnalogImportService
{
    /**
     * Імпорт CSV з 4 колонок:
     * manufacturer_article, article, manufacturer_analog, analog
     * або з хедером (підтримується автоматично).
     */
    public function importCsv(string $absolutePath, string $type, bool $isActive = true): array
    {
        if (! in_array($type, ['cross', 'anti'], true)) {
            throw new \InvalidArgumentException('Invalid type. Must be cross|anti');
        }

        if (! is_file($absolutePath)) {
            throw new \RuntimeException("File not found: {$absolutePath}");
        }

        $handle = fopen($absolutePath, 'r');
        if (! $handle) {
            throw new \RuntimeException("Cannot open file: {$absolutePath}");
        }

        $inserted = 0;
        $skipped  = 0;

        $batch = [];
        $batchSize = 1000;

        $firstRow = true;
        $headerMap = null;

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            // Пропуск пустих рядків
            if ($this->isRowEmpty($row)) {
                continue;
            }

            // Спроба визначити header
            if ($firstRow) {
                $firstRow = false;

                $maybeHeader = array_map(fn ($v) => strtolower(trim((string) $v)), $row);

                // якщо є назви колонок — будуємо мапу
                if (in_array('article', $maybeHeader, true) || in_array('manufacturer_article', $maybeHeader, true)) {
                    $headerMap = $this->buildHeaderMap($maybeHeader);
                    continue; // наступний рядок — дані
                }
            }

            try {
                [$mArticle, $article, $mAnalog, $analog] = $this->extractColumns($row, $headerMap);

                $mArticle = $this->norm($mArticle);
                $article  = $this->norm($article);
                $mAnalog  = $this->norm($mAnalog);
                $analog   = $this->norm($analog);

                if (! $mArticle || ! $article || ! $mAnalog || ! $analog) {
                    $skipped++;
                    continue;
                }

                $batch[] = [
                    'manufacturer_article' => $mArticle,
                    'article' => $article,
                    'manufacturer_analog' => $mAnalog,
                    'analog' => $analog,
                    'type' => $type,
                    'is_active' => $isActive ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($batch) >= $batchSize) {
                    $inserted += $this->flush($batch);
                    $batch = [];
                }
            } catch (\Throwable $e) {
                $skipped++;
            }
        }

        fclose($handle);

        if (! empty($batch)) {
            $inserted += $this->flush($batch);
        }

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
        ];
    }

    private function flush(array $rows): int
    {
        // ВАЖЛИВО: потрібен UNIQUE індекс, тоді дублікати будуть ігноруватись
        // insertOrIgnore повертає кількість вставлених рядків (MySQL).
        return DB::table('article_analogs')->insertOrIgnore($rows);
    }

    private function buildHeaderMap(array $header): array
    {
        // підтримуємо можливі назви
        $map = [
            'manufacturer_article' => null,
            'article' => null,
            'manufacturer_analog' => null,
            'analog' => null,
        ];

        foreach ($header as $i => $col) {
            $col = str_replace([' ', '-'], '_', $col);

            if (array_key_exists($col, $map)) {
                $map[$col] = $i;
            }
        }

        return $map;
    }

    private function extractColumns(array $row, ?array $headerMap): array
    {
        if ($headerMap) {
            return [
                $row[$headerMap['manufacturer_article']] ?? null,
                $row[$headerMap['article']] ?? null,
                $row[$headerMap['manufacturer_analog']] ?? null,
                $row[$headerMap['analog']] ?? null,
            ];
        }

        // Без хедера — позиційно 4 колонки
        // 0 manufacturer_article, 1 article, 2 manufacturer_analog, 3 analog
        return [
            $row[0] ?? null,
            $row[1] ?? null,
            $row[2] ?? null,
            $row[3] ?? null,
        ];
    }

    private function norm(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') return null;
        return mb_strtoupper($value);
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $v) {
            if (trim((string) $v) !== '') return false;
        }
        return true;
    }
}

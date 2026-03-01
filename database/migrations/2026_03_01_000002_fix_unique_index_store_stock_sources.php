<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Переконаємось що колонка існує
        Schema::table('store_stock_sources', function (Blueprint $table) {
            if (! Schema::hasColumn('store_stock_sources', 'stock_source_location_id')) {
                $table->unsignedBigInteger('stock_source_location_id')->nullable()->after('stock_source_id');
            }
        });

        // 2) Забираємо старий UNIQUE (store_id + stock_source_id) незалежно від назви індексу
        $this->dropUniqueByColumns('store_stock_sources', ['store_id', 'stock_source_id']);

        // 3) Додаємо новий UNIQUE (store_id + stock_source_location_id) якщо його ще нема
        if (! $this->uniqueExistsByColumns('store_stock_sources', ['store_id', 'stock_source_location_id'])) {
            Schema::table('store_stock_sources', function (Blueprint $table) {
                $table->unique(['store_id', 'stock_source_location_id'], 'sss_store_location_unique');
            });
        }

        // 4) (опційно) додатковий звичайний індекс для швидких вибірок
        // Не критично, але корисно
        if (! $this->indexExists('store_stock_sources', 'sss_store_location_idx')) {
            Schema::table('store_stock_sources', function (Blueprint $table) {
                $table->index(['store_id', 'stock_source_location_id'], 'sss_store_location_idx');
            });
        }
    }

    public function down(): void
    {
        // прибираємо новий unique/index якщо є
        if ($this->indexExists('store_stock_sources', 'sss_store_location_unique')) {
            DB::statement("ALTER TABLE `store_stock_sources` DROP INDEX `sss_store_location_unique`");
        }

        if ($this->indexExists('store_stock_sources', 'sss_store_location_idx')) {
            DB::statement("ALTER TABLE `store_stock_sources` DROP INDEX `sss_store_location_idx`");
        }

        // відновлюємо старий unique (store_id + stock_source_id) якщо його нема
        if (! $this->uniqueExistsByColumns('store_stock_sources', ['store_id', 'stock_source_id'])) {
            Schema::table('store_stock_sources', function (Blueprint $table) {
                $table->unique(['store_id', 'stock_source_id'], 'store_stock_sources_store_id_stock_source_id_unique');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return ! empty($rows);
    }

    private function uniqueExistsByColumns(string $table, array $columns): bool
    {
        $indexes = $this->getIndexes($table);

        foreach ($indexes as $name => $idx) {
            if (($idx['non_unique'] ?? 1) != 0) continue;

            $cols = $idx['columns'] ?? [];
            if ($this->sameColumns($cols, $columns)) {
                return true;
            }
        }

        return false;
    }

    private function dropUniqueByColumns(string $table, array $columns): void
    {
        $indexes = $this->getIndexes($table);

        foreach ($indexes as $name => $idx) {
            if (($idx['non_unique'] ?? 1) != 0) continue;

            $cols = $idx['columns'] ?? [];
            if ($this->sameColumns($cols, $columns)) {
                // DROP конкретної назви індексу
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
                return;
            }
        }
    }

    private function getIndexes(string $table): array
    {
        $rows = DB::select("SHOW INDEX FROM `{$table}`");

        $out = [];
        foreach ($rows as $r) {
            $name = $r->Key_name;
            if (! isset($out[$name])) {
                $out[$name] = [
                    'non_unique' => (int) $r->Non_unique,
                    'columns' => [],
                ];
            }
            $out[$name]['columns'][(int) $r->Seq_in_index] = $r->Column_name;
        }

        // впорядкувати колонки по Seq_in_index
        foreach ($out as $name => $idx) {
            ksort($out[$name]['columns']);
            $out[$name]['columns'] = array_values($out[$name]['columns']);
        }

        return $out;
    }

    private function sameColumns(array $a, array $b): bool
    {
        // порівнюємо саме порядок колонок теж
        $a = array_values($a);
        $b = array_values($b);

        if (count($a) !== count($b)) return false;

        for ($i = 0; $i < count($a); $i++) {
            if ((string) $a[$i] !== (string) $b[$i]) return false;
        }

        return true;
    }
};
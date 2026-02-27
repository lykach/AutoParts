<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function dropUniqueByColumns(string $table, array $columns): void
    {
        // шукаємо UNIQUE індекс, який ТОЧНО має ці колонки (у будь-якому порядку)
        $dbName = DB::getDatabaseName();
        $colsSorted = $columns;
        sort($colsSorted);

        $rows = DB::select("
            SELECT s.INDEX_NAME, s.COLUMN_NAME, s.SEQ_IN_INDEX, t.NON_UNIQUE
            FROM information_schema.STATISTICS s
            JOIN information_schema.TABLES tt
                ON tt.TABLE_SCHEMA = s.TABLE_SCHEMA AND tt.TABLE_NAME = s.TABLE_NAME
            JOIN (
                SELECT TABLE_SCHEMA, TABLE_NAME, INDEX_NAME, NON_UNIQUE
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                GROUP BY TABLE_SCHEMA, TABLE_NAME, INDEX_NAME, NON_UNIQUE
            ) t
                ON t.TABLE_SCHEMA = s.TABLE_SCHEMA AND t.TABLE_NAME = s.TABLE_NAME AND t.INDEX_NAME = s.INDEX_NAME
            WHERE s.TABLE_SCHEMA = ? AND s.TABLE_NAME = ?
            ORDER BY s.INDEX_NAME, s.SEQ_IN_INDEX
        ", [$dbName, $table, $dbName, $table]);

        // групуємо по INDEX_NAME і дивимось набір колонок
        $byIndex = [];
        foreach ($rows as $r) {
            $byIndex[$r->INDEX_NAME]['non_unique'] = (int) $r->NON_UNIQUE;
            $byIndex[$r->INDEX_NAME]['cols'][] = $r->COLUMN_NAME;
        }

        foreach ($byIndex as $indexName => $info) {
            if (($info['non_unique'] ?? 1) !== 0) continue; // тільки UNIQUE

            $idxCols = $info['cols'] ?? [];
            $idxColsSorted = $idxCols;
            sort($idxColsSorted);

            if ($idxColsSorted === $colsSorted) {
                // DROP INDEX
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
                return;
            }
        }
    }

    public function up(): void
    {
        // 1) прибрати “неправильний” unique (stock_source_id + product_id), якщо він є
        $this->dropUniqueByColumns('stock_items', ['stock_source_id', 'product_id']);

        // 2) перед новим unique: прибрати дублікати (product_id + stock_source_location_id)
        // залишаємо запис з найбільшим id
        DB::statement("
            DELETE si1 FROM stock_items si1
            INNER JOIN stock_items si2
                ON si1.product_id = si2.product_id
               AND si1.stock_source_location_id = si2.stock_source_location_id
               AND si1.id < si2.id
        ");

        // 3) поставити правильний unique (product_id + stock_source_location_id)
        Schema::table('stock_items', function (Blueprint $table) {
            // якщо вже існує — не впаде? MySQL впаде. Тому перевіримо через information_schema.
        });

        $dbName = DB::getDatabaseName();
        $exists = DB::selectOne("
            SELECT 1 as ok
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = 'stock_items'
              AND INDEX_NAME = 'stock_items_product_location_unique'
            LIMIT 1
        ", [$dbName]);

        if (! $exists) {
            Schema::table('stock_items', function (Blueprint $table) {
                $table->unique(['product_id', 'stock_source_location_id'], 'stock_items_product_location_unique');
            });
        }

        // 4) (опційно) індекс на product_id
        $existsProdIdx = DB::selectOne("
            SELECT 1 as ok
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = 'stock_items'
              AND INDEX_NAME = 'stock_items_product_id_index'
            LIMIT 1
        ", [$dbName]);

        if (! $existsProdIdx) {
            Schema::table('stock_items', function (Blueprint $table) {
                $table->index(['product_id'], 'stock_items_product_id_index');
            });
        }
    }

    public function down(): void
    {
        // знімаємо правильний unique та індекс
        try {
            Schema::table('stock_items', function (Blueprint $table) {
                $table->dropIndex('stock_items_product_id_index');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('stock_items', function (Blueprint $table) {
                $table->dropUnique('stock_items_product_location_unique');
            });
        } catch (\Throwable $e) {}
    }
};
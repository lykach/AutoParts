<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table = 'store_stock_sources';
    private string $indexName = 'sss_store_active_priority_idx';

    private function indexExists(string $table, string $indexName): bool
    {
        $dbName = DB::getDatabaseName();

        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', $dbName)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();

        return $exists;
    }

    public function up(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            if (! Schema::hasColumn($this->table, 'markup_percent')) {
                $table->decimal('markup_percent', 6, 2)->nullable()->after('priority');
            }

            if (! Schema::hasColumn($this->table, 'min_delivery_days')) {
                $table->unsignedSmallInteger('min_delivery_days')->nullable()->after('lead_time_days');
            }

            if (! Schema::hasColumn($this->table, 'max_delivery_days')) {
                $table->unsignedSmallInteger('max_delivery_days')->nullable()->after('min_delivery_days');
            }
        });

        if (! $this->indexExists($this->table, $this->indexName)) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->index(['store_id', 'is_active', 'priority'], $this->indexName);
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists($this->table, $this->indexName)) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropIndex($this->indexName);
            });
        }

        Schema::table($this->table, function (Blueprint $table) {
            if (Schema::hasColumn($this->table, 'markup_percent')) {
                $table->dropColumn('markup_percent');
            }
            if (Schema::hasColumn($this->table, 'min_delivery_days')) {
                $table->dropColumn('min_delivery_days');
            }
            if (Schema::hasColumn($this->table, 'max_delivery_days')) {
                $table->dropColumn('max_delivery_days');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('delivery_pickup_points')) {
            return;
        }

        Schema::table('delivery_pickup_points', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_pickup_points', 'name')) {
                $table->string('name', 150)->nullable()->after('code');
            }

            if (! Schema::hasColumn('delivery_pickup_points', 'address')) {
                $table->string('address', 255)->nullable()->after('name');
            }

            if (! Schema::hasColumn('delivery_pickup_points', 'work_schedule')) {
                $table->text('work_schedule')->nullable()->after('phone');
            }
        });

        DB::table('delivery_pickup_points')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('delivery_pickup_points')
                    ->where('id', $row->id)
                    ->update([
                        'name' => $row->name ?? $row->name_uk ?? null,
                        'address' => $row->address ?? $row->address_uk ?? null,
                        'work_schedule' => $row->work_schedule ?? $row->work_schedule_uk ?? null,
                    ]);
            }
        });

        Schema::table('delivery_pickup_points', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_pickup_points', 'name')) {
                $table->string('name', 150)->nullable(false)->change();
            }
        });

        Schema::table('delivery_pickup_points', function (Blueprint $table) {
            $drop = [];

            foreach ([
                'name_uk',
                'name_en',
                'name_ru',
                'address_uk',
                'address_en',
                'address_ru',
                'work_schedule_uk',
                'work_schedule_en',
                'work_schedule_ru',
            ] as $column) {
                if (Schema::hasColumn('delivery_pickup_points', $column)) {
                    $drop[] = $column;
                }
            }

            if (! empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('delivery_pickup_points')) {
            return;
        }

        Schema::table('delivery_pickup_points', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_pickup_points', 'name_uk')) {
                $table->string('name_uk', 150)->nullable()->after('code');
            }
            if (! Schema::hasColumn('delivery_pickup_points', 'name_en')) {
                $table->string('name_en', 150)->nullable()->after('name_uk');
            }
            if (! Schema::hasColumn('delivery_pickup_points', 'name_ru')) {
                $table->string('name_ru', 150)->nullable()->after('name_en');
            }

            if (! Schema::hasColumn('delivery_pickup_points', 'address_uk')) {
                $table->string('address_uk', 255)->nullable()->after('name_ru');
            }
            if (! Schema::hasColumn('delivery_pickup_points', 'address_en')) {
                $table->string('address_en', 255)->nullable()->after('address_uk');
            }
            if (! Schema::hasColumn('delivery_pickup_points', 'address_ru')) {
                $table->string('address_ru', 255)->nullable()->after('address_en');
            }

            if (! Schema::hasColumn('delivery_pickup_points', 'work_schedule_uk')) {
                $table->text('work_schedule_uk')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('delivery_pickup_points', 'work_schedule_en')) {
                $table->text('work_schedule_en')->nullable()->after('work_schedule_uk');
            }
            if (! Schema::hasColumn('delivery_pickup_points', 'work_schedule_ru')) {
                $table->text('work_schedule_ru')->nullable()->after('work_schedule_en');
            }
        });

        DB::table('delivery_pickup_points')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('delivery_pickup_points')
                    ->where('id', $row->id)
                    ->update([
                        'name_uk' => $row->name ?? null,
                        'address_uk' => $row->address ?? null,
                        'work_schedule_uk' => $row->work_schedule ?? null,
                    ]);
            }
        });

        Schema::table('delivery_pickup_points', function (Blueprint $table) {
            $drop = [];

            foreach (['name', 'address', 'work_schedule'] as $column) {
                if (Schema::hasColumn('delivery_pickup_points', $column)) {
                    $drop[] = $column;
                }
            }

            if (! empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
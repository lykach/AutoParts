<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('locale', 5); // uk/en/ru

            $table->string('name')->nullable();
            $table->string('slug')->nullable();

            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->string('source', 32)->default('manual'); // manual/supplier/tecdoc
            $table->boolean('is_locked')->default(false);

            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'locale']);
            $table->unique(['locale', 'slug']);
            $table->index(['locale', 'name']);

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        // Перенесення даних зі старих колонок name_uk/en/ru (якщо вони ще є у БД на момент міграції)
        // Якщо ви вже їх дропнули вручну - цей блок просто нічого не зробить.
        $columns = Schema::getColumnListing('products');
        $hasUk = in_array('name_uk', $columns, true);
        $hasEn = in_array('name_en', $columns, true);
        $hasRu = in_array('name_ru', $columns, true);

        if ($hasUk || $hasEn || $hasRu) {
            $products = DB::table('products')->select('id', 'name_uk', 'name_en', 'name_ru')->get();

            foreach ($products as $p) {
                if ($hasUk && !empty($p->name_uk)) {
                    DB::table('product_translations')->insert([
                        'product_id' => $p->id,
                        'locale' => 'uk',
                        'name' => $p->name_uk,
                        'slug' => null,
                        'source' => 'manual',
                        'is_locked' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                if ($hasEn && !empty($p->name_en)) {
                    DB::table('product_translations')->insert([
                        'product_id' => $p->id,
                        'locale' => 'en',
                        'name' => $p->name_en,
                        'slug' => null,
                        'source' => 'manual',
                        'is_locked' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                if ($hasRu && !empty($p->name_ru)) {
                    DB::table('product_translations')->insert([
                        'product_id' => $p->id,
                        'locale' => 'ru',
                        'name' => $p->name_ru,
                        'slug' => null,
                        'source' => 'manual',
                        'is_locked' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_translations');
    }
};

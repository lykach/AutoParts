<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArticleAnalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('article_analogs')->truncate();

        $manufacturers = [
            'BOSCH',
            'VALEO',
            'MAGNETI MARELLI',
            'DENSO',
            'STELLOX',
            'AUTOMEGA',
            'FEBI',
            'SKF',
            'CONTINENTAL',
        ];

        $rows = [];
        $total = 2000; // для тесту, можеш поставити 10000

        for ($i = 1; $i <= $total; $i++) {

            $m1 = $manufacturers[array_rand($manufacturers)];
            $m2 = $manufacturers[array_rand($manufacturers)];

            $article = strtoupper('A' . str_pad((string) $i, 6, '0', STR_PAD_LEFT));
            $analog  = strtoupper('B' . str_pad((string) ($i + 10000), 6, '0', STR_PAD_LEFT));

            $rows[] = [
                'manufacturer_article' => $m1,
                'article' => $article,
                'manufacturer_analog' => $m2,
                'analog' => $analog,
                'type' => rand(0, 4) === 0 ? 'anti' : 'cross', // ~20% антикросів
                'is_active' => rand(0, 10) > 1, // ~80% активних
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Вставка пачками по 1000
        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('article_analogs')->insert($chunk);
        }

        $this->command->info("ArticleAnalogs seeded: {$total}");
    }
}

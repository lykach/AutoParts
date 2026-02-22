<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductTranslation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected array $translationsData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // ✅ uppercase article_raw (але пробіли не ріжемо)
        if (isset($data['article_raw'])) {
            $data['article_raw'] = mb_strtoupper((string) $data['article_raw'], 'UTF-8');
        }

        // ✅ UUID: якщо uuid_auto=1 і uuid порожній — генеруємо
        $uuidAuto = (bool) ($data['uuid_auto'] ?? false);
        unset($data['uuid_auto']); // цього поля в БД нема

        $uuid = trim((string) ($data['uuid'] ?? ''));
        if ($uuid === '') {
            if ($uuidAuto) {
                $data['uuid'] = (string) Str::uuid();
            } else {
                // ✅ не пишемо в БД нічого (на create буде NULL)
                unset($data['uuid']);
            }
        } else {
            $data['uuid'] = $uuid;
        }

        [$data, $translations] = $this->extractTranslations($data);
        $this->translationsData = $translations;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->saveTranslations($this->record, $this->translationsData);
    }

    private function extractTranslations(array $data): array
    {
        $locales = ['uk', 'en', 'ru'];
        $translations = [];

        foreach ($locales as $locale) {
            $p = $locale . '_';

            $translations[$locale] = [
                'name' => $data[$p . 'name'] ?? null,
                'slug' => $data[$p . 'slug'] ?? null,
                'short_description' => $data[$p . 'short_description'] ?? null,
                'description' => $data[$p . 'description'] ?? null,
                'meta_title' => $data[$p . 'meta_title'] ?? null,
                'meta_description' => $data[$p . 'meta_description'] ?? null,
            ];

            unset(
                $data[$p . 'name'],
                $data[$p . 'slug'],
                $data[$p . 'short_description'],
                $data[$p . 'description'],
                $data[$p . 'meta_title'],
                $data[$p . 'meta_description'],
            );
        }

        return [$data, $translations];
    }

    private function saveTranslations(Product $product, array $translations): void
    {
        $manufacturer = $product->manufacturer_id
            ? Manufacturer::find($product->manufacturer_id)
            : null;

        $defaultSlug = Product::buildDefaultSlug($manufacturer, $product->article_norm);

        foreach ($translations as $locale => $t) {
            $hasAny = !empty($t['name'])
                || !empty($t['slug'])
                || !empty($t['short_description'])
                || !empty($t['description'])
                || !empty($t['meta_title'])
                || !empty($t['meta_description']);

            if (! $hasAny) {
                continue;
            }

            $slug = trim((string) ($t['slug'] ?? ''));
            if ($slug === '') {
                $slug = $defaultSlug;
            } else {
                $slug = Str::slug($slug);
            }

            $slug = $this->makeUniqueSlug($slug, $locale, $product->id);

            ProductTranslation::updateOrCreate(
                ['product_id' => $product->id, 'locale' => $locale],
                [
                    'name' => $t['name'],
                    'slug' => $slug,
                    'short_description' => $t['short_description'],
                    'description' => $t['description'],
                    'meta_title' => $t['meta_title'],
                    'meta_description' => $t['meta_description'],
                    'source' => 'manual',
                ]
            );
        }
    }

    private function makeUniqueSlug(string $slug, string $locale, int $productId): string
    {
        $base = $slug;
        $candidate = $base;

        $exists = ProductTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $candidate)
            ->where('product_id', '!=', $productId)
            ->exists();

        if (! $exists) {
            return $candidate;
        }

        return $base . '-' . $productId;
    }
}
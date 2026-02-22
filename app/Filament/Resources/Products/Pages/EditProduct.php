<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductTranslation;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RestoreAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
        ];
    }

    /**
     * ✅ Підтягуємо translations у поля uk_*, en_*, ru_*
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Product $product */
        $product = $this->record;

        $byLocale = $product->translations()
            ->get()
            ->keyBy('locale');

        foreach (['uk', 'en', 'ru'] as $locale) {
            $t = $byLocale->get($locale);

            $p = $locale . '_';
            $data[$p . 'name'] = $t?->name;
            $data[$p . 'slug'] = $t?->slug;
            $data[$p . 'short_description'] = $t?->short_description;
            $data[$p . 'description'] = $t?->description;
            $data[$p . 'meta_title'] = $t?->meta_title;
            $data[$p . 'meta_description'] = $t?->meta_description;
        }

        return $data;
    }

    /**
     * ✅ Перед save продукту: вирізаємо translation-поля, зберігаємо їх окремо.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // страховка: uppercase article_raw
        if (isset($data['article_raw'])) {
            $data['article_raw'] = mb_strtoupper(trim((string) $data['article_raw']), 'UTF-8');
        }

        [$data, $translations] = $this->extractTranslations($data);

        // після збереження продукту нам потрібні актуальні article_norm і manufacturer_id
        $this->saveTranslationsAfterSave = $translations;

        return $data;
    }

    protected array $saveTranslationsAfterSave = [];

    protected function afterSave(): void
    {
        $this->saveTranslations($this->record, $this->saveTranslationsAfterSave);
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

        foreach (['uk', 'en', 'ru'] as $locale) {
            $t = $translations[$locale] ?? [];

            $hasAny = !empty($t['name'])
                || !empty($t['slug'])
                || !empty($t['short_description'])
                || !empty($t['description'])
                || !empty($t['meta_title'])
                || !empty($t['meta_description']);

            // якщо взагалі нічого не задано — не чіпаємо (не створюємо пусті)
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
        $exists = ProductTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('product_id', '!=', $productId)
            ->exists();

        return $exists ? ($slug . '-' . $productId) : $slug;
    }
}

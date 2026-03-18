<?php

namespace App\Services\Cms;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MenuService
{
    public function getByCode(string $code, bool $onlyActive = true): ?array
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        $cacheKey = Menu::makeCacheKey($code, $onlyActive);

        $ttl = (int) config('cms.menus.cache_ttl', 0);

        $resolver = function () use ($code, $onlyActive): ?array {
            $menuQuery = Menu::query()
                ->where('code', $code);

            if ($onlyActive) {
                $menuQuery->where('is_active', true);
            }

            /** @var Menu|null $menu */
            $menu = $menuQuery->first();

            if (! $menu) {
                return null;
            }

            $itemsQuery = MenuItem::query()
                ->where('menu_id', $menu->id)
                ->with(['page', 'category', 'manufacturer'])
                ->orderBy('sort')
                ->orderBy('id');

            if ($onlyActive) {
                $itemsQuery->where('is_active', true);
            }

            /** @var Collection<int, MenuItem> $items */
            $items = $itemsQuery->get();

            return [
                'id' => $menu->id,
                'name' => $menu->name,
                'code' => $menu->code,
                'location' => $menu->location?->value ?? $menu->location,
                'is_active' => (bool) $menu->is_active,
                'is_system' => (bool) $menu->is_system,
                'items' => $this->buildTree($items),
            ];
        };

        if ($ttl > 0) {
            return Cache::remember($cacheKey, now()->addMinutes($ttl), $resolver);
        }

        return Cache::rememberForever($cacheKey, $resolver);
    }

    public function getItems(string $code, bool $onlyActive = true): array
    {
        return $this->getByCode($code, $onlyActive)['items'] ?? [];
    }

    public function flushByCode(string $code): void
    {
        Cache::forget(Menu::makeCacheKey($code, true));
        Cache::forget(Menu::makeCacheKey($code, false));
    }

    public function flushMenu(Menu $menu): void
    {
        $this->flushByCode($menu->code);
    }

    /**
     * @param Collection<int, MenuItem> $items
     */
    protected function buildTree(Collection $items): array
    {
        $grouped = $items->groupBy(fn (MenuItem $item) => $item->parent_id ?: 0);

        $mapNode = function (MenuItem $item) use (&$mapNode, $grouped): array {
            $children = ($grouped->get($item->id) ?? collect())
                ->map(fn (MenuItem $child) => $mapNode($child))
                ->values()
                ->all();

            return [
                'id' => $item->id,
                'parent_id' => $item->parent_id,
                'title' => $item->resolved_title,
                'title_uk' => $item->title_uk,
                'title_en' => $item->title_en,
                'title_ru' => $item->title_ru,
                'type' => $item->type?->value ?? $item->type,
                'url' => $item->resolved_url,
                'target_blank' => (bool) $item->target_blank,
                'is_active' => (bool) $item->is_active,
                'icon' => $item->icon,
                'badge_text' => $item->badge_text,
                'badge_color' => $item->badge_color,
                'sort' => (int) $item->sort,
                'children' => $children,
                'has_children' => ! empty($children),
            ];
        };

        return ($grouped->get(0) ?? collect())
            ->map(fn (MenuItem $root) => $mapNode($root))
            ->values()
            ->all();
    }
}
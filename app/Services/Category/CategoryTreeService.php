<?php

namespace App\Services\Category;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CategoryTreeService
{
    public function rebuildAll(): void
    {
        DB::transaction(function () {
            $this->normalizeAllSiblingOrders();

            $roots = Category::query()
                ->whereNull('parent_id')
                ->orderBy('order')
                ->orderBy('id')
                ->get(['id', 'slug']);

            foreach ($roots as $root) {
                $this->rebuildSubtreeFromRoot(
                    categoryId: (int) $root->id,
                    depth: 0,
                    pathIds: (string) $root->id,
                    pathSlugs: (string) $root->slug,
                );
            }

            $this->recountAllProductCounters();
        });
    }

    public function afterSave(int $categoryId, ?int $oldParentId, ?int $newParentId): void
    {
        DB::transaction(function () use ($categoryId, $oldParentId, $newParentId) {
            $this->normalizeSiblingOrder($oldParentId);

            if ($newParentId !== $oldParentId) {
                $this->normalizeSiblingOrder($newParentId);
            }

            $this->rebuildSubtree($categoryId);

            $this->refreshAncestorChain($oldParentId);

            if ($newParentId !== $oldParentId) {
                $this->refreshAncestorChain($newParentId);
            }

            $this->recountAllProductCounters();
        });
    }

    public function afterDelete(?int $oldParentId): void
    {
        DB::transaction(function () use ($oldParentId) {
            $this->normalizeSiblingOrder($oldParentId);
            $this->refreshAncestorChain($oldParentId);
            $this->recountAllProductCounters();
        });
    }

    public function recountAllProductCounters(): void
    {
        DB::table('categories')->update([
            'products_direct_count' => 0,
            'products_total_count' => 0,
        ]);

        $directCounts = DB::table('products')
            ->selectRaw('category_id, COUNT(*) as cnt')
            ->whereNull('deleted_at')
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->pluck('cnt', 'category_id');

        foreach ($directCounts as $categoryId => $cnt) {
            DB::table('categories')
                ->where('id', (int) $categoryId)
                ->update([
                    'products_direct_count' => (int) $cnt,
                ]);
        }

        $categories = Category::query()
            ->orderByDesc('depth')
            ->orderByDesc('id')
            ->get([
                'id',
                'parent_id',
                'depth',
                'products_direct_count',
            ]);

        $totals = [];

        foreach ($categories as $category) {
            $id = (int) $category->id;
            $direct = (int) $category->products_direct_count;
            $childSum = (int) ($totals[$id] ?? 0);

            $total = $direct + $childSum;

            DB::table('categories')
                ->where('id', $id)
                ->update([
                    'products_total_count' => $total,
                ]);

            if ($category->parent_id !== null) {
                $parentId = (int) $category->parent_id;
                $totals[$parentId] = (int) ($totals[$parentId] ?? 0) + $total;
            }
        }
    }

    public function rebuildSubtree(int $categoryId): void
    {
        $node = Category::query()->find($categoryId);

        if (! $node) {
            return;
        }

        if ($node->parent_id === null) {
            $depth = 0;
            $pathIds = (string) $node->id;
            $pathSlugs = (string) $node->slug;
        } else {
            $parent = Category::query()->find($node->parent_id);

            if (! $parent) {
                $depth = 0;
                $pathIds = (string) $node->id;
                $pathSlugs = (string) $node->slug;
            } else {
                $depth = ((int) $parent->depth) + 1;
                $pathIds = trim((string) $parent->path_ids, '/');
                $pathIds = $pathIds !== '' ? $pathIds . '/' . $node->id : (string) $node->id;

                $parentPathSlugs = trim((string) $parent->path_slugs, '/');
                $pathSlugs = $parentPathSlugs !== '' ? $parentPathSlugs . '/' . $node->slug : (string) $node->slug;
            }
        }

        $this->rebuildSubtreeFromRoot(
            categoryId: (int) $node->id,
            depth: $depth,
            pathIds: $pathIds,
            pathSlugs: $pathSlugs,
        );
    }

    public function normalizeSiblingOrder(?int $parentId): void
    {
        $query = Category::query();

        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        $rows = $query
            ->orderBy('order')
            ->orderBy('id')
            ->get(['id']);

        $i = 1;

        foreach ($rows as $row) {
            DB::table('categories')
                ->where('id', $row->id)
                ->update(['order' => $i]);

            $i++;
        }
    }

    private function normalizeAllSiblingOrders(): void
    {
        $parentIds = Category::query()
            ->select('parent_id')
            ->distinct()
            ->pluck('parent_id')
            ->all();

        $hasNullGroup = Category::query()->whereNull('parent_id')->exists();

        foreach ($parentIds as $parentId) {
            $this->normalizeSiblingOrder($parentId !== null ? (int) $parentId : null);
        }

        if ($hasNullGroup && ! in_array(null, $parentIds, true)) {
            $this->normalizeSiblingOrder(null);
        }
    }

    private function rebuildSubtreeFromRoot(
        int $categoryId,
        int $depth,
        string $pathIds,
        string $pathSlugs
    ): void {
        $stack = [[
            'id' => $categoryId,
            'depth' => $depth,
            'path_ids' => $pathIds,
            'path_slugs' => $pathSlugs,
        ]];

        while (! empty($stack)) {
            $item = array_pop($stack);

            $children = Category::query()
                ->where('parent_id', $item['id'])
                ->orderBy('order')
                ->orderBy('id')
                ->get(['id', 'slug']);

            DB::table('categories')
                ->where('id', $item['id'])
                ->update([
                    'depth' => $item['depth'],
                    'path_ids' => $item['path_ids'],
                    'path_slugs' => $item['path_slugs'],
                    'children_count' => $children->count(),
                    'is_leaf' => $children->isEmpty(),
                ]);

            /** @var Collection<int, \App\Models\Category> $children */
            foreach ($children->reverse()->values() as $child) {
                $stack[] = [
                    'id' => (int) $child->id,
                    'depth' => $item['depth'] + 1,
                    'path_ids' => $item['path_ids'] . '/' . $child->id,
                    'path_slugs' => $item['path_slugs'] . '/' . $child->slug,
                ];
            }
        }
    }

    private function refreshAncestorChain(?int $categoryId): void
    {
        $maxDepth = 100;
        $currentId = $categoryId;

        while ($currentId !== null && $maxDepth > 0) {
            $node = Category::query()->find($currentId, ['id', 'parent_id', 'slug']);

            if (! $node) {
                break;
            }

            $this->rebuildSingleNode((int) $node->id);

            $currentId = $node->parent_id !== null ? (int) $node->parent_id : null;
            $maxDepth--;
        }
    }

    private function rebuildSingleNode(int $categoryId): void
    {
        $node = Category::query()->find($categoryId);

        if (! $node) {
            return;
        }

        $childrenCount = Category::query()
            ->where('parent_id', $node->id)
            ->count();

        if ($node->parent_id === null) {
            $depth = 0;
            $pathIds = (string) $node->id;
            $pathSlugs = (string) $node->slug;
        } else {
            $parent = Category::query()->find($node->parent_id);

            if (! $parent) {
                $depth = 0;
                $pathIds = (string) $node->id;
                $pathSlugs = (string) $node->slug;
            } else {
                $depth = ((int) $parent->depth) + 1;

                $parentPathIds = trim((string) $parent->path_ids, '/');
                $pathIds = $parentPathIds !== '' ? $parentPathIds . '/' . $node->id : (string) $node->id;

                $parentPathSlugs = trim((string) $parent->path_slugs, '/');
                $pathSlugs = $parentPathSlugs !== '' ? $parentPathSlugs . '/' . $node->slug : (string) $node->slug;
            }
        }

        DB::table('categories')
            ->where('id', $node->id)
            ->update([
                'depth' => $depth,
                'path_ids' => $pathIds,
                'path_slugs' => $pathSlugs,
                'children_count' => $childrenCount,
                'is_leaf' => $childrenCount === 0,
            ]);
    }
}
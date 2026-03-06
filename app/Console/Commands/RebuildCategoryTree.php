<?php

namespace App\Console\Commands;

use App\Services\Category\CategoryTreeService;
use Illuminate\Console\Command;

class RebuildCategoryTree extends Command
{
    protected $signature = 'categories:rebuild-tree';
    protected $description = 'Rebuild category tree structure and product counters';

    public function handle(CategoryTreeService $treeService): int
    {
        $this->info('Починаю rebuild дерева категорій...');

        $treeService->rebuildAll();

        $this->info('Готово. Дерево та лічильники товарів перебудовано.');

        return self::SUCCESS;
    }
}
<?php

use App\Services\Cms\MenuService;
use App\Services\Cms\PageService;

if (! function_exists('cms_menu')) {
    function cms_menu(string $code, bool $onlyActive = true): array
    {
        return app(MenuService::class)->getItems($code, $onlyActive);
    }
}

if (! function_exists('cms_menu_full')) {
    function cms_menu_full(string $code, bool $onlyActive = true): ?array
    {
        return app(MenuService::class)->getByCode($code, $onlyActive);
    }
}

if (! function_exists('cms_page')) {
    function cms_page(string $slug, bool $onlyPublished = true): ?array
    {
        return app(PageService::class)->getBySlug($slug, $onlyPublished);
    }
}

if (! function_exists('cms_system_page')) {
    function cms_system_page(string $slug, bool $onlyPublished = true): ?array
    {
        return app(PageService::class)->getSystemPage($slug, $onlyPublished);
    }
}
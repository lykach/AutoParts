<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Cms\PageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function show(string $slug, Request $request, PageService $pageService): Response
    {
        $page = $pageService->getBySlug($slug, true);

        abort_if(! $page, 404);

        $locale = app()->getLocale();

        $title = match ($locale) {
            'uk', 'ua' => $page['title_uk'] ?: $page['title'],
            'en' => $page['title_en'] ?: $page['title_uk'] ?: $page['title'],
            'ru' => $page['title_ru'] ?: $page['title_uk'] ?: $page['title'],
            default => $page['title_uk'] ?: $page['title'],
        };

        $excerpt = match ($locale) {
            'uk', 'ua' => $page['excerpt_uk'] ?? null,
            'en' => $page['excerpt_en'] ?: ($page['excerpt_uk'] ?? null),
            'ru' => $page['excerpt_ru'] ?: ($page['excerpt_uk'] ?? null),
            default => $page['excerpt_uk'] ?? null,
        };

        $content = match ($locale) {
            'uk', 'ua' => $page['content_uk'] ?? null,
            'en' => $page['content_en'] ?: ($page['content_uk'] ?? null),
            'ru' => $page['content_ru'] ?: ($page['content_uk'] ?? null),
            default => $page['content_uk'] ?? null,
        };

        $seoTitle = match ($locale) {
            'uk', 'ua' => $page['seo_title_uk'] ?? null,
            'en' => $page['seo_title_en'] ?: ($page['seo_title_uk'] ?? null),
            'ru' => $page['seo_title_ru'] ?: ($page['seo_title_uk'] ?? null),
            default => $page['seo_title_uk'] ?? null,
        };

        $seoDescription = match ($locale) {
            'uk', 'ua' => $page['seo_description_uk'] ?? null,
            'en' => $page['seo_description_en'] ?: ($page['seo_description_uk'] ?? null),
            'ru' => $page['seo_description_ru'] ?: ($page['seo_description_uk'] ?? null),
            default => $page['seo_description_uk'] ?? null,
        };

        $component = $this->resolveComponent($page['template'] ?? 'default');

        return Inertia::render($component, [
            'page' => array_merge($page, [
                'title_resolved' => $title,
                'excerpt_resolved' => $excerpt,
                'content_resolved' => $content,
                'seo_title_resolved' => $seoTitle ?: $title,
                'seo_description_resolved' => $seoDescription ?: ($excerpt ?? ''),
                'locale' => $locale,
            ]),
        ]);
    }

    protected function resolveComponent(string $template): string
    {
        return match ($template) {
            'contacts' => 'Cms/ContactsPage',
            'delivery_payment' => 'Cms/DeliveryPaymentPage',
            'useful_info' => 'Cms/UsefulInfoPage',
            default => 'Cms/PageShow',
        };
    }
}
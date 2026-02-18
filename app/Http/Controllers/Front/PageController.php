<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Content\Page\InfoPage;
use App\Services\Content\ContentBlockResolver;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        $locale = app()->getLocale();
        $fallbackLocale = (string) config('app.locale');

        $page = InfoPage::query()
            ->where('is_active', true)
            ->where(function ($q): void {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->whereHas('translations', function ($q) use ($locale, $fallbackLocale, $slug): void {
                $q->whereIn('locale', [$locale, $fallbackLocale])
                    ->where('slug', $slug);
            })
            ->with(['translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale])])
            ->firstOrFail();

        $topBlocks = app(ContentBlockResolver::class)->forPlacement(
            placement: 'page.top',
            locale: $locale,
            targetType: 'page',
            targetRef: $slug,
            frontendVariant: 'desktop'
        );

        $bottomBlocks = app(ContentBlockResolver::class)->forPlacement(
            placement: 'page.bottom',
            locale: $locale,
            targetType: 'page',
            targetRef: $slug,
            frontendVariant: 'desktop'
        );

        return view('front.desktop.pages.show', [
            'page' => $page,
            'topBlocks' => $topBlocks,
            'bottomBlocks' => $bottomBlocks,
            'locale' => $locale,
            'fallbackLocale' => $fallbackLocale,
        ]);
    }
}

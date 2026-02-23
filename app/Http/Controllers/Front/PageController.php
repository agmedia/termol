<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Category\Category;
use App\Http\Controllers\Front\Concerns\ResolvesFrontendView;
use App\Models\Content\Page\InfoPage;
use App\Services\Content\ContentBlockResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    use ResolvesFrontendView;

    public function category(Request $request, string $slug): View
    {
        $locale = app()->getLocale();
        $fallbackLocale = (string) config('app.locale');
        $variant = $this->frontendVariant($request);

        $category = Category::query()
            ->where('scope', Category::SCOPE_PAGE)
            ->where('is_active', true)
            ->whereHas('translations', function ($q) use ($locale, $fallbackLocale, $slug): void {
                $q->where('scope', Category::SCOPE_PAGE)
                    ->whereIn('locale', [$locale, $fallbackLocale])
                    ->where('slug', $slug);
            })
            ->with(['translations' => fn ($q) => $q
                ->where('scope', Category::SCOPE_PAGE)
                ->whereIn('locale', [$locale, $fallbackLocale])])
            ->firstOrFail();

        $pages = InfoPage::query()
            ->where('is_active', true)
            ->where(function ($q): void {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id))
            ->with(['translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale])])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $topBlocks = app(ContentBlockResolver::class)->forPlacement(
            placement: 'page-category.top',
            locale: $locale,
            targetType: 'page-category',
            targetRef: $slug,
            frontendVariant: $variant
        );

        $bottomBlocks = app(ContentBlockResolver::class)->forPlacement(
            placement: 'page-category.bottom',
            locale: $locale,
            targetType: 'page-category',
            targetRef: $slug,
            frontendVariant: $variant
        );

        return view($this->frontendView($request, 'pages.category'), [
            'category' => $category,
            'pages' => $pages,
            'topBlocks' => $topBlocks,
            'bottomBlocks' => $bottomBlocks,
            'locale' => $locale,
            'fallbackLocale' => $fallbackLocale,
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $locale = app()->getLocale();
        $fallbackLocale = (string) config('app.locale');
        $variant = $this->frontendVariant($request);

        $page = InfoPage::query()
            ->where('is_active', true)
            ->where(function ($q): void {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->whereHas('translations', function ($q) use ($slug): void {
                $q->where('slug', $slug);
            })
            ->with('translations')
            ->firstOrFail();

        $selectedTranslation = $page->translations->firstWhere('slug', $slug)
            ?? $page->translations->firstWhere('locale', $locale)
            ?? $page->translations->firstWhere('locale', $fallbackLocale)
            ?? $page->translations->first();

        $topBlocks = app(ContentBlockResolver::class)->forPlacement(
            placement: 'page.top',
            locale: $locale,
            targetType: 'page',
            targetRef: $slug,
            frontendVariant: $variant
        );

        $bottomBlocks = app(ContentBlockResolver::class)->forPlacement(
            placement: 'page.bottom',
            locale: $locale,
            targetType: 'page',
            targetRef: $slug,
            frontendVariant: $variant
        );

        return view($this->frontendView($request, 'pages.show'), [
            'page' => $page,
            'selectedTranslation' => $selectedTranslation,
            'topBlocks' => $topBlocks,
            'bottomBlocks' => $bottomBlocks,
            'locale' => $locale,
            'fallbackLocale' => $fallbackLocale,
        ]);
    }
}

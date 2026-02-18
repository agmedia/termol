<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Product\Product;
use App\Services\Content\ContentBlockResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $locale = app()->getLocale();
        $fallbackLocale = (string) config('app.locale');

        $product = Product::query()
            ->where('is_active', true)
            ->whereHas('translations', function ($q) use ($locale, $fallbackLocale, $slug): void {
                $q->whereIn('locale', [$locale, $fallbackLocale])
                    ->where('slug', $slug);
            })
            ->with([
                'translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
                'categories.translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
                'manufacturer.translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
            ])
            ->firstOrFail();

        $categoryIds = $product->categories->pluck('id')->map(fn ($id) => (int) $id)->all();

        $related = Product::query()
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->when($categoryIds !== [], fn ($q) => $q->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('categories.id', $categoryIds)))
            ->with(['translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale])])
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        $topBlocks = app(ContentBlockResolver::class)->forPlacement(
            placement: 'product.top',
            locale: $locale,
            targetType: 'product',
            targetRef: $slug,
            frontendVariant: 'desktop'
        );

        $bottomBlocks = app(ContentBlockResolver::class)->forPlacement(
            placement: 'product.bottom',
            locale: $locale,
            targetType: 'product',
            targetRef: $slug,
            frontendVariant: 'desktop'
        );

        return view('front.desktop.products.show', [
            'product' => $product,
            'related' => $related,
            'topBlocks' => $topBlocks,
            'bottomBlocks' => $bottomBlocks,
            'locale' => $locale,
            'fallbackLocale' => $fallbackLocale,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Front\Concerns\ResolvesFrontendView;
use App\Models\Catalog\Category\Category;
use App\Models\Catalog\Manufacturer\Manufacturer;
use App\Models\Catalog\Option\OptionValue;
use App\Models\Catalog\Product\Product;
use App\Services\Content\ContentBlockResolver;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    use ResolvesFrontendView;

    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $fallbackLocale = (string) config('app.locale');
        $search = trim((string) $request->query('q', ''));
        $categorySlug = trim((string) $request->query('category', ''));
        $manufacturerSlug = trim((string) $request->query('manufacturer', ''));
        $sizeId = (int) $request->query('size', 0);
        $sort = (string) $request->query('sort', 'newest');
        $gridCols = (int) $request->query('cols', 4);
        if (! in_array($gridCols, [1, 2, 3, 4, 5], true)) {
            $gridCols = 4;
        }

        $query = Product::query()
            ->where('is_active', true)
            ->with([
                'translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
                'categories.translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
                'manufacturer.translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
                'media' => fn ($q) => $q
                    ->whereIn('collection_name', ['product_main', 'product_gallery'])
                    ->orderBy('order_column')
                    ->orderBy('id'),
                'optionValues' => fn ($q) => $q
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->with([
                        'optionValue.translations' => fn ($tq) => $tq->whereIn('locale', [$locale, $fallbackLocale]),
                        'parentOptionValue.translations' => fn ($tq) => $tq->whereIn('locale', [$locale, $fallbackLocale]),
                    ]),
            ]);

        if ($search !== '') {
            $query->whereHas('translations', function ($q) use ($locale, $fallbackLocale, $search): void {
                $q->whereIn('locale', [$locale, $fallbackLocale])
                    ->where(function ($searchQuery) use ($search): void {
                        $searchQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('excerpt', 'like', '%'.$search.'%')
                            ->orWhere('description', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($categorySlug !== '') {
            $query->whereHas('categories', function ($categoryQuery) use ($locale, $fallbackLocale, $categorySlug): void {
                $categoryQuery
                    ->where('scope', Category::SCOPE_CATALOG)
                    ->where('is_active', true)
                    ->whereHas('translations', function ($translationQuery) use ($locale, $fallbackLocale, $categorySlug): void {
                        $translationQuery
                            ->where('scope', Category::SCOPE_CATALOG)
                            ->whereIn('locale', [$locale, $fallbackLocale])
                            ->where('slug', $categorySlug);
                    });
            });
        }

        if ($manufacturerSlug !== '') {
            $query->whereHas('manufacturer', function ($manufacturerQuery) use ($locale, $fallbackLocale, $manufacturerSlug): void {
                $manufacturerQuery
                    ->where('is_active', true)
                    ->whereHas('translations', function ($translationQuery) use ($locale, $fallbackLocale, $manufacturerSlug): void {
                        $translationQuery
                            ->whereIn('locale', [$locale, $fallbackLocale])
                            ->where('slug', $manufacturerSlug);
                    });
            });
        }

        if ($sizeId > 0) {
            $query->whereHas('optionValues', function ($optionQuery) use ($sizeId): void {
                $optionQuery
                    ->where('is_active', true)
                    ->where(function ($sizeQuery) use ($sizeId): void {
                        $sizeQuery
                            ->where('option_value_id', $sizeId)
                            ->orWhere('parent_option_value_id', $sizeId);
                    });
            });
        }

        match ($sort) {
            'price_low' => $query->orderBy('base_price'),
            'price_high' => $query->orderByDesc('base_price'),
            'stock_high' => $query->orderByDesc('stock_qty')->orderByDesc('id'),
            'oldest' => $query->orderBy('id'),
            default => $query->orderByDesc('id'),
        };

        $products = $query
            ->paginate($this->shopPerPage($request, $gridCols))
            ->withQueryString();

        $categories = Category::query()
            ->where('scope', Category::SCOPE_CATALOG)
            ->where('is_active', true)
            ->with(['translations' => fn ($q) => $q
                ->where('scope', Category::SCOPE_CATALOG)
                ->whereIn('locale', [$locale, $fallbackLocale])])
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $manufacturers = Manufacturer::query()
            ->where('is_active', true)
            ->with(['translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale])])
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $sizes = OptionValue::query()
            ->where('is_active', true)
            ->whereHas('productOptionValues', function ($q): void {
                $q->where('is_active', true)
                    ->whereHas('product', fn ($productQuery) => $productQuery->where('is_active', true));
            })
            ->with(['translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale])])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view($this->frontendView($request, 'shop.index'), [
            'products' => $products,
            'categories' => $categories,
            'manufacturers' => $manufacturers,
            'sizes' => $sizes,
            'filters' => [
                'q' => $search,
                'category' => $categorySlug,
                'manufacturer' => $manufacturerSlug,
                'size' => $sizeId > 0 ? $sizeId : '',
                'sort' => $sort,
                'cols' => $gridCols,
            ],
            'locale' => $locale,
            'fallbackLocale' => $fallbackLocale,
        ]);
    }

    public function categories(Request $request): View
    {
        $locale = app()->getLocale();
        $fallbackLocale = (string) config('app.locale');
        $search = trim((string) $request->query('q', ''));

        $categoriesQuery = Category::query()
            ->where('scope', Category::SCOPE_CATALOG)
            ->where('is_active', true)
            ->with(['translations' => fn ($q) => $q
                ->where('scope', Category::SCOPE_CATALOG)
                ->whereIn('locale', [$locale, $fallbackLocale])])
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($search !== '') {
            $categoriesQuery->whereHas('translations', function ($q) use ($locale, $fallbackLocale, $search): void {
                $q->where('scope', Category::SCOPE_CATALOG)
                    ->whereIn('locale', [$locale, $fallbackLocale])
                    ->where('name', 'like', '%'.$search.'%');
            });
        }

        $categories = $categoriesQuery->paginate(18)->withQueryString();

        return view($this->frontendView($request, 'categories.index'), [
            'categories' => $categories,
            'search' => $search,
            'locale' => $locale,
            'fallbackLocale' => $fallbackLocale,
        ]);
    }

    public function showCategory(Request $request, string $slug): View
    {
        $locale = app()->getLocale();
        $fallbackLocale = (string) config('app.locale');
        $variant = $this->frontendVariant($request);

        $category = Category::query()
            ->where('scope', Category::SCOPE_CATALOG)
            ->where('is_active', true)
            ->whereHas('translations', function ($q) use ($locale, $fallbackLocale, $slug): void {
                $q->where('scope', Category::SCOPE_CATALOG)
                    ->whereIn('locale', [$locale, $fallbackLocale])
                    ->where('slug', $slug);
            })
            ->with([
                'translations' => fn ($q) => $q->where('scope', Category::SCOPE_CATALOG)->whereIn('locale', [$locale, $fallbackLocale]),
            ])
            ->firstOrFail();

        $products = $category->products()
            ->where('is_active', true)
            ->with([
                'translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
                'manufacturer.translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
            ])
            ->orderBy('category_product.sort_order')
            ->orderByDesc('products.id')
            ->paginate($this->productPerPage($request, false))
            ->withQueryString();

        $topBlocks = app(ContentBlockResolver::class)->forPlacement(
            placement: 'category.top',
            locale: $locale,
            targetType: 'category',
            targetRef: $slug,
            frontendVariant: $variant
        );

        $bottomBlocks = app(ContentBlockResolver::class)->forPlacement(
            placement: 'category.bottom',
            locale: $locale,
            targetType: 'category',
            targetRef: $slug,
            frontendVariant: $variant
        );

        return view($this->frontendView($request, 'categories.show'), [
            'category' => $category,
            'products' => $products,
            'topBlocks' => $topBlocks,
            'bottomBlocks' => $bottomBlocks,
            'locale' => $locale,
            'fallbackLocale' => $fallbackLocale,
        ]);
    }

    private function productPerPage(Request $request, bool $manufacturerView): int
    {
        $variant = (string) $request->attributes->get('frontend_variant', 'desktop');
        $settings = app(SystemSettingsService::class);

        if ($manufacturerView) {
            return $settings->getInt(
                $variant === 'mobile'
                    ? 'front_manufacturer_products_per_page_mobile'
                    : 'front_manufacturer_products_per_page_desktop',
                (int) config(
                    $variant === 'mobile'
                        ? 'admin_ui.pagination.front_manufacturer_products_per_page_mobile'
                        : 'admin_ui.pagination.front_manufacturer_products_per_page_desktop',
                    12
                ),
                4,
                120
            );
        }

        return $settings->getInt(
            $variant === 'mobile'
                ? 'front_category_products_per_page_mobile'
                : 'front_category_products_per_page_desktop',
            (int) config(
                $variant === 'mobile'
                    ? 'admin_ui.pagination.front_category_products_per_page_mobile'
                    : 'admin_ui.pagination.front_category_products_per_page_desktop',
                12
            ),
            4,
            120
        );
    }

    private function shopPerPage(Request $request, int $gridCols): int
    {
        $basePerPage = $this->productPerPage($request, false);
        $variant = $this->frontendVariant($request);

        if ($variant === 'mobile') {
            $mobileCols = in_array($gridCols, [1, 2], true) ? $gridCols : 1;
            $rows = (int) ceil($basePerPage / max(1, $mobileCols));

            return max($mobileCols, $rows * $mobileCols);
        }

        $desktopCols = in_array($gridCols, [2, 3, 4, 5], true) ? $gridCols : 4;
        $rows = (int) ceil($basePerPage / $desktopCols);

        return max($desktopCols, $rows * $desktopCols);
    }
}

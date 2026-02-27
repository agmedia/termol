<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Front\Concerns\ResolvesFrontendView;
use App\Http\Controllers\Front\Concerns\ResolvesGridColumns;
use App\Models\Catalog\Category\Category;
use App\Models\Catalog\Manufacturer\Manufacturer;
use App\Models\Catalog\Attribute\Attribute;
use App\Models\Catalog\Option\Option;
use App\Models\Catalog\Option\OptionValue;
use App\Models\Catalog\Product\Product;
use App\Services\Content\ContentBlockResolver;
use App\Services\Front\WishlistService;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CatalogController extends Controller
{
    use ResolvesFrontendView;
    use ResolvesGridColumns;

    public function index(Request $request): Response|RedirectResponse
    {
        $locale = app()->getLocale();
        $fallbackLocale = (string) config('app.locale');
        $search = trim((string) $request->query('q', ''));
        $categorySlug = trim((string) $request->query('category', ''));
        $manufacturerSlug = trim((string) $request->query('manufacturer', ''));
        $sort = (string) $request->query('sort', 'newest');
        $configuredOptionIds = $this->configuredFilterOptionIds();
        $configuredAttributeGroups = $this->configuredFilterAttributeGroups();
        $optionFilters = $this->catalogOptionFilters($locale, $fallbackLocale, $configuredOptionIds);
        $attributeFilters = $this->catalogAttributeFilters($locale, $fallbackLocale, $configuredAttributeGroups);

        if ($optionFilters === []) {
            $optionFilters = $this->legacySizeFallbackFilter($locale, $fallbackLocale);
        }

        $selectedOptionFilters = [];
        foreach ($optionFilters as $filter) {
            $valueId = (int) $request->query((string) $filter['query_key'], 0);
            $selectedOptionFilters[(string) $filter['query_key']] = $valueId > 0 ? $valueId : null;
        }
        $selectedAttributeFilters = [];
        foreach ($attributeFilters as $filter) {
            $valueId = (int) $request->query((string) $filter['query_key'], 0);
            $selectedAttributeFilters[(string) $filter['query_key']] = $valueId > 0 ? $valueId : null;
        }
        $gridCols = $this->resolveGridCols($request, 4);
        $this->queueGridColsCookie($gridCols);
        if ($request->query->has('cols')) {
            return $this->redirectWithoutCols($request);
        }

        $query = Product::query()
            ->select(['id', 'code', 'sku', 'base_price', 'stock_qty', 'tax_rate_id', 'manufacturer_id', 'is_active'])
            ->where('is_active', true)
            ->with([
                'taxRate:id,rate,rate_type,is_active',
                'translations' => fn ($q) => $q
                    ->select(['id', 'product_id', 'locale', 'slug', 'name', 'excerpt'])
                    ->whereIn('locale', [$locale, $fallbackLocale]),
                'categories.translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
                'manufacturer.translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
                'media' => fn ($q) => $q
                    ->whereIn('collection_name', ['product_main', 'product_gallery'])
                    ->orderBy('order_column')
                    ->orderBy('id'),
                'optionValues' => fn ($q) => $q
                    ->select(['id', 'product_id', 'option_value_id', 'parent_option_value_id', 'is_active', 'sort_order'])
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->with([
                        'optionValue.translations' => fn ($tq) => $tq
                            ->select(['id', 'option_value_id', 'locale', 'name'])
                            ->whereIn('locale', [$locale, $fallbackLocale]),
                        'parentOptionValue.translations' => fn ($tq) => $tq
                            ->select(['id', 'option_value_id', 'locale', 'name'])
                            ->whereIn('locale', [$locale, $fallbackLocale]),
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

        foreach ($selectedOptionFilters as $selectedOptionValueId) {
            if ($selectedOptionValueId && $selectedOptionValueId > 0) {
                $this->applyOptionValueFilter($query, $selectedOptionValueId);
            }
        }

        foreach ($attributeFilters as $attributeFilter) {
            $queryKey = (string) $attributeFilter['query_key'];
            $selectedAttributeId = (int) ($selectedAttributeFilters[$queryKey] ?? 0);
            if ($selectedAttributeId <= 0) {
                continue;
            }

            $query->whereHas('attributes', function ($attributeQuery) use ($selectedAttributeId): void {
                $attributeQuery->where('catalog_attributes.id', $selectedAttributeId);
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

        $categories = $this->cachedCatalogCategories($locale, $fallbackLocale);
        $manufacturers = $this->cachedCatalogManufacturers($locale, $fallbackLocale);
        $response = response()->view($this->frontendView($request, 'shop.index'), [
            'products' => $products,
            'categories' => $categories,
            'manufacturers' => $manufacturers,
            'optionFilters' => $this->withSelectedFilters($optionFilters, $selectedOptionFilters),
            'attributeFilters' => $this->withSelectedFilters($attributeFilters, $selectedAttributeFilters),
            'filters' => [
                'q' => $search,
                'category' => $categorySlug,
                'manufacturer' => $manufacturerSlug,
                'sort' => $sort,
                'cols' => $gridCols,
            ],
            'locale' => $locale,
            'fallbackLocale' => $fallbackLocale,
        ]);

        return $this->withDesktopCacheHeaders($request, $response, 'shop');
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

    public function showCategory(Request $request, string $slug): Response|RedirectResponse
    {
        $locale = app()->getLocale();
        $fallbackLocale = (string) config('app.locale');
        $variant = $this->frontendVariant($request);
        $search = trim((string) $request->query('q', ''));
        $categorySlug = $slug;
        $manufacturerSlug = trim((string) $request->query('manufacturer', ''));
        $sort = (string) $request->query('sort', 'newest');
        $gridCols = $this->resolveGridCols($request, 4);
        $this->queueGridColsCookie($gridCols);
        if ($request->query->has('cols')) {
            return $this->redirectWithoutCols($request);
        }

        $category = Category::query()
            ->where('scope', Category::SCOPE_CATALOG)
            ->where('is_active', true)
            ->whereHas('translations', function ($q) use ($locale, $fallbackLocale, $categorySlug): void {
                $q->where('scope', Category::SCOPE_CATALOG)
                    ->whereIn('locale', [$locale, $fallbackLocale])
                    ->where('slug', $categorySlug);
            })
            ->with([
                'translations' => fn ($q) => $q->where('scope', Category::SCOPE_CATALOG)->whereIn('locale', [$locale, $fallbackLocale]),
            ])
            ->firstOrFail();

        $categoryTreeIds = $category->descendants()
            ->where('scope', Category::SCOPE_CATALOG)
            ->where('is_active', true)
            ->pluck('id');
        $categoryTreeIds->prepend($category->id);
        $categoryScopeIds = $categoryTreeIds
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $configuredOptionIds = $this->configuredFilterOptionIds();
        $configuredAttributeGroups = $this->configuredFilterAttributeGroups();
        $optionFilters = $this->catalogOptionFilters($locale, $fallbackLocale, $configuredOptionIds, $categoryScopeIds);
        $attributeFilters = $this->catalogAttributeFilters($locale, $fallbackLocale, $configuredAttributeGroups, $categoryScopeIds);

        if ($optionFilters === []) {
            $optionFilters = $this->legacySizeFallbackFilter($locale, $fallbackLocale, $categoryScopeIds);
        }

        $selectedOptionFilters = [];
        foreach ($optionFilters as $filter) {
            $valueId = (int) $request->query((string) $filter['query_key'], 0);
            $selectedOptionFilters[(string) $filter['query_key']] = $valueId > 0 ? $valueId : null;
        }
        $selectedAttributeFilters = [];
        foreach ($attributeFilters as $filter) {
            $valueId = (int) $request->query((string) $filter['query_key'], 0);
            $selectedAttributeFilters[(string) $filter['query_key']] = $valueId > 0 ? $valueId : null;
        }

        $productsQuery = Product::query()
            ->select(['id', 'code', 'sku', 'base_price', 'stock_qty', 'tax_rate_id', 'manufacturer_id', 'is_active'])
            ->where('is_active', true)
            ->with([
                'taxRate:id,rate,rate_type,is_active',
                'translations' => fn ($q) => $q
                    ->select(['id', 'product_id', 'locale', 'slug', 'name', 'excerpt'])
                    ->whereIn('locale', [$locale, $fallbackLocale]),
                'categories.translations' => fn ($q) => $q
                    ->where('scope', Category::SCOPE_CATALOG)
                    ->whereIn('locale', [$locale, $fallbackLocale]),
                'manufacturer.translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
                'media' => fn ($q) => $q
                    ->whereIn('collection_name', ['product_main', 'product_gallery'])
                    ->orderBy('order_column')
                    ->orderBy('id'),
                'optionValues' => fn ($q) => $q
                    ->select(['id', 'product_id', 'option_value_id', 'parent_option_value_id', 'is_active', 'sort_order'])
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->with([
                        'optionValue.translations' => fn ($tq) => $tq
                            ->select(['id', 'option_value_id', 'locale', 'name'])
                            ->whereIn('locale', [$locale, $fallbackLocale]),
                        'parentOptionValue.translations' => fn ($tq) => $tq
                            ->select(['id', 'option_value_id', 'locale', 'name'])
                            ->whereIn('locale', [$locale, $fallbackLocale]),
                    ]),
            ])
            ->whereHas('categories', function ($categoryQuery) use ($categoryTreeIds): void {
                $categoryQuery
                    ->where('scope', Category::SCOPE_CATALOG)
                    ->where('is_active', true)
                    ->whereIn('categories.id', $categoryTreeIds);
            });

        if ($search !== '') {
            $productsQuery->whereHas('translations', function ($q) use ($locale, $fallbackLocale, $search): void {
                $q->whereIn('locale', [$locale, $fallbackLocale])
                    ->where(function ($searchQuery) use ($search): void {
                        $searchQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('excerpt', 'like', '%'.$search.'%')
                            ->orWhere('description', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($manufacturerSlug !== '') {
            $productsQuery->whereHas('manufacturer', function ($manufacturerQuery) use ($locale, $fallbackLocale, $manufacturerSlug): void {
                $manufacturerQuery
                    ->where('is_active', true)
                    ->whereHas('translations', function ($translationQuery) use ($locale, $fallbackLocale, $manufacturerSlug): void {
                        $translationQuery
                            ->whereIn('locale', [$locale, $fallbackLocale])
                            ->where('slug', $manufacturerSlug);
                    });
            });
        }

        foreach ($selectedOptionFilters as $selectedOptionValueId) {
            if ($selectedOptionValueId && $selectedOptionValueId > 0) {
                $this->applyOptionValueFilter($productsQuery, $selectedOptionValueId);
            }
        }

        foreach ($attributeFilters as $attributeFilter) {
            $queryKey = (string) $attributeFilter['query_key'];
            $selectedAttributeId = (int) ($selectedAttributeFilters[$queryKey] ?? 0);
            if ($selectedAttributeId <= 0) {
                continue;
            }

            $productsQuery->whereHas('attributes', function ($attributeQuery) use ($selectedAttributeId): void {
                $attributeQuery->where('catalog_attributes.id', $selectedAttributeId);
            });
        }

        match ($sort) {
            'price_low' => $productsQuery->orderBy('base_price'),
            'price_high' => $productsQuery->orderByDesc('base_price'),
            'stock_high' => $productsQuery->orderByDesc('stock_qty')->orderByDesc('id'),
            'oldest' => $productsQuery->orderBy('id'),
            default => $productsQuery->orderByDesc('id'),
        };

        $products = $productsQuery
            ->paginate($this->shopPerPage($request, $gridCols))
            ->withQueryString();

        $categories = $this->cachedCatalogCategories($locale, $fallbackLocale);
        $manufacturers = $this->cachedCatalogManufacturers($locale, $fallbackLocale);

        $subcategories = $category->children()
            ->where('scope', Category::SCOPE_CATALOG)
            ->where('is_active', true)
            ->with(['translations' => fn ($q) => $q
                ->where('scope', Category::SCOPE_CATALOG)
                ->whereIn('locale', [$locale, $fallbackLocale])])
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $subcategories->transform(function (Category $subCategory): Category {
            $subTreeIds = Category::query()
                ->descendantsAndSelf($subCategory->id)
                ->where('scope', Category::SCOPE_CATALOG)
                ->where('is_active', true)
                ->pluck('id');

            if ($subTreeIds->isEmpty()) {
                $subCategory->setAttribute('products_count', 0);

                return $subCategory;
            }

            $recursiveCount = Product::query()
                ->where('is_active', true)
                ->whereHas('categories', function ($categoryQuery) use ($subTreeIds): void {
                    $categoryQuery
                        ->where('scope', Category::SCOPE_CATALOG)
                        ->where('is_active', true)
                        ->whereIn('categories.id', $subTreeIds);
                })
                ->distinct('products.id')
                ->count('products.id');

            $subCategory->setAttribute('products_count', $recursiveCount);

            return $subCategory;
        });

        $breadcrumbCategories = $category->ancestors()
            ->where('scope', Category::SCOPE_CATALOG)
            ->where('is_active', true)
            ->with(['translations' => fn ($q) => $q
                ->where('scope', Category::SCOPE_CATALOG)
                ->whereIn('locale', [$locale, $fallbackLocale])])
            ->orderBy('_lft')
            ->get()
            ->push($category);

        $topBlocks = app(ContentBlockResolver::class)->forPlacement(
            placement: 'category.top',
            locale: $locale,
            targetType: 'category',
            targetRef: $categorySlug,
            frontendVariant: $variant
        );

        $bottomBlocks = app(ContentBlockResolver::class)->forPlacement(
            placement: 'category.bottom',
            locale: $locale,
            targetType: 'category',
            targetRef: $categorySlug,
            frontendVariant: $variant
        );

        $response = response()->view($this->frontendView($request, 'categories.show'), [
            'category' => $category,
            'products' => $products,
            'categories' => $categories,
            'manufacturers' => $manufacturers,
            'optionFilters' => $this->withSelectedFilters($optionFilters, $selectedOptionFilters),
            'attributeFilters' => $this->withSelectedFilters($attributeFilters, $selectedAttributeFilters),
            'subcategories' => $subcategories,
            'breadcrumbCategories' => $breadcrumbCategories,
            'topBlocks' => $topBlocks,
            'bottomBlocks' => $bottomBlocks,
            'filters' => [
                'q' => $search,
                'manufacturer' => $manufacturerSlug,
                'sort' => $sort,
                'cols' => $gridCols,
            ],
            'locale' => $locale,
            'fallbackLocale' => $fallbackLocale,
        ]);

        return $this->withDesktopCacheHeaders($request, $response, 'category:'.$categorySlug);
    }

    private function cachedCatalogCategories(string $locale, string $fallbackLocale)
    {
        $cacheKey = sprintf('front:catalog:categories:%s:%s', $locale, $fallbackLocale);

        return Cache::remember($cacheKey, now()->addMinutes(10), static function () use ($locale, $fallbackLocale) {
            return Category::query()
                ->select(['id', 'code', 'sort_order'])
                ->where('scope', Category::SCOPE_CATALOG)
                ->where('is_active', true)
                ->with(['translations' => fn ($q) => $q
                    ->select(['id', 'category_id', 'scope', 'locale', 'name', 'slug'])
                    ->where('scope', Category::SCOPE_CATALOG)
                    ->whereIn('locale', [$locale, $fallbackLocale])])
                ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        });
    }

    private function cachedCatalogManufacturers(string $locale, string $fallbackLocale)
    {
        $cacheKey = sprintf('front:catalog:manufacturers:%s:%s', $locale, $fallbackLocale);

        return Cache::remember($cacheKey, now()->addMinutes(10), static function () use ($locale, $fallbackLocale) {
            return Manufacturer::query()
                ->select(['id', 'code', 'sort_order'])
                ->where('is_active', true)
                ->with(['translations' => fn ($q) => $q
                    ->select(['id', 'manufacturer_id', 'locale', 'name', 'slug'])
                    ->whereIn('locale', [$locale, $fallbackLocale])])
                ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        });
    }

    /**
     * @return array<int, int>
     */
    private function configuredFilterOptionIds(): array
    {
        $raw = app(SystemSettingsService::class)->get('store_product_filter_option_ids', []);

        return collect(is_array($raw) ? $raw : [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function configuredFilterAttributeGroups(): array
    {
        $raw = app(SystemSettingsService::class)->get('store_product_filter_attribute_group_codes', []);

        return collect(is_array($raw) ? $raw : [])
            ->map(fn ($code): string => trim((string) $code))
            ->filter(fn (string $code): bool => $code !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $optionIds
     * @return array<int, array{label:string,query_key:string,values:array<int, array{id:int,label:string}>}>
     */
    private function catalogOptionFilters(string $locale, string $fallbackLocale, array $optionIds, ?array $categoryScopeIds = null): array
    {
        if ($optionIds === []) {
            return [];
        }

        $categoryKey = $categoryScopeIds !== null && $categoryScopeIds !== []
            ? sha1(implode(',', array_map(static fn ($id): int => (int) $id, $categoryScopeIds)))
            : 'all';
        $cacheKey = sprintf('front:catalog:option-filters:%s:%s:%s:%s', $locale, $fallbackLocale, sha1(implode(',', $optionIds)), $categoryKey);

        $rows = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($locale, $fallbackLocale, $optionIds, $categoryScopeIds): array {
            $scopeIds = collect($categoryScopeIds ?? [])
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();
            $options = Option::query()
                ->whereIn('id', $optionIds)
                ->where('is_active', true)
                ->with([
                    'translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
                    'values' => fn ($valueQuery) => $valueQuery
                        ->where('is_active', true)
                        ->whereHas('productOptionValues', function ($productOptionQuery) use ($scopeIds): void {
                            $productOptionQuery
                                ->where('is_active', true)
                                ->whereHas('product', function ($productQuery) use ($scopeIds): void {
                                    $productQuery->where('is_active', true);
                                    if ($scopeIds !== []) {
                                        $productQuery->whereHas('categories', function ($categoryQuery) use ($scopeIds): void {
                                            $categoryQuery
                                                ->where('scope', Category::SCOPE_CATALOG)
                                                ->where('is_active', true)
                                                ->whereIn('categories.id', $scopeIds);
                                        });
                                    }
                                });
                        })
                        ->with(['translations' => fn ($tq) => $tq->whereIn('locale', [$locale, $fallbackLocale])]),
                ])
                ->get()
                ->keyBy('id');

            $filters = [];
            foreach ($optionIds as $optionId) {
                /** @var Option|null $option */
                $option = $options->get($optionId);
                if (!$option) {
                    continue;
                }

                $optionTranslation = $option->translations->firstWhere('locale', $locale)
                    ?? $option->translations->firstWhere('locale', $fallbackLocale)
                    ?? $option->translations->first();
                $label = trim((string) ($optionTranslation?->name ?? $option->code));
                if ($label === '') {
                    $label = __('ui.shop.filters.size');
                }

                $values = $option->values
                    ->map(function (OptionValue $value) use ($locale, $fallbackLocale): array {
                        $valueTranslation = $value->translations->firstWhere('locale', $locale)
                            ?? $value->translations->firstWhere('locale', $fallbackLocale)
                            ?? $value->translations->first();
                        $valueLabel = trim((string) ($valueTranslation?->name ?? $value->code));

                        return [
                            'id' => (int) $value->id,
                            'label' => $valueLabel !== '' ? $valueLabel : (string) $value->code,
                        ];
                    })
                    ->values()
                    ->all();

                if ($values === []) {
                    continue;
                }

                $filters[] = [
                    'label' => $label,
                    'query_key' => 'opt_'.$option->id,
                    'values' => $values,
                ];
            }

            return $filters;
        });

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param  array<int, string>  $groupCodes
     * @return array<int, array{label:string,query_key:string,values:array<int, array{id:int,label:string}>}>
     */
    private function catalogAttributeFilters(string $locale, string $fallbackLocale, array $groupCodes, ?array $categoryScopeIds = null): array
    {
        if ($groupCodes === []) {
            return [];
        }

        $categoryKey = $categoryScopeIds !== null && $categoryScopeIds !== []
            ? sha1(implode(',', array_map(static fn ($id): int => (int) $id, $categoryScopeIds)))
            : 'all';
        $cacheKey = sprintf('front:catalog:attribute-filters:%s:%s:%s:%s', $locale, $fallbackLocale, sha1(implode(',', $groupCodes)), $categoryKey);

        $rows = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($locale, $fallbackLocale, $groupCodes, $categoryScopeIds): array {
            $scopeIds = collect($categoryScopeIds ?? [])
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();
            $attributes = Attribute::query()
                ->whereIn('group_code', $groupCodes)
                ->where('is_active', true)
                ->whereHas('products', function ($productQuery) use ($scopeIds): void {
                    $productQuery->where('products.is_active', true);
                    if ($scopeIds !== []) {
                        $productQuery->whereHas('categories', function ($categoryQuery) use ($scopeIds): void {
                            $categoryQuery
                                ->where('scope', Category::SCOPE_CATALOG)
                                ->where('is_active', true)
                                ->whereIn('categories.id', $scopeIds);
                        });
                    }
                })
                ->with(['translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale])])
                ->orderBy('group_code')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->groupBy('group_code');

            $filters = [];
            foreach ($groupCodes as $groupCode) {
                $groupRows = $attributes->get($groupCode);
                if (!$groupRows || $groupRows->isEmpty()) {
                    continue;
                }

                $label = ucfirst(str_replace('_', ' ', $groupCode));
                $values = $groupRows
                    ->map(function (Attribute $attribute) use ($locale, $fallbackLocale): array {
                        $translation = $attribute->translations->firstWhere('locale', $locale)
                            ?? $attribute->translations->firstWhere('locale', $fallbackLocale)
                            ?? $attribute->translations->first();
                        $valueLabel = trim((string) ($translation?->name ?? $attribute->code));

                        return [
                            'id' => (int) $attribute->id,
                            'label' => $valueLabel !== '' ? $valueLabel : (string) $attribute->code,
                        ];
                    })
                    ->values()
                    ->all();

                if ($values === []) {
                    continue;
                }

                $filters[] = [
                    'label' => $label,
                    'query_key' => 'attr_'.Str::slug($groupCode, '_'),
                    'values' => $values,
                ];
            }

            return $filters;
        });

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return array<int, array{label:string,query_key:string,values:array<int, array{id:int,label:string}>}>
     */
    private function legacySizeFallbackFilter(string $locale, string $fallbackLocale, ?array $categoryScopeIds = null): array
    {
        $sizes = $this->cachedCatalogSizes($locale, $fallbackLocale, $categoryScopeIds);

        $values = $sizes
            ->map(function (OptionValue $size) use ($locale, $fallbackLocale): array {
                $translation = $size->translations->firstWhere('locale', $locale)
                    ?? $size->translations->firstWhere('locale', $fallbackLocale)
                    ?? $size->translations->first();

                return [
                    'id' => (int) $size->id,
                    'label' => trim((string) ($translation?->name ?? $size->code)),
                ];
            })
            ->values()
            ->all();

        if ($values === []) {
            return [];
        }

        return [[
            'label' => __('ui.shop.filters.size'),
            'query_key' => 'size',
            'values' => $values,
        ]];
    }

    private function applyOptionValueFilter($query, int $optionValueId): void
    {
        $query->whereHas('optionValues', function ($optionQuery) use ($optionValueId): void {
            $optionQuery
                ->where('is_active', true)
                ->where(function ($sizeQuery) use ($optionValueId): void {
                    $sizeQuery
                        ->where('option_value_id', $optionValueId)
                        ->orWhere('parent_option_value_id', $optionValueId);
                });
        });
    }

    /**
     * @param  array<int, array{label:string,query_key:string,values:array<int, array{id:int,label:string}>}>  $filters
     * @param  array<string, int|null>  $selectedMap
     * @return array<int, array{label:string,query_key:string,selected:int|null,values:array<int, array{id:int,label:string}>}>
     */
    private function withSelectedFilters(array $filters, array $selectedMap): array
    {
        return array_map(function (array $filter) use ($selectedMap): array {
            $queryKey = (string) $filter['query_key'];
            $selected = (int) ($selectedMap[$queryKey] ?? 0);

            return [
                'label' => (string) $filter['label'],
                'query_key' => $queryKey,
                'selected' => $selected > 0 ? $selected : null,
                'values' => $filter['values'],
            ];
        }, $filters);
    }

    private function cachedCatalogSizes(string $locale, string $fallbackLocale, ?array $categoryScopeIds = null)
    {
        $categoryKey = $categoryScopeIds !== null && $categoryScopeIds !== []
            ? sha1(implode(',', array_map(static fn ($id): int => (int) $id, $categoryScopeIds)))
            : 'all';
        $cacheKey = sprintf('front:catalog:sizes:%s:%s:%s', $locale, $fallbackLocale, $categoryKey);

        return Cache::remember($cacheKey, now()->addMinutes(10), static function () use ($locale, $fallbackLocale, $categoryScopeIds) {
            $scopeIds = collect($categoryScopeIds ?? [])
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();
            return OptionValue::query()
                ->select(['id', 'code', 'sort_order'])
                ->where('is_active', true)
                ->whereHas('productOptionValues', function ($q) use ($scopeIds): void {
                    $q->where('is_active', true)
                        ->whereHas('product', function ($productQuery) use ($scopeIds): void {
                            $productQuery->where('is_active', true);
                            if ($scopeIds !== []) {
                                $productQuery->whereHas('categories', function ($categoryQuery) use ($scopeIds): void {
                                    $categoryQuery
                                        ->where('scope', Category::SCOPE_CATALOG)
                                        ->where('is_active', true)
                                        ->whereIn('categories.id', $scopeIds);
                                });
                            }
                        });
                })
                ->with(['translations' => fn ($q) => $q
                    ->select(['id', 'option_value_id', 'locale', 'name'])
                    ->whereIn('locale', [$locale, $fallbackLocale])])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        });
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

    private function redirectWithoutCols(Request $request): RedirectResponse
    {
        $query = $request->query();
        unset($query['cols']);
        $target = $request->url();
        if ($query !== []) {
            $target .= '?'.http_build_query($query);
        }

        return redirect()->to($target);
    }

    private function withDesktopCacheHeaders(Request $request, Response $response, string $scope): Response
    {
        if ($this->frontendVariant($request) !== 'desktop') {
            return $response;
        }

        if (auth()->check()) {
            return $response->header('Cache-Control', 'private, no-cache, must-revalidate');
        }

        $lastModifiedTs = $this->catalogLastModifiedTimestamp();
        $etag = $this->catalogEtag($request, $scope, $lastModifiedTs);

        $response->header('Cache-Control', 'private, max-age=120, stale-while-revalidate=60');
        $response->setEtag($etag);
        if ($lastModifiedTs > 0) {
            $response->setLastModified(\Carbon\CarbonImmutable::createFromTimestampUTC($lastModifiedTs));
        }

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
    }

    private function catalogLastModifiedTimestamp(): int
    {
        return (int) Cache::remember('front:catalog:last-modified-ts', now()->addMinutes(2), static function (): int {
            $modelType = Product::class;

            $timestamps = [
                DB::table('products')->max('updated_at'),
                DB::table('product_translations')->max('updated_at'),
                DB::table('categories')->max('updated_at'),
                DB::table('category_translations')->max('updated_at'),
                DB::table('catalog_manufacturers')->max('updated_at'),
                DB::table('catalog_manufacturer_translations')->max('updated_at'),
                DB::table('catalog_option_values')->max('updated_at'),
                DB::table('catalog_option_value_translations')->max('updated_at'),
                DB::table('media')->where('model_type', $modelType)->max('updated_at'),
            ];

            $max = 0;
            foreach ($timestamps as $timestamp) {
                $unix = $timestamp ? strtotime((string) $timestamp) : 0;
                if ($unix > $max) {
                    $max = $unix;
                }
            }

            return $max;
        });
    }

    private function catalogEtag(Request $request, string $scope, int $lastModifiedTs): string
    {
        $wishlistHash = sha1(implode(',', app(WishlistService::class)->ids()));

        return '"'.sha1(implode('|', [
            'desktop-catalog',
            $scope,
            app()->getLocale(),
            $request->getRequestUri(),
            (string) $lastModifiedTs,
            $wishlistHash,
        ])).'"';
    }
}

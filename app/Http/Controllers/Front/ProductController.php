<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Front\Concerns\ResolvesFrontendView;
use App\Models\Catalog\Category\Category;
use App\Models\Catalog\Product\Product;
use App\Models\Content\Page\InfoPage;
use App\Models\Content\Support\Comment;
use App\Services\Content\ContentBlockResolver;
use App\Services\Front\WishlistService;
use App\Services\Pricing\ProductPricePresentationService;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    use ResolvesFrontendView;

    public function storeComment(Request $request, string $slug)
    {
        $locale = app()->getLocale();
        $fallbackLocale = (string) config('app.locale');

        $product = Product::query()
            ->where('is_active', true)
            ->whereHas('translations', function ($q) use ($locale, $fallbackLocale, $slug): void {
                $q->whereIn('locale', [$locale, $fallbackLocale])
                    ->where('slug', $slug);
            })
            ->firstOrFail();

        $user = $request->user();

        $rules = [
            'body' => ['required', 'string', 'min:3', 'max:2000'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];

        if (! $user) {
            $rules['author_name'] = ['required', 'string', 'max:120'];
            $rules['author_email'] = ['required', 'email', 'max:190'];
        } else {
            $rules['author_name'] = ['nullable', 'string', 'max:120'];
            $rules['author_email'] = ['nullable', 'email', 'max:190'];
        }

        $validated = $request->validate($rules, [
            'author_name.required' => __('ui.product.comment_form.validation.name_required'),
            'author_email.required' => __('ui.product.comment_form.validation.email_required'),
            'author_email.email' => __('ui.product.comment_form.validation.email_invalid'),
            'body.required' => __('ui.product.comment_form.validation.body_required'),
        ]);

        $product->comments()->create([
            'user_id' => $user?->id,
            'parent_id' => null,
            'author_name' => $user?->name ?: trim((string) ($validated['author_name'] ?? '')),
            'author_email' => $user?->email ?: trim((string) ($validated['author_email'] ?? '')),
            'locale' => $locale,
            'body' => trim((string) $validated['body']),
            'rating' => isset($validated['rating']) ? (int) $validated['rating'] : null,
            'status' => Comment::STATUS_PENDING,
            'is_featured' => false,
        ]);

        return redirect()
            ->to(route('products.show', ['slug' => $slug]).'#product-comments')
            ->with('success', __('ui.product.comment_form.status_submitted'));
    }

    public function show(Request $request, string $slug): Response
    {
        $locale = app()->getLocale();
        $fallbackLocale = (string) config('app.locale');
        $variant = $this->frontendVariant($request);

        $product = Product::query()
            ->select(['id', 'code', 'sku', 'base_price', 'stock_qty', 'tax_rate_id', 'manufacturer_id', 'is_active'])
            ->where('is_active', true)
            ->whereHas('translations', function ($q) use ($locale, $fallbackLocale, $slug): void {
                $q->whereIn('locale', [$locale, $fallbackLocale])
                    ->where('slug', $slug);
            })
            ->with([
                'taxRate:id,rate,rate_type,is_active',
                'media' => fn ($q) => $q
                    ->whereIn('collection_name', ['product_main', 'product_gallery'])
                    ->orderBy('order_column')
                    ->orderBy('id'),
                'translations' => fn ($q) => $q
                    ->select(['id', 'product_id', 'locale', 'slug', 'name', 'excerpt', 'description', 'payload'])
                    ->whereIn('locale', [$locale, $fallbackLocale]),
                'categories.translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
                'manufacturer.translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
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
            ->firstOrFail();

        $categoryIds = $product->categories->pluck('id')->map(fn ($id) => (int) $id)->all();
        $relatedLimit = 4;

        $relatedBaseQuery = Product::query()
            ->select(['id', 'code', 'sku', 'base_price', 'stock_qty', 'tax_rate_id', 'manufacturer_id', 'is_active'])
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->with([
                'taxRate:id,rate,rate_type,is_active',
                'media' => fn ($q) => $q
                    ->whereIn('collection_name', ['product_main', 'product_gallery'])
                    ->orderBy('order_column')
                    ->orderBy('id'),
                'translations' => fn ($q) => $q
                    ->select(['id', 'product_id', 'locale', 'slug', 'name', 'excerpt'])
                    ->whereIn('locale', [$locale, $fallbackLocale]),
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
            ->orderByDesc('id');

        $related = collect();
        $excludeIds = [(int) $product->id];

        if ($categoryIds !== []) {
            $productCategories = Category::query()
                ->select(['id', 'parent_id', '_lft', '_rgt'])
                ->whereIn('id', $categoryIds)
                ->withDepth()
                ->get();

            $maxDepth = (int) $productCategories->max('depth');
            $deepestCategories = $productCategories
                ->filter(static fn (Category $category): bool => (int) ($category->depth ?? 0) === $maxDepth)
                ->values();
            $deepestCategoryIds = $deepestCategories->pluck('id')->map(fn ($id): int => (int) $id)->all();

            if ($deepestCategoryIds !== []) {
                $sameSubcategory = (clone $relatedBaseQuery)
                    ->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('categories.id', $deepestCategoryIds))
                    ->limit($relatedLimit)
                    ->get();

                $related = $related->concat($sameSubcategory);
                $excludeIds = array_values(array_unique(array_merge(
                    $excludeIds,
                    $sameSubcategory->pluck('id')->map(fn ($id): int => (int) $id)->all()
                )));
            }

            if ($related->count() < $relatedLimit) {
                $rootCategories = Category::query()
                    ->select(['id', '_lft', '_rgt'])
                    ->whereNull('parent_id')
                    ->orderBy('_lft')
                    ->get();

                $rootBoundaries = [];
                foreach ($deepestCategories as $deepestCategory) {
                    /** @var Category|null $root */
                    $root = $rootCategories->first(static fn (Category $candidate): bool => (int) $candidate->_lft <= (int) $deepestCategory->_lft
                        && (int) $candidate->_rgt >= (int) $deepestCategory->_rgt);
                    if (! $root) {
                        continue;
                    }

                    $rootBoundaries[(int) $root->id] = [
                        'lft' => (int) $root->_lft,
                        'rgt' => (int) $root->_rgt,
                    ];
                }

                if ($rootBoundaries !== []) {
                    $fallback = (clone $relatedBaseQuery)
                        ->whereNotIn('id', $excludeIds)
                        ->whereHas('categories', function ($categoryQuery) use ($rootBoundaries): void {
                            $categoryQuery->where(function ($or) use ($rootBoundaries): void {
                                foreach ($rootBoundaries as $boundary) {
                                    $or->orWhere(function ($nested) use ($boundary): void {
                                        $nested
                                            ->where('categories._lft', '>=', (int) $boundary['lft'])
                                            ->where('categories._rgt', '<=', (int) $boundary['rgt']);
                                    });
                                }
                            });
                        })
                        ->limit($relatedLimit - $related->count())
                        ->get();

                    $related = $related->concat($fallback);
                    $excludeIds = array_values(array_unique(array_merge(
                        $excludeIds,
                        $fallback->pluck('id')->map(fn ($id): int => (int) $id)->all()
                    )));
                }
            }
        }

        if ($related->count() < $relatedLimit) {
            $latestFallback = (clone $relatedBaseQuery)
                ->whereNotIn('id', $excludeIds)
                ->limit($relatedLimit - $related->count())
                ->get();
            $related = $related->concat($latestFallback);
        }

        $related = $related->take($relatedLimit)->values();

        $topBlocks = app(ContentBlockResolver::class)->forPlacement(
            placement: 'product.top',
            locale: $locale,
            targetType: 'product',
            targetRef: $slug,
            frontendVariant: $variant
        );

        $bottomBlocks = app(ContentBlockResolver::class)->forPlacement(
            placement: 'product.bottom',
            locale: $locale,
            targetType: 'product',
            targetRef: $slug,
            frontendVariant: $variant
        );

        $comments = $product->comments()
            ->whereNull('parent_id')
            ->status(Comment::STATUS_APPROVED)
            ->whereIn('locale', [$locale, $fallbackLocale])
            ->with('user:id,name')
            ->orderByDesc('is_featured')
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $sizeGuide = $this->resolveSizeGuide($product, $locale, $fallbackLocale);

        $response = response()->view($this->frontendView($request, 'products.show'), [
            'product' => $product,
            'related' => $related,
            'comments' => $comments,
            'sizeGuide' => $sizeGuide,
            'topBlocks' => $topBlocks,
            'bottomBlocks' => $bottomBlocks,
            'pricePresentation' => app(ProductPricePresentationService::class)->forProduct($product, auth()->user()),
            'locale' => $locale,
            'fallbackLocale' => $fallbackLocale,
        ]);

        return $this->withDesktopCacheHeaders($request, $response, (int) $product->id, $slug);
    }

    private function withDesktopCacheHeaders(Request $request, Response $response, int $productId, string $slug): Response
    {
        if ($this->frontendVariant($request) !== 'desktop') {
            return $response;
        }

        if (auth()->check()) {
            return $response->header('Cache-Control', 'private, no-cache, must-revalidate');
        }

        $lastModifiedTs = $this->productLastModifiedTimestamp($productId);
        $etag = $this->productEtag($request, $slug, $productId, $lastModifiedTs);

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

    /**
     * @return array{code: string, title: string, body_html: string}|null
     */
    private function resolveSizeGuide(Product $product, string $locale, string $fallbackLocale): ?array
    {
        $localeTranslation = $product->translations->firstWhere('locale', $locale);
        $fallbackTranslation = $product->translations->firstWhere('locale', $fallbackLocale);
        $sizeGuideCode = trim((string) (
            data_get($localeTranslation?->payload, 'size_guide_code')
            ?: data_get($fallbackTranslation?->payload, 'size_guide_code')
            ?: data_get($product->payload, 'size_guide_code')
            ?: 'size-guide-women'
        ));

        if ($sizeGuideCode === '') {
            return null;
        }

        $page = InfoPage::query()
            ->where('code', $sizeGuideCode)
            ->where('is_active', true)
            ->where(function ($q): void {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->with(['translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale])])
            ->first();

        if (! $page) {
            return null;
        }

        $translation = $page->translations->firstWhere('locale', $locale)
            ?? $page->translations->firstWhere('locale', $fallbackLocale)
            ?? $page->translations->first();

        $bodyHtml = trim((string) ($translation?->body_html ?? ''));
        if ($bodyHtml === '') {
            return null;
        }

        return [
            'code' => $sizeGuideCode,
            'title' => trim((string) ($translation?->title ?? __('ui.product.size_guide'))),
            'body_html' => $bodyHtml,
        ];
    }

    private function productLastModifiedTimestamp(int $productId): int
    {
        return (int) Cache::remember('front:product:last-modified:'.$productId, now()->addMinutes(2), static function () use ($productId): int {
            $modelType = Product::class;
            $timestamps = [
                DB::table('products')->where('id', $productId)->max('updated_at'),
                DB::table('product_translations')->where('product_id', $productId)->max('updated_at'),
                DB::table('catalog_product_option_values')->where('product_id', $productId)->max('updated_at'),
                DB::table('media')->where('model_type', $modelType)->where('model_id', $productId)->max('updated_at'),
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

    private function productEtag(Request $request, string $slug, int $productId, int $lastModifiedTs): string
    {
        $wishlistHash = sha1(implode(',', app(WishlistService::class)->ids()));

        return '"'.sha1(implode('|', [
            'desktop-product',
            $slug,
            (string) $productId,
            app()->getLocale(),
            $request->getRequestUri(),
            (string) $lastModifiedTs,
            $wishlistHash,
        ])).'"';
    }
}

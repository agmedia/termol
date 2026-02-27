<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Front\Concerns\ResolvesFrontendView;
use App\Models\Catalog\Category\Category;
use App\Models\Catalog\Product\Product;
use App\Models\Content\Page\InfoPage;
use App\Models\Content\Support\Comment;
use App\Models\User\UserProfile;
use App\Services\Content\ContentBlockResolver;
use App\Services\Front\WishlistService;
use App\Services\Pricing\ProductPricePresentationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use ResolvesFrontendView;

    private const FIT_FINDER_SESSION_KEY = 'front_fit_finder_profile';

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
        $fitFinderSelection = $this->resolveFitFinderSelection($request, $product);

        $response = response()->view($this->frontendView($request, 'products.show'), [
            'product' => $product,
            'related' => $related,
            'comments' => $comments,
            'sizeGuide' => $sizeGuide,
            'fitFinderSelection' => $fitFinderSelection,
            'topBlocks' => $topBlocks,
            'bottomBlocks' => $bottomBlocks,
            'pricePresentation' => app(ProductPricePresentationService::class)->forProduct($product, auth()->user()),
            'locale' => $locale,
            'fallbackLocale' => $fallbackLocale,
        ]);

        return $this->withDesktopCacheHeaders($request, $response, (int) $product->id, $slug);
    }

    public function storeFitFinderPreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'size_label' => ['nullable', 'string', 'max:30'],
            'height' => ['nullable', 'integer', 'min:130', 'max:230'],
            'weight' => ['nullable', 'integer', 'min:35', 'max:220'],
            'age' => ['nullable', 'integer', 'min:12', 'max:100'],
            'fit' => ['nullable', 'string', 'in:tighter,average,looser'],
            'chest' => ['nullable', 'string', 'in:slimmer,average,broader'],
            'belly' => ['nullable', 'string', 'in:flatter,average,rounder'],
        ]);

        $selection = [
            'product_id' => (int) $validated['product_id'],
            'size_label' => trim((string) ($validated['size_label'] ?? '')),
            'height' => isset($validated['height']) ? (int) $validated['height'] : null,
            'weight' => isset($validated['weight']) ? (int) $validated['weight'] : null,
            'age' => isset($validated['age']) ? (int) $validated['age'] : null,
            'fit' => trim((string) ($validated['fit'] ?? 'average')),
            'chest' => trim((string) ($validated['chest'] ?? 'average')),
            'belly' => trim((string) ($validated['belly'] ?? 'average')),
            'updated_at' => now()->toIso8601String(),
        ];

        $request->session()->put(self::FIT_FINDER_SESSION_KEY, $selection);

        if ($request->user()) {
            $profile = UserProfile::query()->firstOrCreate(['user_id' => (int) $request->user()->id]);
            $payload = is_array($profile->payload) ? $profile->payload : [];
            $payload['fit_finder'] = $selection;

            $profile->forceFill(['payload' => $payload])->save();
        }

        return response()->json(['ok' => true]);
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
            ?: $this->defaultSizeGuideCode($product, $locale, $fallbackLocale)
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

    private function defaultSizeGuideCode(Product $product, string $locale, string $fallbackLocale): string
    {
        return $this->isMaleProduct($product, $locale, $fallbackLocale)
            ? 'size-guide-main'
            : 'size-guide-women';
    }

    private function isMaleProduct(Product $product, string $locale, string $fallbackLocale): bool
    {
        $localeTranslation = $product->translations->firstWhere('locale', $locale);
        $fallbackTranslation = $product->translations->firstWhere('locale', $fallbackLocale);

        if ($this->payloadSuggestsMale($localeTranslation?->payload)
            || $this->payloadSuggestsMale($fallbackTranslation?->payload)
            || $this->payloadSuggestsMale($product->payload)) {
            return true;
        }

        $tokens = ['men', 'man', 'male', 'muskar', 'muski', 'muški'];
        foreach ($product->categories as $category) {
            foreach ($category->translations ?? [] as $categoryTranslation) {
                $slug = (string) ($categoryTranslation->slug ?? '');
                $name = (string) ($categoryTranslation->name ?? '');
                $value = Str::lower($slug.' '.$name);
                if ($value !== '' && Str::contains($value, $tokens)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function payloadSuggestsMale($payload): bool
    {
        if (! is_array($payload)) {
            return false;
        }

        $tokens = ['men', 'man', 'male', 'muskar', 'muski', 'muški'];
        $candidates = [
            data_get($payload, 'gender'),
            data_get($payload, 'gender_code'),
            data_get($payload, 'target_gender'),
            data_get($payload, 'audience'),
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value === '') {
                continue;
            }

            if (Str::contains(Str::lower($value), $tokens)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{product_id:int,size_label:string,height:int|null,weight:int|null,age:int|null,fit:string,chest:string,belly:string,updated_at:string}|null
     */
    private function resolveFitFinderSelection(Request $request, Product $product): ?array
    {
        $sessionSelection = $request->session()->get(self::FIT_FINDER_SESSION_KEY);
        if (is_array($sessionSelection)
            && (int) ($sessionSelection['product_id'] ?? 0) === (int) $product->id) {
            return [
                'product_id' => (int) $sessionSelection['product_id'],
                'size_label' => trim((string) ($sessionSelection['size_label'] ?? '')),
                'height' => isset($sessionSelection['height']) ? (int) $sessionSelection['height'] : null,
                'weight' => isset($sessionSelection['weight']) ? (int) $sessionSelection['weight'] : null,
                'age' => isset($sessionSelection['age']) ? (int) $sessionSelection['age'] : null,
                'fit' => trim((string) ($sessionSelection['fit'] ?? 'average')),
                'chest' => trim((string) ($sessionSelection['chest'] ?? 'average')),
                'belly' => trim((string) ($sessionSelection['belly'] ?? 'average')),
                'updated_at' => trim((string) ($sessionSelection['updated_at'] ?? now()->toIso8601String())),
            ];
        }

        $user = $request->user();
        if (! $user) {
            return null;
        }

        $profile = $user->relationLoaded('profile') ? $user->profile : $user->profile()->first();
        $profilePayload = is_array($profile?->payload) ? $profile->payload : [];
        $selection = is_array($profilePayload['fit_finder'] ?? null)
            ? $profilePayload['fit_finder']
            : null;

        if (! is_array($selection)
            || (int) ($selection['product_id'] ?? 0) !== (int) $product->id) {
            return null;
        }

        return [
            'product_id' => (int) $selection['product_id'],
            'size_label' => trim((string) ($selection['size_label'] ?? '')),
            'height' => isset($selection['height']) ? (int) $selection['height'] : null,
            'weight' => isset($selection['weight']) ? (int) $selection['weight'] : null,
            'age' => isset($selection['age']) ? (int) $selection['age'] : null,
            'fit' => trim((string) ($selection['fit'] ?? 'average')),
            'chest' => trim((string) ($selection['chest'] ?? 'average')),
            'belly' => trim((string) ($selection['belly'] ?? 'average')),
            'updated_at' => trim((string) ($selection['updated_at'] ?? now()->toIso8601String())),
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
        $fitFinderHash = $this->fitFinderEtagFragment($request, $productId);

        return '"'.sha1(implode('|', [
            'desktop-product',
            $slug,
            (string) $productId,
            app()->getLocale(),
            $request->getRequestUri(),
            (string) $lastModifiedTs,
            $wishlistHash,
            $fitFinderHash,
        ])).'"';
    }

    private function fitFinderEtagFragment(Request $request, int $productId): string
    {
        $selection = $request->session()->get(self::FIT_FINDER_SESSION_KEY);
        if (! is_array($selection) || (int) ($selection['product_id'] ?? 0) !== $productId) {
            return 'fit:none';
        }

        return sha1(json_encode([
            'product_id' => (int) ($selection['product_id'] ?? 0),
            'size_label' => trim((string) ($selection['size_label'] ?? '')),
            'height' => isset($selection['height']) ? (int) $selection['height'] : null,
            'weight' => isset($selection['weight']) ? (int) $selection['weight'] : null,
            'age' => isset($selection['age']) ? (int) $selection['age'] : null,
            'fit' => trim((string) ($selection['fit'] ?? '')),
            'chest' => trim((string) ($selection['chest'] ?? '')),
            'belly' => trim((string) ($selection['belly'] ?? '')),
            'updated_at' => trim((string) ($selection['updated_at'] ?? '')),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'fit:none');
    }
}

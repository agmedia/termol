<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Front\Concerns\ResolvesFrontendView;
use App\Models\Catalog\Product\Product;
use App\Models\Content\Support\Comment;
use App\Services\Content\ContentBlockResolver;
use App\Services\Pricing\ProductPricePresentationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

    public function show(Request $request, string $slug): View
    {
        $locale = app()->getLocale();
        $fallbackLocale = (string) config('app.locale');
        $variant = $this->frontendVariant($request);

        $product = Product::query()
            ->where('is_active', true)
            ->whereHas('translations', function ($q) use ($locale, $fallbackLocale, $slug): void {
                $q->whereIn('locale', [$locale, $fallbackLocale])
                    ->where('slug', $slug);
            })
            ->with([
                'taxRate',
                'media' => fn ($q) => $q
                    ->whereIn('collection_name', ['product_main', 'product_gallery'])
                    ->orderBy('order_column')
                    ->orderBy('id'),
                'translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
                'categories.translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
                'manufacturer.translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
                'optionValues' => fn ($q) => $q
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->with([
                        'optionValue.translations' => fn ($tq) => $tq->whereIn('locale', [$locale, $fallbackLocale]),
                        'parentOptionValue.translations' => fn ($tq) => $tq->whereIn('locale', [$locale, $fallbackLocale]),
                    ]),
            ])
            ->firstOrFail();

        $categoryIds = $product->categories->pluck('id')->map(fn ($id) => (int) $id)->all();

        $related = Product::query()
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->when($categoryIds !== [], fn ($q) => $q->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('categories.id', $categoryIds)))
            ->with([
                'taxRate',
                'media' => fn ($q) => $q
                    ->whereIn('collection_name', ['product_main', 'product_gallery'])
                    ->orderBy('order_column')
                    ->orderBy('id'),
                'translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
                'optionValues' => fn ($q) => $q
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->with([
                        'optionValue.translations' => fn ($tq) => $tq->whereIn('locale', [$locale, $fallbackLocale]),
                        'parentOptionValue.translations' => fn ($tq) => $tq->whereIn('locale', [$locale, $fallbackLocale]),
                    ]),
            ])
            ->orderByDesc('id')
            ->limit(4)
            ->get();

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

        return view($this->frontendView($request, 'products.show'), [
            'product' => $product,
            'related' => $related,
            'comments' => $comments,
            'topBlocks' => $topBlocks,
            'bottomBlocks' => $bottomBlocks,
            'pricePresentation' => app(ProductPricePresentationService::class)->forProduct($product, auth()->user()),
            'locale' => $locale,
            'fallbackLocale' => $fallbackLocale,
        ]);
    }
}

<?php

namespace App\Services\Integrations\Asistent24;

use App\Models\Catalog\Category\Category;
use App\Models\Catalog\Product\Product;
use App\Models\Content\Page\InfoPage;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class CatalogExportService
{
    public function buildOpenCartCompatibleSnapshot(
        ?CarbonImmutable $changedSince = null,
        bool $includeInactive = false,
        ?string $locale = null
    ): array {
        [$locale, $fallbackLocale] = $this->resolveLocales($locale);

        $categories = $this->fetchCategories($changedSince, $includeInactive, $locale, $fallbackLocale);
        $products = $this->fetchProducts($changedSince, $includeInactive, $locale, $fallbackLocale);
        $pages = $this->fetchPages($changedSince, $includeInactive, $locale, $fallbackLocale);

        return [
            'ok' => true,
            'platform' => 'agshop',
            'schema' => 'asistent24-opencart-compat/v1',
            'locale' => $locale,
            'exported_at' => now()->toISOString(),
            'categories' => $categories,
            'products' => $products,
            'blogs' => $pages,
            'meta' => [
                'categories' => count($categories),
                'products' => count($products),
                'blogs' => count($pages),
            ],
        ];
    }

    public function buildCustomApiSnapshot(
        ?CarbonImmutable $changedSince = null,
        bool $includeInactive = false,
        ?string $locale = null
    ): array {
        [$locale, $fallbackLocale] = $this->resolveLocales($locale);

        $categories = $this->fetchCategories($changedSince, $includeInactive, $locale, $fallbackLocale);
        $products = $this->fetchProducts($changedSince, $includeInactive, $locale, $fallbackLocale);
        $pages = $this->fetchPages($changedSince, $includeInactive, $locale, $fallbackLocale);

        return [
            'ok' => true,
            'platform' => 'agshop',
            'schema' => 'asistent24-custom-api/v1',
            'locale' => $locale,
            'exported_at' => now()->toISOString(),
            'entities' => [
                'categories' => $categories,
                'products' => $products,
                'pages' => $pages,
            ],
            'meta' => [
                'categories' => count($categories),
                'products' => count($products),
                'pages' => count($pages),
            ],
        ];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolveLocales(?string $requestedLocale): array
    {
        $defaultLocale = trim((string) config('asistent24.default_locale', ''));
        if ($defaultLocale === '') {
            $defaultLocale = (string) config('app.locale', 'en');
        }

        $locale = strtolower(trim((string) ($requestedLocale ?: $defaultLocale)));
        if ($locale === '') {
            $locale = strtolower((string) config('app.locale', 'en'));
        }

        $fallbackLocale = strtolower((string) config('app.fallback_locale', config('app.locale', 'en')));

        return [$locale, $fallbackLocale];
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function fetchCategories(
        ?CarbonImmutable $changedSince,
        bool $includeInactive,
        string $locale,
        string $fallbackLocale
    ): array {
        $query = Category::query()
            ->where('scope', Category::SCOPE_CATALOG)
            ->with([
                'translations' => fn ($q) => $q->whereIn('locale', array_unique([$locale, $fallbackLocale])),
            ])
            ->orderBy('categories._lft')
            ->orderBy('categories.sort_order')
            ->orderBy('categories.id');

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        if ($changedSince) {
            $query->where(function (Builder $builder) use ($changedSince): void {
                $builder->where('categories.updated_at', '>=', $changedSince)
                    ->orWhereHas('translations', fn (Builder $translationQuery) => $translationQuery->where('updated_at', '>=', $changedSince));
            });
        }

        return $query->get()
            ->map(function (Category $category) use ($locale, $fallbackLocale): array {
                $translation = $this->resolveTranslation($category->translations, $locale, $fallbackLocale);

                return [
                    'id' => (int) $category->id,
                    'code' => (string) $category->code,
                    'parent_id' => $category->parent_id ? (int) $category->parent_id : 0,
                    'name' => (string) ($translation?->name ?? $category->code),
                    'slug' => (string) ($translation?->slug ?? ''),
                    'description' => (string) ($translation?->description ?? ''),
                    'sort_order' => (int) $category->sort_order,
                    'status' => $category->is_active ? 1 : 0,
                    'updated_at' => optional($category->updated_at)?->toISOString(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function fetchProducts(
        ?CarbonImmutable $changedSince,
        bool $includeInactive,
        string $locale,
        string $fallbackLocale
    ): array {
        $query = Product::query()
            ->with([
                'translations' => fn ($q) => $q->whereIn('locale', array_unique([$locale, $fallbackLocale])),
                'categories' => fn ($q) => $q->orderBy('category_product.sort_order'),
            ])
            ->orderBy('products.id');

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        if ($changedSince) {
            $query->where(function (Builder $builder) use ($changedSince): void {
                $builder->where('products.updated_at', '>=', $changedSince)
                    ->orWhereHas('translations', fn (Builder $translationQuery) => $translationQuery->where('updated_at', '>=', $changedSince));
            });
        }

        return $query->get()
            ->map(function (Product $product) use ($locale, $fallbackLocale): array {
                $translation = $this->resolveTranslation($product->translations, $locale, $fallbackLocale);

                return [
                    'id' => (int) $product->id,
                    'model' => (string) $product->code,
                    'code' => (string) $product->code,
                    'sku' => (string) ($product->sku ?? ''),
                    'name' => (string) ($translation?->name ?? $product->code),
                    'slug' => (string) ($translation?->slug ?? ''),
                    'description' => (string) ($translation?->description ?? ''),
                    'price' => (float) $product->base_price,
                    'quantity' => (int) $product->stock_qty,
                    'status' => $product->is_active ? 1 : 0,
                    'category_ids' => $product->categories
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->values()
                        ->all(),
                    'updated_at' => optional($product->updated_at)?->toISOString(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function fetchPages(
        ?CarbonImmutable $changedSince,
        bool $includeInactive,
        string $locale,
        string $fallbackLocale
    ): array {
        $query = InfoPage::query()
            ->with([
                'translations' => fn ($q) => $q->whereIn('locale', array_unique([$locale, $fallbackLocale])),
            ])
            ->orderBy('content_info_pages.sort_order')
            ->orderBy('content_info_pages.id');

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        if ($changedSince) {
            $query->where(function (Builder $builder) use ($changedSince): void {
                $builder->where('content_info_pages.updated_at', '>=', $changedSince)
                    ->orWhereHas('translations', fn (Builder $translationQuery) => $translationQuery->where('updated_at', '>=', $changedSince));
            });
        }

        return $query->get()
            ->map(function (InfoPage $page) use ($locale, $fallbackLocale): array {
                $translation = $this->resolveTranslation($page->translations, $locale, $fallbackLocale);

                return [
                    'id' => (int) $page->id,
                    'code' => (string) $page->code,
                    'title' => (string) ($translation?->title ?? $page->code),
                    'slug' => (string) ($translation?->slug ?? ''),
                    'content' => (string) ($translation?->body_html ?? ''),
                    'sort_order' => (int) $page->sort_order,
                    'status' => $page->is_active ? 1 : 0,
                    'updated_at' => optional($page->updated_at)?->toISOString(),
                ];
            })
            ->values()
            ->all();
    }

    private function resolveTranslation($translations, string $locale, string $fallbackLocale): mixed
    {
        return $translations->firstWhere('locale', $locale)
            ?? $translations->firstWhere('locale', $fallbackLocale)
            ?? $translations->first();
    }
}


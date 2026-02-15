<?php

namespace App\Http\Resources\Api\V1\Wholesale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = strtolower((string) $request->query('locale', config('app.locale', 'en')));
        $fallbackLocale = strtolower((string) config('app.fallback_locale', config('app.locale', 'en')));

        $translation = $this->translations->firstWhere('locale', $locale)
            ?? $this->translations->firstWhere('locale', $fallbackLocale)
            ?? $this->translations->first();

        $manufacturer = null;
        if ($this->relationLoaded('manufacturer') && $this->manufacturer) {
            $manufacturerTranslation = $this->manufacturer->translations->firstWhere('locale', $locale)
                ?? $this->manufacturer->translations->firstWhere('locale', $fallbackLocale)
                ?? $this->manufacturer->translations->first();

            $manufacturer = [
                'id' => $this->manufacturer->id,
                'code' => $this->manufacturer->code,
                'name' => $manufacturerTranslation?->name,
                'slug' => $manufacturerTranslation?->slug,
            ];
        }

        $categories = [];
        if ($this->relationLoaded('categories')) {
            $categories = $this->categories
                ->sortBy(fn ($category) => (int) ($category->pivot?->sort_order ?? 0))
                ->values()
                ->map(function ($category) use ($locale, $fallbackLocale): array {
                    $categoryTranslation = $category->translations->firstWhere('locale', $locale)
                        ?? $category->translations->firstWhere('locale', $fallbackLocale)
                        ?? $category->translations->first();

                    return [
                        'id' => $category->id,
                        'scope' => $category->scope,
                        'code' => $category->code,
                        'name' => $categoryTranslation?->name,
                        'slug' => $categoryTranslation?->slug,
                        'sort_order' => (int) ($category->pivot?->sort_order ?? 0),
                        'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
                    ];
                })
                ->all();
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'sku' => $this->sku,
            'is_active' => (bool) $this->is_active,
            'base_price' => (float) $this->base_price,
            'stock_qty' => (int) $this->stock_qty,
            'locale' => $translation?->locale,
            'name' => $translation?->name,
            'slug' => $translation?->slug,
            'excerpt' => $translation?->excerpt,
            'description' => $translation?->description,
            'meta_title' => $translation?->meta_title,
            'meta_description' => $translation?->meta_description,
            'payload' => $this->payload,
            'manufacturer' => $manufacturer,
            'categories' => $categories,
            'created_at' => optional($this->created_at)?->toISOString(),
            'updated_at' => optional($this->updated_at)?->toISOString(),
        ];
    }
}

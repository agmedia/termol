<?php

namespace App\Support;

use App\Models\Catalog\Attribute\Attribute;
use App\Models\Catalog\Product\Product;
use Closure;

class ProductMaterialLabel
{
    /**
     * @return array<int, string>
     */
    public static function groupCodes(): array
    {
        return ['material', 'materijal', 'sastav'];
    }

    public static function eagerLoadAttributes(string $locale, string $fallbackLocale): Closure
    {
        return static function ($query) use ($locale, $fallbackLocale): void {
            $query
                ->where('catalog_attributes.is_active', true)
                ->whereIn('catalog_attributes.group_code', self::groupCodes())
                ->orderBy('catalog_attribute_product.sort_order')
                ->orderBy('catalog_attributes.sort_order')
                ->orderBy('catalog_attributes.id')
                ->with([
                    'translations' => fn ($translationQuery) => $translationQuery
                        ->select(['id', 'attribute_id', 'locale', 'group_name', 'name'])
                        ->whereIn('locale', [$locale, $fallbackLocale]),
                ]);
        };
    }

    public static function resolve(Product $product, ?string $locale = null, ?string $fallbackLocale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $fallbackLocale = $fallbackLocale ?: (string) config('app.locale');

        if (! $product->relationLoaded('attributes')) {
            $product->loadMissing([
                'attributes' => self::eagerLoadAttributes($locale, $fallbackLocale),
            ]);
        } else {
            $product->loadMissing([
                'attributes.translations' => fn ($translationQuery) => $translationQuery
                    ->select(['id', 'attribute_id', 'locale', 'group_name', 'name'])
                    ->whereIn('locale', [$locale, $fallbackLocale]),
            ]);
        }

        return $product->attributes
            ->filter(static fn (Attribute $attribute): bool => (bool) $attribute->is_active
                && in_array((string) $attribute->group_code, self::groupCodes(), true))
            ->sortBy(static fn (Attribute $attribute): string => sprintf(
                '%010d:%010d:%010d',
                (int) ($attribute->pivot?->sort_order ?? 0),
                (int) $attribute->sort_order,
                (int) $attribute->id
            ))
            ->map(static function (Attribute $attribute) use ($locale, $fallbackLocale): string {
                $translation = $attribute->translations->firstWhere('locale', $locale)
                    ?? $attribute->translations->firstWhere('locale', $fallbackLocale)
                    ?? $attribute->translations->first();

                return trim((string) ($translation?->name ?? $attribute->code));
            })
            ->filter(static fn (string $value): bool => $value !== '')
            ->unique()
            ->implode(', ');
    }
}

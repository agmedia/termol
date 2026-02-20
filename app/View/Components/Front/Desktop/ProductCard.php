<?php

namespace App\View\Components\Front\Desktop;

use App\Models\Catalog\Product\Product;
use App\Services\Pricing\TaxPricingService;
use App\Services\Front\WishlistService;
use Illuminate\View\Component;
use Illuminate\View\View;

class ProductCard extends Component
{
    public function __construct(
        public Product $product,
        public ?string $locale = null,
        public ?string $fallbackLocale = null,
        public bool $flat = false,
    ) {
    }

    public function render(): View
    {
        $locale = $this->locale ?: app()->getLocale();
        $fallbackLocale = $this->fallbackLocale ?: (string) config('app.locale');

        $translation = $this->product->translations->firstWhere('locale', $locale)
            ?? $this->product->translations->firstWhere('locale', $fallbackLocale);

        $mediaItems = $this->product->relationLoaded('media')
            ? $this->product->media->whereIn('collection_name', ['product_main', 'product_gallery'])->values()
            : collect();

        $mainMedia = $mediaItems->firstWhere('collection_name', 'product_main')
            ?? $mediaItems->firstWhere('collection_name', 'product_gallery')
            ?? $this->product->getFirstMedia('product_main')
            ?? $this->product->getFirstMedia('product_gallery');

        $hoverMedia = $mediaItems->first(
            static fn ($media): bool => $media->collection_name === 'product_gallery'
                && (! $mainMedia || (int) $media->id !== (int) $mainMedia->id)
        );

        $imageUrl = $mainMedia
            ? ($mainMedia->hasGeneratedConversion('card_360x240') ? $mainMedia->getUrl('card_360x240') : $mainMedia->getUrl())
            : null;
        $hoverImageUrl = $hoverMedia
            ? ($hoverMedia->hasGeneratedConversion('card_360x240') ? $hoverMedia->getUrl('card_360x240') : $hoverMedia->getUrl())
            : null;

        $optionRows = $this->product->optionValues
            ->where('is_active', true)
            ->values()
            ->map(function ($row) use ($locale, $fallbackLocale): array {
                $rowId = (int) $row->id;
                $valueTranslation = $row->optionValue?->translations?->firstWhere('locale', $locale)
                    ?? $row->optionValue?->translations?->firstWhere('locale', $fallbackLocale)
                    ?? $row->optionValue?->translations?->first();
                $parentTranslation = $row->parentOptionValue?->translations?->firstWhere('locale', $locale)
                    ?? $row->parentOptionValue?->translations?->firstWhere('locale', $fallbackLocale)
                    ?? $row->parentOptionValue?->translations?->first();
                $valueLabel = trim((string) ($valueTranslation?->name ?? $row->optionValue?->code ?? ''));
                $parentLabel = trim((string) ($parentTranslation?->name ?? $row->parentOptionValue?->code ?? ''));
                $label = $parentLabel !== '' && $valueLabel !== ''
                    ? $parentLabel.' / '.$valueLabel
                    : ($valueLabel !== '' ? $valueLabel : $parentLabel);

                return [
                    'id' => $rowId,
                    'input_id' => 'pov-'.$this->product->id.'-'.$rowId,
                    'label' => $label,
                ];
            })
            ->values()
            ->all();

        return view('components.front.desktop.product-card', [
            'productId' => (int) $this->product->id,
            'productUrl' => route('products.show', ['slug' => $translation?->slug ?? $this->product->id]),
            'productName' => $translation?->name ?? $this->product->code,
            'imageUrl' => $imageUrl,
            'hoverImageUrl' => $hoverImageUrl,
            'optionRows' => $optionRows,
            'isWishlisted' => app(WishlistService::class)->has((int) $this->product->id),
            'price' => number_format(
                app(TaxPricingService::class)->grossFromNet((float) $this->product->base_price, $this->product),
                2
            ).' €',
            'flat' => $this->flat,
        ]);
    }
}

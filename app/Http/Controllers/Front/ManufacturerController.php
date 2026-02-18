<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Manufacturer\Manufacturer;
use App\Services\Catalog\CatalogFeatureService;
use App\Services\Content\ContentBlockResolver;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManufacturerController extends Controller
{
    public function __construct(
        private readonly CatalogFeatureService $catalogFeatures
    ) {
    }

    public function index(): View
    {
        $this->ensureEnabled();

        $locale = app()->getLocale();
        $fallbackLocale = (string) config('app.locale');

        $manufacturers = Manufacturer::query()
            ->where('is_active', true)
            ->with(['translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale])])
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('front.desktop.manufacturers.index', [
            'manufacturers' => $manufacturers,
            'locale' => $locale,
            'fallbackLocale' => $fallbackLocale,
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $this->ensureEnabled();

        $locale = app()->getLocale();
        $fallbackLocale = (string) config('app.locale');

        $manufacturer = Manufacturer::query()
            ->where('is_active', true)
            ->whereHas('translations', function ($q) use ($locale, $fallbackLocale, $slug): void {
                $q->whereIn('locale', [$locale, $fallbackLocale])
                    ->where('slug', $slug);
            })
            ->with(['translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale])])
            ->firstOrFail();

        $products = $manufacturer->products()
            ->where('is_active', true)
            ->with(['translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale])])
            ->orderByDesc('products.id')
            ->paginate($this->productPerPage($request))
            ->withQueryString();

        $topBlocks = app(ContentBlockResolver::class)->forPlacement(
            placement: 'manufacturer.top',
            locale: $locale,
            targetType: 'manufacturer',
            targetRef: $slug,
            frontendVariant: 'desktop'
        );

        $bottomBlocks = app(ContentBlockResolver::class)->forPlacement(
            placement: 'manufacturer.bottom',
            locale: $locale,
            targetType: 'manufacturer',
            targetRef: $slug,
            frontendVariant: 'desktop'
        );

        return view('front.desktop.manufacturers.show', [
            'manufacturer' => $manufacturer,
            'products' => $products,
            'topBlocks' => $topBlocks,
            'bottomBlocks' => $bottomBlocks,
            'locale' => $locale,
            'fallbackLocale' => $fallbackLocale,
        ]);
    }

    private function ensureEnabled(): void
    {
        abort_unless($this->catalogFeatures->useManufacturers(), 404);
    }

    private function productPerPage(Request $request): int
    {
        $variant = (string) $request->attributes->get('frontend_variant', 'desktop');

        return app(SystemSettingsService::class)->getInt(
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
}

@php
    $locale = app()->getLocale();
    $fallbackLocale = (string) config('app.locale');
    $title = trim((string) ($translation?->title ?: $block->name));
    $subtitle = trim((string) ($translation?->subtitle ?? ''));
    $preferWebp = (bool) ($storeSettings['images']['use_webp'] ?? false);
    $ctaLabel = trim((string) ($translation?->cta_label ?: __('ui.featured_categories.view_all')));
    $ctaUrl = trim((string) ($translation?->cta_url ?: route('categories.index')));
@endphp

<section class="featured-categories storefront-widget-wide" data-featured-categories>
    @if ($title !== '' || $subtitle !== '' || ($ctaLabel !== '' && $ctaUrl !== ''))
        <header class="featured-categories-heading storefront-widget-heading--split">
            @if ($title !== '')
                <h2 class="storefront-widget-heading-title">{{ $title }}</h2>
            @endif
            @if ($subtitle !== '' || ($ctaLabel !== '' && $ctaUrl !== ''))
                <div class="storefront-widget-heading-meta">
                    @if ($subtitle !== '')
                        <span>{{ $subtitle }}</span>
                    @endif
                    @if ($ctaLabel !== '' && $ctaUrl !== '')
                        <a href="{{ $ctaUrl }}" class="storefront-widget-heading-link">{{ $ctaLabel }}</a>
                    @endif
                </div>
            @endif
        </header>
    @endif

    @if ($categories->isNotEmpty())
        <div class="featured-categories-grid">
            @foreach ($categories as $category)
                @php
                    $categoryTranslation = $category->translations->firstWhere('locale', $locale)
                        ?? $category->translations->firstWhere('locale', $fallbackLocale)
                        ?? $category->translations->first();
                    $categoryName = trim((string) ($categoryTranslation?->name ?? $category->code));
                    $categoryUrl = route('categories.show', [
                        'slug' => $categoryTranslation?->slug ?? $category->id,
                    ]);
                    $categoryMedia = $category->getFirstMedia('category_banner')
                        ?? $category->getFirstMedia('category_icon');
                    $categoryImageUrl = null;

                    if ($categoryMedia) {
                        $conversion = $categoryMedia->collection_name === 'category_banner'
                            ? 'card_360x240'
                            : 'icon_96x96';
                        $categoryImageUrl = \App\Support\Media\MediaUrl::conversionOrNull(
                            $categoryMedia,
                            $conversion,
                            $preferWebp
                        ) ?? $categoryMedia->getUrl();
                    }

                    $categoryImageAlt = $categoryMedia
                        ? trim((string) $categoryMedia->getCustomProperty('alt.'.$locale))
                        : '';

                    if ($categoryImageAlt === '' && $categoryMedia) {
                        $categoryImageAlt = trim((string) $categoryMedia->getCustomProperty('alt.'.$fallbackLocale));
                    }

                    if ($categoryImageAlt === '') {
                        $categoryImageAlt = __('ui.featured_categories.image_alt', ['name' => $categoryName]);
                    }

                    $productsCount = (int) ($category->products_count ?? 0);
                    $subcategoriesCount = (int) ($category->subcategories_count ?? 0);
                @endphp

                <a
                    href="{{ $categoryUrl }}"
                    class="featured-category-card"
                    data-featured-category="{{ $category->id }}"
                    aria-label="{{ $categoryName }}"
                >
                    <span class="featured-category-thumb">
                        @if ($categoryImageUrl)
                            <img
                                src="{{ $categoryImageUrl }}"
                                alt="{{ $categoryImageAlt }}"
                                width="360"
                                height="240"
                                loading="{{ $loop->index < 6 ? 'eager' : 'lazy' }}"
                                decoding="async"
                            >
                        @else
                            <span class="featured-category-placeholder" aria-hidden="true">
                                <svg viewBox="0 0 64 64" fill="none">
                                    <path d="M14 21.5 32 12l18 9.5v21L32 52l-18-9.5v-21Z" stroke="currentColor" stroke-width="2.5"/>
                                    <path d="m14 21.5 18 10 18-10M32 31.5V52" stroke="currentColor" stroke-width="2.5"/>
                                </svg>
                            </span>
                        @endif
                    </span>

                    <span class="featured-category-content">
                        <span class="featured-category-name">{{ $categoryName }}</span>
                        <span class="featured-category-meta">
                            <span>{{ trans_choice('ui.featured_categories.product_count', $productsCount, ['count' => $productsCount]) }}</span>
                            <span aria-hidden="true">·</span>
                            <span>{{ trans_choice('ui.featured_categories.subcategory_count', $subcategoriesCount, ['count' => $subcategoriesCount]) }}</span>
                        </span>
                    </span>
                </a>
            @endforeach
        </div>

    @else
        <div class="featured-categories-empty">{{ __('ui.featured_categories.empty') }}</div>
    @endif
</section>

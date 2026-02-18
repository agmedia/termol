@foreach ($items as $item)
    @php
        $block = $item['block'];
        $translation = $item['translation'];
        $slot = $item['slot'];
        $locale = app()->getLocale();
        $fallbackLocale = config('app.locale');

        $overridePrefix = (string) config('content_blocks.view_overrides.prefix', 'front.content-blocks.instances.');
        $codeOverride = $overridePrefix.$block->code;

        $overrideView = '';
        if (view()->exists($codeOverride)) {
            $overrideView = $codeOverride;
        }

        $partial = 'front.content-blocks.types.'.$block->type;

        $blockItems = $block->relationLoaded('items')
            ? $block->items
            : $block->items()->orderBy('sort_order')->orderBy('id')->get();

        $productIds = $blockItems->where('item_type', 'product')->pluck('item_id')->map(fn ($id) => (int) $id)->all();
        $categoryIds = $blockItems->where('item_type', 'category')->pluck('item_id')->map(fn ($id) => (int) $id)->all();
        $manufacturerIds = $blockItems->where('item_type', 'manufacturer')->pluck('item_id')->map(fn ($id) => (int) $id)->all();
        $blogIds = $blockItems->where('item_type', 'blog')->pluck('item_id')->map(fn ($id) => (int) $id)->all();

        $products = collect();
        if ($productIds !== []) {
            $products = \App\Models\Catalog\Product\Product::query()
                ->whereIn('id', $productIds)
                ->with(['translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale])])
                ->get()
                ->sortBy(fn ($row) => array_search((int) $row->id, $productIds, true))
                ->values();
        }

        $categories = collect();
        if ($categoryIds !== []) {
            $categories = \App\Models\Catalog\Category\Category::query()
                ->whereIn('id', $categoryIds)
                ->with(['translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale])])
                ->get()
                ->sortBy(fn ($row) => array_search((int) $row->id, $categoryIds, true))
                ->values();
        }

        $manufacturers = collect();
        if ($manufacturerIds !== []) {
            $manufacturers = \App\Models\Catalog\Manufacturer\Manufacturer::query()
                ->whereIn('id', $manufacturerIds)
                ->with(['translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale])])
                ->get()
                ->sortBy(fn ($row) => array_search((int) $row->id, $manufacturerIds, true))
                ->values();
        }

        $blogs = collect();
        if ($blogIds !== []) {
            $blogs = \App\Models\Content\Blog\BlogPost::query()
                ->whereIn('id', $blogIds)
                ->with(['translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale])])
                ->get()
                ->sortBy(fn ($row) => array_search((int) $row->id, $blogIds, true))
                ->values();
        }
    @endphp

    @if ($overrideView !== '')
        @include($overrideView, [
            'block' => $block,
            'translation' => $translation,
            'slot' => $slot,
            'blockItems' => $blockItems,
            'products' => $products,
            'categories' => $categories,
            'manufacturers' => $manufacturers,
            'blogs' => $blogs,
        ])
    @elseif (view()->exists($partial))
        @include($partial, [
            'block' => $block,
            'translation' => $translation,
            'slot' => $slot,
            'blockItems' => $blockItems,
            'products' => $products,
            'categories' => $categories,
            'manufacturers' => $manufacturers,
            'blogs' => $blogs,
        ])
    @else
        @include('front.content-blocks.types.custom', [
            'block' => $block,
            'translation' => $translation,
            'slot' => $slot,
            'blockItems' => $blockItems,
            'products' => $products,
            'categories' => $categories,
            'manufacturers' => $manufacturers,
            'blogs' => $blogs,
        ])
    @endif
@endforeach

<?php

namespace App\Services\Front;

use App\Models\Catalog\Category\Category;
use App\Models\Content\Page\InfoPage;
use App\Services\Settings\SystemSettingsService;

class NavigationMenuService
{
    public const SETTINGS_KEY = 'front_navigation_main';

    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    private array $resolvedCache = [];

    public function __construct(private readonly SystemSettingsService $settings)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forLocale(string $locale): array
    {
        $locale = strtolower(trim($locale));
        $fallbackLocale = strtolower((string) config('app.locale', 'en'));
        $cacheKey = $locale.'|'.$fallbackLocale;

        if (isset($this->resolvedCache[$cacheKey])) {
            return $this->resolvedCache[$cacheKey];
        }

        $items = collect($this->configuredItems())
            ->filter(fn ($item): bool => (bool) ($item['is_active'] ?? true))
            ->sortBy(fn ($item): int => (int) ($item['sort_order'] ?? 0))
            ->values();

        if ($items->isEmpty()) {
            return $this->resolvedCache[$cacheKey] = [];
        }

        $categoryIds = $items
            ->where('type', 'category')
            ->pluck('category_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn ($id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $pageIds = $items
            ->where('type', 'page')
            ->pluck('page_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn ($id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $categories = Category::query()
            ->where('scope', Category::SCOPE_CATALOG)
            ->where('is_active', true)
            ->with([
                'translations' => fn ($q) => $q
                    ->where('scope', Category::SCOPE_CATALOG)
                    ->whereIn('locale', [$locale, $fallbackLocale]),
            ])
            ->orderBy('_lft')
            ->get();

        $categoriesById = $categories->keyBy('id');
        $childrenByParentId = $categories->groupBy(fn ($category) => (int) ($category->parent_id ?? 0));

        $pagesById = InfoPage::query()
            ->where('is_active', true)
            ->whereIn('id', $pageIds)
            ->with([
                'translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
            ])
            ->get()
            ->keyBy('id');

        $resolved = [];

        foreach ($items as $index => $item) {
            $type = (string) ($item['type'] ?? 'custom');
            $entry = null;

            if ($type === 'category') {
                $categoryId = (int) ($item['category_id'] ?? 0);
                $category = $categoryId > 0 ? $categoriesById->get($categoryId) : null;

                if ($category instanceof Category) {
                    $entry = $this->resolveCategoryItem($category, $item, $childrenByParentId, $locale, $fallbackLocale);
                }
            } elseif ($type === 'page') {
                $pageId = (int) ($item['page_id'] ?? 0);
                $page = $pageId > 0 ? $pagesById->get($pageId) : null;

                if ($page instanceof InfoPage) {
                    $translation = $this->pickPageTranslation($page, $locale, $fallbackLocale);
                    $slug = trim((string) ($translation?->slug ?? ''));

                    if ($slug !== '') {
                        $label = $this->labelForItem($item, (string) ($translation?->title ?? $page->code));
                        $entry = [
                            'key' => 'page-'.$page->id.'-'.$index,
                            'type' => 'page',
                            'label' => $label,
                            'url' => route('pages.show', ['slug' => $slug]),
                            'children' => [],
                            'open_in_new_tab' => false,
                        ];
                    }
                }
            } elseif ($type === 'blog') {
                $entry = [
                    'key' => 'blog-'.$index,
                    'type' => 'blog',
                    'label' => $this->labelForItem($item, 'Blog'),
                    'url' => route('blog.index'),
                    'children' => [],
                    'open_in_new_tab' => false,
                ];
            } elseif ($type === 'contact') {
                $entry = [
                    'key' => 'contact-'.$index,
                    'type' => 'contact',
                    'label' => $this->labelForItem($item, 'Kontakt'),
                    'url' => route('contact.create'),
                    'children' => [],
                    'open_in_new_tab' => false,
                ];
            } else {
                $url = trim((string) ($item['url'] ?? ''));
                $label = trim((string) ($item['label'] ?? ''));

                if ($url !== '' && $label !== '') {
                    $entry = [
                        'key' => 'custom-'.$index,
                        'type' => 'custom',
                        'label' => $label,
                        'url' => $url,
                        'children' => [],
                        'open_in_new_tab' => (bool) ($item['open_in_new_tab'] ?? false),
                    ];
                }
            }

            if (is_array($entry) && trim((string) ($entry['url'] ?? '')) !== '') {
                $resolved[] = $entry;
            }
        }

        return $this->resolvedCache[$cacheKey] = $resolved;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function configuredItems(): array
    {
        $raw = $this->settings->get(self::SETTINGS_KEY, []);

        if (! is_array($raw)) {
            return [];
        }

        $items = [];
        foreach ($raw as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $items[] = [
                'type' => (string) ($item['type'] ?? 'custom'),
                'label' => trim((string) ($item['label'] ?? '')),
                'category_id' => (int) ($item['category_id'] ?? 0),
                'page_id' => (int) ($item['page_id'] ?? 0),
                'url' => trim((string) ($item['url'] ?? '')),
                'open_in_new_tab' => (bool) ($item['open_in_new_tab'] ?? false),
                'show_dropdown' => (bool) ($item['show_dropdown'] ?? true),
                'is_active' => (bool) ($item['is_active'] ?? true),
                'sort_order' => (int) ($item['sort_order'] ?? $index),
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $item
     * @param \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, Category>> $childrenByParentId
     * @return array<string, mixed>|null
     */
    private function resolveCategoryItem(
        Category $category,
        array $item,
        $childrenByParentId,
        string $locale,
        string $fallbackLocale
    ): ?array {
        $translation = $this->pickCategoryTranslation($category, $locale, $fallbackLocale);
        $slug = trim((string) ($translation?->slug ?? ''));

        if ($slug === '') {
            return null;
        }

        $children = [];
        if ((bool) ($item['show_dropdown'] ?? true)) {
            $children = $this->buildCategoryChildren($category->id, $childrenByParentId, $locale, $fallbackLocale);
        }

        return [
            'key' => 'category-'.$category->id,
            'type' => 'category',
            'label' => $this->labelForItem($item, (string) ($translation?->name ?? $category->code)),
            'url' => route('categories.show', ['slug' => $slug]),
            'children' => $children,
            'open_in_new_tab' => false,
        ];
    }

    /**
     * @param \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, Category>> $childrenByParentId
     * @return array<int, array<string, mixed>>
     */
    private function buildCategoryChildren(int $parentId, $childrenByParentId, string $locale, string $fallbackLocale): array
    {
        $children = [];
        $childrenRows = $childrenByParentId->get($parentId, collect())
            ->sortBy(fn (Category $category): array => [(int) $category->sort_order, (int) $category->id])
            ->values();

        foreach ($childrenRows as $child) {
            $translation = $this->pickCategoryTranslation($child, $locale, $fallbackLocale);
            $slug = trim((string) ($translation?->slug ?? ''));

            if ($slug === '') {
                continue;
            }

            $children[] = [
                'label' => (string) ($translation?->name ?? $child->code),
                'url' => route('categories.show', ['slug' => $slug]),
                'children' => $this->buildCategoryChildren((int) $child->id, $childrenByParentId, $locale, $fallbackLocale),
            ];
        }

        return $children;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function labelForItem(array $item, string $fallback): string
    {
        $label = trim((string) ($item['label'] ?? ''));

        return $label !== '' ? $label : $fallback;
    }

    private function pickCategoryTranslation(Category $category, string $locale, string $fallbackLocale): mixed
    {
        return $category->translations->firstWhere('locale', $locale)
            ?? $category->translations->firstWhere('locale', $fallbackLocale)
            ?? $category->translations->first();
    }

    private function pickPageTranslation(InfoPage $page, string $locale, string $fallbackLocale): mixed
    {
        return $page->translations->firstWhere('locale', $locale)
            ?? $page->translations->firstWhere('locale', $fallbackLocale)
            ?? $page->translations->first();
    }
}

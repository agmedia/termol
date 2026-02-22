<?php

namespace App\Livewire\Admin\Content\Navigation;

use App\Models\Catalog\Category\Category;
use App\Models\Content\Page\InfoPage;
use App\Services\Front\NavigationMenuService;
use App\Services\Settings\SystemSettingsService;
use Livewire\Component;

class Manager extends Component
{
    /**
     * @var array{items: array<int, array<string, mixed>>}
     */
    public array $form = [
        'items' => [],
    ];

    public string $locale = 'en';

    public function mount(): void
    {
        $this->locale = (string) (request()->query('locale') ?: config('app.locale', 'en'));

        $items = app(NavigationMenuService::class)->configuredItems();
        $this->form['items'] = $items;
    }

    public function updatedLocale(): void
    {
        // No-op; options are reloaded from render.
    }

    public function addCategoryItem(): void
    {
        $this->form['items'][] = $this->makeDefaultItem('category');
    }

    public function addPageItem(): void
    {
        $this->form['items'][] = $this->makeDefaultItem('page');
    }

    public function addBlogItem(): void
    {
        $item = $this->makeDefaultItem('blog');
        $item['label'] = 'Blog';
        $this->form['items'][] = $item;
    }

    public function addContactItem(): void
    {
        $item = $this->makeDefaultItem('contact');
        $item['label'] = 'Kontakt';
        $this->form['items'][] = $item;
    }

    public function addCustomItem(): void
    {
        $item = $this->makeDefaultItem('custom');
        $item['label'] = 'Novi link';
        $item['url'] = '/';
        $this->form['items'][] = $item;
    }

    public function removeItem(int $index): void
    {
        if (! isset($this->form['items'][$index])) {
            return;
        }

        unset($this->form['items'][$index]);
        $this->form['items'] = array_values($this->form['items']);
    }

    public function moveUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->form['items'][$index])) {
            return;
        }

        [$this->form['items'][$index - 1], $this->form['items'][$index]] = [$this->form['items'][$index], $this->form['items'][$index - 1]];
    }

    public function moveDown(int $index): void
    {
        $lastIndex = count($this->form['items']) - 1;

        if ($index < 0 || $index >= $lastIndex || ! isset($this->form['items'][$index])) {
            return;
        }

        [$this->form['items'][$index + 1], $this->form['items'][$index]] = [$this->form['items'][$index], $this->form['items'][$index + 1]];
    }

    public function save(): void
    {
        $validated = $this->validate([
            'form.items' => ['array'],
            'form.items.*.type' => ['required', 'in:category,page,blog,contact,custom'],
            'form.items.*.label' => ['nullable', 'string', 'max:120'],
            'form.items.*.category_id' => ['nullable', 'integer', 'min:0'],
            'form.items.*.page_id' => ['nullable', 'integer', 'min:0'],
            'form.items.*.url' => ['nullable', 'string', 'max:2048'],
            'form.items.*.is_active' => ['required', 'boolean'],
            'form.items.*.show_dropdown' => ['required', 'boolean'],
            'form.items.*.open_in_new_tab' => ['required', 'boolean'],
            'form.items.*.sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $normalizedItems = [];
        foreach (($validated['form']['items'] ?? []) as $index => $item) {
            $normalized = $this->normalizeItem($item, $index);
            $normalizedItems[] = $normalized;

            $type = (string) $normalized['type'];
            if ($type === 'category' && (int) $normalized['category_id'] <= 0) {
                $this->addError('form.items.'.$index.'.category_id', 'Odaberite kategoriju.');
            }
            if ($type === 'page' && (int) $normalized['page_id'] <= 0) {
                $this->addError('form.items.'.$index.'.page_id', 'Odaberite stranicu.');
            }
            if ($type === 'custom') {
                if (trim((string) $normalized['label']) === '') {
                    $this->addError('form.items.'.$index.'.label', 'Unesite naziv linka.');
                }
                if (trim((string) $normalized['url']) === '') {
                    $this->addError('form.items.'.$index.'.url', 'Unesite URL linka.');
                }
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        app(SystemSettingsService::class)->put(NavigationMenuService::SETTINGS_KEY, $normalizedItems);

        $this->dispatch('notify', type: 'success', message: 'Navigation menu spremljen.');
    }

    public function render()
    {
        $fallbackLocale = (string) config('app.locale', 'en');
        $locales = array_values(array_unique([$this->locale, $fallbackLocale]));

        $categoryOptions = Category::query()
            ->where('scope', Category::SCOPE_CATALOG)
            ->where('is_active', true)
            ->with([
                'translations' => fn ($q) => $q
                    ->where('scope', Category::SCOPE_CATALOG)
                    ->whereIn('locale', $locales),
            ])
            ->orderBy('_lft')
            ->get()
            ->map(function (Category $category) use ($fallbackLocale): array {
                $translation = $category->translations->firstWhere('locale', $this->locale)
                    ?? $category->translations->firstWhere('locale', $fallbackLocale)
                    ?? $category->translations->first();
                $label = (string) ($translation?->name ?? $category->code);
                $depth = max(0, ((int) ($category->depth ?? 0)) - 1);

                return [
                    'id' => (int) $category->id,
                    'label' => str_repeat('— ', $depth).$label,
                ];
            })
            ->values()
            ->all();

        $pageOptions = InfoPage::query()
            ->where('is_active', true)
            ->with([
                'translations' => fn ($q) => $q->whereIn('locale', $locales),
            ])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(function (InfoPage $page) use ($fallbackLocale): array {
                $translation = $page->translations->firstWhere('locale', $this->locale)
                    ?? $page->translations->firstWhere('locale', $fallbackLocale)
                    ?? $page->translations->first();

                return [
                    'id' => (int) $page->id,
                    'label' => (string) ($translation?->title ?? $page->code),
                ];
            })
            ->values()
            ->all();

        return view('livewire.admin.content.navigation.manager', [
            'categoryOptions' => $categoryOptions,
            'pageOptions' => $pageOptions,
        ]);
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function normalizeItem(array $item, int $index): array
    {
        $type = (string) ($item['type'] ?? 'custom');

        return [
            'type' => $type,
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

    /**
     * @return array<string, mixed>
     */
    private function makeDefaultItem(string $type): array
    {
        return [
            'type' => $type,
            'label' => '',
            'category_id' => 0,
            'page_id' => 0,
            'url' => '',
            'open_in_new_tab' => false,
            'show_dropdown' => true,
            'is_active' => true,
            'sort_order' => count($this->form['items']),
        ];
    }
}

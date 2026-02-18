<?php

namespace App\Livewire\Admin\Content\Block;

use App\Models\Catalog\Category\Category;
use App\Models\Catalog\Manufacturer\Manufacturer;
use App\Models\Catalog\Product\Product;
use App\Models\Content\Blog\BlogPost;
use App\Models\Content\ContentBlock;
use App\Services\Content\ContentBlockResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    private const ITEM_TYPE_MAP = [
        'products' => 'product',
        'categories' => 'category',
        'manufacturers' => 'manufacturer',
        'blogs' => 'blog',
    ];

    public ?int $blockId = null;

    public array $form = [];

    /**
     * @var array<string, string>
     */
    public array $types = [];

    /**
     * @var array<string, string>
     */
    public array $placements = [];

    /**
     * @var array<string, string>
     */
    public array $targetTypes = [
        '' => 'Global (no target)',
        'category' => 'Category',
        'product' => 'Product',
        'blog_post' => 'Blog Post',
        'page' => 'Static Page',
        'custom' => 'Custom',
    ];

    public ?int $pickerItemId = null;
    public string $lastType = '';

    public function mount(?int $blockId = null): void
    {
        /** @var array<string, string> $types */
        $types = config('content_blocks.types', []);
        /** @var array<string, string> $placements */
        $placements = config('content_blocks.placements', []);

        $this->types = $this->orderedTypes($types);
        $this->placements = $placements;

        $this->resetForm();
        $this->lastType = (string) ($this->form['type'] ?? '');

        if ($blockId) {
            $this->blockId = $blockId;
            $this->loadBlock();
        }
    }

    public function getIsEditProperty(): bool
    {
        return (bool) $this->blockId;
    }

    public function getCurrentItemTypeProperty(): ?string
    {
        return $this->itemTypeForBlockType((string) ($this->form['type'] ?? ''));
    }

    public function getIsItemBlockProperty(): bool
    {
        return $this->currentItemType !== null;
    }

    public function getItemOptionsProperty(): Collection
    {
        return $this->loadItemOptions($this->currentItemType);
    }

    public function getSelectedItemsProperty(): Collection
    {
        $optionsById = $this->itemOptions->keyBy('id');

        return collect((array) ($this->form['selected_item_ids'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->map(function (int $id, int $index) use ($optionsById): array {
                $row = $optionsById->get($id);

                return [
                    'id' => $id,
                    'index' => $index,
                    'label' => (string) ($row['label'] ?? ('#'.$id)),
                ];
            });
    }

    public function updatedFormLocale(): void
    {
        $this->loadTranslationForLocale();
    }

    public function updatedFormType(string $type): void
    {
        if (!array_key_exists($type, $this->types)) {
            return;
        }

        $currentItemType = $this->itemTypeForBlockType($type);
        $existingType = $this->itemTypeForBlockType($this->lastType);

        if ($currentItemType !== $existingType) {
            $this->form['selected_item_ids'] = [];
            $this->pickerItemId = null;
        }

        $currentTemplate = (string) ($this->form['template_body'] ?? '');
        $shouldLoadDefault = trim($currentTemplate) === '';

        if (! $shouldLoadDefault && $this->lastType !== '') {
            $previousDefault = $this->defaultTemplateForType($this->lastType);
            $shouldLoadDefault = $this->normalizedTemplate($currentTemplate) === $this->normalizedTemplate($previousDefault);
        }

        if ($shouldLoadDefault) {
            $this->form['template_body'] = $this->defaultTemplateForType($type);
        }

        $this->lastType = $type;
    }

    public function loadTemplatePreset(): void
    {
        $type = (string) ($this->form['type'] ?? 'banner');
        $this->form['template_body'] = $this->defaultTemplateForType($type);
    }

    public function addSelectedItem(): void
    {
        $id = (int) ($this->pickerItemId ?? 0);
        if ($id <= 0) {
            return;
        }

        $existing = collect((array) ($this->form['selected_item_ids'] ?? []))->map(fn ($v) => (int) $v);
        if ($existing->contains($id)) {
            return;
        }

        $optionExists = $this->itemOptions->contains(fn ($row) => (int) ($row['id'] ?? 0) === $id);
        if (! $optionExists) {
            return;
        }

        $this->form['selected_item_ids'][] = $id;
        $this->form['selected_item_ids'] = array_values(array_unique(array_map(
            static fn ($value): int => (int) $value,
            (array) $this->form['selected_item_ids']
        )));
    }

    public function removeSelectedItem(int $id): void
    {
        $this->form['selected_item_ids'] = array_values(array_filter(
            array_map(static fn ($value): int => (int) $value, (array) ($this->form['selected_item_ids'] ?? [])),
            static fn (int $value): bool => $value !== $id
        ));
    }

    public function moveSelectedItemUp(int $index): void
    {
        $rows = array_values(array_map(static fn ($value): int => (int) $value, (array) ($this->form['selected_item_ids'] ?? [])));
        if ($index <= 0 || $index >= count($rows)) {
            return;
        }

        [$rows[$index - 1], $rows[$index]] = [$rows[$index], $rows[$index - 1]];
        $this->form['selected_item_ids'] = $rows;
    }

    public function moveSelectedItemDown(int $index): void
    {
        $rows = array_values(array_map(static fn ($value): int => (int) $value, (array) ($this->form['selected_item_ids'] ?? [])));
        if ($index < 0 || $index >= count($rows) - 1) {
            return;
        }

        [$rows[$index + 1], $rows[$index]] = [$rows[$index], $rows[$index + 1]];
        $this->form['selected_item_ids'] = $rows;
    }

    public function save()
    {
        $validated = $this->validate($this->rules());
        $userId = auth()->id();
        $isEdit = $this->blockId !== null;
        $existingBlockPayload = null;
        $translationPayload = [];
        if ($this->blockId) {
            $existingBlock = ContentBlock::query()
                ->with(['translations' => fn ($q) => $q->where('locale', (string) $validated['form']['locale'])])
                ->find($this->blockId);
            $existingBlockPayload = is_array($existingBlock?->payload ?? null) ? $existingBlock->payload : null;
            $translationPayload = is_array($existingBlock?->translations->first()?->payload ?? null)
                ? $existingBlock->translations->first()->payload
                : [];
        }

        $bgCss = trim((string) ($validated['form']['bg_css'] ?? ''));
        if ($bgCss !== '') {
            $translationPayload['bg_css'] = $bgCss;
        } else {
            unset($translationPayload['bg_css']);
        }

        $customClasses = trim((string) ($validated['form']['custom_classes'] ?? ''));
        if ($customClasses !== '') {
            $translationPayload['custom_classes'] = $customClasses;
        } else {
            unset($translationPayload['custom_classes']);
        }
        unset($translationPayload['render_mode'], $translationPayload['body_html_container_class']);

        $itemType = $this->itemTypeForBlockType((string) $validated['form']['type']);
        $selectedIds = collect((array) ($validated['form']['selected_item_ids'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if (trim((string) ($validated['form']['slot_target_ref'] ?? '')) !== '' && trim((string) ($validated['form']['slot_target_type'] ?? '')) === '') {
            $this->addError('form.slot_target_type', 'Target type is required when target ref is set.');
            $this->dispatch('notify', type: 'warning', message: 'Choose target type when target ref is set.');

            return null;
        }

        if ($itemType !== null && $selectedIds === []) {
            $this->addError('form.selected_item_ids', 'Select at least one item for this block type.');
            $this->dispatch('notify', type: 'warning', message: 'Select at least one item.');

            return null;
        }

        if ($itemType !== null && $selectedIds !== []) {
            $validIds = $this->validIdsForItemType($itemType, $selectedIds);
            if (count($validIds) !== count($selectedIds)) {
                $this->addError('form.selected_item_ids', 'One or more selected items are invalid.');
                $this->dispatch('notify', type: 'warning', message: 'Invalid item selection detected.');

                return null;
            }
        }

        $oldCode = null;
        if ($this->blockId) {
            $oldCode = (string) ContentBlock::query()->whereKey($this->blockId)->value('code');
        }

        DB::transaction(function () use (
            $validated,
            $existingBlockPayload,
            $translationPayload,
            $itemType,
            $selectedIds,
            $userId,
            $oldCode,
            $isEdit
        ): void {
            $blockData = [
                'code' => trim((string) $validated['form']['code']),
                'name' => trim((string) $validated['form']['name']),
                'type' => (string) $validated['form']['type'],
                'is_active' => (bool) $validated['form']['is_active'],
                'payload' => $existingBlockPayload,
                'updated_by' => $userId,
            ];

            if ($this->blockId) {
                $block = ContentBlock::query()->findOrFail($this->blockId);
                $block->update($blockData);
            } else {
                $block = ContentBlock::query()->create($blockData + ['created_by' => $userId]);
                $this->blockId = $block->id;
            }

            $block->translations()->updateOrCreate(
                ['locale' => (string) $validated['form']['locale']],
                [
                    'title' => $validated['form']['title'] ?: null,
                    'subtitle' => $validated['form']['subtitle'] ?: null,
                    'body_html' => null,
                    'cta_label' => $validated['form']['cta_label'] ?: null,
                    'cta_url' => $validated['form']['cta_url'] ?: null,
                    'payload' => $translationPayload === [] ? null : $translationPayload,
                ]
            );

            $this->savePrimarySlot($block, $validated, $userId);
            $this->syncBlockItems($block, $itemType, $selectedIds);

            $template = trim((string) ($validated['form']['template_body'] ?? ''));
            if ($template === '') {
                $template = $this->defaultTemplateForType((string) $block->type);
            }
            $this->writeTemplateFile((string) $block->code, $template);

            if ($oldCode !== null && $oldCode !== '' && $oldCode !== $block->code) {
                $this->deleteTemplateFile($oldCode);
            }

            activity('content_blocks')
                ->performedOn($block)
                ->causedBy(auth()->user())
                ->event($isEdit ? 'updated' : 'created')
                ->withProperties([
                    'code' => $block->code,
                    'type' => $block->type,
                    'placement' => $validated['form']['slot_placement'],
                    'item_type' => $itemType,
                    'item_count' => count($selectedIds),
                    'template_file' => $this->templateViewName((string) $block->code),
                ])
                ->log('Content block saved (v2 builder)');
        });

        ContentBlockResolver::bumpCacheVersion();

        return redirect()->route('admin.content.blocks')->with('notify', [
            'type' => 'success',
            'message' => $isEdit ? 'Content block updated.' : 'Content block created.',
        ]);
    }

    public function backToList()
    {
        return redirect()->route('admin.content.blocks');
    }

    public function render()
    {
        return view('livewire.admin.content.block.form');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'form.code' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('content_blocks', 'code')->ignore($this->blockId)],
            'form.name' => ['required', 'string', 'max:180'],
            'form.type' => ['required', Rule::in(array_keys($this->types))],
            'form.is_active' => ['boolean'],

            'form.locale' => ['required', 'string', 'max:12'],
            'form.title' => ['nullable', 'string', 'max:255'],
            'form.subtitle' => ['nullable', 'string'],
            'form.cta_label' => ['nullable', 'string', 'max:120'],
            'form.cta_url' => ['nullable', 'string', 'max:2048'],
            'form.bg_css' => ['nullable', 'string', 'max:6000'],
            'form.custom_classes' => ['nullable', 'string', 'max:1000'],
            'form.template_body' => ['nullable', 'string'],

            'form.slot_placement' => ['required', 'string', 'max:120'],
            'form.slot_target_type' => ['nullable', 'string', 'max:80'],
            'form.slot_target_ref' => ['nullable', 'string', 'max:191'],
            'form.slot_sort_order' => ['nullable', 'integer', 'min:0'],
            'form.slot_is_active' => ['boolean'],
            'form.slot_starts_at' => ['nullable', 'date'],
            'form.slot_ends_at' => ['nullable', 'date', 'after_or_equal:form.slot_starts_at'],

            'form.selected_item_ids' => ['nullable', 'array'],
            'form.selected_item_ids.*' => ['integer', 'min:1'],
        ];
    }

    private function resetForm(): void
    {
        $defaultType = array_key_exists('banner', $this->types)
            ? 'banner'
            : (array_key_first($this->types) ?: 'custom');

        $this->form = [
            'code' => '',
            'name' => '',
            'type' => $defaultType,
            'is_active' => true,

            'locale' => config('app.locale'),
            'title' => '',
            'subtitle' => '',
            'cta_label' => '',
            'cta_url' => '',
            'bg_css' => '',
            'custom_classes' => '',
            'template_body' => $this->defaultTemplateForType($defaultType),

            'slot_placement' => array_key_first($this->placements) ?: 'home.hero',
            'slot_target_type' => '',
            'slot_target_ref' => '',
            'slot_sort_order' => 0,
            'slot_is_active' => true,
            'slot_starts_at' => '',
            'slot_ends_at' => '',

            'selected_item_ids' => [],
        ];

        $this->pickerItemId = null;
    }

    private function loadBlock(): void
    {
        if (! $this->blockId) {
            return;
        }

        $block = ContentBlock::query()
            ->with([
                'translations',
                'slots' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
                'items' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
            ])
            ->findOrFail($this->blockId);

        $locale = (string) ($this->form['locale'] ?? config('app.locale'));
        $translation = $block->translations->firstWhere('locale', $locale)
            ?? $block->translations->firstWhere('locale', config('app.locale'))
            ?? $block->translations->first();

        $slot = $block->slots->first();
        $translationPayload = is_array($translation?->payload ?? null) ? $translation->payload : [];

        $this->form['code'] = $block->code;
        $this->form['name'] = $block->name;
        $this->form['type'] = $block->type;
        $this->form['is_active'] = (bool) $block->is_active;

        if ($translation) {
            $this->form['locale'] = $translation->locale;
            $this->form['title'] = $translation->title ?? '';
            $this->form['subtitle'] = $translation->subtitle ?? '';
            $this->form['cta_label'] = $translation->cta_label ?? '';
            $this->form['cta_url'] = $translation->cta_url ?? '';
        }

        $this->form['bg_css'] = (string) ($translationPayload['bg_css'] ?? '');
        $this->form['custom_classes'] = (string) ($translationPayload['custom_classes'] ?? '');

        $this->form['slot_placement'] = (string) ($slot?->placement ?? (array_key_first($this->placements) ?: 'home.hero'));
        $this->form['slot_target_type'] = (string) ($slot?->target_type ?? '');
        $this->form['slot_target_ref'] = (string) ($slot?->target_ref ?? '');
        $this->form['slot_sort_order'] = (int) ($slot?->sort_order ?? 0);
        $this->form['slot_is_active'] = (bool) ($slot?->is_active ?? true);
        $this->form['slot_starts_at'] = $slot?->starts_at?->format('Y-m-d\\TH:i') ?? '';
        $this->form['slot_ends_at'] = $slot?->ends_at?->format('Y-m-d\\TH:i') ?? '';

        $expectedItemType = $this->itemTypeForBlockType($block->type);
        $selected = $block->items;
        if ($expectedItemType !== null) {
            $selected = $selected->where('item_type', $expectedItemType);
        }

        $this->form['selected_item_ids'] = $selected
            ->pluck('item_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->lastType = (string) $block->type;

        $existingTemplate = $this->readTemplateFile($block->code);
        $this->form['template_body'] = $existingTemplate !== ''
            ? $existingTemplate
            : $this->defaultTemplateForType($block->type);
    }

    private function loadTranslationForLocale(): void
    {
        if (! $this->blockId) {
            $this->clearTranslationFields();

            return;
        }

        $block = ContentBlock::query()
            ->with('translations')
            ->find($this->blockId);

        if (! $block) {
            return;
        }

        $translation = $block->translations->firstWhere('locale', $this->form['locale']);

        if (! $translation) {
            $this->clearTranslationFields();

            return;
        }

        $translationPayload = is_array($translation->payload ?? null) ? $translation->payload : [];

        $this->form['title'] = $translation->title ?? '';
        $this->form['subtitle'] = $translation->subtitle ?? '';
        $this->form['cta_label'] = $translation->cta_label ?? '';
        $this->form['cta_url'] = $translation->cta_url ?? '';
        $this->form['bg_css'] = (string) ($translationPayload['bg_css'] ?? '');
        $this->form['custom_classes'] = (string) ($translationPayload['custom_classes'] ?? '');
    }

    private function clearTranslationFields(): void
    {
        $this->form['title'] = '';
        $this->form['subtitle'] = '';
        $this->form['cta_label'] = '';
        $this->form['cta_url'] = '';
        $this->form['bg_css'] = '';
        $this->form['custom_classes'] = '';
    }

    private function itemTypeForBlockType(string $type): ?string
    {
        return self::ITEM_TYPE_MAP[$type] ?? null;
    }

    private function loadItemOptions(?string $itemType): Collection
    {
        $locale = (string) ($this->form['locale'] ?? config('app.locale'));
        $fallbackLocale = config('app.locale');

        if ($itemType === 'product') {
            return Product::query()
                ->where('is_active', true)
                ->with(['translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale])])
                ->orderByDesc('id')
                ->limit(300)
                ->get()
                ->map(function (Product $row) use ($locale, $fallbackLocale): array {
                    $translation = $row->translations->firstWhere('locale', $locale)
                        ?? $row->translations->firstWhere('locale', $fallbackLocale);
                    $name = $translation?->name ?: $row->code;

                    return ['id' => (int) $row->id, 'label' => '#'.$row->id.' - '.$name];
                });
        }

        if ($itemType === 'category') {
            return Category::query()
                ->with(['translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale])])
                ->orderByDesc('id')
                ->limit(300)
                ->get()
                ->map(function (Category $row) use ($locale, $fallbackLocale): array {
                    $translation = $row->translations->firstWhere('locale', $locale)
                        ?? $row->translations->firstWhere('locale', $fallbackLocale);
                    $name = $translation?->name ?: $row->code;

                    return ['id' => (int) $row->id, 'label' => '#'.$row->id.' - '.$name.' ('.$row->scope.')'];
                });
        }

        if ($itemType === 'manufacturer') {
            return Manufacturer::query()
                ->where('is_active', true)
                ->with(['translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale])])
                ->orderByDesc('id')
                ->limit(300)
                ->get()
                ->map(function (Manufacturer $row) use ($locale, $fallbackLocale): array {
                    $translation = $row->translations->firstWhere('locale', $locale)
                        ?? $row->translations->firstWhere('locale', $fallbackLocale);
                    $name = $translation?->name ?: $row->code;

                    return ['id' => (int) $row->id, 'label' => '#'.$row->id.' - '.$name];
                });
        }

        if ($itemType === 'blog') {
            return BlogPost::query()
                ->where('is_active', true)
                ->with(['translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale])])
                ->orderByDesc('id')
                ->limit(300)
                ->get()
                ->map(function (BlogPost $row) use ($locale, $fallbackLocale): array {
                    $translation = $row->translations->firstWhere('locale', $locale)
                        ?? $row->translations->firstWhere('locale', $fallbackLocale);
                    $title = $translation?->title ?: $row->code;

                    return ['id' => (int) $row->id, 'label' => '#'.$row->id.' - '.$title];
                });
        }

        return collect();
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    private function validIdsForItemType(string $itemType, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        if ($itemType === 'product') {
            return Product::query()->whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        if ($itemType === 'category') {
            return Category::query()->whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        if ($itemType === 'manufacturer') {
            return Manufacturer::query()->whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        if ($itemType === 'blog') {
            return BlogPost::query()->whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return [];
    }

    private function savePrimarySlot(ContentBlock $block, array $validated, ?int $userId): void
    {
        $slotData = [
            'placement' => (string) $validated['form']['slot_placement'],
            'target_type' => $validated['form']['slot_target_type'] ?: null,
            'target_ref' => $validated['form']['slot_target_ref'] ?: null,
            'sort_order' => (int) $validated['form']['slot_sort_order'],
            'is_active' => (bool) $validated['form']['slot_is_active'],
            'starts_at' => $validated['form']['slot_starts_at'] ?: null,
            'ends_at' => $validated['form']['slot_ends_at'] ?: null,
            'updated_by' => $userId,
        ];

        $slot = $block->slots()->orderBy('sort_order')->orderBy('id')->first();
        if ($slot) {
            $slot->update($slotData);

            return;
        }

        $block->slots()->create($slotData + ['created_by' => $userId]);
    }

    /**
     * @param  array<int, int>  $itemIds
     */
    private function syncBlockItems(ContentBlock $block, ?string $itemType, array $itemIds): void
    {
        if ($itemType === null) {
            $block->items()->delete();

            return;
        }

        $block->items()->delete();

        foreach (array_values($itemIds) as $index => $itemId) {
            $block->items()->create([
                'item_type' => $itemType,
                'item_id' => (int) $itemId,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @param  array<string, string>  $types
     * @return array<string, string>
     */
    private function orderedTypes(array $types): array
    {
        $priority = ['banner', 'products', 'categories', 'manufacturers', 'blogs'];
        $ordered = [];

        foreach ($priority as $key) {
            if (array_key_exists($key, $types)) {
                $ordered[$key] = $types[$key];
                unset($types[$key]);
            }
        }

        foreach ($types as $key => $label) {
            $ordered[$key] = $label;
        }

        return $ordered;
    }

    private function defaultTemplateForType(string $type): string
    {
        return match ($type) {
            'banner' => <<<'BLADE'
@php
    $basePayload = is_array($block->payload ?? null) ? $block->payload : [];
    $translationPayload = is_array($translation?->payload ?? null) ? $translation->payload : [];
    $payload = array_merge($basePayload, $translationPayload);

    $bgCss = trim((string) ($payload['bg_css'] ?? ''));
    $customClasses = trim((string) ($payload['custom_classes'] ?? ''));

    $bgUrl = $block->getFirstMediaUrl('block_background', 'hero_1440x480');
    if ($bgUrl === '') {
        $bgUrl = $block->getFirstMediaUrl('block_background');
    }

    $style = $bgCss;
    if ($bgUrl !== '') {
        $style = "background-image:url('{$bgUrl}');background-size:cover;background-position:center; ".$bgCss;
    }
@endphp

<section class="relative overflow-hidden rounded-3xl border border-slate-200/70 p-8 md:p-12 {{ $customClasses }}" @if($style !== '') style="{{ $style }}" @endif>
    <div class="absolute inset-0 bg-gradient-to-br from-white/80 via-white/60 to-white/40"></div>
    <div class="relative z-10 max-w-3xl">
        <h2 class="text-4xl font-extrabold tracking-tight md:text-5xl">{{ $translation?->title ?: $block->name }}</h2>
        @if(!empty($translation?->subtitle))
            <p class="mt-4 text-lg text-slate-700">{{ $translation->subtitle }}</p>
        @endif
        @if(!empty($translation?->cta_label) && !empty($translation?->cta_url))
            <a href="{{ $translation->cta_url }}" class="mt-8 inline-flex rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">{{ $translation->cta_label }}</a>
        @endif
    </div>
</section>
BLADE,
            'products' => <<<'BLADE'
<section class="rounded-3xl border border-slate-200 bg-white p-6">
    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ $translation?->title ?: $block->name }}</h2>
    @if(!empty($translation?->subtitle))
        <p class="mt-2 text-sm text-slate-600">{{ $translation->subtitle }}</p>
    @endif

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @forelse($products as $product)
            @php
                $pt = $product->translations->firstWhere('locale', app()->getLocale())
                    ?? $product->translations->firstWhere('locale', config('app.locale'));
            @endphp
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="h-36 rounded-xl bg-gradient-to-br from-slate-200 to-slate-100"></div>
                <h3 class="mt-3 text-sm font-semibold text-slate-900">{{ $pt?->name ?? $product->code }}</h3>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ number_format((float)$product->base_price, 2) }} €</p>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 sm:col-span-2 xl:col-span-4">No products selected.</div>
        @endforelse
    </div>
</section>
BLADE,
            'categories' => <<<'BLADE'
<section class="rounded-3xl border border-slate-200 bg-white p-6">
    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ $translation?->title ?: $block->name }}</h2>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @forelse($categories as $category)
            @php
                $ct = $category->translations->firstWhere('locale', app()->getLocale())
                    ?? $category->translations->firstWhere('locale', config('app.locale'));
            @endphp
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <h3 class="text-sm font-semibold text-slate-900">{{ $ct?->name ?? $category->code }}</h3>
                <p class="mt-1 text-xs uppercase tracking-[0.12em] text-slate-500">{{ $category->scope }}</p>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 sm:col-span-2 xl:col-span-4">No categories selected.</div>
        @endforelse
    </div>
</section>
BLADE,
            'manufacturers' => <<<'BLADE'
<section class="rounded-3xl border border-slate-200 bg-white p-6">
    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ $translation?->title ?: $block->name }}</h2>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @forelse($manufacturers as $manufacturer)
            @php
                $mt = $manufacturer->translations->firstWhere('locale', app()->getLocale())
                    ?? $manufacturer->translations->firstWhere('locale', config('app.locale'));
            @endphp
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <h3 class="text-sm font-semibold text-slate-900">{{ $mt?->name ?? $manufacturer->code }}</h3>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 sm:col-span-2 xl:col-span-4">No manufacturers selected.</div>
        @endforelse
    </div>
</section>
BLADE,
            'blogs' => <<<'BLADE'
<section class="rounded-3xl border border-slate-200 bg-white p-6">
    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ $translation?->title ?: $block->name }}</h2>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse($blogs as $post)
            @php
                $bt = $post->translations->firstWhere('locale', app()->getLocale())
                    ?? $post->translations->firstWhere('locale', config('app.locale'));
            @endphp
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <h3 class="text-sm font-semibold text-slate-900">{{ $bt?->title ?? $post->code }}</h3>
                @if(!empty($bt?->excerpt))
                    <p class="mt-1 text-xs text-slate-600">{{ \Illuminate\Support\Str::limit((string)$bt->excerpt, 100, '...') }}</p>
                @endif
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 sm:col-span-2 xl:col-span-3">No blog posts selected.</div>
        @endforelse
    </div>
</section>
BLADE,
            'desktop_hero_banner' => <<<'BLADE'
@php
    $title = $translation?->title ?: 'Modern essentials, built for everyday carry.';
    $subtitle = $translation?->subtitle ?: 'AGShop combines durable materials, clean silhouettes and practical storage to keep your daily setup lightweight and ready.';
    $primaryCtaLabel = $translation?->cta_label ?: 'Shop featured';
    $primaryCtaUrl = $translation?->cta_url ?: '#featured';
@endphp

<div class="max-w-3xl text-white">
    <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm">
        <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
        New season collection live now
    </p>

    <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight lg:text-6xl">
        {!! nl2br(e($title)) !!}
    </h1>

    @if ($subtitle !== '')
        <p class="mt-6 max-w-xl text-lg text-white/90">{{ $subtitle }}</p>
    @endif

    <div class="mt-10 flex flex-wrap items-center gap-4">
        <a href="{{ $primaryCtaUrl }}" class="rounded-xl bg-white px-6 py-3 font-semibold text-blue-700 hover:bg-slate-100">
            {{ $primaryCtaLabel }}
        </a>
        <a href="#categories" class="rounded-xl border border-white/30 px-6 py-3 text-white hover:bg-white/10">
            Browse categories
        </a>
    </div>
</div>
BLADE,
            'hero_highlights_strip' => <<<'BLADE'
<div class="mx-auto grid max-w-7xl gap-8 text-white md:grid-cols-3">
    <div class="flex items-center gap-4">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15">
            <span class="text-xl font-bold">+</span>
        </div>
        <div>
            <div class="text-2xl font-bold leading-none">Fast Dispatch</div>
            <div class="mt-1 text-sm leading-tight text-white/80">Orders before 14:00 ship same day.</div>
        </div>
    </div>

    <div class="flex items-center gap-4">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15">
            <span class="text-xl font-bold">+</span>
        </div>
        <div>
            <div class="text-2xl font-bold leading-none">Easy Returns</div>
            <div class="mt-1 text-sm leading-tight text-white/80">30-day return flow with no paperwork.</div>
        </div>
    </div>

    <div class="flex items-center gap-4">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15">
            <span class="text-xl font-bold">+</span>
        </div>
        <div>
            <div class="text-2xl font-bold leading-none">Secure Checkout</div>
            <div class="mt-1 text-sm leading-tight text-white/80">Card, Apple Pay, and Google Pay support.</div>
        </div>
    </div>
</div>
BLADE,
            default => <<<'BLADE'
<section class="rounded-2xl border border-slate-200 bg-white p-6">
    @if(!empty($translation?->title))
        <h2 class="text-xl font-semibold text-slate-900">{{ $translation->title }}</h2>
    @endif
    @if(!empty($translation?->subtitle))
        <p class="mt-2 text-sm text-slate-600">{{ $translation->subtitle }}</p>
    @endif
</section>
BLADE,
        };
    }

    private function templateViewName(string $code): string
    {
        return 'front.content-blocks.instances.'.$code;
    }

    private function templateFilePath(string $code): string
    {
        return resource_path('views/front/content-blocks/instances/'.$code.'.blade.php');
    }

    private function readTemplateFile(string $code): string
    {
        $path = $this->templateFilePath($code);
        if (! File::exists($path)) {
            return '';
        }

        return (string) File::get($path);
    }

    private function writeTemplateFile(string $code, string $contents): void
    {
        $dir = resource_path('views/front/content-blocks/instances');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($this->templateFilePath($code), rtrim($contents)."\n");
    }

    private function deleteTemplateFile(string $code): void
    {
        $path = $this->templateFilePath($code);
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    private function normalizedTemplate(string $template): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", $template));
    }
}

<?php

namespace App\Livewire\Admin\Content\Block;

use App\Models\Content\ContentBlock;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?int $blockId = null;

    public array $form = [];

    /**
     * @var array<string, string>
     */
    public array $types = [];

    public function mount(?int $blockId = null): void
    {
        /** @var array<string, string> $types */
        $types = config('content_blocks.types', []);
        $this->types = $types;

        $this->resetForm();

        if ($blockId) {
            $this->blockId = $blockId;
            $this->loadBlock();
        }
    }

    public function getIsEditProperty(): bool
    {
        return (bool) $this->blockId;
    }

    public function updatedFormLocale(): void
    {
        $this->loadTranslationForLocale();
    }

    public function save()
    {
        $validated = $this->validate($this->rules());
        $userId = auth()->id();

        $blockPayload = $this->decodeJson('form.payload_text');
        if ($blockPayload === false) {
            return null;
        }
        $blockPayload = $this->applyQuickConfigToPayload(
            $blockPayload,
            (string) $validated['form']['type']
        );

        $translationPayload = $this->decodeJson('form.translation_payload_text');
        if ($translationPayload === false) {
            return null;
        }

        $blockData = [
            'code' => $validated['form']['code'],
            'name' => $validated['form']['name'],
            'type' => $validated['form']['type'],
            'is_active' => (bool) $validated['form']['is_active'],
            'payload' => $blockPayload,
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
            ['locale' => $validated['form']['locale']],
            [
                'title' => $validated['form']['title'] ?: null,
                'subtitle' => $validated['form']['subtitle'] ?: null,
                'body_html' => $validated['form']['body_html'] ?: null,
                'cta_label' => $validated['form']['cta_label'] ?: null,
                'cta_url' => $validated['form']['cta_url'] ?: null,
                'payload' => $translationPayload,
            ]
        );

        activity('content_blocks')
            ->performedOn($block)
            ->causedBy(auth()->user())
            ->event($this->isEdit ? 'updated' : 'created')
            ->withProperties([
                'code' => $block->code,
                'locale' => $validated['form']['locale'],
            ])
            ->log('Content block saved');

        return redirect()->route('admin.content.blocks')->with('notify', [
            'type' => 'success',
            'message' => $this->isEdit ? 'Content block updated.' : 'Content block created.',
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
            'form.body_html' => ['nullable', 'string'],
            'form.cta_label' => ['nullable', 'string', 'max:120'],
            'form.cta_url' => ['nullable', 'string', 'max:2048'],
            'form.payload_text' => ['nullable', 'string'],
            'form.translation_payload_text' => ['nullable', 'string'],
            'form.quick_source' => ['nullable', Rule::in(['manual', 'query'])],
            'form.quick_manual_ids' => ['nullable', 'string', 'max:2000'],
            'form.quick_category_ids' => ['nullable', 'string', 'max:2000'],
            'form.quick_manufacturer_ids' => ['nullable', 'string', 'max:2000'],
            'form.quick_limit' => ['nullable', 'integer', 'min:1', 'max:30'],
            'form.quick_sort' => ['nullable', Rule::in(['newest', 'price_asc', 'price_desc', 'name'])],
            'form.quick_blog_source' => ['nullable', Rule::in(['manual', 'query'])],
            'form.quick_blog_manual_ids' => ['nullable', 'string', 'max:2000'],
            'form.quick_blog_category_ids' => ['nullable', 'string', 'max:2000'],
            'form.quick_blog_limit' => ['nullable', 'integer', 'min:1', 'max:12'],
            'form.quick_blog_sort' => ['nullable', Rule::in(['newest', 'featured', 'title'])],
        ];
    }

    private function resetForm(): void
    {
        $firstType = array_key_first($this->types) ?: 'custom';

        $this->form = [
            'code' => '',
            'name' => '',
            'type' => $firstType,
            'is_active' => true,
            'locale' => config('app.locale'),
            'title' => '',
            'subtitle' => '',
            'body_html' => '',
            'cta_label' => '',
            'cta_url' => '',
            'payload_text' => '',
            'translation_payload_text' => '',
            'quick_source' => 'manual',
            'quick_manual_ids' => '',
            'quick_category_ids' => '',
            'quick_manufacturer_ids' => '',
            'quick_limit' => 10,
            'quick_sort' => 'newest',
            'quick_blog_source' => 'manual',
            'quick_blog_manual_ids' => '',
            'quick_blog_category_ids' => '',
            'quick_blog_limit' => 3,
            'quick_blog_sort' => 'newest',
        ];
    }

    private function loadBlock(): void
    {
        if (!$this->blockId) {
            return;
        }

        $block = ContentBlock::query()
            ->with('translations')
            ->findOrFail($this->blockId);

        $locale = $this->form['locale'] ?? config('app.locale');
        $translation = $block->translations->firstWhere('locale', $locale)
            ?? $block->translations->firstWhere('locale', config('app.locale'));

        $this->form['code'] = $block->code;
        $this->form['name'] = $block->name;
        $this->form['type'] = $block->type;
        $this->form['is_active'] = (bool) $block->is_active;
        $this->form['payload_text'] = $block->payload
            ? json_encode($block->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            : '';
        $this->fillQuickConfigFromPayload($block->payload);

        if ($translation) {
            $this->form['locale'] = $translation->locale;
            $this->form['title'] = $translation->title ?? '';
            $this->form['subtitle'] = $translation->subtitle ?? '';
            $this->form['body_html'] = $translation->body_html ?? '';
            $this->form['cta_label'] = $translation->cta_label ?? '';
            $this->form['cta_url'] = $translation->cta_url ?? '';
            $this->form['translation_payload_text'] = $translation->payload
                ? json_encode($translation->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                : '';
        } else {
            $this->clearTranslationFields();
        }
    }

    private function loadTranslationForLocale(): void
    {
        if (!$this->blockId) {
            $this->clearTranslationFields();
            return;
        }

        $block = ContentBlock::query()->with('translations')->find($this->blockId);
        if (!$block) {
            return;
        }

        $translation = $block->translations->firstWhere('locale', $this->form['locale']);

        if (!$translation) {
            $this->clearTranslationFields();
            return;
        }

        $this->form['title'] = $translation->title ?? '';
        $this->form['subtitle'] = $translation->subtitle ?? '';
        $this->form['body_html'] = $translation->body_html ?? '';
        $this->form['cta_label'] = $translation->cta_label ?? '';
        $this->form['cta_url'] = $translation->cta_url ?? '';
        $this->form['translation_payload_text'] = $translation->payload
            ? json_encode($translation->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            : '';
    }

    private function clearTranslationFields(): void
    {
        $this->form['title'] = '';
        $this->form['subtitle'] = '';
        $this->form['body_html'] = '';
        $this->form['cta_label'] = '';
        $this->form['cta_url'] = '';
        $this->form['translation_payload_text'] = '';
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>|null
     */
    private function applyQuickConfigToPayload(?array $payload, string $type): ?array
    {
        $data = $payload ?? [];

        if ($type === 'products_carousel') {
            $source = ($this->form['quick_source'] ?? 'manual') === 'query' ? 'query' : 'manual';
            $manualIds = $this->parseIdList((string) ($this->form['quick_manual_ids'] ?? ''));
            $categoryIds = $this->parseIdList((string) ($this->form['quick_category_ids'] ?? ''));
            $manufacturerIds = $this->parseIdList((string) ($this->form['quick_manufacturer_ids'] ?? ''));
            $limit = max(1, min(30, (int) ($this->form['quick_limit'] ?? 10)));
            $sort = (string) ($this->form['quick_sort'] ?? 'newest');

            if (!in_array($sort, ['newest', 'price_asc', 'price_desc', 'name'], true)) {
                $sort = 'newest';
            }

            $data['source'] = $source;
            $data['limit'] = $limit;
            $data['sort'] = $sort;
            $data['manual_product_ids'] = $source === 'manual' ? $manualIds : [];
            $data['category_ids'] = $source === 'query' ? $categoryIds : [];
            $data['manufacturer_ids'] = $source === 'query' ? $manufacturerIds : [];
        }

        if ($type === 'blog_grid_3') {
            $source = ($this->form['quick_blog_source'] ?? 'manual') === 'query' ? 'query' : 'manual';
            $manualIds = $this->parseIdList((string) ($this->form['quick_blog_manual_ids'] ?? ''));
            $categoryIds = $this->parseIdList((string) ($this->form['quick_blog_category_ids'] ?? ''));
            $limit = max(1, min(12, (int) ($this->form['quick_blog_limit'] ?? 3)));
            $sort = (string) ($this->form['quick_blog_sort'] ?? 'newest');

            if (!in_array($sort, ['newest', 'featured', 'title'], true)) {
                $sort = 'newest';
            }

            $data['source'] = $source;
            $data['limit'] = $limit;
            $data['sort'] = $sort;
            $data['manual_blog_ids'] = $source === 'manual' ? $manualIds : [];
            $data['category_ids'] = $source === 'query' ? $categoryIds : [];
        }

        return $data === [] ? null : $data;
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function fillQuickConfigFromPayload(?array $payload): void
    {
        $data = $payload ?? [];

        $quickSource = (string) ($data['source'] ?? 'manual');
        if (!in_array($quickSource, ['manual', 'query'], true)) {
            $quickSource = 'manual';
        }

        $productSort = (string) ($data['sort'] ?? 'newest');
        if (!in_array($productSort, ['newest', 'price_asc', 'price_desc', 'name'], true)) {
            $productSort = 'newest';
        }

        $blogSort = (string) ($data['sort'] ?? 'newest');
        if (!in_array($blogSort, ['newest', 'featured', 'title'], true)) {
            $blogSort = 'newest';
        }

        $this->form['quick_source'] = $quickSource;
        $this->form['quick_manual_ids'] = implode(', ', $this->normalizeIdArray($data['manual_product_ids'] ?? []));
        $this->form['quick_category_ids'] = implode(', ', $this->normalizeIdArray($data['category_ids'] ?? []));
        $this->form['quick_manufacturer_ids'] = implode(', ', $this->normalizeIdArray($data['manufacturer_ids'] ?? []));
        $this->form['quick_limit'] = max(1, min(30, (int) ($data['limit'] ?? 10)));
        $this->form['quick_sort'] = $productSort;

        $this->form['quick_blog_source'] = $quickSource;
        $this->form['quick_blog_manual_ids'] = implode(', ', $this->normalizeIdArray($data['manual_blog_ids'] ?? []));
        $this->form['quick_blog_category_ids'] = implode(', ', $this->normalizeIdArray($data['category_ids'] ?? []));
        $this->form['quick_blog_limit'] = max(1, min(12, (int) ($data['limit'] ?? 3)));
        $this->form['quick_blog_sort'] = $blogSort;
    }

    /**
     * @param array<int, mixed>|mixed $value
     * @return array<int, int>
     */
    private function normalizeIdArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($item): int => (int) $item,
            $value
        ))));
    }

    /**
     * @return array<int, int>
     */
    private function parseIdList(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $parts = preg_split('/[\s,;]+/', $raw) ?: [];
        $ids = [];

        foreach ($parts as $part) {
            $value = (int) trim((string) $part);
            if ($value > 0) {
                $ids[] = $value;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<mixed>|null|false
     */
    private function decodeJson(string $field): array|null|false
    {
        $value = trim((string) data_get($this, $field));
        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->addError($field, 'Invalid JSON payload.');
            $this->dispatch('notify', type: 'danger', message: 'Invalid JSON payload.');
            return false;
        }

        return $decoded;
    }
}

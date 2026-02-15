<?php

namespace App\Livewire\Admin\Content\Blog;

use App\Models\Catalog\Category\Category;
use App\Models\Content\Blog\BlogPost;
use App\Models\Content\Blog\BlogPostTranslation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?int $postId = null;

    public array $form = [
        'code' => '',
        'is_active' => true,
        'is_featured' => false,
        'published_at' => '',
        'sort_order' => 0,
        'payload_text' => '',
        'locale' => 'en',
        'title' => '',
        'slug' => '',
        'excerpt' => '',
        'body_html' => '',
        'meta_title' => '',
        'meta_description' => '',
        'translation_payload_text' => '',
        'category_ids' => [],
    ];

    public function mount(?int $postId = null): void
    {
        $this->form['locale'] = (string) (request()->query('locale') ?: config('app.locale', 'en'));

        if ($postId) {
            $this->postId = $postId;
            $this->loadPost();
        }
    }

    public function updatedFormLocale(): void
    {
        $this->loadTranslationForLocale();
    }

    public function generateSlug(): void
    {
        $title = trim((string) $this->form['title']);
        if ($title !== '') {
            $this->form['slug'] = Str::slug($title);
        }
    }

    public function save()
    {
        $validated = $this->validate($this->rules());
        $wasEditing = (bool) $this->postId;

        $payload = $this->decodeJsonField('form.payload_text');
        if ($payload === false) {
            return null;
        }

        $translationPayload = $this->decodeJsonField('form.translation_payload_text');
        if ($translationPayload === false) {
            return null;
        }

        $userId = auth()->id();

        DB::transaction(function () use ($validated, $payload, $translationPayload, $userId, $wasEditing): void {
            $postData = [
                'code' => trim((string) $validated['form']['code']),
                'is_active' => (bool) $validated['form']['is_active'],
                'is_featured' => (bool) $validated['form']['is_featured'],
                'published_at' => $validated['form']['published_at'] ?: null,
                'sort_order' => (int) $validated['form']['sort_order'],
                'payload' => $payload,
                'updated_by' => $userId,
            ];

            if ($this->postId) {
                $post = BlogPost::query()->findOrFail($this->postId);
                $post->fill($postData)->save();
            } else {
                $post = BlogPost::query()->create($postData + ['created_by' => $userId]);
                $this->postId = $post->id;
            }

            $post->translations()->updateOrCreate(
                ['locale' => $validated['form']['locale']],
                [
                    'title' => $validated['form']['title'],
                    'slug' => $validated['form']['slug'],
                    'excerpt' => $validated['form']['excerpt'] ?: null,
                    'body_html' => $validated['form']['body_html'] ?: null,
                    'meta_title' => $validated['form']['meta_title'] ?: null,
                    'meta_description' => $validated['form']['meta_description'] ?: null,
                    'payload' => $translationPayload,
                ]
            );

            $syncPayload = [];
            foreach (array_values($validated['form']['category_ids'] ?? []) as $index => $categoryId) {
                $syncPayload[(int) $categoryId] = [
                    'sort_order' => $index,
                    'is_primary' => $index === 0,
                ];
            }
            $post->categories()->sync($syncPayload);

            activity('content_blog')
                ->performedOn($post)
                ->causedBy(auth()->user())
                ->event($wasEditing ? 'updated' : 'created')
                ->withProperties([
                    'locale' => $validated['form']['locale'],
                    'slug' => $validated['form']['slug'],
                    'category_count' => count($syncPayload),
                ])
                ->log('Blog post saved');
        });

        $message = $wasEditing ? 'Blog post updated.' : 'Blog post created.';

        return redirect()
            ->route('admin.content.blog.index', ['locale' => $this->form['locale']])
            ->with('notify', [
                'type' => 'success',
                'message' => $message,
            ]);
    }

    public function backToList()
    {
        return redirect()->route('admin.content.blog.index', ['locale' => $this->form['locale']]);
    }

    public function render()
    {
        return view('livewire.admin.content.blog.form', [
            'isEdit' => (bool) $this->postId,
        ]);
    }

    public function getCategoryOptionsProperty(): Collection
    {
        return Category::query()
            ->where('scope', Category::SCOPE_BLOG)
            ->withDepth()
            ->defaultOrder()
            ->with([
                'translations' => fn ($q) => $q
                    ->where('scope', Category::SCOPE_BLOG)
                    ->where('locale', $this->form['locale']),
            ])
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'form.code' => ['required', 'string', 'max:120', Rule::unique('content_blog_posts', 'code')->ignore($this->postId)],
            'form.is_active' => ['boolean'],
            'form.is_featured' => ['boolean'],
            'form.published_at' => ['nullable', 'date'],
            'form.sort_order' => ['nullable', 'integer', 'min:0'],
            'form.payload_text' => ['nullable', 'string'],

            'form.locale' => ['required', 'string', 'max:12'],
            'form.title' => ['required', 'string', 'max:255'],
            'form.slug' => [
                'required',
                'string',
                'max:191',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('content_blog_post_translations', 'slug')
                    ->where(fn ($q) => $q->where('locale', $this->form['locale']))
                    ->ignore($this->postId, 'post_id'),
            ],
            'form.excerpt' => ['nullable', 'string'],
            'form.body_html' => ['nullable', 'string'],
            'form.meta_title' => ['nullable', 'string', 'max:255'],
            'form.meta_description' => ['nullable', 'string'],
            'form.translation_payload_text' => ['nullable', 'string'],
            'form.category_ids' => ['nullable', 'array'],
            'form.category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('scope', Category::SCOPE_BLOG)),
            ],
        ];
    }

    private function loadPost(): void
    {
        if (!$this->postId) {
            return;
        }

        $post = BlogPost::query()
            ->with('translations')
            ->with(['categories' => fn ($q) => $q->orderBy('content_blog_post_category.sort_order')])
            ->findOrFail($this->postId);

        $preferredLocale = $this->form['locale'] ?: config('app.locale', 'en');
        $translation = $post->translations->firstWhere('locale', $preferredLocale)
            ?? $post->translations->firstWhere('locale', config('app.locale', 'en'))
            ?? $post->translations->first();

        $this->form['code'] = $post->code;
        $this->form['is_active'] = (bool) $post->is_active;
        $this->form['is_featured'] = (bool) $post->is_featured;
        $this->form['published_at'] = $post->published_at?->format('Y-m-d\TH:i') ?? '';
        $this->form['sort_order'] = (int) $post->sort_order;
        $this->form['payload_text'] = $post->payload
            ? json_encode($post->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            : '';
        $this->form['category_ids'] = $post->categories->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($translation) {
            $this->form['locale'] = $translation->locale;
            $this->form['title'] = $translation->title;
            $this->form['slug'] = $translation->slug;
            $this->form['excerpt'] = $translation->excerpt ?? '';
            $this->form['body_html'] = $translation->body_html ?? '';
            $this->form['meta_title'] = $translation->meta_title ?? '';
            $this->form['meta_description'] = $translation->meta_description ?? '';
            $this->form['translation_payload_text'] = $translation->payload
                ? json_encode($translation->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                : '';
        }
    }

    private function loadTranslationForLocale(): void
    {
        if (!$this->postId) {
            $this->clearTranslationFields();
            return;
        }

        $translation = BlogPostTranslation::query()
            ->where('post_id', $this->postId)
            ->where('locale', $this->form['locale'])
            ->first();

        if (!$translation) {
            $this->clearTranslationFields();
            return;
        }

        $this->form['title'] = $translation->title;
        $this->form['slug'] = $translation->slug;
        $this->form['excerpt'] = $translation->excerpt ?? '';
        $this->form['body_html'] = $translation->body_html ?? '';
        $this->form['meta_title'] = $translation->meta_title ?? '';
        $this->form['meta_description'] = $translation->meta_description ?? '';
        $this->form['translation_payload_text'] = $translation->payload
            ? json_encode($translation->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            : '';
    }

    private function clearTranslationFields(): void
    {
        $this->form['title'] = '';
        $this->form['slug'] = '';
        $this->form['excerpt'] = '';
        $this->form['body_html'] = '';
        $this->form['meta_title'] = '';
        $this->form['meta_description'] = '';
        $this->form['translation_payload_text'] = '';
    }

    /**
     * @return array<mixed>|null|false
     */
    private function decodeJsonField(string $field): array|null|false
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

        if (!is_array($decoded)) {
            $this->addError($field, 'JSON payload must decode to object/array.');
            $this->dispatch('notify', type: 'danger', message: 'JSON payload must decode to object/array.');
            return false;
        }

        return $decoded;
    }
}

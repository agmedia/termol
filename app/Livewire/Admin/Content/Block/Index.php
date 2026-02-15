<?php

namespace App\Livewire\Admin\Content\Block;

use App\Models\Content\ContentBlock;
use App\Services\Settings\SystemSettingsService;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    private const PAGE_NAME = 'contentBlocksPage';

    public string $search = '';
    public ?int $previewBlockId = null;
    public string $locale;

    public function mount(): void
    {
        $this->locale = (string) (request()->query('locale') ?: config('app.locale'));
    }

    public function updatedSearch(): void
    {
        $this->resetPage(pageName: self::PAGE_NAME);
    }

    public function openPreview(int $id): void
    {
        $exists = ContentBlock::query()->whereKey($id)->exists();
        $this->previewBlockId = $exists ? $id : null;
    }

    public function closePreview(): void
    {
        $this->previewBlockId = null;
    }

    public function delete(int $id): void
    {
        $block = ContentBlock::query()->findOrFail($id);

        activity('content_blocks')
            ->performedOn($block)
            ->causedBy(auth()->user())
            ->event('deleted')
            ->withProperties(['code' => $block->code])
            ->log('Content block deleted');

        $block->delete();

        $this->dispatch('notify', type: 'success', message: 'Content block deleted.');
    }

    public function render()
    {
        $perPage = app(SystemSettingsService::class)->getInt(
            'admin_items_per_page',
            (int) config('admin_ui.pagination.admin_items_per_page', 20),
            5,
            200
        );

        $rows = ContentBlock::query()
            ->withCount('slots')
            ->with(['translations' => fn ($q) => $q->where('locale', $this->locale)])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($q): void {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%')
                        ->orWhere('type', 'like', '%'.$this->search.'%')
                        ->orWhereHas('translations', function ($tq): void {
                            $tq->where('title', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], self::PAGE_NAME);

        $previewBlock = null;
        if ($this->previewBlockId) {
            $previewBlock = ContentBlock::query()
                ->withCount('slots')
                ->with(['translations' => fn ($q) => $q->whereIn('locale', [$this->locale, config('app.locale')])])
                ->find($this->previewBlockId);
        }

        return view('livewire.admin.content.block.index', [
            'rows' => $rows,
            'perPage' => $perPage,
            'previewBlock' => $previewBlock,
        ]);
    }
}

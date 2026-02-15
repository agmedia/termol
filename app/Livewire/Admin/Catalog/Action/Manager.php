<?php

namespace App\Livewire\Admin\Catalog\Action;

use App\Models\Catalog\Action\CatalogAction;
use App\Services\Settings\SystemSettingsService;
use Livewire\Component;
use Livewire\WithPagination;

class Manager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $locale = 'en';
    public string $scopeFilter = 'all';
    public string $typeFilter = 'all';
    public string $stateFilter = 'active';

    public function mount(): void
    {
        $this->locale = (string) (request()->query('locale') ?: config('app.locale', 'en'));
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLocale(): void
    {
        $this->resetPage();
    }

    public function updatedScopeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStateFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $perPage = app(SystemSettingsService::class)->getInt(
            'admin_items_per_page',
            (int) config('admin_ui.pagination.admin_items_per_page', 20),
            5,
            200
        );

        $rows = CatalogAction::query()
            ->withCount('targets')
            ->with([
                'translations' => fn ($q) => $q->where('locale', $this->locale),
                'audienceCustomerGroup:id,name',
                'audienceUser:id,name,email',
            ])
            ->when($this->scopeFilter !== 'all', function ($query): void {
                $query->where('scope', $this->scopeFilter);
            })
            ->when($this->typeFilter !== 'all', function ($query): void {
                $query->where('type', $this->typeFilter);
            })
            ->when($this->stateFilter === 'active', function ($query): void {
                $query->where('is_active', true);
            })
            ->when($this->stateFilter === 'inactive', function ($query): void {
                $query->where('is_active', false);
            })
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($q): void {
                    $q->where('code', 'like', '%'.$this->search.'%')
                        ->orWhere('coupon_code', 'like', '%'.$this->search.'%')
                        ->orWhereHas('translations', function ($tq): void {
                            $tq->where('title', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->paginate($perPage);

        return view('livewire.admin.catalog.action.manager', [
            'rows' => $rows,
            'perPage' => $perPage,
            'scopeLabels' => $this->scopeLabels(),
            'typeLabels' => $this->typeLabels(),
            'stateLabels' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
                'all' => 'All',
            ],
            'targetLabels' => $this->targetLabels(),
            'audienceLabels' => $this->audienceLabels(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function scopeLabels(): array
    {
        return [
            CatalogAction::SCOPE_PRODUCT => 'Product',
            CatalogAction::SCOPE_CART => 'Cart',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function typeLabels(): array
    {
        return [
            CatalogAction::TYPE_PERCENTAGE => 'Percentage',
            CatalogAction::TYPE_FIXED => 'Fixed Amount',
            CatalogAction::TYPE_BUY_X_GET_Y => 'Buy X Get Y',
            CatalogAction::TYPE_GIFT_ON_AMOUNT => 'Gift On Amount',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function targetLabels(): array
    {
        return [
            CatalogAction::TARGET_ALL => 'All',
            CatalogAction::TARGET_PRODUCT => 'Products',
            CatalogAction::TARGET_CATEGORY => 'Categories',
            CatalogAction::TARGET_MANUFACTURER => 'Manufacturers',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function audienceLabels(): array
    {
        return [
            CatalogAction::AUDIENCE_ALL => 'All users',
            CatalogAction::AUDIENCE_USER_GROUP => 'User group',
            CatalogAction::AUDIENCE_USER => 'Single user',
            CatalogAction::AUDIENCE_ROLE => 'Legacy role',
        ];
    }
}

<?php

namespace App\Livewire\Admin\Catalog\Pricing;

use App\Models\Catalog\Pricing\B2BPriceRule;
use App\Services\Settings\SystemSettingsService;
use Livewire\Component;
use Livewire\WithPagination;

class B2BPriceRuleManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $groupFilter = 'all';

    public string $targetFilter = 'all';

    public string $stateFilter = 'active';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedGroupFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTargetFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStateFilter(): void
    {
        $this->resetPage();
    }

    public function delete(int $ruleId): void
    {
        $rule = B2BPriceRule::query()->find($ruleId);
        if (! $rule) {
            $this->dispatch('notify', type: 'warning', message: __('B2B pravilo nije pronađeno.'));

            return;
        }

        $properties = [
            'rule_id' => $rule->id,
            'code' => $rule->code,
            'customer_group_id' => $rule->customer_group_id,
        ];
        $rule->delete();

        activity('catalog_b2b_prices')
            ->causedBy(auth()->user())
            ->event('deleted')
            ->withProperties($properties)
            ->log('B2B price rule deleted');

        $this->dispatch('notify', type: 'success', message: __('B2B pravilo je obrisano.'));
        $this->resetPage();
    }

    public function render()
    {
        $perPage = app(SystemSettingsService::class)->getInt(
            'admin_items_per_page',
            (int) config('admin_ui.pagination.admin_items_per_page', 20),
            5,
            200,
        );

        $rows = B2BPriceRule::query()
            ->with('customerGroup:id,name,code')
            ->withCount('targets')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('code', 'like', '%'.$this->search.'%')
                        ->orWhere('name', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->groupFilter !== 'all', fn ($query) => $query
                ->where('customer_group_id', (int) $this->groupFilter))
            ->when($this->targetFilter !== 'all', fn ($query) => $query
                ->where('target_type', $this->targetFilter))
            ->when($this->stateFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->stateFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->paginate($perPage);

        return view('livewire.admin.catalog.pricing.b2b-price-rule-manager', [
            'rows' => $rows,
            'perPage' => $perPage,
            'calculationTypeOptions' => B2BPriceRule::calculationTypeOptions(),
            'targetTypeOptions' => B2BPriceRule::targetTypeOptions(),
        ]);
    }
}

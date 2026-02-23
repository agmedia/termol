<?php

namespace App\Livewire\Admin\Settings\Local;

use App\Services\Front\AddressDirectoryService;
use App\Models\Settings\Local\Currency;
use App\Models\Settings\Local\GeoZone;
use App\Models\Settings\Local\GeoZoneCountry;
use App\Models\Settings\Local\Language;
use App\Models\Settings\Local\OrderStatus;
use App\Models\Settings\Local\PaymentMethod;
use App\Models\Settings\Local\Region;
use App\Models\Settings\Local\ShippingMethod;
use App\Models\Settings\Local\TaxRate;
use App\Services\Settings\LocalSettingsService;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ResourceManager extends Component
{
    use WithPagination;

    public string $resource = 'payment-methods';
    public array $form = [];
    public ?int $editingId = null;
    public string $search = '';

    /**
     * @var array<string, array<string, mixed>>
     */
    public array $resources = [
        'payment-methods' => [
            'title' => 'Payment Methods',
            'model' => PaymentMethod::class,
            'fields' => ['code', 'name', 'provider', 'geo_zone_id', 'fee_type', 'fee_value', 'min_subtotal', 'max_subtotal', 'description', 'settings_text', 'is_active', 'sort_order'],
        ],
        'shipping-methods' => [
            'title' => 'Shipping Methods',
            'model' => ShippingMethod::class,
            'fields' => ['code', 'name', 'geo_zone_id', 'price', 'free_over', 'min_subtotal', 'max_subtotal', 'description', 'settings_text', 'is_active', 'sort_order'],
        ],
        'geo-zones' => [
            'title' => 'Geo Zones',
            'model' => GeoZone::class,
            'fields' => ['code', 'name', 'description', 'is_active', 'sort_order'],
        ],
        'geo-zone-countries' => [
            'title' => 'Geo Zone Countries',
            'model' => GeoZoneCountry::class,
            'fields' => ['geo_zone_id', 'country_code', 'region_code', 'postal_code_from', 'postal_code_to'],
        ],
        'regions' => [
            'title' => 'Regions',
            'model' => Region::class,
            'fields' => ['country_code', 'code', 'name', 'is_active', 'sort_order'],
        ],
        'currencies' => [
            'title' => 'Currencies',
            'model' => Currency::class,
            'fields' => ['code', 'name', 'symbol', 'symbol_position', 'decimal_places', 'exchange_rate', 'is_default', 'is_active', 'sort_order', 'settings_text'],
        ],
        'tax-rates' => [
            'title' => 'Tax Rates',
            'model' => TaxRate::class,
            'fields' => ['code', 'name', 'geo_zone_id', 'rate_type', 'rate', 'priority', 'is_default', 'is_active', 'sort_order', 'settings_text'],
        ],
        'order-statuses' => [
            'title' => 'Order Statuses',
            'model' => OrderStatus::class,
            'fields' => ['code', 'name', 'description', 'color', 'is_default', 'is_paid', 'is_cancelled', 'is_active', 'sort_order', 'settings_text'],
        ],
        'languages' => [
            'title' => 'Languages',
            'model' => Language::class,
            'fields' => ['code', 'locale', 'name', 'native_name', 'direction', 'is_default', 'is_active', 'sort_order', 'settings_text'],
        ],
    ];

    public function mount(string $resource): void
    {
        abort_unless(array_key_exists($resource, $this->resources), 404);

        $this->resource = $resource;
        $this->resetForm();
    }

    public function getTitleProperty(): string
    {
        return __($this->resources[$this->resource]['title']);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules());

        $data = $validated['form'];
        foreach ($this->booleanFields() as $field) {
            if ($this->hasColumn($field)) {
                $data[$field] = (bool) ($data[$field] ?? false);
            }
        }

        if (array_key_exists('country_code', $data)) {
            $data['country_code'] = strtoupper(trim((string) ($data['country_code'] ?? '')));
        }

        if ($this->hasColumn('is_default') && $this->hasColumn('is_active') && !empty($data['is_default'])) {
            // Default value must remain active.
            $data['is_active'] = true;
        }

        if ($this->hasColumn('settings') && !empty($data['settings_text'])) {
            $decoded = json_decode((string) $data['settings_text'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addError('form.settings_text', __('Settings JSON is invalid.'));
                $this->dispatch('notify', type: 'error', message: __('Settings JSON is invalid.'));
                return;
            }
            $data['settings'] = $decoded;
        }

        unset($data['settings_text']);

        $modelClass = $this->modelClass();

        if ($this->editingId) {
            $record = $modelClass::query()->findOrFail($this->editingId);
            $record->update($data);
        } else {
            $record = $modelClass::query()->create($data);
        }

        if ($this->hasColumn('is_default') && !empty($data['is_default'])) {
            $modelClass::query()
                ->where('id', '!=', $record->id)
                ->update(['is_default' => false]);
        }

        $this->dispatch('profile-updated', name: auth()->user()->name);
        $this->dispatch('notify', type: 'success', message: $this->editingId ? __('Record updated.') : __('Record created.'));
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $modelClass = $this->modelClass();
        $record = $modelClass::query()->findOrFail($id);
        $this->editingId = $record->id;

        $defaults = $this->defaultForm();
        foreach ($defaults as $key => $default) {
            if ($key === 'settings_text') {
                $this->form[$key] = $this->hasColumn('settings')
                    ? json_encode($record->settings ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : '';
                continue;
            }

            if (in_array($key, $this->booleanFields(), true)) {
                $this->form[$key] = (bool) ($record->{$key} ?? false);
                continue;
            }

            $this->form[$key] = $record->{$key} ?? $default;
        }
    }

    public function delete(int $id): void
    {
        $modelClass = $this->modelClass();
        $modelClass::query()->findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        $this->dispatch('notify', type: 'success', message: __('Record deleted.'));
    }

    public function toggleActive(int $id): void
    {
        if (!$this->hasColumn('is_active')) {
            return;
        }

        $modelClass = $this->modelClass();
        $record = $modelClass::query()->findOrFail($id);
        $record->update(['is_active' => !$record->is_active]);
        $this->dispatch('notify', type: 'info', message: $record->is_active ? __('Item switched to active.') : __('Item switched to inactive.'));
    }

    public function makeDefault(int $id): void
    {
        if (!$this->hasColumn('is_default')) {
            return;
        }

        $modelClass = $this->modelClass();
        $modelClass::query()->update(['is_default' => false]);
        $modelClass::query()->where('id', $id)->update(['is_default' => true]);
        $this->dispatch('notify', type: 'success', message: __('Default item updated.'));
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function geoZoneOptions(): array
    {
        $zones = app(LocalSettingsService::class)->active(GeoZone::class);
        return $zones->pluck('name', 'id')->all();
    }

    public function countryOptions(): array
    {
        $locale = (string) app()->getLocale();
        $countries = app(AddressDirectoryService::class)->countries($locale);

        $options = [];
        foreach ($countries as $country) {
            $code = strtoupper((string) ($country['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $options[$code] = (string) ($country['label'] ?? $code);
        }

        return $options;
    }

    private function modelClass(): string
    {
        return $this->resources[$this->resource]['model'];
    }

    private function hasColumn(string $column): bool
    {
        return in_array($column, $this->resources[$this->resource]['fields'], true)
            || ($column === 'settings' && in_array('settings_text', $this->resources[$this->resource]['fields'], true));
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->form = $this->defaultForm();
    }

    private function defaultForm(): array
    {
        return [
            'code' => '',
            'name' => '',
            'provider' => '',
            'geo_zone_id' => null,
            'description' => '',
            'fee_type' => 'fixed',
            'fee_value' => 0,
            'min_subtotal' => null,
            'max_subtotal' => null,
            'price' => 0,
            'free_over' => null,
            'country_code' => '',
            'region_code' => '',
            'postal_code_from' => '',
            'postal_code_to' => '',
            'symbol' => 'EUR',
            'symbol_position' => 'left',
            'decimal_places' => 2,
            'exchange_rate' => 1,
            'rate_type' => 'percent',
            'rate' => 25,
            'priority' => 1,
            'color' => 'slate',
            'locale' => '',
            'native_name' => '',
            'direction' => 'ltr',
            'is_default' => false,
            'is_paid' => false,
            'is_cancelled' => false,
            'is_active' => true,
            'sort_order' => 0,
            'settings_text' => '',
        ];
    }

    private function rules(): array
    {
        $model = new ($this->modelClass());
        $table = $model->getTable();

        $rules = [
            'form.code' => ['nullable', 'string', 'max:60'],
            'form.name' => ['nullable', 'string', 'max:255'],
            'form.provider' => ['nullable', 'string', 'max:255'],
            'form.geo_zone_id' => ['nullable', 'integer', 'exists:geo_zones,id'],
            'form.description' => ['nullable', 'string'],
            'form.fee_type' => ['nullable', Rule::in(['fixed', 'percent'])],
            'form.fee_value' => ['nullable', 'numeric', 'min:0'],
            'form.min_subtotal' => ['nullable', 'numeric', 'min:0'],
            'form.max_subtotal' => ['nullable', 'numeric', 'min:0'],
            'form.price' => ['nullable', 'numeric', 'min:0'],
            'form.free_over' => ['nullable', 'numeric', 'min:0'],
            'form.country_code' => ['nullable', 'string', 'size:2'],
            'form.region_code' => ['nullable', 'string', 'max:12'],
            'form.postal_code_from' => ['nullable', 'string', 'max:20'],
            'form.postal_code_to' => ['nullable', 'string', 'max:20'],
            'form.symbol' => ['nullable', 'string', 'max:8'],
            'form.symbol_position' => ['nullable', Rule::in(['left', 'right'])],
            'form.decimal_places' => ['nullable', 'integer', 'between:0,8'],
            'form.exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'form.rate_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'form.rate' => ['nullable', 'numeric', 'min:0'],
            'form.priority' => ['nullable', 'integer', 'between:1,255'],
            'form.color' => ['nullable', 'string', 'max:32'],
            'form.locale' => ['nullable', 'string', 'max:10'],
            'form.native_name' => ['nullable', 'string', 'max:255'],
            'form.direction' => ['nullable', Rule::in(['ltr', 'rtl'])],
            'form.is_default' => ['boolean'],
            'form.is_paid' => ['boolean'],
            'form.is_cancelled' => ['boolean'],
            'form.is_active' => ['boolean'],
            'form.sort_order' => ['nullable', 'integer', 'min:0'],
            'form.settings_text' => ['nullable', 'string'],
        ];

        if ($this->hasColumn('code')) {
            $rules['form.code'][] = 'required';
            if ($this->resource === 'regions') {
                $rules['form.code'][] = Rule::unique($table, 'code')
                    ->where(fn ($query) => $query->where('country_code', strtoupper((string) ($this->form['country_code'] ?? ''))))
                    ->ignore($this->editingId);
            } else {
                $rules['form.code'][] = Rule::unique($table, 'code')->ignore($this->editingId);
            }
        }

        if ($this->hasColumn('name')) {
            $rules['form.name'][] = 'required';
        }

        if ($this->resource === 'geo-zone-countries') {
            $rules['form.geo_zone_id'][] = 'required';
            $rules['form.country_code'][] = 'required';
        }

        if ($this->resource === 'regions') {
            $rules['form.country_code'][] = 'required';
        }

        $allowed = $this->resources[$this->resource]['fields'];
        return collect($rules)
            ->filter(fn ($v, $k) => in_array(str_replace('form.', '', $k), $allowed, true))
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function booleanFields(): array
    {
        return ['is_default', 'is_paid', 'is_cancelled', 'is_active'];
    }

    public function render()
    {
        $modelClass = $this->modelClass();
        $query = $modelClass::query();

        if ($this->search !== '') {
            $query->where(function ($builder): void {
                if ($this->hasColumn('code')) {
                    $builder->orWhere('code', 'like', '%'.$this->search.'%');
                }
                if ($this->hasColumn('name')) {
                    $builder->orWhere('name', 'like', '%'.$this->search.'%');
                }
                if ($this->hasColumn('country_code')) {
                    $builder->orWhere('country_code', 'like', '%'.$this->search.'%');
                }
            });
        }

        if ($this->hasColumn('sort_order')) {
            $query->orderBy('sort_order');
        }

        $rows = $query
            ->orderByDesc('id')
            ->paginate($this->adminPerPage());

        return view('livewire.admin.settings.local.resource-manager', [
            'rows' => $rows,
            'perPage' => $this->adminPerPage(),
            'geoZoneLabels' => $this->geoZoneOptions(),
            'countryLabels' => $this->countryOptions(),
        ]);
    }

    private function adminPerPage(): int
    {
        return app(SystemSettingsService::class)->getInt(
            'admin_items_per_page',
            (int) config('admin_ui.pagination.admin_items_per_page', 20),
            5,
            200
        );
    }
}

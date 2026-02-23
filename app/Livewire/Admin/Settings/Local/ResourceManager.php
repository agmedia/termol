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

    private const WSPAY_FORM_URL_TEST = 'https://formtest.wspay.biz/authorization.aspx';
    private const WSPAY_FORM_URL_LIVE = 'https://form.wspay.biz/authorization.aspx';

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

        $modelClass = $this->modelClass();
        $editingRecord = $this->editingId ? $modelClass::query()->findOrFail($this->editingId) : null;

        $settings = [];
        if ($this->hasColumn('settings')) {
            $settings = is_array($editingRecord?->settings) ? $editingRecord->settings : [];
            if (! empty($data['settings_text'])) {
                $decoded = json_decode((string) $data['settings_text'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->addError('form.settings_text', __('Settings JSON is invalid.'));
                    $this->dispatch('notify', type: 'error', message: __('Settings JSON is invalid.'));
                    return;
                }
                $settings = is_array($decoded) ? $decoded : [];
            }
            $settings = $this->mergePaymentMethodSettings($settings, $data);
            $settings = $this->mergeBankTransferUpiSettings($settings, $data);
            $settings = $this->mergeBoxNowSettings($settings, $data);
            $settings = $this->mergeWspaySettings($settings, $data);
            if ($this->isBankTransferCode((string) ($data['code'] ?? ''))) {
                if (! $this->hasRequiredBankTransferUpiSettings($settings)) {
                    $this->addError('form.upi_receiver_name', __('UPI receiver name, street, place and IBAN are required for bank transfer.'));
                    $this->dispatch('notify', type: 'error', message: __('UPI receiver name, street, place and IBAN are required for bank transfer.'));
                    return;
                }
            }
            if ($this->isBoxNowCode((string) ($data['code'] ?? '')) && trim((string) ($settings['boxnow_partner_id'] ?? '')) === '') {
                $this->addError('form.boxnow_partner_id', __('BOX NOW partner ID is required for boxnow shipping method.'));
                $this->dispatch('notify', type: 'error', message: __('BOX NOW partner ID is required for boxnow shipping method.'));
                return;
            }
            if ($this->isWspayCode((string) ($data['code'] ?? '')) && ! $this->hasRequiredWspaySettings($settings)) {
                $this->addError('form.wspay_shop_id', __('WSPay Shop ID and Secret Key are required.'));
                $this->dispatch('notify', type: 'error', message: __('WSPay Shop ID and Secret Key are required.'));
                return;
            }
            $data['settings'] = $settings;
        }

        unset(
            $data['settings_text'],
            $data['upi_receiver_name'],
            $data['upi_receiver_street'],
            $data['upi_receiver_place'],
            $data['upi_receiver_iban'],
            $data['upi_model'],
            $data['upi_purpose_code'],
            $data['upi_description'],
            $data['boxnow_partner_id'],
            $data['default_order_status_id'],
            $data['wspay_form_url'],
            $data['wspay_mode'],
            $data['wspay_shop_id'],
            $data['wspay_secret_key'],
            $data['wspay_return_method'],
        );

        if ($this->editingId) {
            $record = $editingRecord;
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
        $settings = is_array($record->settings ?? null) ? $record->settings : [];

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

            if (str_starts_with($key, 'upi_') || str_starts_with($key, 'wspay_') || in_array($key, ['boxnow_partner_id', 'default_order_status_id'], true)) {
                $this->form[$key] = (string) ($settings[$key] ?? $default);
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

    public function orderStatusOptions(): array
    {
        return OrderStatus::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
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
            'upi_receiver_name' => '',
            'upi_receiver_street' => '',
            'upi_receiver_place' => '',
            'upi_receiver_iban' => '',
            'upi_model' => '00',
            'upi_purpose_code' => 'SUPP',
            'upi_description' => 'Web narudzba',
            'boxnow_partner_id' => '',
            'default_order_status_id' => '',
            'wspay_mode' => 'test',
            'wspay_form_url' => '',
            'wspay_shop_id' => '',
            'wspay_secret_key' => '',
            'wspay_return_method' => 'GET',
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
            'form.upi_receiver_name' => ['nullable', 'string', 'max:255'],
            'form.upi_receiver_street' => ['nullable', 'string', 'max:255'],
            'form.upi_receiver_place' => ['nullable', 'string', 'max:255'],
            'form.upi_receiver_iban' => ['nullable', 'string', 'max:64'],
            'form.upi_model' => ['nullable', 'string', 'max:20'],
            'form.upi_purpose_code' => ['nullable', 'string', 'max:20'],
            'form.upi_description' => ['nullable', 'string', 'max:255'],
            'form.boxnow_partner_id' => ['nullable', 'string', 'max:60'],
            'form.default_order_status_id' => ['nullable', 'integer', 'exists:order_statuses,id'],
            'form.wspay_mode' => ['nullable', Rule::in(['test', 'live'])],
            'form.wspay_form_url' => ['nullable', 'string', 'max:255'],
            'form.wspay_shop_id' => ['nullable', 'string', 'max:120'],
            'form.wspay_secret_key' => ['nullable', 'string', 'max:255'],
            'form.wspay_return_method' => ['nullable', Rule::in(['GET', 'POST'])],
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
            ->filter(function ($v, $k) use ($allowed): bool {
                $field = str_replace('form.', '', $k);
                if (in_array($field, $allowed, true)) {
                    return true;
                }

                if ($this->resource === 'payment-methods' && str_starts_with($field, 'upi_')) {
                    return true;
                }

                if ($this->resource === 'payment-methods' && str_starts_with($field, 'wspay_')) {
                    return true;
                }

                if ($this->resource === 'payment-methods' && $field === 'default_order_status_id') {
                    return true;
                }

                return $this->resource === 'shipping-methods' && $field === 'boxnow_partner_id';
            })
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

    public function isBankTransferForm(): bool
    {
        if ($this->resource !== 'payment-methods') {
            return false;
        }

        return $this->isBankTransferCode((string) ($this->form['code'] ?? ''));
    }

    public function isBoxNowForm(): bool
    {
        if ($this->resource !== 'shipping-methods') {
            return false;
        }

        return $this->isBoxNowCode((string) ($this->form['code'] ?? ''));
    }

    public function isWspayForm(): bool
    {
        if ($this->resource !== 'payment-methods') {
            return false;
        }

        return $this->isWspayCode((string) ($this->form['code'] ?? ''));
    }

    private function isBankTransferCode(string $code): bool
    {
        return in_array(strtolower(trim($code)), ['bank', 'bank_transfer'], true);
    }

    private function isBoxNowCode(string $code): bool
    {
        return in_array(strtolower(trim($code)), ['boxnow', 'box_now'], true);
    }

    private function isWspayCode(string $code): bool
    {
        return in_array(strtolower(trim($code)), ['wspay', 'ws_pay'], true);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mergePaymentMethodSettings(array $settings, array $data): array
    {
        if ($this->resource !== 'payment-methods') {
            return $settings;
        }

        $statusId = (int) ($data['default_order_status_id'] ?? 0);
        if ($statusId > 0) {
            $settings['default_order_status_id'] = $statusId;
        } else {
            unset($settings['default_order_status_id']);
        }

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mergeBankTransferUpiSettings(array $settings, array $data): array
    {
        if (! $this->isBankTransferCode((string) ($data['code'] ?? ''))) {
            return $settings;
        }

        foreach ([
            'upi_receiver_name',
            'upi_receiver_street',
            'upi_receiver_place',
            'upi_receiver_iban',
            'upi_model',
            'upi_purpose_code',
            'upi_description',
        ] as $key) {
            $value = trim((string) ($data[$key] ?? ''));
            if ($value !== '') {
                $settings[$key] = $value;
            }
        }

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mergeBoxNowSettings(array $settings, array $data): array
    {
        if (! $this->isBoxNowCode((string) ($data['code'] ?? ''))) {
            return $settings;
        }

        $partnerId = trim((string) ($data['boxnow_partner_id'] ?? ''));
        if ($partnerId !== '') {
            $settings['boxnow_partner_id'] = $partnerId;
        }

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mergeWspaySettings(array $settings, array $data): array
    {
        if (! $this->isWspayCode((string) ($data['code'] ?? ''))) {
            return $settings;
        }

        $mode = strtolower(trim((string) ($data['wspay_mode'] ?? 'test')));
        $shopId = trim((string) ($data['wspay_shop_id'] ?? ''));
        $secret = trim((string) ($data['wspay_secret_key'] ?? ''));
        $returnMethod = strtoupper(trim((string) ($data['wspay_return_method'] ?? 'GET')));

        if (! in_array($mode, ['test', 'live'], true)) {
            $mode = 'test';
        }
        $settings['wspay_mode'] = $mode;
        $settings['wspay_form_url'] = $this->wspayFormUrlForMode($mode);
        if ($shopId !== '') {
            $settings['wspay_shop_id'] = $shopId;
        }
        if ($secret !== '') {
            $settings['wspay_secret_key'] = $secret;
        }
        if (in_array($returnMethod, ['GET', 'POST'], true)) {
            $settings['wspay_return_method'] = $returnMethod;
        }

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function hasRequiredBankTransferUpiSettings(array $settings): bool
    {
        foreach (['upi_receiver_name', 'upi_receiver_street', 'upi_receiver_place', 'upi_receiver_iban'] as $key) {
            if (trim((string) ($settings[$key] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function hasRequiredWspaySettings(array $settings): bool
    {
        foreach (['wspay_shop_id', 'wspay_secret_key'] as $key) {
            if (trim((string) ($settings[$key] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }

    private function wspayFormUrlForMode(string $mode): string
    {
        return $mode === 'live'
            ? self::WSPAY_FORM_URL_LIVE
            : self::WSPAY_FORM_URL_TEST;
    }
}

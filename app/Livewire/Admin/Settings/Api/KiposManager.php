<?php

namespace App\Livewire\Admin\Settings\Api;

use App\Services\Integrations\Kipos\KiposSdkService;
use Livewire\Component;
use Silber\Bouncer\BouncerFacade as Bouncer;

class KiposManager extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $form = [
        'kipos_api_enabled' => false,
        'kipos_api_base_uri' => '',
        'kipos_api_image_base_uri' => '',
        'kipos_api_query_suffix' => 'webshop=1',
        'kipos_api_timeout_seconds' => 30,
        'kipos_api_verify_tls' => true,
    ];

    /**
     * @var array{probe:string,result_count:int,first_item:array<string,mixed>|null}|null
     */
    public ?array $lastProbeResult = null;

    public ?string $lastProbeError = null;

    public function mount(KiposSdkService $kipos): void
    {
        $this->authorizeAccess();

        $this->form = array_merge($this->form, $kipos->getSettings());
    }

    public function toggleEnabled(): void
    {
        $this->authorizeAccess();

        $this->form['kipos_api_enabled'] = ! (bool) ($this->form['kipos_api_enabled'] ?? false);
    }

    public function toggleTls(): void
    {
        $this->authorizeAccess();

        $this->form['kipos_api_verify_tls'] = ! (bool) ($this->form['kipos_api_verify_tls'] ?? true);
    }

    public function save(KiposSdkService $kipos): void
    {
        $this->authorizeAccess();

        $validated = $this->validate($this->rules());
        $payload = $this->normalizePayload($validated['form']);

        $kipos->saveSettings($payload);

        $this->dispatch('notify', type: 'success', message: __('Kipos API settings saved.'));
    }

    public function testConnection(KiposSdkService $kipos): void
    {
        $this->authorizeAccess();

        $validated = $this->validate($this->rules());
        $payload = $this->normalizePayload($validated['form']);

        $this->lastProbeResult = null;
        $this->lastProbeError = null;

        try {
            $this->lastProbeResult = $kipos->testConnection($payload);
            $this->dispatch('notify', type: 'success', message: __('Kipos connection test passed.'));
        } catch (\Throwable $exception) {
            $this->lastProbeError = $exception->getMessage();
            $this->dispatch('notify', type: 'error', message: __('Kipos connection test failed. Check base URL or webshop suffix.'));
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.api.kipos-manager');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'form.kipos_api_enabled' => ['required', 'boolean'],
            'form.kipos_api_base_uri' => ['required', 'string', 'max:255', 'regex:/^https?:\\/\\//i'],
            'form.kipos_api_image_base_uri' => ['nullable', 'string', 'max:255', 'regex:/^$|^https?:\\/\\//i'],
            'form.kipos_api_query_suffix' => ['nullable', 'string', 'max:255'],
            'form.kipos_api_timeout_seconds' => ['required', 'integer', 'min:5', 'max:120'],
            'form.kipos_api_verify_tls' => ['required', 'boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function normalizePayload(array $raw): array
    {
        return [
            'kipos_api_enabled' => (bool) ($raw['kipos_api_enabled'] ?? false),
            'kipos_api_base_uri' => trim((string) ($raw['kipos_api_base_uri'] ?? '')),
            'kipos_api_image_base_uri' => trim((string) ($raw['kipos_api_image_base_uri'] ?? '')),
            'kipos_api_query_suffix' => ltrim(trim((string) ($raw['kipos_api_query_suffix'] ?? '')), '?&'),
            'kipos_api_timeout_seconds' => max(5, (int) ($raw['kipos_api_timeout_seconds'] ?? 30)),
            'kipos_api_verify_tls' => (bool) ($raw['kipos_api_verify_tls'] ?? true),
        ];
    }

    private function authorizeAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && (Bouncer::is($user)->an('superadmin') || $user->can('settings.api.manage')),
            403
        );
    }
}

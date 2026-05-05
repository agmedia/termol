<?php

namespace Tests\Feature\Admin;

use App\Services\Integrations\Kipos\KiposSdkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KiposSdkServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_connection_retries_after_temporary_connection_failure(): void
    {
        Http::fake([
            '*' => Http::sequence()
                ->pushFailedConnection('Connection refused')
                ->push([
                    [
                        'IDROBA' => 'KP001.ONE SIZE',
                        'NAZIV' => 'KP001 ONE SIZE',
                    ],
                ], 200),
        ]);

        $result = app(KiposSdkService::class)->testConnection([
            'kipos_api_enabled' => true,
            'kipos_api_base_uri' => 'http://example.test/kipos.web.api/?route=',
            'kipos_api_image_base_uri' => 'http://example.test/slike/',
            'kipos_api_query_suffix' => 'webshop=1',
            'kipos_api_timeout_seconds' => 30,
            'kipos_api_verify_tls' => true,
        ]);

        $this->assertSame(KiposSdkService::PROBE_ITEMS, $result['probe']);
        $this->assertSame(1, $result['result_count']);
        $this->assertSame('KP001.ONE SIZE', $result['first_item']['IDROBA'] ?? null);
        Http::assertSentCount(2);
    }
}

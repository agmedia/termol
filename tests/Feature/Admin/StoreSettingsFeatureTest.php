<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Settings\System\StoreSettings;
use App\Models\User;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

class StoreSettingsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_tab_can_save_even_when_newsletter_tab_is_invalid(): void
    {
        $admin = $this->makeUserWithRole('superadmin');

        app(SystemSettingsService::class)->putMany([
            'store_newsletter_provider' => 'mailchimp',
            'store_newsletter_mailchimp_api_key' => '',
            'store_newsletter_mailchimp_list_id' => '',
            'store_product_fit_finder_enabled' => false,
            'store_search_autocomplete_enabled' => false,
            'store_product_mobile_default_cols' => 1,
            'store_product_catalog_pagination_mode' => 'pagination',
            'store_product_filter_option_ids' => [],
            'store_product_filter_attribute_group_codes' => [],
        ]);

        Livewire::actingAs($admin)
            ->test(StoreSettings::class)
            ->set('tab', 'products')
            ->set('form.store_product_fit_finder_enabled', true)
            ->set('form.store_search_autocomplete_enabled', true)
            ->set('form.store_product_mobile_default_cols', 2)
            ->set('form.store_product_catalog_pagination_mode', 'load_more')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $settings = app(SystemSettingsService::class);

        $this->assertTrue((bool) $settings->get('store_product_fit_finder_enabled'));
        $this->assertTrue((bool) $settings->get('store_search_autocomplete_enabled'));
        $this->assertSame(2, (int) $settings->get('store_product_mobile_default_cols'));
        $this->assertSame('load_more', $settings->get('store_product_catalog_pagination_mode'));
        $this->assertSame('mailchimp', $settings->get('store_newsletter_provider'));
        $this->assertSame('', $settings->get('store_newsletter_mailchimp_api_key'));
        $this->assertSame('', $settings->get('store_newsletter_mailchimp_list_id'));
    }

    public function test_newsletter_tab_still_requires_mailchimp_credentials_when_active(): void
    {
        $admin = $this->makeUserWithRole('superadmin');

        Livewire::actingAs($admin)
            ->test(StoreSettings::class)
            ->set('tab', 'newsletter')
            ->set('form.store_newsletter_provider', 'mailchimp')
            ->set('form.store_newsletter_mailchimp_api_key', '')
            ->set('form.store_newsletter_mailchimp_list_id', '')
            ->call('save')
            ->assertHasErrors([
                'form.store_newsletter_mailchimp_api_key',
                'form.store_newsletter_mailchimp_list_id',
            ]);
    }

    private function makeUserWithRole(string $role): User
    {
        $user = User::factory()->create();

        Bouncer::role()->firstOrCreate(['name' => 'superadmin']);
        Bouncer::role()->firstOrCreate(['name' => 'admin']);
        Bouncer::role()->firstOrCreate(['name' => 'editor']);
        Bouncer::role()->firstOrCreate(['name' => 'customer']);

        Bouncer::assign($role)->to($user);

        return $user;
    }
}

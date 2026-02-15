<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Catalog\Product\Form as ProductForm;
use App\Models\Catalog\Product\Product;
use App\Models\User;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

class CatalogOptionsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_options_routes_are_blocked_when_feature_disabled(): void
    {
        $user = $this->makeAdminUser();

        $response = $this->actingAs($user)->get('/admin/options');

        $response
            ->assertRedirect(route('admin.settings.system.catalog-features'))
            ->assertSessionHas('notify.type', 'warning');
    }

    public function test_product_form_saves_without_option_assignment_when_feature_enabled(): void
    {
        app(SystemSettingsService::class)->put('catalog_use_options', true);

        $user = $this->makeAdminUser();

        Livewire::actingAs($user)
            ->test(ProductForm::class)
            ->set('form.code', 'p-option-1')
            ->set('form.sku', 'SKU-OPT-1')
            ->set('form.is_active', true)
            ->set('form.base_price', 19.99)
            ->set('form.stock_qty', 5)
            ->set('form.locale', 'en')
            ->set('form.name', 'Option Product')
            ->set('form.slug', 'option-product')
            ->call('save')
            ->assertRedirect(route('admin.products', ['locale' => 'en']));

        $product = Product::query()->where('code', 'p-option-1')->first();

        $this->assertNotNull($product);
        $this->assertSame('SKU-OPT-1', $product->sku);
    }

    private function makeAdminUser(): User
    {
        $user = User::factory()->create();

        Bouncer::role()->firstOrCreate(['name' => 'admin']);
        Bouncer::assign('admin')->to($user);

        return $user;
    }
}

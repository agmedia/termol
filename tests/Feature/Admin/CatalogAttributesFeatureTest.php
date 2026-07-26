<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Catalog\Attribute\Manager as AttributeManager;
use App\Livewire\Admin\Catalog\Product\Form as ProductForm;
use App\Models\Catalog\Attribute\Attribute;
use App\Models\Catalog\Product\Product;
use App\Models\Settings\Local\TaxRate;
use App\Models\User;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

class CatalogAttributesFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_attributes_routes_are_blocked_when_feature_disabled(): void
    {
        $user = $this->makeAdminUser();

        $response = $this->actingAs($user)->get('/admin/attributes');

        $response
            ->assertRedirect(route('admin.settings.system.catalog-features'))
            ->assertSessionHas('notify.type', 'warning');
    }

    public function test_product_form_saves_attribute_assignments_when_feature_enabled(): void
    {
        app(SystemSettingsService::class)->put('catalog_use_attributes', true);
        $this->createDefaultTaxRate();

        $user = $this->makeAdminUser();

        $materialBamboo = Attribute::query()->create([
            'code' => 'material-bamboo',
            'group_code' => 'material',
            'type' => Attribute::TYPE_SELECT,
            'is_active' => true,
            'sort_order' => 10,
            'payload' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $materialBamboo->translations()->create([
            'locale' => 'en',
            'group_name' => 'Material',
            'name' => 'Bamboo',
            'slug' => 'material-bamboo',
            'description' => null,
            'payload' => null,
        ]);

        $originJapan = Attribute::query()->create([
            'code' => 'origin-japan',
            'group_code' => 'origin',
            'type' => Attribute::TYPE_SELECT,
            'is_active' => true,
            'sort_order' => 20,
            'payload' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $originJapan->translations()->create([
            'locale' => 'en',
            'group_name' => 'Origin',
            'name' => 'Japan',
            'slug' => 'origin-japan',
            'description' => null,
            'payload' => null,
        ]);

        $component = Livewire::actingAs($user)
            ->test(ProductForm::class)
            ->call('setTab', 'attributes')
            ->assertSet('activeTab', 'attributes')
            ->set('form.code', 'p-attribute-1')
            ->set('form.sku', 'SKU-ATTR-1')
            ->set('form.is_active', true)
            ->set('form.base_price', 19.99)
            ->set('form.stock_qty', 5)
            ->set('form.locale', 'en')
            ->set('form.name', 'Attribute Product')
            ->set('form.slug', 'attribute-product')
            ->set('attributeSelections.material', (string) $materialBamboo->id)
            ->set('attributeSelections.origin', (string) $originJapan->id)
            ->call('save');

        $product = Product::query()->where('code', 'p-attribute-1')->first();

        $this->assertNotNull($product);
        $component->assertRedirect(route('admin.products.edit', ['product' => $product->id, 'locale' => 'en']));
        $this->assertSame(
            [$materialBamboo->id, $originJapan->id],
            $product->attributes()->orderBy('catalog_attribute_product.sort_order')->pluck('catalog_attributes.id')->all()
        );
    }

    public function test_product_form_rejects_multiple_values_for_single_type_group(): void
    {
        app(SystemSettingsService::class)->put('catalog_use_attributes', true);
        $this->createDefaultTaxRate();

        $user = $this->makeAdminUser();

        $genderM = Attribute::query()->create([
            'code' => 'gender-m',
            'group_code' => 'gender',
            'type' => Attribute::TYPE_SELECT,
            'is_active' => true,
            'sort_order' => 10,
            'payload' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $genderM->translations()->create([
            'locale' => 'en',
            'group_name' => 'Gender',
            'name' => 'M',
            'slug' => 'gender-m',
            'description' => null,
            'payload' => null,
        ]);

        $genderF = Attribute::query()->create([
            'code' => 'gender-f',
            'group_code' => 'gender',
            'type' => Attribute::TYPE_SELECT,
            'is_active' => true,
            'sort_order' => 20,
            'payload' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $genderF->translations()->create([
            'locale' => 'en',
            'group_name' => 'Gender',
            'name' => 'F',
            'slug' => 'gender-f',
            'description' => null,
            'payload' => null,
        ]);

        Livewire::actingAs($user)
            ->test(ProductForm::class)
            ->set('form.code', 'p-attribute-invalid')
            ->set('form.sku', 'SKU-ATTR-INV')
            ->set('form.is_active', true)
            ->set('form.base_price', 10)
            ->set('form.stock_qty', 2)
            ->set('form.locale', 'en')
            ->set('form.name', 'Invalid Attribute Product')
            ->set('form.slug', 'invalid-attribute-product')
            ->set('attributeSelections.gender', [$genderM->id, $genderF->id])
            ->call('save')
            ->assertHasErrors(['attributeSelections.gender']);

        $this->assertDatabaseMissing('products', ['code' => 'p-attribute-invalid']);
    }

    public function test_attribute_manager_deletes_attribute_from_list_and_detaches_products(): void
    {
        app(SystemSettingsService::class)->put('catalog_use_attributes', true);

        $user = $this->makeAdminUser();

        $attribute = Attribute::query()->create([
            'code' => 'material-linen',
            'group_code' => 'material',
            'type' => Attribute::TYPE_SELECT,
            'is_active' => true,
            'sort_order' => 10,
            'payload' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $attribute->translations()->create([
            'locale' => 'en',
            'group_name' => 'Material',
            'name' => 'Linen',
            'slug' => 'material-linen',
            'description' => null,
            'payload' => null,
        ]);

        $product = Product::query()->create([
            'code' => 'product-with-attribute',
            'sku' => 'PRODUCT-WITH-ATTRIBUTE',
            'is_active' => true,
            'manufacturer_id' => null,
            'tax_rate_id' => null,
            'base_price' => 29.99,
            'stock_qty' => 4,
            'payload' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $product->attributes()->attach($attribute->id, [
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(AttributeManager::class)
            ->call('delete', $attribute->id)
            ->assertDispatched('notify', type: 'success', message: __('Attribute deleted.'));

        $this->assertDatabaseMissing('catalog_attributes', ['id' => $attribute->id]);
        $this->assertDatabaseMissing('catalog_attribute_translations', ['attribute_id' => $attribute->id]);
        $this->assertDatabaseMissing('catalog_attribute_product', [
            'attribute_id' => $attribute->id,
            'product_id' => $product->id,
        ]);
    }

    private function makeAdminUser(): User
    {
        $user = User::factory()->create();

        Bouncer::role()->firstOrCreate(['name' => 'admin']);
        Bouncer::assign('admin')->to($user);

        return $user;
    }

    private function createDefaultTaxRate(): TaxRate
    {
        return TaxRate::query()->create([
            'code' => 'pdv25',
            'name' => 'PDV 25%',
            'geo_zone_id' => null,
            'rate_type' => 'percent',
            'rate' => 25,
            'priority' => 1,
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 1,
            'settings' => null,
        ]);
    }
}

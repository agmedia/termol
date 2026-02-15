<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Catalog\Product\Form as ProductForm;
use App\Models\Catalog\Attribute\Attribute;
use App\Models\Catalog\Product\Product;
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

        Livewire::actingAs($user)
            ->test(ProductForm::class)
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
            ->call('save')
            ->assertRedirect(route('admin.products', ['locale' => 'en']));

        $product = Product::query()->where('code', 'p-attribute-1')->first();

        $this->assertNotNull($product);
        $this->assertSame(
            [$materialBamboo->id, $originJapan->id],
            $product->attributes()->orderBy('catalog_attribute_product.sort_order')->pluck('catalog_attributes.id')->all()
        );
    }

    public function test_product_form_rejects_multiple_values_for_single_type_group(): void
    {
        app(SystemSettingsService::class)->put('catalog_use_attributes', true);

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

    private function makeAdminUser(): User
    {
        $user = User::factory()->create();

        Bouncer::role()->firstOrCreate(['name' => 'admin']);
        Bouncer::assign('admin')->to($user);

        return $user;
    }
}

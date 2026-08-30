<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Catalog\Product\Form as ProductForm;
use App\Livewire\Admin\Media\Manager as MediaManager;
use App\Models\Catalog\Product\Product;
use App\Models\Catalog\Product\ProductEnergyDeclaration;
use App\Models\Integrations\Msan\MsanProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class ProductEnergyDeclarationsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_saves_manual_primary_declaration_without_overwriting_imported_rows(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $imported = ProductEnergyDeclaration::query()->create([
            'product_id' => $product->id,
            'context_code' => 'msan-sdr',
            'label' => 'SDR',
            'energy_class' => 'D',
            'scale_min' => 'A',
            'scale_max' => 'G',
            'eprel_registration_number' => '1713217',
            'eprel_product_group' => 'electronicdisplays',
            'energy_label_image' => 'D A-G.svg',
            'is_primary' => true,
            'source' => ProductEnergyDeclaration::SOURCE_MSAN,
        ]);

        Livewire::actingAs($user)
            ->test(ProductForm::class, ['productId' => $product->id])
            ->call('setTab', 'energy')
            ->assertSet('activeTab', 'energy')
            ->call('addEnergyDeclaration')
            ->set('form.energy_label_required', true)
            ->set('energyDeclarations.1.context_code', 'manual-hdr')
            ->set('energyDeclarations.1.label', 'HDR')
            ->set('energyDeclarations.1.energy_class', 'E')
            ->set('energyDeclarations.1.scale_min', 'A')
            ->set('energyDeclarations.1.scale_max', 'G')
            ->set('energyDeclarations.1.energy_label_url', 'https://cdn.example.test/labels/hdr.pdf')
            ->set('energyDeclarations.1.product_information_sheet_url', 'https://cdn.example.test/fiches/hdr.pdf')
            ->call('setPrimaryEnergyDeclaration', 1)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('product_energy_declarations', [
            'id' => $imported->id,
            'source' => ProductEnergyDeclaration::SOURCE_MSAN,
            'energy_class' => 'D',
            'is_primary' => false,
        ]);
        $this->assertDatabaseHas('product_energy_declarations', [
            'product_id' => $product->id,
            'context_code' => 'manual-hdr',
            'source' => ProductEnergyDeclaration::SOURCE_MANUAL,
            'energy_class' => 'E',
            'is_primary' => true,
        ]);

        $product->refresh();
        $this->assertTrue($product->energy_label_required);
        $this->assertSame('E', $product->energy_efficiency_class);
        $this->assertSame('A-G', $product->energy_efficiency_scale);
    }

    public function test_pdf_information_sheet_is_never_promoted_or_edited_as_main_image(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $component = Livewire::actingAs($user)
            ->test(MediaManager::class, [
                'modelClass' => Product::class,
                'modelId' => $product->id,
                'locale' => 'hr',
            ])
            ->set('uploads.product_information_sheet', UploadedFile::fake()->createWithContent(
                'informacijski-list.pdf',
                "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF",
            ))
            ->call('uploadCollection', 'product_information_sheet')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $product->refresh();
        $document = $product->getFirstMedia('product_information_sheet');
        $this->assertNotNull($document);
        $this->assertNull($product->getFirstMedia('product_main'));

        $component
            ->assertSee('Open document')
            ->assertDontSee('data-image-edit-open', false)
            ->call('copyToMain', $document->id)
            ->assertDispatched('notify');

        $this->assertNull($product->fresh()->getFirstMedia('product_main'));
    }

    public function test_required_energy_documentation_warning_uses_only_valid_assets(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $product->forceFill(['energy_label_required' => true])->save();
        ProductEnergyDeclaration::query()->create([
            'product_id' => $product->id,
            'context_code' => 'unsafe-import',
            'energy_class' => 'A',
            'scale_min' => 'A',
            'scale_max' => 'G',
            'energy_label_image' => '../A.svg?token=secret',
            'is_primary' => true,
            'source' => ProductEnergyDeclaration::SOURCE_MSAN,
        ]);

        Livewire::actingAs($user)
            ->test(ProductForm::class, ['productId' => $product->id])
            ->call('setTab', 'energy')
            ->assertSee('data-energy-compliance-warning', false)
            ->assertSee('Nedostaju službena energetska oznaka i informacijski list proizvoda (PIS).');

        $product->energyDeclarations()->update([
            'energy_label_image' => 'A A-G.svg',
        ]);

        Livewire::actingAs($user)
            ->test(ProductForm::class, ['productId' => $product->id])
            ->call('setTab', 'energy')
            ->assertSee('data-energy-compliance-warning', false)
            ->assertSee('Nedostaju službena energetska oznaka i informacijski list proizvoda (PIS).');

        $product->energyDeclarations()->update([
            'eprel_registration_number' => '1713217',
            'eprel_product_group' => 'electronicdisplays',
        ]);

        Livewire::actingAs($user)
            ->test(ProductForm::class, ['productId' => $product->id])
            ->call('setTab', 'energy')
            ->assertDontSee('data-energy-compliance-warning', false);
    }

    public function test_admin_rejects_an_energy_class_outside_the_selected_scale(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        Livewire::actingAs($user)
            ->test(ProductForm::class, ['productId' => $product->id])
            ->call('addEnergyDeclaration')
            ->set('energyDeclarations.0.context_code', 'invalid-range')
            ->set('energyDeclarations.0.energy_class', 'B')
            ->set('energyDeclarations.0.scale_min', 'C')
            ->set('energyDeclarations.0.scale_max', 'G')
            ->set('energyDeclarations.0.energy_label_url', 'https://cdn.example.test/labels/product.pdf')
            ->call('save')
            ->assertHasErrors(['energyDeclarations.0.energy_class']);

        $this->assertDatabaseMissing('product_energy_declarations', [
            'product_id' => $product->id,
            'context_code' => 'invalid-range',
        ]);
    }

    public function test_manual_eprel_identity_change_invalidates_the_cached_supplier_match(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $product->forceFill([
            'eprel_registration_number' => '1111111',
            'eprel_product_group' => 'electronicdisplays',
        ])->save();
        $source = MsanProduct::query()->create([
            'external_code' => 'ENERGY-ADMIN-SOURCE',
            'name' => 'Energetski artikl',
            'local_product_id' => $product->id,
            'eprel_match_status' => MsanProduct::EPREL_EXACT,
            'eprel_identifier_checksum' => str_repeat('b', 64),
            'eprel_checked_at' => now(),
            'is_stale' => false,
        ]);

        Livewire::actingAs($user)
            ->test(ProductForm::class, ['productId' => $product->id])
            ->call('addEnergyDeclaration')
            ->set('energyDeclarations.0.context_code', 'manual-new-eprel')
            ->set('energyDeclarations.0.energy_class', 'C')
            ->set('energyDeclarations.0.scale_min', 'A')
            ->set('energyDeclarations.0.scale_max', 'G')
            ->set('energyDeclarations.0.eprel_registration_number', '2222222')
            ->set('energyDeclarations.0.eprel_product_group', 'refrigeratingappliances2019')
            ->set('energyDeclarations.0.is_primary', true)
            ->call('save')
            ->assertHasNoErrors();

        $source->refresh();
        $this->assertSame(MsanProduct::EPREL_PENDING, $source->eprel_match_status);
        $this->assertNull($source->eprel_identifier_checksum);
        $this->assertNull($source->eprel_checked_at);
    }

    private function product(): Product
    {
        $product = Product::query()->create([
            'code' => 'ENERGY-ADMIN-'.str()->random(8),
            'sku' => 'ENERGY-ADMIN-'.str()->random(8),
            'is_active' => true,
            'base_price' => 100,
            'stock_qty' => 5,
        ]);
        $product->translations()->create([
            'locale' => 'hr',
            'name' => 'Energetski artikl',
            'slug' => 'energetski-artikl-'.$product->id,
        ]);

        return $product;
    }
}

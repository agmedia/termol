<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Integrations\Msan\CategoryMappingManager;
use App\Livewire\Admin\Integrations\Msan\ProductSelectionManager;
use App\Livewire\Admin\Integrations\Msan\RunHistoryManager;
use App\Livewire\Admin\Integrations\Msan\SettingsForm;
use App\Models\Catalog\Category\Category;
use App\Models\Integrations\Msan\MsanCategory;
use App\Models\Integrations\Msan\MsanCategoryMapping;
use App\Models\Integrations\Msan\MsanProduct;
use App\Models\Integrations\Msan\MsanSyncRun;
use App\Models\User;
use App\Services\Integrations\Msan\MsanCertificateService;
use App\Services\Integrations\Msan\MsanImportCoordinator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

class MsanLivewireFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_map_multiple_msan_categories_to_one_local_category_and_ignore_another(): void
    {
        $admin = $this->makeAdmin();
        $localCategory = $this->createLocalCategory('grijanje', 'Grijanje');
        $first = $this->createMsanCategory('MSAN-HEAT-1', 'Radijatori');
        $second = $this->createMsanCategory('MSAN-HEAT-2', 'Konvektori');
        $ignored = $this->createMsanCategory('MSAN-OLD', 'Stari program');

        $component = Livewire::actingAs($admin)
            ->test(CategoryMappingManager::class)
            ->assertSee('Radijatori')
            ->assertSee('Konvektori')
            ->call('openEditor', $first->id)
            ->set('localCategoryId', (string) $localCategory->id)
            ->call('saveMapping')
            ->assertHasNoErrors()
            ->call('openEditor', $second->id)
            ->set('localCategoryId', (string) $localCategory->id)
            ->call('saveMapping')
            ->assertHasNoErrors()
            ->call('ignoreCategory', $ignored->id);

        $component->set('status', 'mapped')
            ->assertSee('Radijatori')
            ->assertSee('Konvektori')
            ->assertDontSee('Stari program');

        $this->assertDatabaseHas('msan_category_mappings', [
            'msan_category_id' => $first->id,
            'local_category_id' => $localCategory->id,
            'status' => MsanCategoryMapping::STATUS_MAPPED,
            'updated_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('msan_category_mappings', [
            'msan_category_id' => $second->id,
            'local_category_id' => $localCategory->id,
            'status' => MsanCategoryMapping::STATUS_MAPPED,
        ]);
        $this->assertDatabaseHas('msan_category_mappings', [
            'msan_category_id' => $ignored->id,
            'local_category_id' => null,
            'status' => MsanCategoryMapping::STATUS_IGNORED,
        ]);
    }

    public function test_exact_name_automatch_maps_only_unique_local_names(): void
    {
        $admin = $this->makeAdmin();
        $uniqueLocal = $this->createLocalCategory('bojleri', 'Bojleri');
        $duplicateSupplierLocal = $this->createLocalCategory('radijatori', 'Radijatori');
        $this->createLocalCategory('duplikat-a', 'Duplikat');
        $this->createLocalCategory('duplikat-b', 'Duplikat');
        $uniqueMsan = $this->createMsanCategory('MSAN-BOJLERI', '  BOJLERI ');
        $duplicateMsan = $this->createMsanCategory('MSAN-DUPLIKAT', 'Duplikat');
        $duplicateSupplierFirst = $this->createMsanCategory('MSAN-RAD-1', 'Radijatori');
        $duplicateSupplierSecond = $this->createMsanCategory('MSAN-RAD-2', ' radijatori ');

        Livewire::actingAs($admin)
            ->test(CategoryMappingManager::class)
            ->call('autoMatchExactNames')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('msan_category_mappings', [
            'msan_category_id' => $uniqueMsan->id,
            'local_category_id' => $uniqueLocal->id,
            'status' => MsanCategoryMapping::STATUS_MAPPED,
        ]);
        $this->assertDatabaseMissing('msan_category_mappings', [
            'msan_category_id' => $duplicateMsan->id,
        ]);
        $this->assertDatabaseMissing('msan_category_mappings', [
            'msan_category_id' => $duplicateSupplierFirst->id,
            'local_category_id' => $duplicateSupplierLocal->id,
        ]);
        $this->assertDatabaseMissing('msan_category_mappings', [
            'msan_category_id' => $duplicateSupplierSecond->id,
            'local_category_id' => $duplicateSupplierLocal->id,
        ]);
    }

    public function test_product_bulk_selection_persists_only_filtered_products_with_a_mapped_category(): void
    {
        $admin = $this->makeAdmin();
        $localCategory = $this->createLocalCategory('klima', 'Klima uređaji');
        $mappedCategory = $this->createMsanCategory('MSAN-CLIMA', 'Klima uređaji');
        $unmappedCategory = $this->createMsanCategory('MSAN-OTHER', 'Ostalo');
        MsanCategoryMapping::query()->create([
            'msan_category_id' => $mappedCategory->id,
            'local_category_id' => $localCategory->id,
            'status' => MsanCategoryMapping::STATUS_MAPPED,
            'updated_by' => $admin->id,
        ]);

        $eligible = $this->createMsanProduct('AC-100', 'Klima Alpha', 'Alpha');
        $eligible->update(['image_url' => 'https://b2b.msan.hr/private/admin-tracker.jpg']);
        $filteredOut = $this->createMsanProduct('AC-200', 'Klima Beta', 'Beta');
        $unmapped = $this->createMsanProduct('OTHER-100', 'Klima bez kategorije', 'Alpha');
        $eligible->categories()->attach($mappedCategory->id);
        $filteredOut->categories()->attach($mappedCategory->id);
        $unmapped->categories()->attach($unmappedCategory->id);

        $component = Livewire::actingAs($admin)
            ->test(ProductSelectionManager::class)
            ->assertDontSee('https://b2b.msan.hr/private/admin-tracker.jpg', escape: false)
            ->set('brand', 'Alpha')
            ->call('selectFiltered')
            ->assertDispatched('notify');

        $this->assertTrue($eligible->fresh()->selected);
        $this->assertFalse($filteredOut->fresh()->selected);
        $this->assertFalse($unmapped->fresh()->selected);

        $coordinator = Mockery::mock(MsanImportCoordinator::class);
        $coordinator->shouldReceive('queueSelected')
            ->once()
            ->with($admin->id)
            ->andReturn(new MsanSyncRun);
        $this->app->instance(MsanImportCoordinator::class, $coordinator);

        $component->call('queueSelectedImport');

        $component->set('categoryId', (string) $unmappedCategory->id)
            ->call('toggleSelection', $unmapped->id)
            ->assertDispatched('notify', type: 'warning');

        $this->assertFalse($unmapped->fresh()->selected);

        $component->call('clearFilters')
            ->set('brand', 'Alpha')
            ->call('deselectFiltered');

        $this->assertFalse($eligible->fresh()->selected);
    }

    public function test_product_search_waits_for_submit_and_uses_index_friendly_prefix_matching(): void
    {
        $admin = $this->makeAdmin();
        $prefixMatch = $this->createMsanProduct('AC-100', 'Klima Alpha', 'Alpha');
        $containsOnly = $this->createMsanProduct('AC-200', 'Premium Klima', 'Beta');

        Livewire::actingAs($admin)
            ->test(ProductSelectionManager::class)
            ->set('searchInput', 'K')
            ->call('applySearch')
            ->assertHasErrors('searchInput')
            ->set('searchInput', 'Klima')
            ->assertSee($prefixMatch->name)
            ->assertSee($containsOnly->name)
            ->call('applySearch')
            ->assertHasNoErrors('searchInput')
            ->assertSee($prefixMatch->name)
            ->assertDontSee($containsOnly->name);
    }

    public function test_run_history_is_paginated_read_only_and_filters_by_kind_and_status(): void
    {
        $admin = $this->makeAdmin();
        MsanSyncRun::query()->create([
            'kind' => MsanSyncRun::KIND_CATALOG,
            'status' => MsanSyncRun::STATUS_COMPLETED,
            'requested_by' => $admin->id,
            'progress' => 100,
            'total_count' => 10,
            'processed_count' => 10,
            'succeeded_count' => 10,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);
        MsanSyncRun::query()->create([
            'kind' => MsanSyncRun::KIND_IMPORT,
            'status' => MsanSyncRun::STATUS_FAILED,
            'requested_by' => $admin->id,
            'progress' => 50,
            'total_count' => 4,
            'processed_count' => 2,
            'failed_count' => 1,
            'error_message' => 'Kontrolirana testna pogreška',
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(RunHistoryManager::class)
            ->assertSee('Kontrolirana testna pogreška')
            ->set('kind', MsanSyncRun::KIND_CATALOG)
            ->set('status', MsanSyncRun::STATUS_COMPLETED)
            ->assertSee('Katalog')
            ->assertDontSee('Kontrolirana testna pogreška')
            ->assertDontSee('Ponovi');
    }

    public function test_invalid_ftp_form_does_not_replace_certificate_before_full_validation(): void
    {
        $admin = $this->makeAdmin();
        Storage::fake('msan-livewire-temp');
        config(['livewire.temporary_file_upload.disk' => 'msan-livewire-temp']);
        $certificates = Mockery::mock(MsanCertificateService::class);
        $certificates->shouldReceive('hasCertificate')->once()->andReturn(false);
        $certificates->shouldNotReceive('replaceFromPath');
        $this->app->instance(MsanCertificateService::class, $certificates);

        Livewire::actingAs($admin)
            ->test(SettingsForm::class)
            ->set('certificate', UploadedFile::fake()->create('client.p12', 10, 'application/x-pkcs12'))
            ->set('form.msan_enabled', true)
            ->set('form.msan_p12_pin', 'synthetic-pin')
            ->set('form.msan_ftp_enabled', true)
            ->set('form.msan_ftp_username', '')
            ->set('form.msan_ftp_password', '')
            ->call('save')
            ->assertHasErrors('form.msan_ftp_username');

        $this->assertSame([], Storage::disk('msan-livewire-temp')->allFiles('livewire-tmp'));
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        Bouncer::assign('admin')->to($user);

        return $user;
    }

    private function createLocalCategory(string $code, string $name): Category
    {
        $category = Category::query()->create([
            'scope' => Category::SCOPE_CATALOG,
            'code' => $code,
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 0,
        ]);
        $category->translations()->create([
            'locale' => 'hr',
            'name' => $name,
            'slug' => $code,
        ]);

        return $category;
    }

    private function createMsanCategory(string $externalId, string $name): MsanCategory
    {
        return MsanCategory::query()->create([
            'external_id' => $externalId,
            'name' => $name,
            'path' => $name,
            'product_count' => 0,
            'last_seen_at' => now(),
            'is_stale' => false,
        ]);
    }

    private function createMsanProduct(string $externalCode, string $name, string $brand): MsanProduct
    {
        return MsanProduct::query()->create([
            'external_code' => $externalCode,
            'name' => $name,
            'brand' => $brand,
            'currency_code' => 'EUR',
            'availability_level' => 2,
            'selected' => false,
            'is_stale' => false,
            'match_status' => MsanProduct::MATCH_UNMATCHED,
            'import_status' => MsanProduct::IMPORT_PENDING,
            'last_seen_at' => now(),
        ]);
    }
}

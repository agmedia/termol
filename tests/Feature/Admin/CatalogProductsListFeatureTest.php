<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Catalog\Product\Manager as ProductManager;
use App\Models\Catalog\Category\Category;
use App\Models\Catalog\Product\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

class CatalogProductsListFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_list_filters_by_state_stock_and_category(): void
    {
        $user = $this->makeAdminUser();

        $catalogRoot = new Category([
            'scope' => Category::SCOPE_CATALOG,
            'code' => 'catalog-root',
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 10,
            'payload' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $catalogRoot->saveAsRoot();
        $catalogRoot->translations()->create([
            'scope' => Category::SCOPE_CATALOG,
            'locale' => 'en',
            'name' => 'Catalog',
            'slug' => 'catalog',
            'description' => null,
            'meta_title' => null,
            'meta_description' => null,
            'payload' => null,
        ]);

        $targetCategory = new Category([
            'scope' => Category::SCOPE_CATALOG,
            'code' => 'target-cat',
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 20,
            'payload' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $targetCategory->appendToNode($catalogRoot)->save();
        $targetCategory->translations()->create([
            'scope' => Category::SCOPE_CATALOG,
            'locale' => 'en',
            'name' => 'Target Category',
            'slug' => 'target-category',
            'description' => null,
            'meta_title' => null,
            'meta_description' => null,
            'payload' => null,
        ]);

        $otherCategory = new Category([
            'scope' => Category::SCOPE_CATALOG,
            'code' => 'other-cat',
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 30,
            'payload' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $otherCategory->appendToNode($catalogRoot)->save();
        $otherCategory->translations()->create([
            'scope' => Category::SCOPE_CATALOG,
            'locale' => 'en',
            'name' => 'Other Category',
            'slug' => 'other-category',
            'description' => null,
            'meta_title' => null,
            'meta_description' => null,
            'payload' => null,
        ]);

        $matching = $this->makeProduct($user, 'P-FILTER-1', 'Filter Match', true, 8, 25);
        $outOfStock = $this->makeProduct($user, 'P-FILTER-2', 'Filter Out Of Stock', true, 0, 19);
        $inactive = $this->makeProduct($user, 'P-FILTER-3', 'Filter Inactive', false, 12, 29);

        $matching->categories()->attach($targetCategory->id, ['sort_order' => 0, 'is_primary' => true]);
        $outOfStock->categories()->attach($targetCategory->id, ['sort_order' => 0, 'is_primary' => true]);
        $inactive->categories()->attach($otherCategory->id, ['sort_order' => 0, 'is_primary' => true]);

        Livewire::actingAs($user)
            ->test(ProductManager::class)
            ->set('stateFilter', 'active')
            ->set('stockFilter', 'in_stock')
            ->set('categoryFilter', (string) $targetCategory->id)
            ->assertSee('Filter Match')
            ->assertDontSee('Filter Out Of Stock')
            ->assertDontSee('Filter Inactive');
    }

    public function test_product_list_sorts_by_price_desc(): void
    {
        $user = $this->makeAdminUser();

        $this->makeProduct($user, 'P-SORT-1', 'Price Low', true, 10, 10);
        $this->makeProduct($user, 'P-SORT-2', 'Price High', true, 10, 30);
        $this->makeProduct($user, 'P-SORT-3', 'Price Mid', true, 10, 20);

        Livewire::actingAs($user)
            ->test(ProductManager::class)
            ->set('sortBy', 'price_desc')
            ->assertSeeInOrder(['Price High', 'Price Mid', 'Price Low']);
    }

    private function makeProduct(User $user, string $code, string $name, bool $isActive, int $stockQty, float $basePrice): Product
    {
        $product = Product::query()->create([
            'code' => $code,
            'sku' => $code.'-SKU',
            'manufacturer_id' => null,
            'is_active' => $isActive,
            'base_price' => $basePrice,
            'stock_qty' => $stockQty,
            'payload' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $product->translations()->create([
            'locale' => 'en',
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'excerpt' => null,
            'description' => null,
            'meta_title' => null,
            'meta_description' => null,
            'payload' => null,
        ]);

        return $product;
    }

    private function makeAdminUser(): User
    {
        $user = User::factory()->create();

        Bouncer::role()->firstOrCreate(['name' => 'admin']);
        Bouncer::assign('admin')->to($user);

        return $user;
    }
}


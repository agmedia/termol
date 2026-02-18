<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Content\Block\Form as BlockForm;
use App\Models\Catalog\Category\Category;
use App\Models\Content\ContentBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

class ContentBlocksFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_form_preselects_saved_type_global_target_and_selected_items(): void
    {
        $user = $this->makeAdminUser();

        $categoryA = Category::query()->create([
            'scope' => Category::SCOPE_CATALOG,
            'code' => 'cat-a',
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 10,
            'payload' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $categoryA->translations()->create([
            'scope' => Category::SCOPE_CATALOG,
            'locale' => 'en',
            'name' => 'Category A',
            'slug' => 'category-a',
        ]);

        $categoryB = Category::query()->create([
            'scope' => Category::SCOPE_CATALOG,
            'code' => 'cat-b',
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 20,
            'payload' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $categoryB->translations()->create([
            'scope' => Category::SCOPE_CATALOG,
            'locale' => 'en',
            'name' => 'Category B',
            'slug' => 'category-b',
        ]);

        $block = ContentBlock::query()->create([
            'code' => 'front-shop-by-category',
            'name' => 'Front Shop by Category',
            'type' => 'categories',
            'is_active' => true,
            'payload' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $block->translations()->create([
            'locale' => 'en',
            'title' => 'Shop by category',
            'subtitle' => 'Subtitle',
            'cta_label' => 'Explore collection',
            'cta_url' => '#categories',
            'payload' => null,
        ]);

        $block->slots()->create([
            'placement' => 'home.categories',
            'frontend_variant' => 'mobile',
            'target_type' => null,
            'target_ref' => null,
            'sort_order' => 0,
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $block->items()->create([
            'item_type' => 'category',
            'item_id' => $categoryA->id,
            'sort_order' => 0,
        ]);
        $block->items()->create([
            'item_type' => 'category',
            'item_id' => $categoryB->id,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(BlockForm::class, ['blockId' => $block->id])
            ->assertSet('form.type', 'categories')
            ->assertSet('form.slot_placement', 'home.categories')
            ->assertSet('form.slot_frontend_variant', 'mobile')
            ->assertSet('form.slot_target_type', '')
            ->assertSet('form.slot_target_ref', '')
            ->assertSet('form.selected_item_ids', [$categoryA->id, $categoryB->id])
            ->assertSee('value="categories" selected', false)
            ->assertSee('value="mobile" selected', false)
            ->assertSee('value="" selected', false)
            ->assertSee('Category A')
            ->assertSee('Category B');
    }

    public function test_type_switch_auto_sets_surface_for_mobile_and_desktop_hero_types(): void
    {
        $user = $this->makeAdminUser();

        Livewire::actingAs($user)
            ->test(BlockForm::class)
            ->assertSet('form.slot_frontend_variant', 'all')
            ->assertDontSee('No items selected.')
            ->set('form.type', 'mobile_hero_banner')
            ->assertSet('form.slot_frontend_variant', 'mobile')
            ->assertSee('No items selected.')
            ->set('form.type', 'desktop_hero_banner')
            ->assertSet('form.slot_frontend_variant', 'desktop');
    }

    private function makeAdminUser(): User
    {
        $user = User::factory()->create();

        Bouncer::role()->firstOrCreate(['name' => 'admin']);
        Bouncer::assign('admin')->to($user);

        return $user;
    }
}

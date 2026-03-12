<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Content\Block\Form as BlockForm;
use App\Livewire\Admin\Media\Manager as MediaManager;
use App\Models\Catalog\Category\Category;
use App\Models\Content\ContentBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
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

    public function test_admin_can_import_instagram_preview_into_block_slide_on_save_meta(): void
    {
        $user = $this->makeAdminUser();

        $block = ContentBlock::query()->create([
            'code' => 'home-instagram-import-test',
            'name' => 'Home Instagram Import Test',
            'type' => 'instagram_curated_grid',
            'is_active' => true,
            'payload' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $block->translations()->create([
            'locale' => 'hr',
            'title' => 'Instagram',
            'subtitle' => '@kozo_bodywear',
            'cta_label' => null,
            'cta_url' => null,
            'payload' => null,
        ]);

        $block->slots()->create([
            'placement' => 'home.bottom',
            'frontend_variant' => 'desktop',
            'target_type' => null,
            'target_ref' => null,
            'sort_order' => 0,
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $initialImage = UploadedFile::fake()->image('initial-slide.jpg', 600, 600);
        $media = $block->addMedia($initialImage->getPathname())
            ->usingName('Instagram post 1')
            ->usingFileName('initial-slide.jpg')
            ->toMediaCollection('block_slides');

        $downloadedPreview = UploadedFile::fake()->image('instagram-preview.jpg', 640, 640);
        $previewBytes = file_get_contents($downloadedPreview->getPathname());
        $this->assertNotFalse($previewBytes);

        Http::fake([
            'https://www.instagram.com/api/v1/oembed/*' => Http::response([
                'title' => "Less flowers. More attitude. ❤️\nValentinovo je vibe.",
                'author_name' => 'kozo_bodywear',
                'thumbnail_url' => 'https://cdn.example.test/instagram-preview.jpg',
                'html' => '<blockquote data-instgrm-permalink="https://www.instagram.com/reel/DUiweB6jCTT/?utm_source=ig_embed"></blockquote>',
            ], 200),
            'https://cdn.example.test/*' => Http::response($previewBytes, 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        Livewire::actingAs($user)
            ->test(MediaManager::class, [
                'modelClass' => ContentBlock::class,
                'modelId' => $block->id,
                'locale' => 'hr',
            ])
            ->set("meta.{$media->id}.link_url", 'https://www.instagram.com/p/DUiweB6jCTT/')
            ->set("meta.{$media->id}.block_title", 'Stari naslov')
            ->set("meta.{$media->id}.caption", '')
            ->set("meta.{$media->id}.alt", '')
            ->call('saveMeta', $media->id)
            ->assertDispatched('notify', type: 'success', message: 'Image metadata saved and Instagram preview refreshed.');

        $media->refresh();

        $this->assertSame(
            'https://www.instagram.com/reel/DUiweB6jCTT/',
            (string) data_get($media->custom_properties, 'link_url.hr')
        );
        $this->assertSame(
            'Less flowers. More attitude. ❤️ Valentinovo je vibe.',
            (string) data_get($media->custom_properties, 'caption.hr')
        );
        $this->assertStringStartsWith(
            'Less flowers. More attitude.',
            (string) data_get($media->custom_properties, 'block_title.hr')
        );
        $this->assertSame('kozo_bodywear', (string) data_get($media->custom_properties, 'instagram_author_name'));
        $this->assertSame('video', (string) data_get($media->custom_properties, 'instagram_media_kind'));
        $this->assertSame('image/jpeg', (string) $media->mime_type);
        $this->assertSame($previewBytes, file_get_contents($media->getPath()));
    }

    public function test_admin_can_add_new_instagram_post_slot_without_uploading_a_file(): void
    {
        $user = $this->makeAdminUser();

        $block = ContentBlock::query()->create([
            'code' => 'home-instagram-add-post-test',
            'name' => 'Home Instagram Add Post Test',
            'type' => 'instagram_curated_grid',
            'is_active' => true,
            'payload' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $block->translations()->create([
            'locale' => 'hr',
            'title' => 'Instagram',
            'subtitle' => '@kozo_bodywear',
            'cta_label' => null,
            'cta_url' => null,
            'payload' => null,
        ]);

        Livewire::actingAs($user)
            ->test(MediaManager::class, [
                'modelClass' => ContentBlock::class,
                'modelId' => $block->id,
                'locale' => 'hr',
            ])
            ->call('addInstagramPostSlide', 'block_slides')
            ->assertDispatched('notify', type: 'success', message: 'New Instagram post slot added. Paste the post URL and click Save Meta.');

        $media = $block->fresh()->getMedia('block_slides');

        $this->assertCount(1, $media);
        $this->assertSame('Instagram post', $media->first()?->name);
        $this->assertSame('image/png', (string) $media->first()?->mime_type);
    }

    private function makeAdminUser(): User
    {
        $user = User::factory()->create();

        Bouncer::role()->firstOrCreate(['name' => 'admin']);
        Bouncer::assign('admin')->to($user);

        return $user;
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Content\Navigation\Manager as NavigationManager;
use App\Models\Catalog\Category\Category;
use App\Models\Catalog\Category\CategoryTranslation;
use App\Models\Content\Page\InfoPage;
use App\Models\Content\Page\InfoPageTranslation;
use App\Models\Settings\Local\Language;
use App\Models\User;
use App\Services\Front\NavigationMenuService;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

class ContentNavigationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_navigation_page(): void
    {
        $user = $this->makeAdminUser();

        $this->actingAs($user)
            ->get('/admin/content/navigation')
            ->assertOk()
            ->assertSee(__('admin.content.navigation.title'));
    }

    public function test_admin_can_save_navigation_config(): void
    {
        $user = $this->makeAdminUser();

        $category = Category::query()->create([
            'scope' => Category::SCOPE_CATALOG,
            'code' => 'food',
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 10,
        ]);

        CategoryTranslation::query()->create([
            'category_id' => $category->id,
            'scope' => Category::SCOPE_CATALOG,
            'locale' => 'en',
            'name' => 'Food',
            'slug' => 'food',
        ]);

        $page = InfoPage::query()->create([
            'code' => 'faq',
            'layout' => 'default',
            'is_active' => true,
            'show_in_footer' => false,
            'sort_order' => 20,
        ]);

        InfoPageTranslation::query()->create([
            'page_id' => $page->id,
            'locale' => 'en',
            'title' => 'FAQ',
            'slug' => 'faq',
        ]);

        Livewire::actingAs($user)
            ->test(NavigationManager::class)
            ->set('form.items', [
                [
                    'type' => 'category',
                    'label' => '',
                    'category_id' => $category->id,
                    'page_id' => 0,
                    'url' => '',
                    'open_in_new_tab' => false,
                    'show_dropdown' => true,
                    'is_active' => true,
                    'sort_order' => 0,
                ],
                [
                    'type' => 'page',
                    'label' => '',
                    'category_id' => 0,
                    'page_id' => $page->id,
                    'url' => '',
                    'open_in_new_tab' => false,
                    'show_dropdown' => false,
                    'is_active' => true,
                    'sort_order' => 1,
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $saved = app(SystemSettingsService::class)->get(NavigationMenuService::SETTINGS_KEY, []);

        $this->assertIsArray($saved);
        $this->assertCount(2, $saved);
        $this->assertSame('category', $saved[0]['type']);
        $this->assertSame((int) $category->id, (int) $saved[0]['category_id']);
        $this->assertSame('page', $saved[1]['type']);
        $this->assertSame((int) $page->id, (int) $saved[1]['page_id']);
    }

    public function test_inactive_languages_remain_available_for_content_translation(): void
    {
        $user = $this->makeAdminUser();

        Language::query()->create([
            'code' => 'en',
            'locale' => 'en_US',
            'name' => 'English',
            'native_name' => 'English',
            'direction' => 'ltr',
            'is_default' => false,
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $this->actingAs($user)
            ->get('/admin/content/navigation')
            ->assertOk()
            ->assertSee('value="en"', false);
    }

    public function test_navigation_promo_content_is_resolved_per_locale(): void
    {
        config(['app.locale' => 'hr']);

        app(SystemSettingsService::class)->put(NavigationMenuService::SETTINGS_KEY, [[
            'type' => 'custom',
            'label' => 'Žene',
            'label_translations' => ['hr' => 'Žene', 'en' => 'Women'],
            'url' => '/shop',
            'url_translations' => ['hr' => '/shop', 'en' => '/shop'],
            'is_active' => true,
            'show_dropdown' => true,
            'open_in_new_tab' => false,
            'sort_order' => 0,
            'desktop_promo_title' => 'Nova kolekcija',
            'desktop_promo_title_translations' => ['hr' => 'Nova kolekcija', 'en' => 'New collection'],
            'desktop_promo_subtitle' => 'Istaknuti komadi sezone',
            'desktop_promo_subtitle_translations' => ['hr' => 'Istaknuti komadi sezone', 'en' => 'Season highlights'],
            'desktop_promo_cta_label' => 'Pogledaj više',
            'desktop_promo_cta_label_translations' => ['hr' => 'Pogledaj više', 'en' => 'Shop now'],
            'desktop_promo_cta_url' => '/category/zene',
            'desktop_promo_cta_url_translations' => ['hr' => '/category/zene', 'en' => '/category/women'],
        ]]);

        $service = app(NavigationMenuService::class);
        $croatian = $service->forLocale('hr')[0]['mega_promo'];
        $english = $service->forLocale('en')[0]['mega_promo'];

        $this->assertSame('Nova kolekcija', $croatian['title']);
        $this->assertSame('Pogledaj više', $croatian['cta_label']);
        $this->assertSame('/category/zene', $croatian['cta_url']);
        $this->assertSame('New collection', $english['title']);
        $this->assertSame('Shop now', $english['cta_label']);
        $this->assertSame('/category/women', $english['cta_url']);
    }

    private function makeAdminUser(): User
    {
        $user = User::factory()->create();

        Bouncer::role()->firstOrCreate(['name' => 'admin']);
        Bouncer::assign('admin')->to($user);

        return $user;
    }
}

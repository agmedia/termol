<?php

namespace Tests\Feature\Front;

use App\Models\Settings\Local\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontLocaleFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_locale_switch_persists_selected_language_between_requests(): void
    {
        $this->seedLanguages();

        $this->get('/contact')
            ->assertOk()
            ->assertSee('lang="hr"', false);

        $this->from('/contact')
            ->get('/locale/en')
            ->assertRedirect('/contact')
            ->assertSessionHas('front_locale', 'en');

        $this->get('/contact')
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSessionHas('front_locale', 'en');
    }

    private function seedLanguages(): void
    {
        Language::query()->create([
            'code' => 'hr',
            'locale' => 'hr_HR',
            'name' => 'Croatian',
            'native_name' => 'Hrvatski',
            'direction' => 'ltr',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Language::query()->create([
            'code' => 'en',
            'locale' => 'en_US',
            'name' => 'English',
            'native_name' => 'English',
            'direction' => 'ltr',
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }
}

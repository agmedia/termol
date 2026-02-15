<?php

namespace App\Providers;

use App\Models\Content\ContentBlock;
use App\Models\Content\ContentBlockSlot;
use App\Models\Content\ContentBlockTranslation;
use App\Models\Settings\Local\Currency;
use App\Models\Settings\Local\GeoZone;
use App\Models\Settings\Local\GeoZoneCountry;
use App\Models\Settings\Local\Language;
use App\Models\Settings\Local\OrderStatus;
use App\Models\Settings\Local\PaymentMethod;
use App\Models\Settings\Local\ShippingMethod;
use App\Models\Settings\Local\TaxRate;
use App\Observers\Content\ContentCacheObserver;
use App\Observers\Settings\LocalSettingObserver;
use App\Services\Catalog\CatalogFeatureService;
use App\Services\Content\ContentBlockResolver;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Settings\LocalSettingsService;
use App\Services\Settings\SystemSettingsService;
use App\Services\UserTracking\UserTrackingService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LocalSettingsService::class, fn () => new LocalSettingsService());
        $this->app->singleton(SystemSettingsService::class, fn () => new SystemSettingsService());
        $this->app->singleton(CatalogFeatureService::class, fn ($app) => new CatalogFeatureService($app->make(SystemSettingsService::class)));
        $this->app->singleton(ContentBlockResolver::class, fn () => new ContentBlockResolver());
        $this->app->singleton(UserTrackingService::class, fn ($app) => new UserTrackingService($app->make(SystemSettingsService::class)));
        $this->app->singleton(LoyaltyService::class, fn ($app) => new LoyaltyService($app->make(SystemSettingsService::class)));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::addPersistentMiddleware([
            \App\Http\Middleware\EnsureAdminAbility::class,
        ]);

        RateLimiter::for('wholesale-api', static function (Request $request) {
            $perMinute = max(30, (int) env('WHOLESALE_API_RATE_LIMIT', 240));
            $key = (string) ($request->user()?->id ?: $request->ip());

            return [
                Limit::perMinute($perMinute)->by('wholesale:'.$key),
            ];
        });

        View::composer('livewire.admin.*', static function ($view): void {
            static $localeOptions = null;

            if ($localeOptions === null) {
                try {
                    $localeOptions = Language::query()
                        ->where('is_active', true)
                        ->orderByDesc('is_default')
                        ->orderBy('sort_order')
                        ->orderBy('code')
                        ->pluck('code')
                        ->filter(fn ($code) => is_string($code) && trim($code) !== '')
                        ->map(fn ($code) => strtolower(trim((string) $code)))
                        ->unique()
                        ->values()
                        ->all();
                } catch (\Throwable) {
                    $localeOptions = [];
                }

                if ($localeOptions === []) {
                    $localeOptions = [strtolower((string) config('app.locale', 'en'))];
                }
            }

            $view->with('adminLocaleOptions', $localeOptions);
        });

        PaymentMethod::observe(LocalSettingObserver::class);
        ShippingMethod::observe(LocalSettingObserver::class);
        GeoZone::observe(LocalSettingObserver::class);
        GeoZoneCountry::observe(LocalSettingObserver::class);
        Currency::observe(LocalSettingObserver::class);
        TaxRate::observe(LocalSettingObserver::class);
        OrderStatus::observe(LocalSettingObserver::class);
        Language::observe(LocalSettingObserver::class);

        ContentBlock::observe(ContentCacheObserver::class);
        ContentBlockTranslation::observe(ContentCacheObserver::class);
        ContentBlockSlot::observe(ContentCacheObserver::class);
    }
}

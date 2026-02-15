<?php

use App\Http\Controllers\Admin\SystemToolsController;
use App\Http\Controllers\Admin\AdminAiController;
use App\Http\Controllers\Front\StorefrontController;
use App\Models\Catalog\Action\CatalogAction;
use App\Models\Catalog\Attribute\Attribute as CatalogAttribute;
use App\Models\Catalog\Category\Category;
use App\Models\Catalog\Manufacturer\Manufacturer;
use App\Models\Catalog\Option\Option;
use App\Models\Catalog\Product\Product;
use App\Models\Content\Blog\BlogPost;
use App\Models\Content\ContentBlock;
use App\Models\Content\ContentBlockSlot;
use App\Models\Content\Page\InfoPage;
use App\Models\Content\Support\Faq;
use App\Models\Sales\Order\Order as SalesOrder;
use App\Models\User;
use App\Models\User\LoyaltyTransaction;
use App\Models\User\UserTrackingEvent;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\Models\Activity;

Route::middleware('front.device')
    ->get('/', [StorefrontController::class, 'home'])
    ->name('home');

Route::get('dashboard', function (Request $request) {
    $user = $request->user();

    if ($user && $user->isA('customer')) {
        return redirect('/');
    }

    return redirect()->route('admin.dashboard');
})
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'admin.access', 'admin.ability'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function (): void {
        Route::redirect('/', '/admin/dashboard');
        Route::view('dashboard', 'admin.dashboard')->name('dashboard');
        Route::view('categories', 'admin.categories')->name('categories');
        Route::view('categories/create', 'admin.categories.create')->name('categories.create');
        Route::get('categories/{category}/edit', function (Category $category) {
            return view('admin.categories.edit', compact('category'));
        })->name('categories.edit');
        Route::view('products', 'admin.products')->name('products');
        Route::view('products/create', 'admin.products.create')->name('products.create');
        Route::get('products/{product}/edit', function (Product $product) {
            return view('admin.products.edit', compact('product'));
        })->name('products.edit');
        Route::view('orders', 'admin.orders.index')->name('orders');
        Route::get('orders/{order}/show', function (SalesOrder $order) {
            return view('admin.orders.show', compact('order'));
        })->name('orders.show');
        Route::get('orders/{order}/invoice', function (SalesOrder $order) {
            $order->load([
                'status:id,code,name,color',
                'items',
                'totals',
            ]);

            return view('admin.orders.invoice', compact('order'));
        })->name('orders.invoice');
        Route::middleware('catalog.feature:catalog_use_options')->group(function (): void {
            Route::get('products/{product}/options', function (Product $product) {
                return view('admin.products.options', compact('product'));
            })->name('products.options');
        });
        Route::middleware('catalog.feature:catalog_use_options')->group(function (): void {
            Route::view('options', 'admin.options')->name('options');
            Route::view('options/create', 'admin.options.create')->name('options.create');
            Route::get('options/{option}/edit', function (Option $option) {
                return view('admin.options.edit', compact('option'));
            })->name('options.edit');
            Route::get('options/{option}/values', function (Option $option) {
                return view('admin.options.values', compact('option'));
            })->name('options.values');
        });
        Route::middleware('catalog.feature:catalog_use_attributes')->group(function (): void {
            Route::view('attributes', 'admin.attributes')->name('attributes');
            Route::view('attributes/create', 'admin.attributes.create')->name('attributes.create');
            Route::get('attributes/{attribute}/edit', function (CatalogAttribute $attribute) {
                return view('admin.attributes.edit', compact('attribute'));
            })->name('attributes.edit');
        });
        Route::middleware('catalog.feature:catalog_use_manufacturers')->group(function (): void {
            Route::view('manufacturers', 'admin.manufacturers')->name('manufacturers');
            Route::view('manufacturers/create', 'admin.manufacturers.create')->name('manufacturers.create');
            Route::get('manufacturers/{manufacturer}/edit', function (Manufacturer $manufacturer) {
                return view('admin.manufacturers.edit', compact('manufacturer'));
            })->name('manufacturers.edit');
        });
        Route::middleware('catalog.feature:catalog_use_actions')->group(function (): void {
            Route::view('actions', 'admin.actions')->name('actions');
            Route::view('actions/create', 'admin.actions.create')->name('actions.create');
            Route::get('actions/{action}/edit', function (CatalogAction $action) {
                return view('admin.actions.edit', compact('action'));
            })->name('actions.edit');
        });
        Route::view('users', 'admin.users.index')->name('users');
        Route::view('users/groups', 'admin.users.groups')->name('users.groups');
        Route::view('users/access', 'admin.users.access')->name('users.access');
        Route::view('users/activity', 'admin.users.activity')->name('users.activity');
        Route::middleware('user.feature:user_loyalty_enabled')->group(function (): void {
            Route::view('users/loyalty', 'admin.users.loyalty')->name('users.loyalty');
        });
        Route::get('users/{user}/show', function (User $user) {
            $current = auth()->user();
            abort_unless($current && ($current->isA('superadmin') || $current->can('users.list.view')), 403);

            $user->load(['roles:id,name,title', 'profile', 'addresses', 'customerGroups:id,name']);
            $adminActivity = Activity::query()
                ->where('subject_type', User::class)
                ->where('subject_id', $user->id)
                ->latest('id')
                ->limit(12)
                ->get();
            $trackingEvents = UserTrackingEvent::query()
                ->where('user_id', $user->id)
                ->latest('occurred_at')
                ->limit(12)
                ->get();

            $loyaltyEnabled = (bool) app(SystemSettingsService::class)->get(
                'user_loyalty_enabled',
                (bool) config('user_features.flags.user_loyalty_enabled', true)
            );

            $loyaltyStats = [
                'balance' => 0,
                'entries' => 0,
                'earned' => 0,
                'spent' => 0,
            ];
            $loyaltyEntries = collect();
            $recentOrders = collect();

            if ($loyaltyEnabled) {
                $loyaltyQuery = LoyaltyTransaction::query()->where('user_id', $user->id);
                $loyaltyStats = [
                    'balance' => (int) (clone $loyaltyQuery)->sum('points'),
                    'entries' => (int) (clone $loyaltyQuery)->count(),
                    'earned' => (int) (clone $loyaltyQuery)->where('points', '>', 0)->sum('points'),
                    'spent' => abs((int) (clone $loyaltyQuery)->where('points', '<', 0)->sum('points')),
                ];
                $loyaltyEntries = (clone $loyaltyQuery)
                    ->with(['order:id,order_number,grand_total,currency_code', 'creator:id,name'])
                    ->latest('id')
                    ->limit(10)
                    ->get();
            }

            $recentOrders = SalesOrder::query()
                ->where('user_id', $user->id)
                ->with('status:id,name,color')
                ->latest('id')
                ->limit(10)
                ->get(['id', 'order_number', 'status_id', 'grand_total', 'currency_code', 'created_at']);

            return view('admin.users.show', compact(
                'user',
                'adminActivity',
                'trackingEvents',
                'loyaltyEnabled',
                'loyaltyStats',
                'loyaltyEntries',
                'recentOrders'
            ));
        })->name('users.show');
        Route::get('users/{user}/edit', function (User $user) {
            return view('admin.users.edit', compact('user'));
        })->name('users.edit');
        Route::view('profile', 'profile')->name('profile');

        Route::prefix('content')
            ->as('content.')
            ->group(function (): void {
                Route::redirect('/', '/admin/content/blocks')->name('index');
                Route::middleware('catalog.feature:catalog_use_blog')->group(function (): void {
                    Route::view('blog', 'admin.content.blog.index')->name('blog.index');
                    Route::view('blog/create', 'admin.content.blog.create')->name('blog.create');
                    Route::get('blog/{post}/edit', function (BlogPost $post) {
                        return view('admin.content.blog.edit', compact('post'));
                    })->name('blog.edit');
                });
                Route::view('pages', 'admin.content.pages.index')->name('pages.index');
                Route::view('pages/create', 'admin.content.pages.create')->name('pages.create');
                Route::get('pages/{page}/edit', function (InfoPage $page) {
                    return view('admin.content.pages.edit', compact('page'));
                })->name('pages.edit');
                Route::view('faqs', 'admin.content.faqs.index')->name('faqs.index');
                Route::view('faqs/create', 'admin.content.faqs.create')->name('faqs.create');
                Route::get('faqs/{faq}/edit', function (Faq $faq) {
                    return view('admin.content.faqs.edit', compact('faq'));
                })->name('faqs.edit');
                Route::view('comments', 'admin.content.comments.index')->name('comments.index');
                Route::view('blocks', 'admin.content.blocks.index')->name('blocks');
                Route::view('blocks/create', 'admin.content.blocks.create')->name('blocks.create');
                Route::get('blocks/{block}/edit', function (ContentBlock $block) {
                    return view('admin.content.blocks.edit', compact('block'));
                })->name('blocks.edit');

                Route::view('slots', 'admin.content.slots.index')->name('slots');
                Route::view('slots/create', 'admin.content.slots.create')->name('slots.create');
                Route::get('slots/{slot}/edit', function (ContentBlockSlot $slot) {
                    return view('admin.content.slots.edit', compact('slot'));
                })->name('slots.edit');
            });

        Route::prefix('settings')
            ->as('settings.')
            ->group(function (): void {
                Route::redirect('/', '/admin/settings/local/payment-methods')->name('index');

                Route::get('local/{resource}', function (string $resource) {
                    return view('admin.settings.local.resource', compact('resource'));
                })
                    ->where('resource', 'payment-methods|shipping-methods|geo-zones|geo-zone-countries|currencies|tax-rates|order-statuses|languages')
                    ->name('local.resource');

                Route::get('system/runtime', function () {
                    $current = auth()->user();
                    abort_unless(
                        $current && ($current->isA('superadmin') || $current->can('settings.system.runtime.manage')),
                        403
                    );

                    return view('admin.settings.system.runtime');
                })->name('system.runtime');
                Route::view('system/admin-appearance-controls', 'admin.settings.system.admin-appearance-controls')->name('system.admin-appearance-controls');
                Route::view('system/catalog-features', 'admin.settings.system.catalog-features')->name('system.catalog-features');
                Route::middleware('catalog.feature:catalog_use_api')->get('api', function () {
                    $current = auth()->user();
                    abort_unless(
                        $current && ($current->isA('superadmin') || $current->can('settings.api.manage')),
                        403
                    );

                    return view('admin.settings.api.index');
                })->name('api.index');
                Route::view('user', 'admin.settings.user.index')->name('user.index');
            });

        Route::prefix('system')
            ->as('system.')
            ->group(function (): void {
                Route::post('cache/clear', [SystemToolsController::class, 'clearCache'])->name('cache.clear');
                Route::post('maintenance/on', [SystemToolsController::class, 'maintenanceOn'])->name('maintenance.on');
                Route::post('maintenance/off', [SystemToolsController::class, 'maintenanceOff'])->name('maintenance.off');
            });

        Route::prefix('ai')
            ->as('ai.')
            ->group(function (): void {
                Route::post('preview', [AdminAiController::class, 'preview'])->name('preview');
                Route::post('execute', [AdminAiController::class, 'execute'])->name('execute');
            });
    });

Route::redirect('profile', '/admin/profile')
    ->middleware(['auth', 'verified'])
    ->name('profile');

Route::post('logout', function (Request $request) {
    Auth::guard('web')->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

require __DIR__.'/auth.php';

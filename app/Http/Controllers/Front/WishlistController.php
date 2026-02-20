<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Front\Concerns\ResolvesFrontendView;
use App\Models\Catalog\Product\Product;
use App\Services\Front\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    use ResolvesFrontendView;

    public function __construct(
        private readonly WishlistService $wishlist
    ) {
    }

    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $fallbackLocale = (string) config('app.locale');

        return view($this->frontendView($request, 'wishlist.index'), [
            'products' => $this->wishlist->products($locale),
            'locale' => $locale,
            'fallbackLocale' => $fallbackLocale,
        ]);
    }

    public function store(Product $product): RedirectResponse
    {
        $ok = $this->wishlist->add($product);

        return back()->with(
            'status',
            $ok ? __('ui.wishlist.status.added') : __('ui.wishlist.status.unavailable')
        );
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->wishlist->remove((int) $product->id);

        return back()->with('status', __('ui.wishlist.status.removed'));
    }

    public function toggle(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $productId = (int) $product->id;
        $isActive = $this->wishlist->has($productId);

        if ($isActive) {
            $this->wishlist->remove($productId);
            $active = false;
            $message = __('ui.wishlist.status.removed');
            $ok = true;
        } else {
            $added = $this->wishlist->add($product);
            $active = $added;
            $message = $added ? __('ui.wishlist.status.added') : __('ui.wishlist.status.unavailable');
            $ok = $added;
        }

        $payload = [
            'ok' => $ok,
            'active' => $active,
            'count' => (int) ($this->wishlist->summary()['item_count'] ?? 0),
            'message' => $message,
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($payload);
        }

        return back()->with('status', $message);
    }
}

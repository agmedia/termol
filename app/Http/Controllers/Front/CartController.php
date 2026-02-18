<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Front\Concerns\ResolvesFrontendView;
use App\Models\Catalog\Product\Product;
use App\Services\Front\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    use ResolvesFrontendView;

    public function __construct(
        private readonly CartService $cart
    ) {
    }

    public function index(Request $request): View
    {
        return view($this->frontendView($request, 'cart.index'), [
            'lines' => $this->cart->lines(),
            'summary' => $this->cart->summary(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        $product = Product::query()
            ->where('is_active', true)
            ->findOrFail((int) $validated['product_id']);

        $ok = $this->cart->add($product, (int) ($validated['quantity'] ?? 1));

        return back()->with(
            'status',
            $ok ? 'Product added to cart.' : 'Product is currently out of stock.'
        );
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $ok = $this->cart->set($product, (int) $validated['quantity']);

        return back()->with(
            'status',
            $ok ? 'Cart updated.' : 'Quantity adjusted because product is unavailable.'
        );
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->cart->remove((int) $product->id);

        return back()->with('status', 'Item removed from cart.');
    }

    public function clear(): RedirectResponse
    {
        $this->cart->clear();

        return redirect()->route('cart.index')->with('status', 'Cart cleared.');
    }
}

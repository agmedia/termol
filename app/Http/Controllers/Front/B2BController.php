<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Front\Concerns\ResolvesFrontendView;
use App\Models\Catalog\Product\Product;
use App\Models\Catalog\Product\ProductOptionValue;
use App\Models\Sales\Order\Order;
use App\Models\Sales\Order\OrderItem;
use App\Models\User\B2BAccount;
use App\Services\Front\CartService;
use App\Services\Pricing\ProductPricePresentationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class B2BController extends Controller
{
    use ResolvesFrontendView;

    public function __construct(
        private readonly CartService $cart,
        private readonly ProductPricePresentationService $prices,
    ) {}

    public function quickOrder(Request $request): View
    {
        $account = $this->approvedAccount($request);
        $user = $request->user();

        $frequentIds = OrderItem::query()
            ->select('product_id')
            ->selectRaw('SUM(quantity) as ordered_quantity')
            ->whereNotNull('product_id')
            ->whereHas('order', fn ($query) => $query->where('user_id', $user->getKey()))
            ->groupBy('product_id')
            ->orderByDesc('ordered_quantity')
            ->limit(12)
            ->pluck('product_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $favoriteIds = $user->wishlistItems()
            ->latest('id')
            ->limit(12)
            ->pluck('product_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        return view($this->frontendView($request, 'account.b2b-quick-order'), [
            'b2bAccount' => $account,
            'frequentProducts' => $this->productSuggestions($frequentIds, $user),
            'favoriteProducts' => $this->productSuggestions($favoriteIds, $user),
        ]);
    }

    public function storeQuickOrder(Request $request): RedirectResponse
    {
        $this->approvedAccount($request);

        $validated = $request->validate([
            'items' => ['required', 'array', 'max:100'],
            'items.*.identifier' => ['nullable', 'string', 'max:191'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        $items = collect($validated['items'])
            ->map(static fn (array $item): array => [
                'identifier' => trim((string) ($item['identifier'] ?? '')),
                'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
            ])
            ->filter(static fn (array $item): bool => $item['identifier'] !== '')
            ->values();

        if ($items->isEmpty()) {
            return back()
                ->withErrors(['items' => __('Unesite barem jednu šifru, SKU ili barkod.')])
                ->withInput();
        }

        $added = 0;
        $skipped = [];

        foreach ($items as $item) {
            [$product, $optionValueId] = $this->resolveIdentifier($item['identifier']);

            if (! $product) {
                $skipped[] = $item['identifier'].' — '.__('artikl nije pronađen');

                continue;
            }

            if (! $this->cart->add($product, $item['quantity'], $optionValueId)) {
                $skipped[] = $item['identifier'].' — '.__('nije dostupan ili zahtijeva odabir varijante');

                continue;
            }

            $added++;
        }

        if ($added === 0) {
            return back()
                ->with('warning', __('Nijedan artikl nije dodan.').' '.implode('; ', $skipped))
                ->withInput();
        }

        $response = redirect()
            ->route('cart.index')
            ->with('status', trans_choice(
                ':count stavka dodana je u košaricu.|:count stavke dodane su u košaricu.|:count stavki dodano je u košaricu.',
                $added,
                ['count' => $added],
            ));

        if ($skipped !== []) {
            $response->with('warning', __('Preskočeno:').' '.implode('; ', $skipped));
        }

        return $response;
    }

    public function reorder(Request $request, string $orderNumber): RedirectResponse
    {
        $this->approvedAccount($request);

        $order = Order::query()
            ->where('user_id', $request->user()->getKey())
            ->where('order_number', $orderNumber)
            ->with([
                'items.product',
                'items.productOptionValue',
            ])
            ->firstOrFail();

        $added = 0;
        $skipped = [];

        foreach ($order->items as $item) {
            $product = $item->product;
            if (! $product || ! $product->is_active) {
                $skipped[] = $item->name;

                continue;
            }

            $optionValueId = $item->productOptionValue?->is_active
                ? (int) $item->product_option_value_id
                : null;

            if (! $this->cart->add($product, max(1, (int) $item->quantity), $optionValueId)) {
                $skipped[] = $item->name;

                continue;
            }

            $added++;
        }

        if ($added === 0) {
            return back()->with('warning', __('Artikle iz ove narudžbe trenutno nije moguće ponovno dodati.'));
        }

        $response = redirect()
            ->route('cart.index')
            ->with('status', __('Dostupni artikli iz narudžbe :number dodani su u košaricu.', ['number' => $order->order_number]));

        if ($skipped !== []) {
            $response->with('warning', __('Nedostupni artikli su preskočeni:').' '.implode(', ', $skipped));
        }

        return $response;
    }

    private function approvedAccount(Request $request): B2BAccount
    {
        $request->user()->loadMissing('b2bAccount');
        $account = $request->user()->b2bAccount;

        abort_unless($account && $account->contractIsActive(), 403, __('B2B pristup nije aktivan.'));

        return $account;
    }

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, array{product:Product,name:string,identifier:string,price:array<string,mixed>}>
     */
    private function productSuggestions(array $ids, $user): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $locale = (string) app()->getLocale();
        $fallbackLocale = (string) config('app.locale');
        $positions = array_flip($ids);

        return Product::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->with([
                'taxRate',
                'categories:id',
                'translations' => fn ($query) => $query->whereIn('locale', [$locale, $fallbackLocale]),
            ])
            ->get()
            ->sortBy(static fn (Product $product): int => $positions[(int) $product->getKey()] ?? 9999)
            ->map(function (Product $product) use ($locale, $fallbackLocale, $user): array {
                $translation = $product->translations->firstWhere('locale', $locale)
                    ?? $product->translations->firstWhere('locale', $fallbackLocale)
                    ?? $product->translations->first();

                return [
                    'product' => $product,
                    'name' => (string) ($translation?->name ?: $product->code),
                    'identifier' => (string) ($product->sku ?: $product->code),
                    'price' => $this->prices->forProduct($product, $user),
                ];
            })
            ->values();
    }

    /**
     * @return array{0:Product|null,1:int|null}
     */
    private function resolveIdentifier(string $identifier): array
    {
        $identifier = trim($identifier);

        $product = Product::query()
            ->where('is_active', true)
            ->where(function ($query) use ($identifier): void {
                $query
                    ->where('code', $identifier)
                    ->orWhere('sku', $identifier)
                    ->orWhere('barcode', $identifier);
            })
            ->first();

        if ($product) {
            return [$product, null];
        }

        $option = ProductOptionValue::query()
            ->where('is_active', true)
            ->where('sku', $identifier)
            ->with('product')
            ->first();

        if ($option?->product?->is_active) {
            return [$option->product, (int) $option->getKey()];
        }

        return [null, null];
    }
}

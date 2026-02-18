<?php

namespace App\Services\Front;

use App\Models\Catalog\Product\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    private const SESSION_KEY = 'front.cart.items';

    /**
     * @return array<int, int>
     */
    public function raw(): array
    {
        $items = Session::get(self::SESSION_KEY, []);

        if (! is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $productId => $quantity) {
            $id = (int) $productId;
            $qty = (int) $quantity;

            if ($id <= 0 || $qty <= 0) {
                continue;
            }

            $normalized[$id] = $qty;
        }

        return $normalized;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function lines(?string $locale = null): Collection
    {
        $items = $this->raw();

        if ($items === []) {
            return collect();
        }

        $locale = $locale ?: app()->getLocale();
        $fallbackLocale = (string) config('app.locale');

        $products = Product::query()
            ->whereIn('id', array_keys($items))
            ->where('is_active', true)
            ->with([
                'translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
                'manufacturer.translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
            ])
            ->get()
            ->keyBy('id');

        $lines = collect();

        foreach ($items as $productId => $quantity) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }

            $qty = max(1, min($quantity, 999));
            $unitPrice = (float) $product->base_price;
            $lineTotal = round($unitPrice * $qty, 2);
            $translation = $product->translations->firstWhere('locale', $locale)
                ?? $product->translations->firstWhere('locale', $fallbackLocale);

            $lines->push([
                'product' => $product,
                'translation' => $translation,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);
        }

        return $lines->values();
    }

    /**
     * @return array{line_count:int,item_qty:int,subtotal:float}
     */
    public function summary(?string $locale = null): array
    {
        $lines = $this->lines($locale);

        return [
            'line_count' => $lines->count(),
            'item_qty' => (int) $lines->sum('quantity'),
            'subtotal' => round((float) $lines->sum('line_total'), 2),
        ];
    }

    public function hasItems(): bool
    {
        return $this->summary()['line_count'] > 0;
    }

    public function add(Product $product, int $quantity = 1): bool
    {
        if (! $product->is_active) {
            return false;
        }

        $items = $this->raw();
        $existing = (int) ($items[$product->id] ?? 0);
        $requested = max(1, min($quantity, 999));
        $target = $existing + $requested;

        $stock = (int) $product->stock_qty;
        if ($stock <= 0) {
            return false;
        }

        $items[$product->id] = min($target, $stock);
        Session::put(self::SESSION_KEY, $items);

        return true;
    }

    public function set(Product $product, int $quantity): bool
    {
        $items = $this->raw();

        if ($quantity <= 0) {
            unset($items[$product->id]);
            Session::put(self::SESSION_KEY, $items);

            return true;
        }

        if (! $product->is_active) {
            return false;
        }

        $stock = (int) $product->stock_qty;
        if ($stock <= 0) {
            unset($items[$product->id]);
            Session::put(self::SESSION_KEY, $items);

            return false;
        }

        $items[$product->id] = min(max(1, $quantity), $stock);
        Session::put(self::SESSION_KEY, $items);

        return true;
    }

    public function remove(int $productId): void
    {
        $items = $this->raw();
        unset($items[$productId]);
        Session::put(self::SESSION_KEY, $items);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}

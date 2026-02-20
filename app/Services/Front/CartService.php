<?php

namespace App\Services\Front;

use App\Models\Catalog\Product\ProductOptionValue;
use App\Models\Catalog\Product\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    private const SESSION_KEY = 'front.cart.items';

    /**
     * @return array<string, array{product_id:int,product_option_value_id:int|null,quantity:int}>
     */
    public function raw(): array
    {
        $items = Session::get(self::SESSION_KEY, []);

        if (! is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $key => $item) {
            if (is_array($item) && isset($item['product_id'])) {
                $productId = (int) ($item['product_id'] ?? 0);
                $optionValueId = (int) ($item['product_option_value_id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);
            } else {
                // Backward compatibility with previous storage format: [product_id => quantity]
                $productId = (int) $key;
                $optionValueId = 0;
                $quantity = (int) $item;
            }

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $resolvedKey = $this->lineKey($productId, $optionValueId > 0 ? $optionValueId : null);
            $normalized[$resolvedKey] = [
                'product_id' => $productId,
                'product_option_value_id' => $optionValueId > 0 ? $optionValueId : null,
                'quantity' => $quantity,
            ];
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

        $productIds = array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['product_id'],
            $items
        )));
        $productOptionValueIds = array_values(array_unique(array_filter(array_map(
            static fn (array $row): int => (int) ($row['product_option_value_id'] ?? 0),
            $items
        ))));

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->with([
                'translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
                'manufacturer.translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
            ])
            ->get()
            ->keyBy('id');

        $optionRows = ProductOptionValue::query()
            ->whereIn('id', $productOptionValueIds)
            ->with([
                'optionValue.translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
                'parentOptionValue.translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
            ])
            ->get()
            ->keyBy('id');

        $lines = collect();

        foreach ($items as $key => $item) {
            $productId = (int) $item['product_id'];
            $quantity = (int) $item['quantity'];
            $optionValueId = (int) ($item['product_option_value_id'] ?? 0);

            $product = $products->get($productId);
            if (! $product) {
                continue;
            }

            $optionRow = $optionValueId > 0 ? $optionRows->get($optionValueId) : null;
            if ($optionRow && (int) $optionRow->product_id !== $productId) {
                $optionRow = null;
            }

            $maxStock = $optionRow ? (int) $optionRow->stock_qty : (int) $product->stock_qty;
            if ($maxStock <= 0) {
                continue;
            }

            $qty = min(max(1, $quantity), $maxStock);
            $unitPrice = $optionRow && $optionRow->price_override !== null
                ? (float) $optionRow->price_override
                : (float) $product->base_price;
            $lineTotal = round($unitPrice * $qty, 2);
            $translation = $product->translations->firstWhere('locale', $locale)
                ?? $product->translations->firstWhere('locale', $fallbackLocale);
            $optionLabel = $this->optionLabel($optionRow, $locale, $fallbackLocale);

            $lines->push([
                'key' => $key,
                'product' => $product,
                'translation' => $translation,
                'product_option_value_id' => $optionRow?->id,
                'option_label' => $optionLabel,
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

    public function add(Product $product, int $quantity = 1, ?int $productOptionValueId = null): bool
    {
        if (! $product->is_active) {
            return false;
        }

        $optionRow = $this->resolveProductOptionValue($product, $productOptionValueId);
        if ($this->requiresOptionSelection($product) && ! $optionRow) {
            return false;
        }

        $items = $this->raw();
        $lineKey = $this->lineKey($product->id, $optionRow?->id);
        $existing = (int) ($items[$lineKey]['quantity'] ?? 0);
        $requested = max(1, min($quantity, 999));
        $target = $existing + $requested;

        $stock = $optionRow ? (int) $optionRow->stock_qty : (int) $product->stock_qty;
        if ($stock <= 0) {
            return false;
        }

        $items[$lineKey] = [
            'product_id' => (int) $product->id,
            'product_option_value_id' => $optionRow?->id,
            'quantity' => min($target, $stock),
        ];
        Session::put(self::SESSION_KEY, $items);

        return true;
    }

    public function set(Product $product, int $quantity, ?int $productOptionValueId = null): bool
    {
        $items = $this->raw();
        $lineKey = $this->lineKey((int) $product->id, $productOptionValueId);

        if ($quantity <= 0) {
            unset($items[$lineKey]);
            Session::put(self::SESSION_KEY, $items);

            return true;
        }

        if (! $product->is_active) {
            return false;
        }

        $optionRow = $this->resolveProductOptionValue($product, $productOptionValueId);
        $stock = $optionRow ? (int) $optionRow->stock_qty : (int) $product->stock_qty;
        if ($stock <= 0) {
            unset($items[$lineKey]);
            Session::put(self::SESSION_KEY, $items);

            return false;
        }

        $items[$lineKey] = [
            'product_id' => (int) $product->id,
            'product_option_value_id' => $optionRow?->id,
            'quantity' => min(max(1, $quantity), $stock),
        ];
        Session::put(self::SESSION_KEY, $items);

        return true;
    }

    public function remove(int $productId, ?int $productOptionValueId = null): void
    {
        $items = $this->raw();

        if ($productOptionValueId !== null) {
            unset($items[$this->lineKey($productId, $productOptionValueId)]);
        } else {
            foreach ($items as $key => $item) {
                if ((int) ($item['product_id'] ?? 0) === $productId) {
                    unset($items[$key]);
                }
            }
        }

        Session::put(self::SESSION_KEY, $items);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    private function lineKey(int $productId, ?int $productOptionValueId): string
    {
        return $productId.':'.(int) ($productOptionValueId ?? 0);
    }

    private function resolveProductOptionValue(Product $product, ?int $productOptionValueId): ?ProductOptionValue
    {
        $id = (int) ($productOptionValueId ?? 0);
        if ($id <= 0) {
            return null;
        }

        return ProductOptionValue::query()
            ->where('id', $id)
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->first();
    }

    private function requiresOptionSelection(Product $product): bool
    {
        return ProductOptionValue::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->exists();
    }

    private function optionLabel(?ProductOptionValue $optionRow, string $locale, string $fallbackLocale): ?string
    {
        if (! $optionRow) {
            return null;
        }

        $child = $optionRow->optionValue?->translations?->firstWhere('locale', $locale)
            ?? $optionRow->optionValue?->translations?->firstWhere('locale', $fallbackLocale)
            ?? $optionRow->optionValue?->translations?->first();
        $parent = $optionRow->parentOptionValue?->translations?->firstWhere('locale', $locale)
            ?? $optionRow->parentOptionValue?->translations?->firstWhere('locale', $fallbackLocale)
            ?? $optionRow->parentOptionValue?->translations?->first();

        $childLabel = trim((string) ($child?->name ?? $optionRow->optionValue?->code ?? ''));
        $parentLabel = trim((string) ($parent?->name ?? $optionRow->parentOptionValue?->code ?? ''));

        if ($parentLabel !== '' && $childLabel !== '') {
            return $parentLabel.' / '.$childLabel;
        }

        if ($childLabel !== '') {
            return $childLabel;
        }

        return $parentLabel !== '' ? $parentLabel : null;
    }
}

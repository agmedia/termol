<?php

namespace App\Services\Front;

use App\Models\Catalog\Product\Product;
use App\Models\Sales\Order\Order;
use App\Models\Sales\Order\OrderHistory;
use App\Models\Sales\Order\OrderItem;
use App\Models\Sales\Order\OrderTotal;
use App\Models\Settings\Local\Currency;
use App\Models\Settings\Local\OrderStatus;
use App\Models\Settings\Local\PaymentMethod;
use App\Models\Settings\Local\ShippingMethod;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cart
    ) {
    }

    /**
     * @return Collection<int, PaymentMethod>
     */
    public function availablePaymentMethods(float $subtotal): Collection
    {
        return PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (PaymentMethod $method) => $this->subtotalFits($subtotal, $method->min_subtotal, $method->max_subtotal))
            ->values();
    }

    /**
     * @return Collection<int, ShippingMethod>
     */
    public function availableShippingMethods(float $subtotal): Collection
    {
        return ShippingMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (ShippingMethod $method) => $this->subtotalFits($subtotal, $method->min_subtotal, $method->max_subtotal))
            ->values();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function placeOrder(array $payload, ?User $user = null): Order
    {
        $lines = $this->cart->lines();

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        $subtotal = round((float) $lines->sum('line_total'), 2);

        /** @var ShippingMethod|null $shippingMethod */
        $shippingMethod = $this->availableShippingMethods($subtotal)
            ->firstWhere('code', (string) ($payload['shipping_method_code'] ?? ''));

        /** @var PaymentMethod|null $paymentMethod */
        $paymentMethod = $this->availablePaymentMethods($subtotal)
            ->firstWhere('code', (string) ($payload['payment_method_code'] ?? ''));

        if (! $shippingMethod) {
            throw ValidationException::withMessages([
                'shipping_method_code' => 'Selected shipping method is not available.',
            ]);
        }

        if (! $paymentMethod) {
            throw ValidationException::withMessages([
                'payment_method_code' => 'Selected payment method is not available.',
            ]);
        }

        $shippingTotal = $this->resolveShippingTotal($shippingMethod, $subtotal);
        $paymentFeeTotal = $this->resolvePaymentFeeTotal($paymentMethod, $subtotal);
        $grandTotal = round($subtotal + $shippingTotal + $paymentFeeTotal, 2);

        /** @var Currency|null $currency */
        $currency = Currency::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        /** @var OrderStatus|null $status */
        $status = OrderStatus::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        $locale = (string) app()->getLocale();

        return DB::transaction(function () use (
            $payload,
            $user,
            $lines,
            $subtotal,
            $shippingMethod,
            $shippingTotal,
            $paymentMethod,
            $paymentFeeTotal,
            $grandTotal,
            $currency,
            $status,
            $locale
        ): Order {
            $customerFirst = (string) ($payload['customer_first_name'] ?? '');
            $customerLast = (string) ($payload['customer_last_name'] ?? '');
            $customerName = trim($customerFirst.' '.$customerLast);

            $order = Order::query()->create([
                'order_number' => $this->nextOrderNumber(),
                'status_id' => $status?->id,
                'user_id' => $user?->id,
                'source' => 'web',
                'locale' => $locale,
                'currency_code' => (string) ($currency?->code ?? 'EUR'),
                'currency_rate' => (float) ($currency?->exchange_rate ?? 1),

                'customer_name' => $customerName !== '' ? $customerName : (string) ($user?->name ?? 'Guest Customer'),
                'customer_email' => (string) ($payload['customer_email'] ?? ''),
                'customer_phone' => (string) ($payload['customer_phone'] ?? ''),

                'billing_first_name' => (string) ($payload['billing_first_name'] ?? $customerFirst),
                'billing_last_name' => (string) ($payload['billing_last_name'] ?? $customerLast),
                'billing_company' => (string) ($payload['billing_company'] ?? ''),
                'billing_oib' => (string) ($payload['billing_oib'] ?? ''),
                'billing_vat_id' => (string) ($payload['billing_vat_id'] ?? ''),
                'billing_address_line_1' => (string) ($payload['billing_address_line_1'] ?? ''),
                'billing_address_line_2' => (string) ($payload['billing_address_line_2'] ?? ''),
                'billing_postal_code' => (string) ($payload['billing_postal_code'] ?? ''),
                'billing_city' => (string) ($payload['billing_city'] ?? ''),
                'billing_state' => (string) ($payload['billing_state'] ?? ''),
                'billing_country_code' => (string) ($payload['billing_country_code'] ?? 'HR'),

                'shipping_first_name' => (string) ($payload['shipping_first_name'] ?? $customerFirst),
                'shipping_last_name' => (string) ($payload['shipping_last_name'] ?? $customerLast),
                'shipping_company' => (string) ($payload['shipping_company'] ?? ''),
                'shipping_oib' => (string) ($payload['shipping_oib'] ?? ''),
                'shipping_vat_id' => (string) ($payload['shipping_vat_id'] ?? ''),
                'shipping_address_line_1' => (string) ($payload['shipping_address_line_1'] ?? ''),
                'shipping_address_line_2' => (string) ($payload['shipping_address_line_2'] ?? ''),
                'shipping_postal_code' => (string) ($payload['shipping_postal_code'] ?? ''),
                'shipping_city' => (string) ($payload['shipping_city'] ?? ''),
                'shipping_state' => (string) ($payload['shipping_state'] ?? ''),
                'shipping_country_code' => (string) ($payload['shipping_country_code'] ?? 'HR'),

                'payment_method_code' => (string) $paymentMethod->code,
                'payment_method_name' => (string) $paymentMethod->name,
                'shipping_method_code' => (string) $shippingMethod->code,
                'shipping_method_name' => (string) $shippingMethod->name,

                'item_qty' => (int) $lines->sum('quantity'),
                'subtotal' => $subtotal,
                'shipping_total' => $shippingTotal,
                'payment_fee_total' => $paymentFeeTotal,
                'discount_total' => 0,
                'tax_total' => 0,
                'grand_total' => $grandTotal,

                'customer_note' => (string) ($payload['customer_note'] ?? ''),
                'payload' => [
                    'placed_from' => 'frontend_checkout',
                ],
                'placed_at' => now(),
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]);

            $index = 0;
            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];
                $translation = $line['translation'];

                $quantity = (int) $line['quantity'];
                $unitPrice = (float) $line['unit_price'];
                $lineTotal = (float) $line['line_total'];

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_option_value_id' => null,
                    'sku' => $product->sku,
                    'code' => $product->code,
                    'name' => (string) ($translation?->name ?? $product->code),
                    'unit_price' => $unitPrice,
                    'discount_amount' => 0,
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                    'sort_order' => $index++,
                    'payload' => [
                        'product_slug' => (string) ($translation?->slug ?? ''),
                    ],
                ]);

                if ((int) $product->stock_qty > 0) {
                    $nextStock = max(0, ((int) $product->stock_qty) - $quantity);
                    $product->forceFill(['stock_qty' => $nextStock])->save();
                }
            }

            OrderTotal::query()->create([
                'order_id' => $order->id,
                'code' => 'subtotal',
                'title' => 'Subtotal',
                'value' => $subtotal,
                'sort_order' => 100,
            ]);

            OrderTotal::query()->create([
                'order_id' => $order->id,
                'code' => 'shipping',
                'title' => 'Shipping',
                'value' => $shippingTotal,
                'sort_order' => 200,
            ]);

            OrderTotal::query()->create([
                'order_id' => $order->id,
                'code' => 'payment_fee',
                'title' => 'Payment Fee',
                'value' => $paymentFeeTotal,
                'sort_order' => 300,
            ]);

            OrderTotal::query()->create([
                'order_id' => $order->id,
                'code' => 'grand_total',
                'title' => 'Grand Total',
                'value' => $grandTotal,
                'sort_order' => 900,
            ]);

            OrderHistory::query()->create([
                'order_id' => $order->id,
                'from_status_id' => null,
                'to_status_id' => $status?->id,
                'changed_by' => $user?->id,
                'comment' => 'Order placed from storefront checkout.',
            ]);

            return $order;
        });
    }

    private function subtotalFits(float $subtotal, mixed $min, mixed $max): bool
    {
        $minVal = is_numeric($min) ? (float) $min : null;
        $maxVal = is_numeric($max) ? (float) $max : null;

        if ($minVal !== null && $subtotal < $minVal) {
            return false;
        }

        if ($maxVal !== null && $subtotal > $maxVal) {
            return false;
        }

        return true;
    }

    private function resolveShippingTotal(ShippingMethod $shippingMethod, float $subtotal): float
    {
        $price = (float) $shippingMethod->price;
        $freeOver = is_numeric($shippingMethod->free_over) ? (float) $shippingMethod->free_over : null;

        if ($freeOver !== null && $freeOver >= 0 && $subtotal >= $freeOver) {
            return 0.0;
        }

        return round(max(0, $price), 2);
    }

    private function resolvePaymentFeeTotal(PaymentMethod $paymentMethod, float $subtotal): float
    {
        $feeType = (string) ($paymentMethod->fee_type ?? 'fixed');
        $feeValue = (float) ($paymentMethod->fee_value ?? 0);

        if ($feeType === 'percent') {
            return round(($subtotal * max(0, $feeValue)) / 100, 2);
        }

        return round(max(0, $feeValue), 2);
    }

    private function nextOrderNumber(): string
    {
        $datePrefix = now()->format('Ymd');

        for ($attempt = 0; $attempt < 15; $attempt++) {
            $candidate = sprintf('AG-%s-%04d', $datePrefix, random_int(1, 9999));
            $exists = Order::query()->where('order_number', $candidate)->exists();

            if (! $exists) {
                return $candidate;
            }
        }

        return sprintf('AG-%s-%s', $datePrefix, strtoupper((string) str()->random(6)));
    }
}

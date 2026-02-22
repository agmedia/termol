<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Front\Concerns\ResolvesFrontendView;
use App\Models\Sales\Order\Order;
use App\Models\User;
use App\Models\User\UserAddress;
use App\Models\User\UserProfile;
use App\Services\Front\AddressDirectoryService;
use App\Services\Front\CartService;
use App\Services\Front\CheckoutService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    use ResolvesFrontendView;

    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutService $checkout
    ) {
    }

    public function create(Request $request): View|RedirectResponse
    {
        $summary = $this->cart->summary();

        if ($summary['line_count'] === 0) {
            return redirect()->route('cart.index')->with('status', 'Your cart is empty.');
        }

        $user = $request->user();
        $user?->loadMissing(['profile', 'addresses']);

        $billing = $user?->addresses?->firstWhere('type', UserAddress::TYPE_BILLING);
        $shipping = $user?->addresses?->firstWhere('type', UserAddress::TYPE_SHIPPING);

        $addressDirectory = app(AddressDirectoryService::class);

        return view($this->frontendView($request, 'checkout.create'), [
            'lines' => $this->cart->lines(),
            'summary' => $summary,
            'shippingMethods' => $this->checkout->availableShippingMethods((float) ($summary['subtotal_after_discount'] ?? $summary['subtotal'])),
            'paymentMethods' => $this->checkout->availablePaymentMethods((float) ($summary['subtotal_after_discount'] ?? $summary['subtotal'])),
            'countryOptions' => $addressDirectory->countries((string) app()->getLocale()),
            'countyOptions' => $addressDirectory->counties(),
            'placesAssetUrl' => $addressDirectory->placesAssetUrl(),
            'prefill' => [
                'name' => (string) ($user?->name ?? ''),
                'email' => (string) ($user?->email ?? ''),
                'first_name' => (string) ($user?->profile?->first_name ?? ''),
                'last_name' => (string) ($user?->profile?->last_name ?? ''),
                'phone' => (string) ($user?->profile?->phone ?? ''),
                'newsletter_opt_in' => (bool) ($user?->profile?->newsletter_opt_in ?? false),
                'billing' => [
                    'first_name' => (string) ($billing?->first_name ?? ''),
                    'last_name' => (string) ($billing?->last_name ?? ''),
                    'company' => (string) ($billing?->company ?? ''),
                    'oib' => (string) ($billing?->oib ?? ''),
                    'vat_id' => (string) ($billing?->vat_id ?? ''),
                    'address_line_1' => (string) ($billing?->address_line_1 ?? ''),
                    'address_line_2' => (string) ($billing?->address_line_2 ?? ''),
                    'postal_code' => (string) ($billing?->postal_code ?? ''),
                    'city' => (string) ($billing?->city ?? ''),
                    'state' => (string) ($billing?->state ?? ''),
                    'country_code' => (string) ($billing?->country_code ?? 'HR'),
                ],
                'shipping' => [
                    'first_name' => (string) ($shipping?->first_name ?? ''),
                    'last_name' => (string) ($shipping?->last_name ?? ''),
                    'company' => (string) ($shipping?->company ?? ''),
                    'oib' => (string) ($shipping?->oib ?? ''),
                    'vat_id' => (string) ($shipping?->vat_id ?? ''),
                    'address_line_1' => (string) ($shipping?->address_line_1 ?? ''),
                    'address_line_2' => (string) ($shipping?->address_line_2 ?? ''),
                    'postal_code' => (string) ($shipping?->postal_code ?? ''),
                    'city' => (string) ($shipping?->city ?? ''),
                    'state' => (string) ($shipping?->state ?? ''),
                    'country_code' => (string) ($shipping?->country_code ?? 'HR'),
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if (! $this->cart->hasItems()) {
            return redirect()->route('cart.index')->with('status', 'Your cart is empty.');
        }

        $validated = $request->validate([
            'customer_first_name' => ['nullable', 'string', 'max:120'],
            'customer_last_name' => ['nullable', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:191'],
            'customer_phone' => ['required', 'string', 'max:25', 'regex:/^\+?[0-9][0-9\s\-\/]{5,24}$/'],

            'billing_first_name' => ['required', 'string', 'max:120'],
            'billing_last_name' => ['required', 'string', 'max:120'],
            'billing_company' => ['nullable', 'string', 'max:191'],
            'billing_oib' => ['nullable', 'string', 'max:60'],
            'billing_vat_id' => ['nullable', 'string', 'max:60'],
            'billing_address_line_1' => ['required', 'string', 'max:191'],
            'billing_address_line_2' => ['nullable', 'string', 'max:191'],
            'billing_postal_code' => ['required', 'string', 'max:32'],
            'billing_city' => ['required', 'string', 'max:120'],
            'billing_state' => ['required_if:billing_country_code,HR', 'nullable', 'string', 'max:120'],
            'billing_country_code' => ['required', 'string', 'size:2'],

            'use_billing_for_shipping' => ['nullable', 'boolean'],
            'ship_to_different_address' => ['nullable', 'boolean'],

            'shipping_first_name' => ['nullable', 'string', 'max:120'],
            'shipping_last_name' => ['nullable', 'string', 'max:120'],
            'shipping_company' => ['nullable', 'string', 'max:191'],
            'shipping_oib' => ['nullable', 'string', 'max:60'],
            'shipping_vat_id' => ['nullable', 'string', 'max:60'],
            'shipping_address_line_1' => ['nullable', 'string', 'max:191'],
            'shipping_address_line_2' => ['nullable', 'string', 'max:191'],
            'shipping_postal_code' => ['nullable', 'string', 'max:32'],
            'shipping_city' => ['nullable', 'string', 'max:120'],
            'shipping_state' => ['nullable', 'string', 'max:120'],
            'shipping_country_code' => ['nullable', 'string', 'size:2'],

            'shipping_method_code' => ['required', 'string', 'max:60'],
            'payment_method_code' => ['required', 'string', 'max:60'],
            'customer_note' => ['nullable', 'string', 'max:2000'],
            'newsletter_opt_in' => ['nullable', 'boolean'],
            'register_account' => ['nullable', 'boolean'],
            'register_password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'accept_terms' => ['accepted'],
        ], [
            'required' => __('ui.checkout.validation.required'),
            'customer_email.required' => __('ui.checkout.validation.customer_email_required'),
            'customer_email.email' => __('ui.checkout.validation.customer_email_invalid'),
            'customer_email.max' => __('ui.checkout.validation.customer_email_max'),
            'customer_phone.required' => __('ui.checkout.validation.customer_phone_required'),
            'customer_phone.regex' => __('ui.checkout.validation.customer_phone_invalid'),
            'customer_phone.max' => __('ui.checkout.validation.customer_phone_max'),
            'billing_state.required_if' => __('ui.checkout.validation.billing_state_required_hr'),
            'register_password.required' => __('ui.checkout.validation.register_password_required'),
            'register_password.min' => __('ui.checkout.validation.register_password_min'),
            'register_password.confirmed' => __('ui.checkout.validation.register_password_confirmed'),
            'accept_terms.accepted' => __('ui.checkout.validation.accept_terms'),
        ]);

        $shippingFromBilling = array_key_exists('ship_to_different_address', $validated)
            ? ! ((bool) $validated['ship_to_different_address'])
            : (bool) ($validated['use_billing_for_shipping'] ?? false);

        if ($shippingFromBilling) {
            $validated['shipping_first_name'] = $validated['billing_first_name'];
            $validated['shipping_last_name'] = $validated['billing_last_name'];
            $validated['shipping_company'] = $validated['billing_company'] ?? null;
            $validated['shipping_oib'] = $validated['billing_oib'] ?? null;
            $validated['shipping_vat_id'] = $validated['billing_vat_id'] ?? null;
            $validated['shipping_address_line_1'] = $validated['billing_address_line_1'];
            $validated['shipping_address_line_2'] = $validated['billing_address_line_2'] ?? null;
            $validated['shipping_postal_code'] = $validated['billing_postal_code'];
            $validated['shipping_city'] = $validated['billing_city'];
            $validated['shipping_state'] = $validated['billing_state'] ?? null;
            $validated['shipping_country_code'] = $validated['billing_country_code'];
        }

        $validated['customer_first_name'] = (string) ($validated['billing_first_name'] ?? $validated['customer_first_name'] ?? '');
        $validated['customer_last_name'] = (string) ($validated['billing_last_name'] ?? $validated['customer_last_name'] ?? '');

        $checkoutUser = $request->user();
        $registerAccount = (bool) ($validated['register_account'] ?? false);

        if (! $checkoutUser && $registerAccount) {
            $request->validate([
                'customer_email' => ['required', 'email', 'max:191', 'unique:users,email'],
                'register_password' => ['required', 'string', 'min:8', 'confirmed'],
            ], [
                'required' => __('ui.checkout.validation.required'),
                'customer_email.required' => __('ui.checkout.validation.customer_email_required'),
                'customer_email.email' => __('ui.checkout.validation.customer_email_invalid'),
                'customer_email.max' => __('ui.checkout.validation.customer_email_max'),
                'customer_email.unique' => __('ui.checkout.validation.customer_email_unique'),
                'register_password.required' => __('ui.checkout.validation.register_password_required'),
                'register_password.min' => __('ui.checkout.validation.register_password_min'),
                'register_password.confirmed' => __('ui.checkout.validation.register_password_confirmed'),
            ]);

            $checkoutUser = User::query()->create([
                'name' => trim(((string) $validated['customer_first_name']).' '.((string) $validated['customer_last_name'])),
                'email' => (string) $validated['customer_email'],
                'password' => Hash::make((string) $validated['register_password']),
            ]);

            Auth::login($checkoutUser);
            $request->setUserResolver(static fn () => $checkoutUser);
        }

        $order = $this->checkout->placeOrder($validated, $checkoutUser);

        $this->cart->clear();
        $request->session()->put('front.checkout.last_order_id', (int) $order->id);

        $this->syncCustomerData($request, $validated);

        $successUrl = route('checkout.success', ['orderNumber' => $order->order_number]);

        if (
            $request->boolean('_ajax')
            || $request->expectsJson()
            || $request->wantsJson()
            || $request->ajax()
        ) {
            return response()->json([
                'redirect' => $successUrl,
                'status' => 'ok',
            ])->header('X-Checkout-Redirect', $successUrl);
        }

        return redirect()
            ->to($successUrl)
            ->with('status', 'Order created successfully.');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        if (! Auth::attempt([
            'email' => (string) $credentials['email'],
            'password' => (string) $credentials['password'],
        ], (bool) ($credentials['remember'] ?? false))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route('checkout.create');
    }

    public function success(Request $request, string $orderNumber): View
    {
        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->with(['status', 'items', 'totals'])
            ->firstOrFail();

        $allowedBySession = ((int) $request->session()->get('front.checkout.last_order_id', 0)) === (int) $order->id;
        $allowedByUser = $request->user() && ((int) $request->user()->id === (int) $order->user_id);

        abort_unless($allowedBySession || $allowedByUser, 404);

        return view($this->frontendView($request, 'checkout.success'), [
            'order' => $order,
        ]);
    }

    public function successLatest(Request $request): RedirectResponse
    {
        $orderId = (int) $request->session()->get('front.checkout.last_order_id', 0);
        if ($orderId <= 0) {
            return redirect()->route('checkout.create');
        }

        $order = Order::query()->find($orderId);
        if (! $order) {
            return redirect()->route('checkout.create');
        }

        return redirect()->route('checkout.success', ['orderNumber' => $order->order_number]);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function syncCustomerData(Request $request, array $validated): void
    {
        $user = $request->user();
        if (! $user) {
            return;
        }

        $user->forceFill([
            'name' => trim(((string) $validated['customer_first_name']).' '.((string) $validated['customer_last_name'])),
            'email' => (string) $validated['customer_email'],
        ])->save();

        UserProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => (string) $validated['customer_first_name'],
                'last_name' => (string) $validated['customer_last_name'],
                'phone' => (string) ($validated['customer_phone'] ?? ''),
                'newsletter_opt_in' => (bool) ($validated['newsletter_opt_in'] ?? false),
            ]
        );

        UserAddress::query()->updateOrCreate(
            ['user_id' => $user->id, 'type' => UserAddress::TYPE_BILLING],
            [
                'first_name' => (string) $validated['billing_first_name'],
                'last_name' => (string) $validated['billing_last_name'],
                'company' => (string) ($validated['billing_company'] ?? ''),
                'oib' => (string) ($validated['billing_oib'] ?? ''),
                'vat_id' => (string) ($validated['billing_vat_id'] ?? ''),
                'phone' => (string) ($validated['customer_phone'] ?? ''),
                'address_line_1' => (string) $validated['billing_address_line_1'],
                'address_line_2' => (string) ($validated['billing_address_line_2'] ?? ''),
                'postal_code' => (string) $validated['billing_postal_code'],
                'city' => (string) $validated['billing_city'],
                'state' => (string) ($validated['billing_state'] ?? ''),
                'country_code' => (string) $validated['billing_country_code'],
                'is_default' => true,
            ]
        );

        UserAddress::query()->updateOrCreate(
            ['user_id' => $user->id, 'type' => UserAddress::TYPE_SHIPPING],
            [
                'first_name' => (string) ($validated['shipping_first_name'] ?? ''),
                'last_name' => (string) ($validated['shipping_last_name'] ?? ''),
                'company' => (string) ($validated['shipping_company'] ?? ''),
                'oib' => (string) ($validated['shipping_oib'] ?? ''),
                'vat_id' => (string) ($validated['shipping_vat_id'] ?? ''),
                'phone' => (string) ($validated['customer_phone'] ?? ''),
                'address_line_1' => (string) ($validated['shipping_address_line_1'] ?? ''),
                'address_line_2' => (string) ($validated['shipping_address_line_2'] ?? ''),
                'postal_code' => (string) ($validated['shipping_postal_code'] ?? ''),
                'city' => (string) ($validated['shipping_city'] ?? ''),
                'state' => (string) ($validated['shipping_state'] ?? ''),
                'country_code' => (string) ($validated['shipping_country_code'] ?? 'HR'),
                'is_default' => true,
            ]
        );
    }
}

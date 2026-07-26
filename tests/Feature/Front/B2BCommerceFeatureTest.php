<?php

namespace Tests\Feature\Front;

use App\Livewire\Admin\User\B2BAccountManager;
use App\Models\Catalog\Pricing\B2BPriceRule;
use App\Models\Catalog\Product\Product;
use App\Models\Sales\Order\Order;
use App\Models\User;
use App\Models\User\B2BAccount;
use App\Models\User\CustomerGroup;
use App\Services\Pricing\ProductGroupPriceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

class B2BCommerceFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        if ($this->app?->bound('livewire')) {
            $this->app->make('livewire')->flushState();
        }

        parent::tearDown();
    }

    public function test_business_registration_creates_pending_account_profile_and_billing_address(): void
    {
        $response = $this->post(route('front.auth.b2b-register.store'), [
            'first_name' => 'Ana',
            'last_name' => 'Horvat',
            'email' => 'ana@tvrtka.test',
            'phone' => '+385 1 555 123',
            'company_name' => 'Tvrtka d.o.o.',
            'oib' => '12345678901',
            'vat_id' => 'HR12345678901',
            'address_line_1' => 'Ilica 1',
            'address_line_2' => '',
            'postal_code' => '10000',
            'city' => 'Zagreb',
            'country_code' => 'HR',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms_accepted' => '1',
        ]);

        $user = User::query()->where('email', 'ana@tvrtka.test')->firstOrFail();

        $response->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('b2b_accounts', [
            'user_id' => $user->id,
            'status' => B2BAccount::STATUS_PENDING,
            'company_name' => 'Tvrtka d.o.o.',
            'oib' => '12345678901',
            'customer_group_id' => null,
            'erp_customer_id' => null,
        ]);
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'company' => 'Tvrtka d.o.o.',
            'oib' => '12345678901',
        ]);
        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $user->id,
            'type' => 'billing',
            'company' => 'Tvrtka d.o.o.',
            'city' => 'Zagreb',
        ]);
        $this->assertCount(0, $user->customerGroups);
    }

    public function test_admin_can_approve_request_assign_group_and_store_future_erp_link(): void
    {
        $admin = $this->makeAdmin();
        $customer = User::factory()->create();
        $group = $this->makeGroup('b2b-standard');
        $account = $this->makeB2BAccount($customer, B2BAccount::STATUS_PENDING);

        Livewire::actingAs($admin)
            ->test(B2BAccountManager::class)
            ->call('selectAccount', $account->id)
            ->set('form.status', B2BAccount::STATUS_APPROVED)
            ->set('form.customer_group_id', $group->id)
            ->set('form.erp_customer_id', 'ERP-K-1024')
            ->set('form.erp_company_code', 'TERMOL-HR')
            ->set('form.contract_number', 'UG-2026-44')
            ->set('form.payment_terms_days', 30)
            ->set('form.purchase_order_required', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('b2b_accounts', [
            'id' => $account->id,
            'status' => B2BAccount::STATUS_APPROVED,
            'customer_group_id' => $group->id,
            'erp_customer_id' => 'ERP-K-1024',
            'contract_number' => 'UG-2026-44',
            'payment_terms_days' => 30,
            'purchase_order_required' => true,
            'reviewed_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('customer_group_user', [
            'user_id' => $customer->id,
            'customer_group_id' => $group->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.b2b'))
            ->assertOk()
            ->assertSee('B2B zahtjevi i računi');
    }

    public function test_individual_contract_price_overrides_group_price_rule(): void
    {
        $customer = User::factory()->create();
        $group = $this->makeGroup('wholesale');
        $customer->customerGroups()->attach($group);
        $this->makeB2BAccount($customer, B2BAccount::STATUS_APPROVED, $group);
        $product = $this->makeProduct('B2B-PRICE-01', 100);

        B2BPriceRule::query()->create([
            'code' => 'GROUP-20',
            'name' => 'Grupni popust',
            'customer_group_id' => $group->id,
            'calculation_type' => B2BPriceRule::TYPE_PERCENTAGE_DISCOUNT,
            'value' => 20,
            'target_type' => B2BPriceRule::TARGET_ALL,
            'minimum_quantity' => 1,
            'currency_code' => 'EUR',
            'priority' => 10,
            'is_active' => true,
        ]);
        $individual = B2BPriceRule::query()->create([
            'code' => 'CUSTOMER-FIXED-65',
            'name' => 'Ugovorena cijena kupca',
            'user_id' => $customer->id,
            'contract_number' => 'UG-IND-1',
            'calculation_type' => B2BPriceRule::TYPE_FIXED_PRICE,
            'value' => 65,
            'target_type' => B2BPriceRule::TARGET_PRODUCT,
            'minimum_quantity' => 1,
            'currency_code' => 'EUR',
            'priority' => 1,
            'is_active' => true,
        ]);
        $individual->targets()->create([
            'target_type' => B2BPriceRule::TARGET_PRODUCT,
            'target_id' => $product->id,
            'sort_order' => 0,
        ]);

        $resolved = app(ProductGroupPriceResolver::class)->resolve($product, $customer, 1);

        $this->assertSame(65.0, $resolved?->price);
        $this->assertSame($customer->id, $resolved?->user_id);
        $this->assertSame($individual->id, $resolved?->rule_id);
    }

    public function test_only_approved_b2b_customer_can_use_quick_order_by_code_sku_or_barcode(): void
    {
        $pending = User::factory()->create();
        $this->makeB2BAccount($pending, B2BAccount::STATUS_PENDING);
        $this->actingAs($pending)
            ->get(route('account.b2b.quick-order'))
            ->assertForbidden();

        $customer = User::factory()->create();
        $group = $this->makeGroup('approved-b2b');
        $this->makeB2BAccount($customer, B2BAccount::STATUS_APPROVED, $group);
        $productByCode = $this->makeProduct('CODE-100', 15, 'SKU-100', '3850000000100');
        $productByBarcode = $this->makeProduct('CODE-200', 25, 'SKU-200', '3850000000200');

        $this->actingAs($customer)
            ->get(route('account.b2b.quick-order'))
            ->assertOk()
            ->assertSee('B2B brza kupnja');

        $response = $this->actingAs($customer)
            ->post(route('account.b2b.quick-order.store'), [
                'items' => [
                    ['identifier' => $productByCode->code, 'quantity' => 2],
                    ['identifier' => $productByBarcode->barcode, 'quantity' => 3],
                ],
            ]);

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('front.cart.items.'.$productByCode->id.':0.quantity', 2);
        $response->assertSessionHas('front.cart.items.'.$productByBarcode->id.':0.quantity', 3);
    }

    public function test_approved_customer_can_repeat_a_previous_order(): void
    {
        $customer = User::factory()->create();
        $group = $this->makeGroup('repeat-buyers');
        $this->makeB2BAccount($customer, B2BAccount::STATUS_APPROVED, $group);
        $product = $this->makeProduct('REPEAT-100', 40);
        $order = Order::query()->create([
            'order_number' => 'WEB-REPEAT-100',
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'sku' => $product->sku,
            'code' => $product->code,
            'name' => 'Artikl za ponavljanje',
            'unit_price' => 40,
            'quantity' => 4,
            'line_total' => 160,
        ]);

        $response = $this->actingAs($customer)
            ->post(route('account.orders.reorder', ['orderNumber' => $order->order_number]));

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('front.cart.items.'.$product->id.':0.quantity', 4);
    }

    public function test_suspended_b2b_account_cannot_receive_group_prices_or_use_quick_order(): void
    {
        $customer = User::factory()->create();
        $group = $this->makeGroup('suspended-buyers');
        $this->makeB2BAccount($customer, B2BAccount::STATUS_SUSPENDED, $group);
        $product = $this->makeProduct('SUSPENDED-100', 100);
        B2BPriceRule::query()->create([
            'code' => 'SUSPENDED-GROUP-20',
            'name' => 'Grupni popust',
            'customer_group_id' => $group->id,
            'calculation_type' => B2BPriceRule::TYPE_PERCENTAGE_DISCOUNT,
            'value' => 20,
            'target_type' => B2BPriceRule::TARGET_ALL,
            'minimum_quantity' => 1,
            'currency_code' => 'EUR',
            'priority' => 10,
            'is_active' => true,
        ]);

        $resolved = app(ProductGroupPriceResolver::class)->resolve($product, $customer, 1);

        $this->assertNull($resolved);
        $this->actingAs($customer)
            ->get(route('account.b2b.quick-order'))
            ->assertForbidden();
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        Bouncer::role()->firstOrCreate(['name' => 'admin']);
        Bouncer::assign('admin')->to($user);

        return $user;
    }

    private function makeGroup(string $code): CustomerGroup
    {
        return CustomerGroup::query()->create([
            'code' => $code,
            'name' => ucfirst(str_replace('-', ' ', $code)),
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 10,
        ]);
    }

    private function makeB2BAccount(
        User $user,
        string $status,
        ?CustomerGroup $group = null,
    ): B2BAccount {
        if ($group) {
            $user->customerGroups()->syncWithoutDetaching([$group->id]);
        }

        return B2BAccount::query()->create([
            'user_id' => $user->id,
            'status' => $status,
            'company_name' => 'Test tvrtka '.$user->id,
            'oib' => str_pad((string) $user->id, 11, '0', STR_PAD_LEFT),
            'phone' => '+385 1 555 000',
            'address_line_1' => 'Testna 1',
            'postal_code' => '10000',
            'city' => 'Zagreb',
            'country_code' => 'HR',
            'customer_group_id' => $group?->id,
            'requested_at' => now(),
            'reviewed_at' => $status === B2BAccount::STATUS_APPROVED ? now() : null,
        ]);
    }

    private function makeProduct(
        string $code,
        float $price,
        ?string $sku = null,
        ?string $barcode = null,
    ): Product {
        $product = Product::query()->create([
            'code' => $code,
            'sku' => $sku ?: $code,
            'barcode' => $barcode,
            'is_active' => true,
            'base_price' => $price,
            'stock_qty' => 100,
            'minimum_order_quantity' => 1,
            'order_quantity_step' => 1,
        ]);
        $product->translations()->create([
            'locale' => 'hr',
            'name' => 'Artikl '.$code,
            'slug' => strtolower($code),
        ]);

        return $product;
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Pages\Auth\LoginPage;
use App\Livewire\Pages\CheckoutPage;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutPageTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();

        $this->customer = Customer::factory()->create();
        $this->actingAs($this->customer, 'customer');
    }

    public function test_placing_an_order_creates_the_order_and_items_and_clears_the_cart(): void
    {
        $service = Service::factory()->create(['base_price' => 20]);
        $plan = ServicePlan::factory()->create(['service_id' => $service->id, 'price' => 15]);
        app(CartService::class)->add($service->id, $plan->id, 2);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Sok Dara')
            ->set('customer_email', 'dara@example.com')
            ->set('customer_phone', '012345678')
            ->call('placeOrder')
            ->assertRedirect();

        $this->assertDatabaseCount('orders', 1);

        $order = Order::first();
        $this->assertSame($this->customer->id, $order->customer_id);
        $this->assertSame('Sok Dara', $order->customer_name);
        $this->assertSame('pending_payment', $order->status);
        $this->assertSame(30.0, (float) $order->total);

        $this->assertCount(1, $order->items);
        $item = $order->items->first();
        $this->assertSame($service->id, $item->service_id);
        $this->assertSame($plan->id, $item->service_plan_id);
        $this->assertSame(2, $item->quantity);
        $this->assertSame(30.0, (float) $item->line_total);

        $this->assertCount(0, app(CartService::class)->items());
    }

    public function test_place_order_asks_for_confirmation_before_creating_the_order(): void
    {
        $service = Service::factory()->create(['base_price' => 25]);
        app(CartService::class)->add($service->id, null, 1);

        $page = Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Sok Dara')
            ->set('customer_phone', '012345678')
            ->call('reviewOrder')
            ->assertSet('confirmingOrder', true)
            ->assertSee(__('Confirm your order'))
            ->assertSee('012345678');

        $this->assertDatabaseCount('orders', 0);

        $page->call('placeOrder')->assertRedirect();
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_cancelling_the_confirmation_creates_no_order(): void
    {
        $service = Service::factory()->create(['base_price' => 25]);
        app(CartService::class)->add($service->id, null, 1);

        Livewire::test(CheckoutPage::class)
            ->set('customer_phone', '012345678')
            ->call('reviewOrder')
            ->assertSet('confirmingOrder', true)
            ->set('confirmingOrder', false)
            ->assertSet('confirmingOrder', false);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_an_invalid_form_shows_errors_instead_of_the_confirmation(): void
    {
        $service = Service::factory()->create(['base_price' => 25]);
        app(CartService::class)->add($service->id, null, 1);

        Livewire::test(CheckoutPage::class)
            ->set('customer_phone', '')
            ->call('reviewOrder')
            ->assertHasErrors(['customer_phone' => 'required'])
            ->assertSet('confirmingOrder', false);
    }

    public function test_customer_name_email_and_phone_are_prefilled_from_the_account(): void
    {
        $customer = Customer::factory()->create(['name' => 'Sok Dara', 'email' => 'dara@example.com', 'phone' => '098 765 432']);
        $this->actingAs($customer, 'customer');

        Livewire::test(CheckoutPage::class)
            ->assertSet('customer_name', 'Sok Dara')
            ->assertSet('customer_email', 'dara@example.com')
            ->assertSet('customer_phone', '098 765 432');
    }

    public function test_an_older_account_without_a_phone_keeps_the_one_typed_at_checkout(): void
    {
        $customer = Customer::factory()->create(['phone' => null]);
        $this->actingAs($customer, 'customer');
        $service = Service::factory()->create(['base_price' => 20]);
        app(CartService::class)->add($service->id, null, 1);

        Livewire::test(CheckoutPage::class)
            ->assertSet('customer_phone', '')
            ->set('customer_phone', '011 222 333')
            ->call('placeOrder');

        $this->assertSame('011 222 333', $customer->fresh()->phone);
    }

    public function test_placing_an_order_applies_and_records_the_promo_code(): void
    {
        $service = Service::factory()->create(['base_price' => 100]);
        $promoCode = PromoCode::factory()->percentage(10)->create();
        $cart = app(CartService::class);
        $cart->add($service->id, null, 1);
        $cart->applyPromoCode($promoCode->code);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Sok Dara')
            ->set('customer_email', 'dara@example.com')
            ->set('customer_phone', '012345678')
            ->call('placeOrder');

        $order = Order::first();
        $this->assertSame($promoCode->code, $order->promo_code);
        $this->assertSame(10.0, (float) $order->discount_amount);
        $this->assertSame(90.0, (float) $order->total);
        $this->assertSame(1, $promoCode->fresh()->times_used);
    }

    public function test_a_promo_code_can_be_applied_and_removed_on_the_checkout_page(): void
    {
        $service = Service::factory()->create(['base_price' => 100]);
        $promoCode = PromoCode::factory()->percentage(10)->create();
        app(CartService::class)->add($service->id, null, 1);

        $page = Livewire::test(CheckoutPage::class)
            ->assertSee(__('Promo code'))
            ->set('promoCodeInput', strtolower($promoCode->code))
            ->call('applyPromoCode')
            ->assertSet('promoSuccess', true)
            ->assertSee('90.00');

        $this->assertSame($promoCode->code, app(CartService::class)->appliedPromoCode()?->code);

        $page->call('removePromoCode')->assertSee('100.00');
        $this->assertNull(app(CartService::class)->appliedPromoCode());
    }

    public function test_an_invalid_promo_code_on_checkout_shows_an_error_and_places_no_order(): void
    {
        $service = Service::factory()->create(['base_price' => 100]);
        app(CartService::class)->add($service->id, null, 1);

        Livewire::test(CheckoutPage::class)
            ->set('promoCodeInput', 'NOPE')
            ->call('applyPromoCode')
            ->assertSet('promoSuccess', false)
            ->assertNotSet('promoMessage', null);

        $this->assertSame(0, Order::count());
    }

    public function test_placing_an_order_with_an_empty_cart_stays_on_checkout_without_creating_an_order(): void
    {
        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Sok Dara')
            ->set('customer_email', 'dara@example.com')
            ->set('customer_phone', '012345678')
            ->call('placeOrder')
            ->assertRedirect('/checkout');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_placing_an_order_with_an_out_of_stock_item_stays_on_checkout_without_creating_an_order(): void
    {
        $service = Service::factory()->outOfStock()->create();
        app(CartService::class)->add($service->id, null, 1);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Sok Dara')
            ->set('customer_email', 'dara@example.com')
            ->set('customer_phone', '012345678')
            ->call('placeOrder')
            ->assertRedirect('/checkout');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_placing_an_order_with_an_out_of_stock_plan_stays_on_checkout_without_creating_an_order(): void
    {
        $service = Service::factory()->create();
        $plan = ServicePlan::factory()->outOfStock()->create(['service_id' => $service->id]);
        app(CartService::class)->add($service->id, $plan->id, 1);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Sok Dara')
            ->set('customer_email', 'dara@example.com')
            ->set('customer_phone', '012345678')
            ->call('placeOrder')
            ->assertRedirect('/checkout');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_placing_an_order_without_required_fields_fails_validation(): void
    {
        $service = Service::factory()->create();
        app(CartService::class)->add($service->id, null, 1);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', '')
            ->set('customer_email', 'not-an-email')
            ->set('customer_phone', '')
            ->call('placeOrder')
            ->assertHasErrors(['customer_name', 'customer_email', 'customer_phone']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_guests_can_see_and_edit_their_cart_on_checkout_but_must_log_in_to_order(): void
    {
        auth('customer')->logout();
        $service = Service::factory()->create(['base_price' => 100]);
        app(CartService::class)->add($service->id, null, 1);

        $this->get('/checkout')->assertOk()->assertSee($service->name_en);

        Livewire::test(CheckoutPage::class)
            ->assertSee(__('Log in or create an account to place your order.'))
            ->assertDontSee(__('Contact information'))
            ->call('placeOrder')
            ->assertRedirect('/login');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_old_cart_links_open_the_checkout_page(): void
    {
        $this->get('/cart')->assertRedirect('/checkout');
    }

    public function test_items_can_be_updated_and_removed_on_checkout(): void
    {
        $service = Service::factory()->create(['base_price' => 100]);
        $cart = app(CartService::class);
        $cart->add($service->id, null, 1);
        $key = $cart->items()->first()->key;

        Livewire::test(CheckoutPage::class)
            ->call('updateQuantity', $key, 3)
            ->assertDispatched('cart-updated')
            ->assertSee('300.00');

        $this->assertSame(3, app(CartService::class)->items()->first()->quantity);

        Livewire::test(CheckoutPage::class)
            ->call('removeItem', $key)
            ->assertSee(__('Your cart is empty'));
    }

    public function test_logging_in_from_checkout_returns_to_checkout(): void
    {
        auth('customer')->logout();

        // Guest presses "Log in" on checkout, logs in, and should land back on /checkout.
        Livewire::test(CheckoutPage::class)->call('goToAuth', 'login')->assertRedirect('/login');

        $customer = Customer::factory()->create(['email' => 'dara@example.com']);

        Livewire::test(LoginPage::class)
            ->set('email', 'dara@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect('/checkout');
    }
}

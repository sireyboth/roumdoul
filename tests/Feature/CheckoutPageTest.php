<?php

namespace Tests\Feature;

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

    public function test_customer_name_and_email_are_prefilled_from_the_account(): void
    {
        $customer = Customer::factory()->create(['name' => 'Sok Dara', 'email' => 'dara@example.com']);
        $this->actingAs($customer, 'customer');

        Livewire::test(CheckoutPage::class)
            ->assertSet('customer_name', 'Sok Dara')
            ->assertSet('customer_email', 'dara@example.com');
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

    public function test_placing_an_order_with_an_empty_cart_redirects_to_cart_without_creating_an_order(): void
    {
        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Sok Dara')
            ->set('customer_email', 'dara@example.com')
            ->set('customer_phone', '012345678')
            ->call('placeOrder')
            ->assertRedirect('/cart');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_placing_an_order_with_an_out_of_stock_item_redirects_to_cart_without_creating_an_order(): void
    {
        $service = Service::factory()->outOfStock()->create();
        app(CartService::class)->add($service->id, null, 1);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Sok Dara')
            ->set('customer_email', 'dara@example.com')
            ->set('customer_phone', '012345678')
            ->call('placeOrder')
            ->assertRedirect('/cart');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_placing_an_order_with_an_out_of_stock_plan_redirects_to_cart_without_creating_an_order(): void
    {
        $service = Service::factory()->create();
        $plan = ServicePlan::factory()->outOfStock()->create(['service_id' => $service->id]);
        app(CartService::class)->add($service->id, $plan->id, 1);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Sok Dara')
            ->set('customer_email', 'dara@example.com')
            ->set('customer_phone', '012345678')
            ->call('placeOrder')
            ->assertRedirect('/cart');

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

    public function test_guests_are_redirected_to_login_when_visiting_checkout(): void
    {
        auth('customer')->logout();

        $this->get('/checkout')->assertRedirect('/login');
    }

    public function test_logging_in_from_a_checkout_redirect_returns_to_checkout(): void
    {
        auth('customer')->logout();

        // Simulate the bounce: guest hits /checkout, gets sent to /login with the
        // intended URL stored in session — then logs in and should land back on /checkout.
        $this->get('/checkout');

        $customer = Customer::factory()->create(['email' => 'dara@example.com']);

        Livewire::test(\App\Livewire\Pages\Auth\LoginPage::class)
            ->set('email', 'dara@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect('/checkout');
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Pages\OrderConfirmationPage;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderConfirmationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owning_customer_can_view_their_order_confirmation(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        Livewire::actingAs($customer, 'customer')
            ->test(OrderConfirmationPage::class, ['order' => $order])
            ->assertOk();
    }

    public function test_a_different_customer_cannot_view_someone_elses_order_confirmation(): void
    {
        $owner = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $owner->id]);

        $intruder = Customer::factory()->create();

        Livewire::actingAs($intruder, 'customer')
            ->test(OrderConfirmationPage::class, ['order' => $order])
            ->assertForbidden();
    }

    public function test_a_guest_cannot_view_an_order_confirmation(): void
    {
        $order = Order::factory()->create(['customer_id' => Customer::factory()->create()->id]);

        $this->get("/order/{$order->id}/confirmation")->assertRedirect('/login');
    }

    public function test_a_guest_hitting_the_route_directly_is_redirected_to_login_not_shown_the_order(): void
    {
        $order = Order::factory()->create(['customer_id' => Customer::factory()->create()->id]);

        $response = $this->get(route('order.confirmation', $order));

        $response->assertRedirect(route('login'));
    }
}

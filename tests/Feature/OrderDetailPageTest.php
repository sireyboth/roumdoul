<?php

namespace Tests\Feature;

use App\Livewire\Pages\Dashboard\OrderDetailPage;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderDetailPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owning_customer_can_view_their_order_detail(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        Livewire::actingAs($customer, 'customer')
            ->test(OrderDetailPage::class, ['order' => $order])
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_a_different_customer_cannot_view_someone_elses_order_detail(): void
    {
        $owner = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $owner->id]);

        $intruder = Customer::factory()->create();

        Livewire::actingAs($intruder, 'customer')
            ->test(OrderDetailPage::class, ['order' => $order])
            ->assertForbidden();
    }

    public function test_a_guest_hitting_the_route_directly_is_redirected_to_login(): void
    {
        $order = Order::factory()->create(['customer_id' => Customer::factory()->create()->id]);

        $response = $this->get(route('dashboard.orders.show', $order));

        $response->assertRedirect(route('login'));
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Pages\Auth\LoginPage;
use App\Livewire\Pages\Auth\RegisterPage;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_can_register_and_is_logged_in(): void
    {
        Livewire::test(RegisterPage::class)
            ->set('name', 'Sok Dara')
            ->set('email', 'dara@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertRedirect('/dashboard');

        $this->assertTrue(Auth::guard('customer')->check());
        $this->assertDatabaseHas('customers', ['email' => 'dara@example.com']);
    }

    public function test_registration_requires_a_unique_email(): void
    {
        Customer::factory()->create(['email' => 'dara@example.com']);

        Livewire::test(RegisterPage::class)
            ->set('name', 'Sok Dara')
            ->set('email', 'dara@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email']);
    }

    public function test_a_customer_can_log_in_with_correct_credentials(): void
    {
        Customer::factory()->create([
            'email' => 'dara@example.com',
            'password' => Hash::make('password123'),
        ]);

        Livewire::test(LoginPage::class)
            ->set('email', 'dara@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect('/dashboard');

        $this->assertTrue(Auth::guard('customer')->check());
    }

    public function test_login_fails_with_wrong_password(): void
    {
        Customer::factory()->create([
            'email' => 'dara@example.com',
            'password' => Hash::make('password123'),
        ]);

        Livewire::test(LoginPage::class)
            ->set('email', 'dara@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertFalse(Auth::guard('customer')->check());
    }

    public function test_guests_are_redirected_away_from_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_logged_in_customers_can_view_the_dashboard(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer')
            ->get('/dashboard')
            ->assertStatus(200);
    }

    public function test_dashboard_shows_the_customers_own_orders_only(): void
    {
        $customer = Customer::factory()->create();
        $ownOrder = Order::factory()->create(['customer_id' => $customer->id, 'order_number' => 'RD-MINE0001']);
        $otherOrder = Order::factory()->create(['order_number' => 'RD-OTHER001']);

        $response = $this->actingAs($customer, 'customer')->get('/dashboard');

        $response->assertSee('RD-MINE0001');
        $response->assertDontSee('RD-OTHER001');
    }

    public function test_logged_in_customers_are_redirected_away_from_login(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer')
            ->get('/login')
            ->assertRedirect('/dashboard');
    }

    public function test_a_customer_can_log_out(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer')
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertFalse(Auth::guard('customer')->check());
    }
}

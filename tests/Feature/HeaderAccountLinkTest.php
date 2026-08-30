<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderAccountLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_header_links_guests_to_login(): void
    {
        $response = $this->get('/');

        $response->assertSee('href="/login"', false);
    }

    public function test_the_header_links_logged_in_customers_to_the_dashboard(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'customer')->get('/');

        $response->assertSee('href="/dashboard"', false);
    }
}

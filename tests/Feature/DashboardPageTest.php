<?php

namespace Tests\Feature;

use App\Livewire\Pages\Dashboard\DashboardPage;
use App\Livewire\Pages\Dashboard\InvitationManagePage;
use App\Models\Customer;
use App\Models\Invitation;
use App\Models\InvitationTemplate;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_can_be_searched_and_filtered_by_status(): void
    {
        $customer = Customer::factory()->create();
        Order::factory()->create(['customer_id' => $customer->id, 'order_number' => 'RD-ALPHA001', 'status' => 'paid']);
        Order::factory()->create(['customer_id' => $customer->id, 'order_number' => 'RD-BRAVO002', 'status' => 'pending_payment']);

        Livewire::actingAs($customer, 'customer')->test(DashboardPage::class)
            ->set('orderSearch', 'alpha')
            ->assertSee('RD-ALPHA001')->assertDontSee('RD-BRAVO002')
            ->set('orderSearch', '')
            ->set('orderStatus', 'pending_payment')
            ->assertSee('RD-BRAVO002')->assertDontSee('RD-ALPHA001');
    }

    public function test_orders_sort_by_total_and_toggle_direction(): void
    {
        $customer = Customer::factory()->create();
        Order::factory()->create(['customer_id' => $customer->id, 'order_number' => 'RD-CHEAP001', 'total' => 5]);
        Order::factory()->create(['customer_id' => $customer->id, 'order_number' => 'RD-PRICY001', 'total' => 90]);

        Livewire::actingAs($customer, 'customer')->test(DashboardPage::class)
            ->call('sortOrdersBy', 'total')
            ->assertSeeInOrder(['RD-PRICY001', 'RD-CHEAP001'])
            ->call('sortOrdersBy', 'total')
            ->assertSeeInOrder(['RD-CHEAP001', 'RD-PRICY001']);
    }

    public function test_unknown_sort_columns_and_statuses_are_ignored(): void
    {
        $customer = Customer::factory()->create();

        Livewire::actingAs($customer, 'customer')->test(DashboardPage::class)
            ->call('sortOrdersBy', 'customer_email; drop table orders')
            ->assertSet('orderSort', 'created_at')
            ->set('orderStatus', 'bogus')
            ->assertSet('orderStatus', '');
    }

    public function test_orders_are_paginated(): void
    {
        $customer = Customer::factory()->create();
        Order::factory()->count(15)->sequence(fn ($s) => ['order_number' => sprintf('RD-PAGE%04d', $s->index)])
            ->create(['customer_id' => $customer->id]);

        Livewire::actingAs($customer, 'customer')->test(DashboardPage::class)
            ->call('sortOrdersBy', 'order_number') // ascending
            ->assertSee('RD-PAGE0011')->assertDontSee('RD-PAGE0012')
            ->call('nextPage', 'ordersPage')
            ->assertSee('RD-PAGE0014')->assertDontSee('RD-PAGE0003');
    }

    public function test_invitations_can_be_searched_by_guest_and_filtered_by_status(): void
    {
        $customer = Customer::factory()->create();
        $wedding = Invitation::factory()->create([
            'customer_id' => $customer->id,
            'invitation_template_id' => InvitationTemplate::factory()->create(['name' => 'Golden Wedding']),
        ]);
        $wedding->recipients()->create(['recipient_name' => 'Dara Sok']);
        Invitation::factory()->create([
            'customer_id' => $customer->id,
            'invitation_template_id' => InvitationTemplate::factory()->create(['name' => 'Birthday Fun']),
            'expires_at' => now()->subDay(),
        ]);

        Livewire::actingAs($customer, 'customer')->test(DashboardPage::class)
            ->set('invitationSearch', 'dara')
            ->assertSee('Golden Wedding')->assertDontSee('Birthday Fun')
            ->set('invitationSearch', '')
            ->set('invitationStatus', 'expired')
            ->assertSee('Birthday Fun')->assertDontSee('Golden Wedding');
    }

    public function test_dashboard_only_lists_the_customers_own_invitations(): void
    {
        $customer = Customer::factory()->create();
        Invitation::factory()->create(['invitation_template_id' => InvitationTemplate::factory()->create(['name' => 'Someone Else'])]);

        Livewire::actingAs($customer, 'customer')->test(DashboardPage::class)
            ->assertDontSee('Someone Else');
    }

    public function test_guest_list_can_be_searched_filtered_and_paginated(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id, 'max_recipients' => 50]);
        foreach (range(1, 12) as $i) {
            $invitation->recipients()->create(['recipient_name' => sprintf('Guest%02d', $i)]);
        }
        $invitation->recipients()->create(['recipient_name' => 'Sophea Opened', 'viewed_at' => now()]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('section', 'guests')
            ->set('guestSearch', 'sophea')
            ->assertSee('Sophea Opened')->assertDontSee('Guest01')
            ->set('guestSearch', '')
            ->set('guestFilter', 'not_opened')
            ->assertDontSee('Sophea Opened')
            ->set('guestFilter', '')
            ->set('guestSort', 'name')
            ->set('guestPerPage', 10)
            ->assertSee('Guest10')->assertDontSee('Guest11')
            ->call('nextPage', 'guestsPage')
            ->assertSee('Guest11')->assertSee('Sophea Opened')->assertDontSee('Guest10');
    }

    public function test_responses_can_be_filtered_by_answer(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->recipients()->create(['recipient_name' => 'Yes Person', 'rsvp_status' => 'yes']);
        $invitation->recipients()->create(['recipient_name' => 'No Person', 'rsvp_status' => 'no']);
        $invitation->recipients()->create(['recipient_name' => 'Silent Person']);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('section', 'responses')
            ->set('responseFilter', 'none')
            ->assertSee('Silent Person')->assertDontSee('Yes Person')->assertDontSee('No Person')
            ->set('responseFilter', 'yes')
            ->assertSee('Yes Person')->assertDontSee('Silent Person');
    }

    public function test_invitation_workspace_has_a_language_toggle(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($customer, 'customer')
            ->get('/dashboard/invitations/'.$invitation->id)
            ->assertSee(route('locale.switch', 'km'), false)
            ->assertSee(route('locale.switch', 'en'), false);
    }
}

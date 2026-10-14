<?php

namespace Tests\Feature;

use App\Livewire\Pages\Dashboard\InvitationManagePage;
use App\Models\Customer;
use App\Models\Invitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvitationWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_only_lists_sections_the_template_has_fields_for(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['sender_name', 'cover_image']]);

        $component = Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation]);

        $this->assertSame(['overview', 'details', 'media', 'guests'], $component->instance()->availableSections());
    }

    public function test_responses_section_appears_when_the_template_uses_rsvp(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['sender_name', 'rsvp_enabled', 'event_schedule']]);

        $component = Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation]);

        $this->assertSame(['overview', 'details', 'program', 'guests', 'responses'], $component->instance()->availableSections());
    }

    public function test_switching_sections_shows_that_sections_fields(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['sender_name', 'cover_image']]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->call('showSection', 'details')
            ->assertSet('section', 'details')
            ->assertSee('Your name (sender)')
            ->assertDontSee('Cover image')
            ->call('showSection', 'media')
            ->assertSee('Cover image');
    }

    public function test_an_unavailable_section_is_ignored(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['sender_name']]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->call('showSection', 'program')
            ->assertSet('section', 'overview');
    }

    public function test_responses_section_lists_rsvp_answers_and_notes(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id, 'max_recipients' => 5]);
        $invitation->template->update(['fields' => ['rsvp_enabled']]);
        $invitation->recipients()->create(['recipient_name' => 'Bella', 'rsvp_status' => 'yes', 'rsvp_note' => 'Wouldn’t miss it']);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->call('showSection', 'responses')
            ->assertSee('Bella')
            ->assertSee('Wouldn’t miss it');
    }

    public function test_owner_can_preview_without_marking_any_recipient_viewed(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id, 'max_recipients' => 5]);
        $recipient = $invitation->recipients()->create(['recipient_name' => 'Bella']);

        $this->actingAs($customer, 'customer')
            ->get(route('dashboard.invitations.preview', $invitation))
            ->assertOk();

        $this->assertNull($recipient->fresh()->viewed_at);
    }

    public function test_preview_is_forbidden_for_other_customers_and_unpaid_invitations(): void
    {
        $owner = Customer::factory()->create();
        $paid = Invitation::factory()->create(['customer_id' => $owner->id]);
        $unpaid = Invitation::factory()->unpaid()->create(['customer_id' => $owner->id]);

        $this->actingAs(Customer::factory()->create(), 'customer')
            ->get(route('dashboard.invitations.preview', $paid))
            ->assertForbidden();

        $this->actingAs($owner, 'customer')
            ->get(route('dashboard.invitations.preview', $unpaid))
            ->assertForbidden();
    }

    public function test_dashboard_opens_invitations_in_a_new_tab(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($customer, 'customer')
            ->get('/dashboard')
            ->assertSee('href="/dashboard/invitations/'.$invitation->id.'" target="_blank"', false);
    }
}

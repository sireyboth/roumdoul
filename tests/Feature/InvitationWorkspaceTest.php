<?php

namespace Tests\Feature;

use App\Livewire\Pages\Dashboard\InvitationManagePage;
use App\Models\Customer;
use App\Models\Invitation;
use App\Models\InvitationTemplate;
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

        $this->assertSame(['overview', 'details', 'media', 'guests', 'guide'], $component->instance()->availableSections());
    }

    public function test_responses_section_appears_when_the_template_uses_rsvp(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['sender_name', 'rsvp_enabled', 'event_schedule']]);

        $component = Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation]);

        $this->assertSame(['overview', 'details', 'program', 'guests', 'responses', 'guide'], $component->instance()->availableSections());
    }

    public function test_schedule_image_is_edited_under_program_not_media(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['sender_name', 'schedule_image']]);

        $component = Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation]);

        $this->assertSame(['overview', 'details', 'program', 'guests', 'guide'], $component->instance()->availableSections());
        $this->assertSame(['schedule_image'], $component->instance()->sectionFields('program'));
        $this->assertSame([], $component->instance()->sectionFields('media'));
    }

    public function test_saving_reports_success_with_a_toast(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['sender_name']]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('fieldValues.sender_name', 'Dara')
            ->call('save')
            ->assertDispatched('toast', type: 'success')
            ->assertDispatched('invitation-saved');
    }

    public function test_a_failed_save_toasts_and_jumps_to_the_bad_field_on_its_tab(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['sender_name', 'music_url', 'cover_image']]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->call('showSection', 'media')
            ->set('fieldValues.music_url', 'javascript:alert(1)')
            ->call('save')
            ->assertHasErrors('fieldValues.music_url')
            ->assertDispatched('toast', type: 'error')
            ->assertDispatched('field-error', key: 'music_url')
            ->assertSet('section', 'details')
            ->assertNotDispatched('invitation-saved');
    }

    public function test_guest_actions_each_report_their_outcome(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id, 'max_recipients' => 1]);
        $page = Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation]);

        $page->call('addRecipient')->assertDispatched('toast', type: 'error');

        $page->set('newRecipientName', 'Bella')->call('addRecipient')->assertDispatched('toast', type: 'success');
        $recipient = $invitation->recipients()->first();

        $page->set('newRecipientName', 'Chan')->call('addRecipient')->assertDispatched('toast', type: 'warning');
        $page->call('toggleRecipient', $recipient->id)->assertDispatched('toast', type: 'warning');
        $page->call('toggleRecipient', $recipient->id)->assertDispatched('toast', type: 'success');
        $page->call('rotateRecipient', $recipient->id)->assertDispatched('toast', type: 'success');
        $page->call('removeRecipient', $recipient->id)->assertDispatched('toast', type: 'success');
        $page->call('removeRecipient', $recipient->id)->assertDispatched('toast', type: 'warning');
    }

    public function test_the_workspace_renders_the_toast_stack(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($customer, 'customer')
            ->get('/dashboard/invitations/'.$invitation->id)
            ->assertOk()
            ->assertSee('id="toast-root"', false);
    }

    public function test_guide_can_jump_straight_to_a_field(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['sender_name', 'cover_image']]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->call('showSection', 'guide')
            ->call('editField', 'cover_image')
            ->assertSet('section', 'media')
            ->assertSet('focusField', 'cover_image')
            ->assertSee('id="field-cover_image"', false)
            ->call('editField', 'groom_name')
            ->assertSet('section', 'media');
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

    public function test_guide_explains_each_field_the_template_uses(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['sender_name', 'photo_gallery']]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->call('showSection', 'guide')
            ->assertSet('section', 'guide')
            ->assertSee(InvitationTemplate::FIELD_CATALOG['sender_name']['hint'])
            ->assertSee(InvitationTemplate::FIELD_CATALOG['photo_gallery']['hint'])
            ->assertDontSee(InvitationTemplate::FIELD_CATALOG['groom_name']['hint']);
    }

    public function test_guide_is_open_before_payment_but_editing_sections_are_not(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->unpaid()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['sender_name']]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->call('showSection', 'details')
            ->assertSet('section', 'overview')
            ->call('showSection', 'guide')
            ->assertSet('section', 'guide')
            ->assertSee(InvitationTemplate::FIELD_CATALOG['sender_name']['hint']);
    }

    public function test_every_catalog_field_has_a_customer_hint(): void
    {
        foreach (InvitationTemplate::FIELD_CATALOG as $key => $field) {
            $this->assertNotEmpty($field['hint'] ?? null, "Field [{$key}] is missing a customer-facing hint.");
        }
    }
}

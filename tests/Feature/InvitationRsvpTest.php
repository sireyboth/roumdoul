<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\InvitationRecipient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationRsvpTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_recipient_can_rsvp_yes_with_a_note(): void
    {
        $invitation = Invitation::factory()->withFeatures(['rsvp_enabled'])->create();
        $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

        $response = $this->postJson(route('invitation.rsvp', $recipient), [
            'status' => 'yes',
            'note' => 'Can\'t wait!! 🥹',
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertSame('yes', $recipient->fresh()->rsvp_status);
        $this->assertSame('Can\'t wait!! 🥹', $recipient->fresh()->rsvp_note);
    }

    public function test_a_recipient_can_rsvp_no_without_a_note(): void
    {
        $invitation = Invitation::factory()->withFeatures(['rsvp_enabled'])->create();
        $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

        $response = $this->postJson(route('invitation.rsvp', $recipient), [
            'status' => 'no',
        ]);

        $response->assertOk();
        $this->assertSame('no', $recipient->fresh()->rsvp_status);
        $this->assertNull($recipient->fresh()->rsvp_note);
    }

    public function test_rsvp_rejects_an_invalid_status(): void
    {
        $invitation = Invitation::factory()->withFeatures(['rsvp_enabled'])->create();
        $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

        $this->postJson(route('invitation.rsvp', $recipient), [
            'status' => 'maybe',
        ])->assertStatus(422);
    }

    public function test_rsvp_404s_for_an_unknown_link_code(): void
    {
        $invitation = Invitation::factory()->withFeatures(['rsvp_enabled'])->create();
        InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

        $this->postJson('/i/NoSuchCode12/rsvp', ['status' => 'yes'])->assertNotFound();
    }

    public function test_guest_links_are_short(): void
    {
        $invitation = Invitation::factory()->create();
        $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

        $this->assertMatchesRegularExpression('#/i/[A-Za-z0-9]{12}$#', route('invitation.show', $recipient));
        $this->get('/i/NoSuchCode12')->assertNotFound();
    }

    public function test_old_long_links_redirect_to_the_short_link(): void
    {
        $invitation = Invitation::factory()->create();
        $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

        $this->get("/invite/{$invitation->slug}/{$recipient->token}")
            ->assertStatus(301)
            ->assertRedirect(route('invitation.show', $recipient));

        $other = Invitation::factory()->create();
        $this->get("/invite/{$other->slug}/{$recipient->token}")->assertNotFound();
    }

    public function test_rsvp_is_blocked_once_the_invitation_has_expired(): void
    {
        $invitation = Invitation::factory()->withFeatures(['rsvp_enabled'])->expired()->create();
        $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

        $this->postJson(route('invitation.rsvp', $recipient), [
            'status' => 'yes',
        ])->assertStatus(410);
    }

    public function test_rsvp_is_refused_when_the_plan_does_not_include_it(): void
    {
        $invitation = Invitation::factory()->withFeatures([])->create();
        $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

        $this->postJson(route('invitation.rsvp', $recipient), [
            'status' => 'yes',
        ])->assertForbidden();

        $this->assertNull($recipient->fresh()->rsvp_status);
    }

    public function test_rsvp_is_refused_once_the_order_is_no_longer_paid(): void
    {
        $invitation = Invitation::factory()->withFeatures(['rsvp_enabled'])->create();
        $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);
        $invitation->order->update(['status' => 'pending_payment']);

        $this->postJson(route('invitation.rsvp', $recipient), [
            'status' => 'yes',
        ])->assertNotFound();

        $this->assertNull($recipient->fresh()->rsvp_status);
    }

    public function test_the_guest_page_stops_working_once_the_order_is_no_longer_paid(): void
    {
        $invitation = Invitation::factory()->create();
        $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);
        $this->get(route('invitation.show', $recipient))->assertOk();

        $invitation->order->update(['status' => 'pending_payment']);

        $this->get(route('invitation.show', $recipient))->assertStatus(410);
    }
}

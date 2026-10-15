<?php

namespace Tests\Feature;

use App\Livewire\Components\PublicFeedbackForm;
use App\Livewire\Pages\Dashboard\InvitationManagePage;
use App\Models\Customer;
use App\Models\Invitation;
use App\Models\InvitationRecipient;
use App\Models\ModerationLog;
use App\Models\Review;
use App\Models\User;
use App\Policies\ReviewPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function ownedInvitation(array $fields): array
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => $fields]);

        return [$customer, $invitation];
    }

    // ---- stored photo paths -------------------------------------------------------------

    public function test_an_external_url_cannot_be_stored_as_a_cover_image(): void
    {
        [$customer, $invitation] = $this->ownedInvitation(['cover_image']);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('fieldValues.cover_image', 'https://tracker.example.net/pixel.gif')
            ->call('save')
            ->assertHasErrors(['fieldValues.cover_image']);

        $this->assertNull($invitation->fresh()->field_values['cover_image'] ?? null);
    }

    public function test_another_invitations_storage_path_cannot_be_used(): void
    {
        [$customer, $invitation] = $this->ownedInvitation(['cover_image', 'photo_gallery']);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('fieldValues.cover_image', 'invitations/999999/stolen.jpg')
            ->set('fieldValues.photo_gallery', ['invitations/999999/stolen.jpg'])
            ->call('save')
            ->assertHasErrors(['fieldValues.cover_image', 'fieldValues.photo_gallery.0']);
    }

    public function test_path_traversal_in_a_photo_path_is_rejected(): void
    {
        [$customer, $invitation] = $this->ownedInvitation(['cover_image']);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('fieldValues.cover_image', "invitations/{$invitation->id}/../1/secret.jpg")
            ->call('save')
            ->assertHasErrors(['fieldValues.cover_image']);
    }

    public function test_own_storage_path_and_unknown_keys_are_handled(): void
    {
        [$customer, $invitation] = $this->ownedInvitation(['cover_image', 'headline']);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('fieldValues.cover_image', "invitations/{$invitation->id}/abc123.jpg")
            ->set('fieldValues.headline', 'Hi')
            ->set('fieldValues.not_in_template', '<script>x</script>')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(
            ['cover_image' => "invitations/{$invitation->id}/abc123.jpg", 'headline' => 'Hi'],
            $invitation->fresh()->field_values,
        );
    }

    // ---- RSVP note ----------------------------------------------------------------------

    private function recipient(): array
    {
        $invitation = Invitation::factory()->create();
        $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

        return [$invitation, $recipient];
    }

    public function test_an_abusive_rsvp_note_is_rejected(): void
    {
        [$invitation, $recipient] = $this->recipient();

        $this->postJson(route('invitation.rsvp', [$invitation, $recipient]), ['status' => 'yes', 'note' => 'you are a b!tch'])
            ->assertStatus(422);

        $this->assertNull($recipient->fresh()->rsvp_status);
    }

    public function test_a_link_in_an_rsvp_note_is_rejected(): void
    {
        [$invitation, $recipient] = $this->recipient();

        $this->postJson(route('invitation.rsvp', [$invitation, $recipient]), ['status' => 'yes', 'note' => 'see https://scam.example.com'])
            ->assertStatus(422);
    }

    public function test_a_khmer_rsvp_note_is_saved_and_html_is_stripped(): void
    {
        [$invitation, $recipient] = $this->recipient();

        $this->postJson(route('invitation.rsvp', [$invitation, $recipient]), ['status' => 'yes', 'note' => 'ខ្ញុំនឹងទៅ ក្តីរីករាយ'])
            ->assertOk();

        $this->assertSame('ខ្ញុំនឹងទៅ ក្តីរីករាយ', $recipient->fresh()->rsvp_note);
    }

    public function test_one_link_cannot_flood_rsvp_updates(): void
    {
        [$invitation, $recipient] = $this->recipient();
        $limit = (int) config('moderation.rsvp_updates_per_window');

        RateLimiter::clear('rsvp-recipient:'.$recipient->id);

        for ($i = 0; $i < $limit; $i++) {
            $this->postJson(route('invitation.rsvp', [$invitation, $recipient]), ['status' => $i % 2 ? 'yes' : 'no', 'note' => "note {$i}"])
                ->assertOk();
        }

        $this->postJson(route('invitation.rsvp', [$invitation, $recipient]), ['status' => 'yes'])
            ->assertStatus(429);
    }

    // ---- link revocation ----------------------------------------------------------------

    public function test_a_paused_link_stops_working_and_cannot_rsvp(): void
    {
        [$invitation, $recipient] = $this->recipient();
        $recipient->update(['revoked_at' => now()]);

        $this->get(route('invitation.show', [$invitation, $recipient]))->assertStatus(410);
        $this->postJson(route('invitation.rsvp', [$invitation, $recipient]), ['status' => 'yes'])->assertNotFound();
    }

    public function test_rotating_a_link_invalidates_the_old_token(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);
        $oldToken = $recipient->token;

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->call('rotateRecipient', $recipient->id);

        $this->assertNotSame($oldToken, $recipient->fresh()->token);
        $this->get("/invite/{$invitation->slug}/{$oldToken}")->assertNotFound();
    }

    public function test_a_customer_cannot_pause_another_customers_recipient(): void
    {
        $customer = Customer::factory()->create();
        $mine = Invitation::factory()->create(['customer_id' => $customer->id]);
        $other = Invitation::factory()->create();
        $foreign = InvitationRecipient::factory()->create(['invitation_id' => $other->id]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $mine])
            ->call('toggleRecipient', $foreign->id);
    }

    // ---- reviews / feedback form --------------------------------------------------------

    public function test_the_feedback_form_blocks_abusive_comments_and_links(): void
    {
        foreach (['f u c k', 'buy at www.scam.com'] as $comment) {
            Livewire::test(PublicFeedbackForm::class)
                ->set('comment', $comment)
                ->call('submit')
                ->assertHasErrors(['comment']);
        }

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_the_feedback_form_rejects_html_and_script_payloads(): void
    {
        Livewire::test(PublicFeedbackForm::class)
            ->set('comment', 'Great <script>alert(1)</script> service')
            ->call('submit')
            ->assertHasErrors(['comment']);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_a_clean_comment_is_saved_normalised_and_held_for_approval(): void
    {
        Livewire::test(PublicFeedbackForm::class)
            ->set('comment', "  Great   service \u{200B}thank you <3  ")
            ->call('submit')
            ->assertHasNoErrors();

        $review = Review::first();
        $this->assertSame('Great service thank you <3', $review->comment);
        $this->assertFalse($review->is_approved);
    }

    public function test_duplicate_feedback_is_rejected_and_logged_without_text(): void
    {
        Livewire::test(PublicFeedbackForm::class)->set('comment', 'Lovely work!')->call('submit')->assertHasNoErrors();

        Livewire::test(PublicFeedbackForm::class)->set('comment', 'lovely  work!')->call('submit')->assertHasErrors(['comment']);

        $this->assertDatabaseCount('reviews', 1);
        $log = ModerationLog::first();
        $this->assertSame('review_rejected', $log->action);
        $this->assertStringNotContainsString('Lovely', json_encode($log->toArray()));
    }

    public function test_the_feedback_form_is_rate_limited_per_ip(): void
    {
        RateLimiter::clear('feedback|127.0.0.1');
        $max = (int) config('moderation.feedback_per_hour_per_ip');

        for ($i = 0; $i < $max; $i++) {
            Livewire::test(PublicFeedbackForm::class)->set('comment', "Unique comment number {$i}")->call('submit')->assertHasNoErrors();
        }

        Livewire::test(PublicFeedbackForm::class)->set('comment', 'One more unique one')->call('submit')->assertHasErrors(['comment']);
    }

    // ---- permissions --------------------------------------------------------------------

    public function test_only_admins_can_delete_reviews_but_staff_can_moderate(): void
    {
        $staff = User::factory()->make(['role' => 'staff']);
        $admin = User::factory()->make(['role' => 'admin']);
        $review = new Review;
        $policy = new ReviewPolicy;

        $this->assertTrue($policy->update($staff, $review));
        $this->assertFalse($policy->delete($staff, $review));
        $this->assertTrue($policy->delete($admin, $review));
    }

    public function test_security_headers_are_present_on_public_pages(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy-Report-Only'));
    }
}

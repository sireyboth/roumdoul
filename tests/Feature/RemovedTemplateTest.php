<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** A template whose design file was removed from the site (e.g. the old Christmas template). */
class RemovedTemplateTest extends TestCase
{
    use RefreshDatabase;

    private function invitationWithRemovedDesign(Customer $owner): Invitation
    {
        $invitation = Invitation::factory()->create(['customer_id' => $owner->id]);
        $invitation->template->update(['view' => 'invitations.templates.christmas', 'is_active' => true]);

        return $invitation->fresh();
    }

    public function test_guest_links_show_the_expired_page_instead_of_an_error(): void
    {
        $invitation = $this->invitationWithRemovedDesign(Customer::factory()->create());
        $recipient = $invitation->recipients()->create(['recipient_name' => 'Bella']);

        $this->get(route('invitation.show', $recipient))->assertStatus(410);
    }

    public function test_the_owner_preview_and_the_demo_are_not_found(): void
    {
        $owner = Customer::factory()->create();
        $invitation = $this->invitationWithRemovedDesign($owner);

        $this->actingAs($owner, 'customer')
            ->get(route('dashboard.invitations.preview', $invitation))
            ->assertNotFound();

        $this->get(route('templates.demo', $invitation->template))->assertNotFound();
    }
}

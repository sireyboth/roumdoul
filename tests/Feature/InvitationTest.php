<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\InvitationRecipient;
use App\Models\InvitationTemplate;
use App\Models\Service;
use App\Models\ServicePlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitation_is_not_expired_without_an_expiry_date(): void
    {
        $invitation = Invitation::factory()->create(['expires_at' => null]);

        $this->assertFalse($invitation->isExpired());
    }

    public function test_invitation_is_expired_once_past_its_expiry_date(): void
    {
        $invitation = Invitation::factory()->expired()->create();

        $this->assertTrue($invitation->isExpired());
    }

    public function test_invitation_is_not_expired_before_its_expiry_date(): void
    {
        $invitation = Invitation::factory()->create(['expires_at' => now()->addMonth()]);

        $this->assertFalse($invitation->isExpired());
    }

    public function test_has_feature_checks_the_purchased_plans_features(): void
    {
        $service = Service::factory()->create();
        $plan = ServicePlan::factory()->create([
            'service_id' => $service->id,
            'features' => ['venue_address', 'countdown_enabled'],
        ]);
        $invitation = Invitation::factory()->create(['service_plan_id' => $plan->id]);

        $this->assertTrue($invitation->hasFeature('venue_address'));
        $this->assertTrue($invitation->hasFeature('countdown_enabled'));
        $this->assertFalse($invitation->hasFeature('rsvp_enabled'));
    }

    public function test_has_feature_is_false_when_no_plan_is_attached(): void
    {
        $invitation = Invitation::factory()->create(['service_plan_id' => null]);

        $this->assertFalse($invitation->hasFeature('venue_address'));
    }

    public function test_field_unlocked_is_always_true_for_free_fields_regardless_of_plan(): void
    {
        $invitation = Invitation::factory()->create(['service_plan_id' => null]);

        $this->assertTrue($invitation->fieldUnlocked('sender_name'));
        $this->assertTrue($invitation->fieldUnlocked('music_url'));
        $this->assertFalse($invitation->fieldUnlocked('venue_address'));
    }

    public function test_field_unlocked_checks_the_plans_features_for_premium_fields(): void
    {
        $service = Service::factory()->create();
        $plan = ServicePlan::factory()->create([
            'service_id' => $service->id,
            'features' => ['photo_gallery'],
        ]);
        $invitation = Invitation::factory()->create(['service_plan_id' => $plan->id]);

        $this->assertTrue($invitation->fieldUnlocked('photo_gallery'));
        $this->assertFalse($invitation->fieldUnlocked('event_schedule'));
    }

    public function test_template_uses_field_checks_its_field_catalog(): void
    {
        $template = InvitationTemplate::factory()->create([
            'fields' => ['sender_name', 'headline', 'message', 'cover_image', 'event_date'],
        ]);

        $this->assertTrue($template->usesField('event_date'));
        $this->assertFalse($template->usesField('venue_address'));
    }

    public function test_invitation_recipient_gets_a_unique_token_automatically(): void
    {
        $invitation = Invitation::factory()->create();
        $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

        $this->assertNotEmpty($recipient->token);
        $this->assertSame(32, strlen($recipient->token));
    }

    public function test_invitation_recipient_route_key_is_the_token(): void
    {
        $recipient = InvitationRecipient::factory()->create();

        $this->assertSame('token', $recipient->getRouteKeyName());
    }
}

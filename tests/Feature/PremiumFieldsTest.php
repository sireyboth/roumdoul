<?php

namespace Tests\Feature;

use App\Filament\Resources\InvitationTemplates\Pages\ListInvitationTemplates;
use App\Models\Customer;
use App\Models\Invitation;
use App\Models\InvitationTemplate;
use App\Models\Order;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Models\User;
use Filament\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Which fields are ★ is the admin's choice per template (`premium_fields`), each plan ticks its own ★ fields
 * (`features`), and a sold invitation never loses a field it had when it was paid.
 */
class PremiumFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    public function test_a_field_the_admin_does_not_mark_premium_is_free_on_every_plan(): void
    {
        $template = InvitationTemplate::factory()->create([
            'fields' => ['headline', 'rsvp_enabled', 'venue_address'],
            'premium_fields' => ['venue_address'],
        ]);
        $invitation = Invitation::factory()->create(['invitation_template_id' => $template->id, 'features' => []]);

        $this->assertTrue($invitation->fieldUnlocked('rsvp_enabled'));
        $this->assertFalse($invitation->fieldUnlocked('venue_address'));
    }

    public function test_the_admin_can_make_a_normally_free_field_premium(): void
    {
        $template = InvitationTemplate::factory()->create([
            'fields' => ['headline', 'dress_code'],
            'premium_fields' => ['dress_code'],
        ]);
        $invitation = Invitation::factory()->create(['invitation_template_id' => $template->id, 'features' => [], 'free_fields' => []]);

        $this->assertFalse($invitation->fieldUnlocked('dress_code'));
        $this->assertTrue($invitation->fieldUnlocked('headline'));
    }

    public function test_each_plan_gets_only_the_premium_fields_ticked_for_it(): void
    {
        [$template, $service] = $this->templateWithService(['headline', 'rsvp_enabled', 'countdown_enabled', 'venue_address']);
        $small = ServicePlan::factory()->create(['service_id' => $service->id, 'features' => ['rsvp_enabled']]);
        $big = ServicePlan::factory()->create(['service_id' => $service->id, 'features' => ['rsvp_enabled', 'countdown_enabled', 'venue_address']]);

        $a = $this->buy($service, $small);
        $b = $this->buy($service, $big);

        $this->assertTrue($a->fieldUnlocked('rsvp_enabled'));
        $this->assertFalse($a->fieldUnlocked('countdown_enabled'));
        $this->assertFalse($a->fieldUnlocked('venue_address'));
        $this->assertTrue($b->fieldUnlocked('countdown_enabled'));
        $this->assertTrue($b->fieldUnlocked('venue_address'));
    }

    public function test_a_sold_invitation_keeps_a_field_that_is_marked_premium_later(): void
    {
        [$template, $service] = $this->templateWithService(['headline', 'dress_code', 'rsvp_enabled']);
        $plan = ServicePlan::factory()->create(['service_id' => $service->id, 'features' => []]);
        $sold = $this->buy($service, $plan);

        $template->update(['premium_fields' => ['rsvp_enabled', 'dress_code']]);

        $this->assertTrue($sold->fresh()->fieldUnlocked('dress_code'));
        $this->assertFalse($sold->fresh()->fieldUnlocked('rsvp_enabled'));
        $this->assertFalse($this->buy($service, $plan)->fieldUnlocked('dress_code'));
    }

    public function test_a_sold_invitation_keeps_its_plans_fields_when_the_plan_changes(): void
    {
        [, $service] = $this->templateWithService(['headline', 'rsvp_enabled', 'countdown_enabled']);
        $plan = ServicePlan::factory()->create(['service_id' => $service->id, 'features' => ['rsvp_enabled']]);
        $sold = $this->buy($service, $plan);

        $plan->update(['features' => ['countdown_enabled']]);

        $this->assertTrue($sold->fresh()->fieldUnlocked('rsvp_enabled'));
        $this->assertFalse($sold->fresh()->fieldUnlocked('countdown_enabled'));
    }

    public function test_a_premium_field_made_free_reaches_invitations_already_sold(): void
    {
        [$template, $service] = $this->templateWithService(['headline', 'rsvp_enabled']);
        $plan = ServicePlan::factory()->create(['service_id' => $service->id, 'features' => []]);
        $sold = $this->buy($service, $plan);
        $this->assertFalse($sold->fieldUnlocked('rsvp_enabled'));

        $template->update(['premium_fields' => []]);

        $this->assertTrue($sold->fresh()->fieldUnlocked('rsvp_enabled'));
    }

    public function test_the_shop_card_lists_what_each_plan_includes(): void
    {
        [$template] = $this->templateWithService(['headline', 'rsvp_enabled', 'countdown_enabled']);

        $this->assertSame(['RSVP'], $template->premiumExtraNames(['rsvp_enabled']));
        $this->assertSame(['RSVP', 'Countdown'], $template->premiumExtraNames());
    }

    public function test_the_admin_edits_the_premium_list_in_the_fields_tab(): void
    {
        $this->actingAs(User::factory()->create());
        [$template] = $this->templateWithService(['headline', 'rsvp_enabled', 'countdown_enabled']);

        Livewire::test(ListInvitationTemplates::class)
            ->mountTableAction(EditAction::class, $template)
            ->assertSet('mountedActions.0.data.premium_fields', ['rsvp_enabled', 'countdown_enabled'])
            // ticking a field the catalog suggests as ★ marks it ★; unticking a field drops it from the list
            ->set('mountedActions.0.data.fields_premium', ['rsvp_enabled', 'countdown_enabled', 'khmer_date'])
            ->assertSet('mountedActions.0.data.premium_fields', ['rsvp_enabled', 'countdown_enabled', 'khmer_date'])
            ->set('mountedActions.0.data.fields_premium', ['rsvp_enabled', 'khmer_date'])
            ->assertSet('mountedActions.0.data.premium_fields', ['rsvp_enabled', 'khmer_date'])
            // and the admin can make one free for every plan
            ->set('mountedActions.0.data.premium_fields', ['khmer_date'])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $template->refresh();
        $this->assertSame(['khmer_date'], $template->premiumFields());
        $this->assertFalse($template->isPremiumField('rsvp_enabled'));
    }

    /** @return array{InvitationTemplate, Service} */
    private function templateWithService(array $fields): array
    {
        $service = Service::factory()->create();
        $template = InvitationTemplate::factory()->create(['service_id' => $service->id, 'fields' => $fields, 'premium_fields' => null]);

        return [$template, $service];
    }

    private function buy(Service $service, ServicePlan $plan): Invitation
    {
        $order = Order::factory()->create(['customer_id' => Customer::factory()->create()->id, 'status' => 'pending_payment']);
        $order->items()->create([
            'service_id' => $service->id,
            'service_plan_id' => $plan->id,
            'service_name_snapshot' => $service->name_en,
            'unit_price' => $plan->price,
            'quantity' => 1,
            'line_total' => $plan->price,
        ]);
        $order->update(['status' => 'paid']);

        return Invitation::where('order_id', $order->id)->firstOrFail();
    }
}

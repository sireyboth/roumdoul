<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\InvitationTemplates\Pages\ListInvitationTemplates;
use App\Filament\Resources\InvitationTemplates\Pages\ViewInvitationTemplate;
use App\Filament\Resources\InvitationTemplates\RelationManagers\InvitationsRelationManager;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\Invitation;
use App\Models\InvitationTemplate;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvitationTemplateFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_page_renders(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListInvitationTemplates::class)->assertSuccessful();
    }

    public function test_create_modal_opens_and_offers_the_design_select(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListInvitationTemplates::class)
            ->mountAction(CreateAction::class)
            ->assertActionMounted(CreateAction::class)
            ->assertSchemaComponentExists('view', 'mountedActionSchema0');
    }

    public function test_creating_a_template_automatically_creates_its_service_and_plans(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListInvitationTemplates::class)
            ->callAction(CreateAction::class, [
                'name' => 'Birthday Bash',
                'slug' => 'birthday-bash',
                'category' => 'birthday',
                'view' => 'invitations.templates.date-asking',
                'fields_universal' => ['sender_name', 'headline'],
                'is_active' => true,
                'plans' => [
                    ['label' => 'Basic', 'price' => 4.99, 'max_recipients' => 10, 'retention_months' => 3, 'features' => [], 'in_stock' => true],
                    ['label' => 'Premium', 'price' => 9.99, 'max_recipients' => 20, 'retention_months' => 12, 'features' => ['venue_address', 'countdown_enabled'],
                        'in_stock' => false, 'extra_guest_price' => 0.25, 'extend_price' => 2,
                        'feature_prices' => [['feature' => 'rsvp_enabled', 'price' => 1.5]]],
                ],
            ])
            ->assertHasNoActionErrors();

        $template = InvitationTemplate::where('slug', 'birthday-bash')->firstOrFail();
        $this->assertNotNull($template->service_id);
        $this->assertSame(['sender_name', 'headline'], $template->fields);

        $service = $template->service;
        $this->assertSame('Birthday Bash', $service->name_en);
        $this->assertCount(2, $service->plans);

        $plans = $service->plans->keyBy('label');
        $this->assertSame(10, $plans['Basic']->max_recipients);
        $this->assertSame(['venue_address', 'countdown_enabled'], $plans['Premium']->features);
        $this->assertTrue($plans['Basic']->in_stock);
        $this->assertFalse($plans['Premium']->in_stock);
        $this->assertSame('0.25', $plans['Premium']->extra_guest_price);
        $this->assertSame('2.00', $plans['Premium']->extend_price);
        $this->assertEquals(['rsvp_enabled' => 1.5], $plans['Premium']->feature_prices);
    }

    public function test_editing_a_templates_fields_does_not_require_touching_plans(): void
    {
        $this->actingAs(User::factory()->create());
        $template = InvitationTemplate::factory()->create(['fields' => ['sender_name']]);

        Livewire::test(ListInvitationTemplates::class)
            ->callAction(TestAction::make(EditAction::class)->table($template), ['fields_universal' => ['sender_name', 'headline', 'message']])
            ->assertHasNoActionErrors();

        $this->assertSame(['sender_name', 'headline', 'message'], $template->fresh()->fields);
    }

    public function test_editing_form_loads_existing_fields_split_into_their_sections(): void
    {
        $this->actingAs(User::factory()->create());
        $template = InvitationTemplate::factory()->create([
            'fields' => ['sender_name', 'groom_name', 'bride_name', 'celebrant_name'],
        ]);

        Livewire::test(ListInvitationTemplates::class)
            ->mountAction(TestAction::make(EditAction::class)->table($template))
            ->assertSet('mountedActions.0.data.fields_universal', ['sender_name'])
            ->assertSet('mountedActions.0.data.fields_wedding', ['groom_name', 'bride_name'])
            ->assertSet('mountedActions.0.data.fields_birthday', ['celebrant_name']);
    }

    public function test_editing_a_templates_wedding_and_birthday_fields_saves_correctly(): void
    {
        $this->actingAs(User::factory()->create());
        $template = InvitationTemplate::factory()->create(['fields' => []]);

        Livewire::test(ListInvitationTemplates::class)
            ->callAction(TestAction::make(EditAction::class)->table($template), [
                'fields_universal' => ['sender_name'],
                'fields_wedding' => ['groom_name', 'bride_name', 'khmer_date'],
                'fields_birthday' => [],
            ])
            ->assertHasNoActionErrors();

        $this->assertEqualsCanonicalizing(
            ['sender_name', 'groom_name', 'bride_name', 'khmer_date'],
            $template->fresh()->fields,
        );
    }

    public function test_editing_a_templates_name_syncs_to_its_linked_service(): void
    {
        $this->actingAs(User::factory()->create());
        $service = Service::factory()->create(['name_en' => 'Old Name']);
        $template = InvitationTemplate::factory()->create(['service_id' => $service->id, 'name' => 'Old Name']);

        Livewire::test(ListInvitationTemplates::class)
            ->callAction(TestAction::make(EditAction::class)->table($template), ['name' => 'New Name'])
            ->assertHasNoActionErrors();

        $this->assertSame('New Name', $service->fresh()->name_en);
    }

    public function test_plans_of_the_linked_service_are_edited_in_the_template_modal(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create();
        $plan = ServicePlan::factory()->create(['service_id' => $service->id, 'price' => 5, 'max_recipients' => 10, 'extra_photo_price' => null]);
        $template = InvitationTemplate::factory()->create(['service_id' => $service->id]);

        $path = "mountedActions.0.data.service.plans.record-{$plan->id}";

        Livewire::test(ListInvitationTemplates::class)
            ->mountAction(TestAction::make(EditAction::class)->table($template))
            ->assertSet("{$path}.label", $plan->label)
            ->set("{$path}.price", 7)
            ->set("{$path}.extra_photo_price", 0.2)
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $plan->refresh();
        $this->assertSame('7.00', $plan->price);
        $this->assertSame('0.20', $plan->extra_photo_price);
        $this->assertSame(1, $service->plans()->count());
        $this->assertSame(1, Service::count());
    }

    public function test_invitation_linked_services_are_hidden_from_the_main_services_list(): void
    {
        $this->actingAs(User::factory()->create());

        $regularService = Service::factory()->create(['name_en' => 'Gemini Pro']);
        $invitationService = Service::factory()->create(['name_en' => 'Will You Go Out With Me?']);
        InvitationTemplate::factory()->create(['service_id' => $invitationService->id]);

        Livewire::test(ListServices::class)
            ->assertCanSeeTableRecords([$regularService])
            ->assertCanNotSeeTableRecords([$invitationService]);
    }

    public function test_deleting_a_template_purges_its_soft_deleted_invitations(): void
    {
        $this->actingAs(User::factory()->create());

        $template = InvitationTemplate::factory()->create();
        $trashed = Invitation::factory()->for($template, 'template')->create();
        $trashed->delete();

        Livewire::test(ListInvitationTemplates::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($template));

        $this->assertModelMissing($template);
        $this->assertDatabaseMissing('invitations', ['id' => $trashed->id]);
    }

    public function test_a_template_with_live_invitations_is_not_deleted(): void
    {
        $this->actingAs(User::factory()->create());

        $template = InvitationTemplate::factory()->create();
        Invitation::factory()->for($template, 'template')->create();

        Livewire::test(ListInvitationTemplates::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($template))
            ->assertNotified('Template is still in use');

        $this->assertModelExists($template);
    }

    public function test_a_template_whose_service_was_deleted_can_be_relinked_to_the_shop(): void
    {
        $this->actingAs(User::factory()->create());

        $template = InvitationTemplate::factory()->create(['service_id' => null, 'is_active' => true]);

        Livewire::test(ListInvitationTemplates::class)
            ->assertActionVisible(TestAction::make('setUpPricing')->table($template))
            ->callAction(TestAction::make('setUpPricing')->table($template));

        $template->refresh();
        $this->assertNotNull($template->service_id);
        $this->assertSame($template->name, $template->service->name_en);
        $this->assertTrue($template->service->is_active);
        $this->assertSame('digital-invitations', $template->service->category->slug);
        $this->assertCount(1, $template->service->plans);
    }

    public function test_a_linked_template_does_not_offer_set_up_pricing(): void
    {
        $this->actingAs(User::factory()->create());

        $template = InvitationTemplate::factory()->create(['service_id' => Service::factory()]);

        Livewire::test(ListInvitationTemplates::class)
            ->assertActionHidden(TestAction::make('setUpPricing')->table($template));
    }

    public function test_view_page_renders(): void
    {
        $this->actingAs(User::factory()->create());

        $template = InvitationTemplate::factory()->create();

        Livewire::test(ViewInvitationTemplate::class, ['record' => $template->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_template_page_lists_the_template_invitations_and_can_delete_them_to_unblock_deletion(): void
    {
        $this->actingAs(User::factory()->create());

        $template = InvitationTemplate::factory()->create();
        $invitation = Invitation::factory()->for($template, 'template')->create();
        $other = Invitation::factory()->create();

        Livewire::test(InvitationsRelationManager::class, [
            'ownerRecord' => $template,
            'pageClass' => ViewInvitationTemplate::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$invitation])
            ->assertCanNotSeeTableRecords([$other])
            ->callAction(TestAction::make(DeleteAction::class)->table($invitation));

        $this->assertSoftDeleted($invitation);

        Livewire::test(ListInvitationTemplates::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($template));

        $this->assertModelMissing($template);
    }

    public function test_admin_can_give_one_invitation_more_guests_photos_extras_and_time(): void
    {
        $this->actingAs(User::factory()->create());

        $template = InvitationTemplate::factory()->create();
        $invitation = Invitation::factory()->for($template, 'template')->create([
            'max_recipients' => 10,
            'max_gallery_photos' => null,
            'features' => [],
            'expires_at' => now()->addDay(),
            'expiry_reminded_at' => now(),
        ]);

        Livewire::test(InvitationsRelationManager::class, [
            'ownerRecord' => $template,
            'pageClass' => ViewInvitationTemplate::class,
        ])
            ->callAction(TestAction::make(EditAction::class)->table($invitation), [
                'max_recipients' => 60,
                'max_gallery_photos' => 40,
                'features' => ['venue_address'],
                'expires_at' => now()->addMonths(6)->startOfMinute()->toDateTimeString(),
            ])
            ->assertHasNoActionErrors();

        $invitation->refresh();
        $this->assertSame(60, $invitation->max_recipients);
        $this->assertSame(40, $invitation->max_gallery_photos);
        $this->assertSame(['venue_address'], $invitation->features);
        $this->assertTrue($invitation->expires_at->isAfter(now()->addMonths(5)));
        $this->assertNull($invitation->expiry_reminded_at);
    }
}

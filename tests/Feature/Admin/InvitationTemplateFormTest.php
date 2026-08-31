<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\InvitationTemplates\Pages\CreateInvitationTemplate;
use App\Filament\Resources\InvitationTemplates\Pages\EditInvitationTemplate;
use App\Filament\Resources\InvitationTemplates\Pages\ListInvitationTemplates;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\InvitationTemplate;
use App\Models\Service;
use App\Models\User;
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

    public function test_create_page_renders_and_offers_the_real_template_view_as_an_option(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateInvitationTemplate::class)
            ->assertSuccessful()
            ->assertFormFieldExists('view');
    }

    public function test_creating_a_template_automatically_creates_its_service_and_plans(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateInvitationTemplate::class)
            ->fillForm([
                'name' => 'Birthday Bash',
                'slug' => 'birthday-bash',
                'category' => 'birthday',
                'view' => 'invitations.templates.date-asking',
                'fields' => ['sender_name', 'headline'],
                'is_active' => true,
                'plans' => [
                    ['label' => 'Basic', 'price' => 4.99, 'max_recipients' => 10, 'retention_months' => 3, 'features' => []],
                    ['label' => 'Premium', 'price' => 9.99, 'max_recipients' => 20, 'retention_months' => 12, 'features' => ['venue_address', 'countdown_enabled']],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $template = InvitationTemplate::where('slug', 'birthday-bash')->firstOrFail();
        $this->assertNotNull($template->service_id);

        $service = $template->service;
        $this->assertSame('Birthday Bash', $service->name_en);
        $this->assertCount(2, $service->plans);

        $plans = $service->plans->keyBy('label');
        $this->assertSame(10, $plans['Basic']->max_recipients);
        $this->assertSame(['venue_address', 'countdown_enabled'], $plans['Premium']->features);
    }

    public function test_editing_a_templates_fields_does_not_require_touching_plans(): void
    {
        $this->actingAs(User::factory()->create());
        $template = InvitationTemplate::factory()->create(['fields' => ['sender_name']]);

        Livewire::test(EditInvitationTemplate::class, ['record' => $template->getRouteKey()])
            ->fillForm(['fields' => ['sender_name', 'headline', 'message']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['sender_name', 'headline', 'message'], $template->fresh()->fields);
    }

    public function test_editing_a_templates_name_syncs_to_its_linked_service(): void
    {
        $this->actingAs(User::factory()->create());
        $service = Service::factory()->create(['name_en' => 'Old Name']);
        $template = InvitationTemplate::factory()->create(['service_id' => $service->id, 'name' => 'Old Name']);

        Livewire::test(EditInvitationTemplate::class, ['record' => $template->getRouteKey()])
            ->fillForm(['name' => 'New Name'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('New Name', $service->fresh()->name_en);
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
}

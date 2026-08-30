<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_service_page_renders(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateService::class)->assertSuccessful();
    }

    public function test_edit_service_page_renders_with_existing_repeater_data(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create([
            'how_to_use_steps' => ['Step one', 'Step two'],
            'faqs' => [
                ['question' => 'Is this real?', 'answer' => 'Yes.'],
            ],
        ]);

        $component = Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
            ->assertSuccessful();

        $this->assertSame(
            ['Step one', 'Step two'],
            array_values($component->instance()->form->getState()['how_to_use_steps']),
        );
    }

    public function test_plan_retention_and_features_can_be_edited_and_saved(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create();
        $plan = ServicePlan::factory()->create([
            'service_id' => $service->id,
            'retention_months' => null,
            'features' => [],
        ]);

        Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
            ->set("data.plans.record-{$plan->id}.retention_months", 6)
            ->set("data.plans.record-{$plan->id}.features", ['map', 'countdown'])
            ->call('save')
            ->assertHasNoFormErrors();

        $plan->refresh();
        $this->assertSame(6, $plan->retention_months);
        $this->assertSame(['map', 'countdown'], $plan->features);
    }
}

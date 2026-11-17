<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\Category;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_list_renders_with_create_modal(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListServices::class)
            ->assertSuccessful()
            ->mountAction(CreateAction::class)
            ->assertActionMounted(CreateAction::class);
    }

    public function test_a_service_can_be_created_from_the_modal(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();

        Livewire::test(ListServices::class)
            ->callAction(CreateAction::class, [
                'category_id' => $category->id,
                'name_en' => 'Gemini Pro',
                'name_km' => 'Gemini Pro',
                'slug' => 'gemini-pro',
                'short_description' => 'Short',
                'description' => 'Long',
                'base_price' => 9.5,
                'sort_order' => 0,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('services', ['slug' => 'gemini-pro', 'category_id' => $category->id]);
    }

    public function test_edit_modal_loads_existing_repeater_data(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create([
            'how_to_use_steps' => ['Step one', 'Step two'],
            'faqs' => [
                ['question' => 'Is this real?', 'answer' => 'Yes.'],
            ],
        ]);

        $component = Livewire::test(ListServices::class)
            ->mountAction(TestAction::make(EditAction::class)->table($service))
            ->assertActionMounted(TestAction::make(EditAction::class)->table($service));

        $this->assertSame(
            ['Step one', 'Step two'],
            array_values(array_map(fn ($item) => $item['step'], $component->get('mountedActions.0.data.how_to_use_steps'))),
        );
    }

    public function test_plan_retention_features_and_addon_prices_can_be_edited_and_saved(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create();
        $plan = ServicePlan::factory()->create([
            'service_id' => $service->id,
            'retention_months' => null,
            'features' => [],
            'feature_prices' => ['venue_address' => 2],
        ]);

        $component = Livewire::test(ListServices::class)
            ->mountAction(TestAction::make(EditAction::class)->table($service));

        $path = "mountedActions.0.data.plans.record-{$plan->id}";

        // the stored {key: price} json shows up as one friendly row
        $rows = array_values($component->get("{$path}.feature_prices"));
        $this->assertSame([['feature' => 'venue_address', 'price' => 2]], $rows);

        $component
            ->set("{$path}.retention_months", 6)
            ->set("{$path}.features", ['venue_address', 'countdown_enabled'])
            ->set("{$path}.extra_guest_price", 0.5)
            ->set("{$path}.extend_price", 3)
            ->set("{$path}.extend_months", 12)
            ->set("{$path}.feature_prices", [
                'a' => ['feature' => 'venue_address', 'price' => 2.5],
                'b' => ['feature' => 'countdown_enabled', 'price' => 1],
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $plan->refresh();
        $this->assertSame(6, $plan->retention_months);
        $this->assertSame(['venue_address', 'countdown_enabled'], $plan->features);
        $this->assertSame('0.50', $plan->extra_guest_price);
        $this->assertSame('3.00', $plan->extend_price);
        $this->assertSame(12, $plan->extend_months);
        $this->assertNull($plan->extra_photo_price);
        $this->assertEquals(['venue_address' => 2.5, 'countdown_enabled' => 1.0], $plan->feature_prices);
    }
}

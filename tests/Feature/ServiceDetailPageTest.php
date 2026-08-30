<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServicePlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceDetailPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_with_an_out_of_stock_plan(): void
    {
        $service = Service::factory()->create();
        ServicePlan::factory()->create(['service_id' => $service->id, 'label' => '1 Month']);
        ServicePlan::factory()->outOfStock()->create(['service_id' => $service->id, 'label' => '3 Months']);

        $response = $this->get("/service/{$service->slug}");

        $response->assertStatus(200);
        $response->assertSee('1 Month');
        $response->assertSee('3 Months');
    }

    public function test_mount_selects_the_first_in_stock_plan_by_default(): void
    {
        $service = Service::factory()->create();
        $outOfStockPlan = ServicePlan::factory()->outOfStock()->create(['service_id' => $service->id, 'sort_order' => 0]);
        $inStockPlan = ServicePlan::factory()->create(['service_id' => $service->id, 'sort_order' => 1]);

        \Livewire\Livewire::test(\App\Livewire\Pages\ServiceDetailPage::class, ['service' => $service])
            ->assertSet('selectedPlanId', $inStockPlan->id);
    }

    public function test_add_to_cart_is_blocked_when_the_selected_plan_is_out_of_stock(): void
    {
        $service = Service::factory()->create();
        $plan = ServicePlan::factory()->outOfStock()->create(['service_id' => $service->id]);

        \Livewire\Livewire::test(\App\Livewire\Pages\ServiceDetailPage::class, ['service' => $service])
            ->set('selectedPlanId', $plan->id)
            ->call('addToCart');

        $this->assertCount(0, app(\App\Services\CartService::class)->items());
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Pages\ServiceDetailPage;
use App\Models\InvitationTemplate;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_invitation_plan_cards_list_what_each_plan_includes(): void
    {
        $service = Service::factory()->create();
        InvitationTemplate::factory()->create([
            'service_id' => $service->id,
            'fields' => ['sender_name', 'photo_gallery', 'venue_address', 'map_url', 'rsvp_enabled'],
        ]);
        ServicePlan::factory()->create(['service_id' => $service->id, 'label' => 'Basic', 'max_recipients' => 10,
            'max_gallery_photos' => 6, 'retention_months' => 3, 'features' => [], 'sort_order' => 0]);
        ServicePlan::factory()->create(['service_id' => $service->id, 'label' => 'Premium', 'badge' => 'Best value',
            'max_recipients' => 50, 'retention_months' => 12, 'features' => ['venue_address'], 'sort_order' => 1]);

        $this->get("/service/{$service->slug}")
            ->assertOk()
            ->assertSee('Best value')
            ->assertSee('10 guest links')
            ->assertSee('6 gallery photos')
            ->assertSee('Online 3 months')
            ->assertSee('Online 1 year')
            ->assertSee('Map &amp; directions · RSVP', false); // map_url + venue_address listed once
    }

    public function test_mount_selects_the_first_in_stock_plan_by_default(): void
    {
        $service = Service::factory()->create();
        $outOfStockPlan = ServicePlan::factory()->outOfStock()->create(['service_id' => $service->id, 'sort_order' => 0]);
        $inStockPlan = ServicePlan::factory()->create(['service_id' => $service->id, 'sort_order' => 1]);

        Livewire::test(ServiceDetailPage::class, ['service' => $service])
            ->assertSet('selectedPlanId', $inStockPlan->id);
    }

    public function test_add_to_cart_is_blocked_when_the_selected_plan_is_out_of_stock(): void
    {
        $service = Service::factory()->create();
        $plan = ServicePlan::factory()->outOfStock()->create(['service_id' => $service->id]);

        Livewire::test(ServiceDetailPage::class, ['service' => $service])
            ->set('selectedPlanId', $plan->id)
            ->call('addToCart');

        $this->assertCount(0, app(CartService::class)->items());
    }

    public function test_add_to_cart_rejects_a_plan_from_another_service(): void
    {
        $service = Service::factory()->create(['base_price' => 20]);
        ServicePlan::factory()->create(['service_id' => $service->id, 'price' => 20]);
        $cheapPlan = ServicePlan::factory()->create(['service_id' => Service::factory()->create()->id, 'price' => 1]);

        Livewire::test(ServiceDetailPage::class, ['service' => $service])
            ->set('selectedPlanId', $cheapPlan->id)
            ->call('addToCart')
            ->assertSet('added', false);

        $this->assertSame([], app(CartService::class)->raw());
    }

    public function test_add_to_cart_rejects_no_plan_on_a_service_that_has_plans(): void
    {
        $service = Service::factory()->create(['base_price' => 1]);
        ServicePlan::factory()->create(['service_id' => $service->id, 'price' => 20]);

        Livewire::test(ServiceDetailPage::class, ['service' => $service])
            ->set('selectedPlanId', null)
            ->call('addToCart')
            ->assertSet('added', false);

        $this->assertSame([], app(CartService::class)->raw());
    }

    public function test_add_to_cart_rejects_a_negative_quantity(): void
    {
        $service = Service::factory()->create(['base_price' => 19]);

        Livewire::test(ServiceDetailPage::class, ['service' => $service])
            ->set('quantity', -1)
            ->call('addToCart')
            ->assertHasErrors('quantity')
            ->assertSet('added', false);

        $this->assertSame([], app(CartService::class)->raw());
    }
}

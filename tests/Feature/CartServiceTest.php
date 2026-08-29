<?php

namespace Tests\Feature;

use App\Models\PromoCode;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CartService $cart;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cart = app(CartService::class);
    }

    public function test_adding_a_service_creates_a_line_item(): void
    {
        $service = Service::factory()->create(['base_price' => 10]);

        $this->cart->add($service->id, null, 1);

        $items = $this->cart->items();

        $this->assertCount(1, $items);
        $this->assertSame(1, $items->first()->quantity);
        $this->assertSame(10.0, $items->first()->unit_price);
    }

    public function test_adding_the_same_service_and_plan_twice_increments_quantity(): void
    {
        $service = Service::factory()->create();

        $this->cart->add($service->id, null, 1);
        $this->cart->add($service->id, null, 2);

        $items = $this->cart->items();

        $this->assertCount(1, $items);
        $this->assertSame(3, $items->first()->quantity);
    }

    public function test_items_use_plan_price_over_base_price(): void
    {
        $service = Service::factory()->create(['base_price' => 10]);
        $plan = ServicePlan::factory()->create(['service_id' => $service->id, 'price' => 25]);

        $this->cart->add($service->id, $plan->id, 2);

        $item = $this->cart->items()->first();

        $this->assertSame(25.0, $item->unit_price);
        $this->assertSame(50.0, $item->line_total);
    }

    public function test_items_silently_drops_lines_whose_service_was_deleted(): void
    {
        $service = Service::factory()->create();
        $this->cart->add($service->id, null, 1);

        $service->delete();

        $this->assertCount(0, $this->cart->items());
    }

    public function test_update_quantity_removes_line_when_quantity_drops_below_one(): void
    {
        $service = Service::factory()->create();
        $this->cart->add($service->id, null, 1);
        $key = $this->cart->key($service->id, null);

        $this->cart->updateQuantity($key, 0);

        $this->assertCount(0, $this->cart->items());
    }

    public function test_remove_deletes_the_line(): void
    {
        $service = Service::factory()->create();
        $this->cart->add($service->id, null, 1);
        $key = $this->cart->key($service->id, null);

        $this->cart->remove($key);

        $this->assertCount(0, $this->cart->items());
    }

    public function test_clear_empties_cart_and_removes_promo_code(): void
    {
        $service = Service::factory()->create();
        $promoCode = PromoCode::factory()->create();
        $this->cart->add($service->id, null, 1);
        $this->cart->applyPromoCode($promoCode->code);

        $this->cart->clear();

        $this->assertCount(0, $this->cart->items());
        $this->assertNull($this->cart->appliedPromoCode());
    }

    public function test_subtotal_and_total_sum_line_totals(): void
    {
        $serviceA = Service::factory()->create(['base_price' => 10]);
        $serviceB = Service::factory()->create(['base_price' => 5]);
        $this->cart->add($serviceA->id, null, 2);
        $this->cart->add($serviceB->id, null, 3);

        $this->assertSame(35.0, $this->cart->subtotal());
        $this->assertSame(35.0, $this->cart->total());
    }

    public function test_percentage_promo_code_discounts_subtotal(): void
    {
        $service = Service::factory()->create(['base_price' => 100]);
        $this->cart->add($service->id, null, 1);
        $promoCode = PromoCode::factory()->percentage(20)->create();

        $result = $this->cart->applyPromoCode($promoCode->code);

        $this->assertTrue($result['success']);
        $this->assertSame(20.0, $this->cart->discount());
        $this->assertSame(80.0, $this->cart->total());
    }

    public function test_fixed_promo_code_discount_is_capped_at_subtotal(): void
    {
        $service = Service::factory()->create(['base_price' => 10]);
        $this->cart->add($service->id, null, 1);
        $promoCode = PromoCode::factory()->fixed(50)->create();

        $this->cart->applyPromoCode($promoCode->code);

        $this->assertSame(10.0, $this->cart->discount());
        $this->assertSame(0.0, $this->cart->total());
    }

    public function test_expired_promo_code_is_rejected(): void
    {
        $promoCode = PromoCode::factory()->expired()->create();

        $result = $this->cart->applyPromoCode($promoCode->code);

        $this->assertFalse($result['success']);
        $this->assertNull($this->cart->appliedPromoCode());
    }

    public function test_inactive_promo_code_is_rejected(): void
    {
        $promoCode = PromoCode::factory()->inactive()->create();

        $result = $this->cart->applyPromoCode($promoCode->code);

        $this->assertFalse($result['success']);
    }

    public function test_promo_code_past_usage_limit_is_rejected(): void
    {
        $promoCode = PromoCode::factory()->usedUp()->create();

        $result = $this->cart->applyPromoCode($promoCode->code);

        $this->assertFalse($result['success']);
    }

    public function test_unknown_promo_code_is_rejected(): void
    {
        $result = $this->cart->applyPromoCode('DOES-NOT-EXIST');

        $this->assertFalse($result['success']);
    }

    public function test_promo_code_lookup_is_case_insensitive(): void
    {
        $service = Service::factory()->create(['base_price' => 100]);
        $this->cart->add($service->id, null, 1);
        $promoCode = PromoCode::factory()->percentage(10)->create(['code' => 'SAVE10']);

        $result = $this->cart->applyPromoCode('save10');

        $this->assertTrue($result['success']);
        $this->assertSame(10.0, $this->cart->discount());
    }

    public function test_service_percentage_discount_reduces_the_cart_unit_price(): void
    {
        $service = Service::factory()->percentageDiscount(25)->create(['base_price' => 40]);

        $this->cart->add($service->id, null, 1);

        $item = $this->cart->items()->first();
        $this->assertSame(30.0, $item->unit_price);
        $this->assertSame(30.0, $item->line_total);
    }

    public function test_service_fixed_discount_reduces_the_cart_unit_price_and_is_capped_at_zero(): void
    {
        $service = Service::factory()->fixedDiscount(50)->create(['base_price' => 30]);

        $this->cart->add($service->id, null, 1);

        $item = $this->cart->items()->first();
        $this->assertSame(0.0, $item->unit_price);
    }

    public function test_service_discount_also_applies_to_a_selected_plans_price(): void
    {
        $service = Service::factory()->percentageDiscount(10)->create();
        $plan = ServicePlan::factory()->create(['service_id' => $service->id, 'price' => 50]);

        $this->cart->add($service->id, $plan->id, 1);

        $item = $this->cart->items()->first();
        $this->assertSame(45.0, $item->unit_price);
    }

    public function test_service_discount_and_promo_code_stack(): void
    {
        $service = Service::factory()->percentageDiscount(50)->create(['base_price' => 100]);
        $this->cart->add($service->id, null, 1);
        $promoCode = PromoCode::factory()->fixed(10)->create();

        $this->cart->applyPromoCode($promoCode->code);

        $this->assertSame(50.0, $this->cart->subtotal());
        $this->assertSame(10.0, $this->cart->discount());
        $this->assertSame(40.0, $this->cart->total());
    }
}

<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServicePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServicePlan>
 */
class ServicePlanFactory extends Factory
{
    protected $model = ServicePlan::class;

    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'label' => fake()->randomElement(['1 Month', '3 Months', '12 Months']),
            'price' => fake()->randomFloat(2, 1, 100),
            'in_stock' => true,
            'sort_order' => 0,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'in_stock' => false,
        ]);
    }
}

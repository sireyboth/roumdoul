<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invitation;
use App\Models\InvitationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'invitation_template_id' => InvitationTemplate::factory(),
            'slug' => Str::random(12),
            'field_values' => [],
            'max_recipients' => 10,
            'expires_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}

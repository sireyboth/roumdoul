<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invitation;
use App\Models\InvitationTemplate;
use App\Models\Order;
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
            // Paid by default — an invitation only exists in real usage once its order is paid
            // (see Order::provisionInvitations()), so that's the realistic default for tests too.
            // Use ->unpaid() to explicitly test the "waiting for payment" state.
            'order_id' => Order::factory(['status' => 'paid']),
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

    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => Order::factory(['status' => 'pending_payment']),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\InvitationRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvitationRecipient>
 */
class InvitationRecipientFactory extends Factory
{
    protected $model = InvitationRecipient::class;

    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'recipient_name' => fake()->firstName(),
        ];
    }
}

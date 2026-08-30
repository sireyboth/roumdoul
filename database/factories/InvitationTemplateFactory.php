<?php

namespace Database\Factories;

use App\Models\InvitationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InvitationTemplate>
 */
class InvitationTemplateFactory extends Factory
{
    protected $model = InvitationTemplate::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'service_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 100000),
            'category' => fake()->randomElement(['wedding', 'breakup', 'date-asking']),
            'is_premium' => false,
            'is_active' => true,
            'fields' => ['sender_name', 'headline', 'message', 'cover_image'],
            'view' => 'invitations.templates.date-asking',
        ];
    }
}

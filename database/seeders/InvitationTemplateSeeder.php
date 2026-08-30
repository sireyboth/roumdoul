<?php

namespace Database\Seeders;

use App\Models\InvitationTemplate;
use Illuminate\Database\Seeder;

class InvitationTemplateSeeder extends Seeder
{
    /**
     * Seed the invitation template catalog.
     */
    public function run(): void
    {
        InvitationTemplate::updateOrCreate(
            ['slug' => 'date-asking-cute'],
            [
                'name' => 'Will You Go Out With Me?',
                'category' => 'date-asking',
                'is_premium' => false,
                'is_active' => true,
                'fields' => ['sender_name', 'headline', 'message', 'cover_image', 'accent_color'],
                'view' => 'invitations.templates.date-asking',
            ]
        );
    }
}

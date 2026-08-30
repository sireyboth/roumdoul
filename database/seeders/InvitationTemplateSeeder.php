<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\InvitationTemplate;
use App\Models\Service;
use App\Models\ServicePlan;
use Illuminate\Database\Seeder;

class InvitationTemplateSeeder extends Seeder
{
    /**
     * Seed the invitation template catalog, plus a purchasable product + plans so the
     * whole checkout-to-invitation flow can actually be tested end to end.
     */
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['slug' => 'digital-invitations'],
            ['name_en' => 'Digital Invitations', 'name_km' => 'កម្មវត្ថុអញ្ជើញឌីជីថល', 'icon' => 'sparkles']
        );

        $service = Service::updateOrCreate(
            ['slug' => 'date-asking-invitation'],
            [
                'category_id' => $category->id,
                'name_en' => 'Will You Go Out With Me?',
                'name_km' => 'តើអ្នកព្រមចេញលេងជាមួយខ្ញុំទេ?',
                'short_description' => 'A cute, animated digital invitation to ask someone out — send a personal link to each recipient.',
                'description' => 'Pick a plan, fill in your message and photo, then generate a unique shareable link for each recipient. Each link shows the invitation personalized with their name.',
                'base_price' => 2.99,
                'is_active' => true,
            ]
        );

        ServicePlan::updateOrCreate(
            ['service_id' => $service->id, 'label' => 'Basic — 10 recipients, 3 months'],
            ['price' => 2.99, 'max_recipients' => 10, 'retention_months' => 3, 'features' => [], 'sort_order' => 0]
        );

        ServicePlan::updateOrCreate(
            ['service_id' => $service->id, 'label' => 'Premium — 20 recipients, 1 year'],
            ['price' => 5.99, 'max_recipients' => 20, 'retention_months' => 12, 'features' => ['map', 'countdown', 'rsvp', 'music'], 'sort_order' => 1]
        );

        InvitationTemplate::updateOrCreate(
            ['slug' => 'date-asking-cute'],
            [
                'service_id' => $service->id,
                'name' => 'Will You Go Out With Me?',
                'category' => 'date-asking',
                'is_premium' => false,
                'is_active' => true,
                'fields' => [
                    'sender_name', 'headline', 'message', 'cover_image', 'event_date',
                    'venue_name', 'venue_address', 'rsvp_enabled', 'countdown_enabled',
                    'music_url', 'cta_label', 'cta_url', 'accent_color',
                ],
                'view' => 'invitations.templates.date-asking',
            ]
        );
    }
}

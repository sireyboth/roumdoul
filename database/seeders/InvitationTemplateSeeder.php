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
            ['price' => 5.99, 'max_recipients' => 20, 'retention_months' => 12, 'features' => ['venue_address', 'countdown_enabled', 'rsvp_enabled', 'cta_label', 'cta_url'], 'sort_order' => 1]
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

        // Demo-only for now — no Service/plans yet, so it's previewable at
        // /templates/admire-gallery/demo but not purchasable until priced in admin
        // (Catalog > Invitation Templates > this row > "Manage pricing & plans").
        InvitationTemplate::updateOrCreate(
            ['slug' => 'admire-gallery'],
            [
                'service_id' => null,
                'name' => 'Admire',
                'category' => 'romantic-surprise',
                'is_premium' => true,
                'is_active' => true,
                'fields' => [
                    'sender_name', 'headline', 'message', 'cover_image',
                    'photo_gallery', 'music_url', 'cta_label', 'cta_url', 'accent_color',
                ],
                'view' => 'invitations.templates.admire',
            ]
        );

        // Demo-only for now — no Service/plans yet, so it's previewable at
        // /templates/private-screening/demo but not purchasable until priced in admin
        // (Catalog > Invitation Templates > this row > "Manage pricing & plans").
        InvitationTemplate::updateOrCreate(
            ['slug' => 'private-screening'],
            [
                'service_id' => null,
                'name' => 'Private Screening',
                'category' => 'wedding',
                'is_premium' => true,
                'is_active' => true,
                'fields' => [
                    'sender_name', 'groom_name', 'bride_name', 'message', 'cover_image',
                    'event_date', 'venue_name', 'venue_address', 'photo_gallery',
                    'event_schedule', 'rsvp_enabled',
                ],
                'view' => 'invitations.templates.private-screening',
            ]
        );

        // Khmer Traditional Wedding — purchasable (Service + plans), previewable at
        // /templates/khmer-traditional-wedding/demo. Plans unlock the premium extras
        // (map, countdown, RSVP, gallery, schedule, story) via `features`.
        $khmerService = Service::updateOrCreate(
            ['slug' => 'khmer-traditional-wedding'],
            [
                'category_id' => $category->id,
                'name_en' => 'Khmer Traditional Wedding',
                'name_km' => 'លិខិតអញ្ជើញមង្គលការបែបប្រពៃណីខ្មែរ',
                'short_description' => 'A premium Cambodian wedding invitation with Khmer ceremony schedule, story, gallery, map and RSVP.',
                'description' => 'Fill in your names, photos, ceremony program and venue, then send a personal link to each guest. Includes countdown, map, RSVP and background music.',
                'base_price' => 9.99,
                'demo_url' => '/templates/khmer-traditional-wedding/demo',
                'is_active' => true,
            ]
        );

        ServicePlan::updateOrCreate(
            ['service_id' => $khmerService->id, 'label' => 'Essential — 50 recipients, 6 months'],
            ['price' => 9.99, 'max_recipients' => 50, 'retention_months' => 6, 'features' => ['venue_address', 'event_schedule'], 'sort_order' => 0]
        );

        ServicePlan::updateOrCreate(
            ['service_id' => $khmerService->id, 'label' => 'Premium — 200 recipients, 1 year'],
            ['price' => 19.99, 'max_recipients' => 200, 'retention_months' => 12, 'features' => ['venue_address', 'event_schedule', 'story_chapters', 'photo_gallery', 'countdown_enabled', 'rsvp_enabled', 'khmer_date'], 'sort_order' => 1]
        );

        InvitationTemplate::updateOrCreate(
            ['slug' => 'khmer-traditional-wedding'],
            [
                'service_id' => $khmerService->id,
                'name' => 'Khmer Traditional Wedding',
                'category' => 'wedding',
                'is_premium' => true,
                'is_active' => true,
                'fields' => [
                    'groom_name', 'bride_name', 'groom_photo', 'bride_photo', 'cover_image',
                    'message', 'event_date', 'khmer_date', 'venue_name', 'venue_address',
                    'story_chapters', 'event_schedule', 'photo_gallery', 'rsvp_enabled',
                    'countdown_enabled', 'music_url', 'groom_phone', 'bride_phone',
                ],
                'view' => 'invitations.templates.khmer-traditional-wedding',
            ]
        );
    }
}

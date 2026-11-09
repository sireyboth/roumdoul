<?php

namespace Tests\Feature;

use App\Models\InvitationTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationTemplateDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_date_asking_demo_renders(): void
    {
        $template = InvitationTemplate::factory()->create([
            'slug' => 'date-asking-cute',
            'view' => 'invitations.templates.date-asking',
            'is_active' => true,
        ]);

        $response = $this->get("/templates/{$template->slug}/demo");

        $response->assertStatus(200);
        $response->assertSee('Bella');
        $response->assertSee('Yes!', false);
    }

    public function test_the_admire_gallery_demo_renders(): void
    {
        $template = InvitationTemplate::factory()->create([
            'slug' => 'admire-gallery',
            'view' => 'invitations.templates.admire',
            'is_active' => true,
        ]);

        $response = $this->get("/templates/{$template->slug}/demo");

        $response->assertStatus(200);
        $response->assertSee('Bella');
        $response->assertSee("let&#039;s play a game", false);
    }

    public function test_the_khmer_traditional_wedding_demo_renders_every_section(): void
    {
        $template = InvitationTemplate::factory()->create([
            'slug' => 'khmer-traditional-wedding',
            'view' => 'invitations.templates.khmer-traditional-wedding',
            'is_active' => true,
        ]);

        $response = $this->get("/templates/{$template->slug}/demo");

        $response->assertOk();
        $response->assertSee('SOKHA');
        $response->assertSee('SREYNEANG');
        $response->assertSee('15 · 12 · 2026', false);
        $response->assertSee('ដំណើរជីវិតរបស់យើង');
        $response->assertSee('កម្មវិធីពិធីមង្គលការ');
        $response->assertSee('ពិធីបង្វិលពពិល');
        $response->assertSee('THE GRAND BALLROOM');
        $response->assertSee('សូមអញ្ជើញបញ្ជាក់ការចូលរួម');
        $response->assertSee('+855 12 345 678');
    }

    public function test_the_khmer_wedding_hides_sections_with_no_customer_data(): void
    {
        $template = InvitationTemplate::factory()->create([
            'view' => 'invitations.templates.khmer-traditional-wedding',
        ]);
        $invitation = \App\Models\Invitation::factory()->create([
            'invitation_template_id' => $template->id,
            'field_values' => ['groom_name' => 'Dara', 'bride_name' => 'Maly'],
        ]);
        $recipient = \App\Models\InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

        $response = $this->get(route('invitation.show', [$invitation, $recipient]));

        $response->assertOk();
        $response->assertSee('DARA');
        $response->assertSee('MALY');
        $response->assertDontSee('កម្មវិធីពិធីមង្គលការ');
        $response->assertDontSee('ទីតាំងប្រារព្ធពិធី');
        $response->assertDontSee('សូមអញ្ជើញបញ្ជាក់ការចូលរួម');
    }

    public function test_the_khmer_wedding_shows_a_schedule_image_instead_of_the_typed_schedule(): void
    {
        \Illuminate\Support\Facades\Storage::fake('s3');
        $template = InvitationTemplate::factory()->create([
            'view' => 'invitations.templates.khmer-traditional-wedding',
        ]);
        $plan = \App\Models\ServicePlan::factory()->create([
            'service_id' => \App\Models\Service::factory()->create()->id,
            'features' => ['event_schedule'],
        ]);
        $invitation = \App\Models\Invitation::factory()->create([
            'invitation_template_id' => $template->id,
            'service_plan_id' => $plan->id,
            'field_values' => [
                'groom_name' => 'Dara', 'bride_name' => 'Maly',
                'event_schedule' => [['time' => '07:00', 'label' => 'TYPED-PROGRAM-ROW']],
                'schedule_image' => 'invitations/1/kbach-schedule.jpg',
            ],
        ]);
        $recipient = \App\Models\InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

        $response = $this->get(route('invitation.show', [$invitation, $recipient]));

        $response->assertOk();
        $response->assertSee('kbach-schedule.jpg');
        $response->assertDontSee('class="kw-sched-item', false);
    }

    public function test_the_kathina_demo_renders_every_section(): void
    {
        $template = InvitationTemplate::factory()->create([
            'slug' => 'kak-thern',
            'view' => 'invitations.templates.kak-thern',
            'is_active' => true,
        ]);

        $response = $this->get("/templates/{$template->slug}/demo");

        $response->assertOk();
        $response->assertSee('title.webp', false);
        $response->assertSee('សូមគោរពអញ្ជើញ');
        $response->assertSee('លោក លោកស្រី សុខ សុភា');
        $response->assertSee('លោក សុខ ហេង និងលោកស្រី ចាន់ ធីតា');
        $response->assertSee('22 · 11 · 2026', false);
        $response->assertSee('កម្មវិធីបុណ្យ');
        $response->assertSee('ពិធីវេរកឋិនទានប្រគេនព្រះសង្ឃ');
        $response->assertSee('វត្តឧណ្ណាលោម');
        $response->assertSee('ចូលរួមបច្ច័យ');
        $response->assertSee('សូមបញ្ជាក់ការចូលរួម');
        $response->assertSee('012 345 678');
        $response->assertDontSee('kt-gate', false);
    }

    public function test_the_kathina_template_hides_sections_with_no_customer_data(): void
    {
        $template = InvitationTemplate::factory()->create([
            'view' => 'invitations.templates.kak-thern',
        ]);
        $invitation = \App\Models\Invitation::factory()->create([
            'invitation_template_id' => $template->id,
            'field_values' => ['venue_name' => 'វត្តបទុមវតី'],
        ]);
        $recipient = \App\Models\InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

        $response = $this->get(route('invitation.show', [$invitation, $recipient]));

        $response->assertOk();
        $response->assertSee('បុណ្យកឋិនទានសាមគ្គី');
        $response->assertSee('វត្តបទុមវតី');
        $response->assertDontSee('កម្មវិធីបុណ្យ');
        $response->assertDontSee('ចូលរួមបច្ច័យ');
        $response->assertDontSee('សូមបញ្ជាក់ការចូលរួម');
    }

    public function test_the_kathina_title_is_the_uploaded_image_else_the_headline_text_else_the_artwork(): void
    {
        $template = InvitationTemplate::factory()->create([
            'view' => 'invitations.templates.kak-thern',
        ]);
        $render = function (array $fields) use ($template) {
            $invitation = \App\Models\Invitation::factory()->create([
                'invitation_template_id' => $template->id,
                'field_values' => $fields,
            ]);
            $recipient = \App\Models\InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

            return $this->get(route('invitation.show', [$invitation, $recipient]))->assertOk();
        };

        $render(['headline' => 'បុណ្យកឋិនវត្តថ្មី', 'title_image' => 'https://example.com/my-title.png'])
            ->assertSee('https://example.com/my-title.png', false)
            ->assertDontSee('title.webp', false)
            ->assertDontSee('<h1 class="kt-headline', false);

        $render(['headline' => 'បុណ្យកឋិនវត្តថ្មី'])
            ->assertSee('<h1 class="kt-headline kt-fit kt-rev">បុណ្យកឋិនវត្តថ្មី</h1>', false)
            ->assertDontSee('title.webp', false);

        $render([])->assertSee('title.webp', false);
    }

    public function test_the_kathina_wording_and_pictures_come_from_the_customer_when_filled_in(): void
    {
        $template = InvitationTemplate::factory()->create([
            'view' => 'invitations.templates.kak-thern',
        ]);
        $plan = \App\Models\ServicePlan::factory()->create([
            'service_id' => \App\Models\Service::factory()->create()->id,
            'features' => ['qr_code', 'rsvp_enabled'],
        ]);
        $invitation = \App\Models\Invitation::factory()->create([
            'invitation_template_id' => $template->id,
            'service_plan_id' => $plan->id,
            'field_values' => [
                'opening_line' => 'សូមនមស្ការព្រះរតនត្រ័យ',
                'cover_image' => 'https://example.com/my-trai.png',
                'background_image' => 'https://example.com/my-wat.jpg',
                'qr_code' => 'https://example.com/qr.png',
                'gift_message' => 'សូមចូលរួមកសាងព្រះវិហារ',
                'rsvp_enabled' => true,
                'rsvp_message' => 'តើលោកអ្នកមកដែរទេ?',
                'thanks_message' => 'សូមឲ្យលោកអ្នកមានសុខភាពល្អ',
            ],
        ]);
        $recipient = \App\Models\InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

        $response = $this->get(route('invitation.show', [$invitation, $recipient]))->assertOk();

        foreach (['សូមនមស្ការព្រះរតនត្រ័យ', 'https://example.com/my-trai.png', 'https://example.com/my-wat.jpg',
            'សូមចូលរួមកសាងព្រះវិហារ', 'តើលោកអ្នកមកដែរទេ?', 'សូមឲ្យលោកអ្នកមានសុខភាពល្អ'] as $own) {
            $response->assertSee($own, false);
        }
        foreach (['នមោ តស្ស', 'trai.webp', 'portrait.webp', 'សម្រាប់សប្បុរសជន', 'តើលោកអ្នកនឹងអញ្ជើញ', 'អាយុ វណ្ណៈ'] as $default) {
            $response->assertDontSee($default, false);
        }
    }

    public function test_an_uploaded_song_plays_instead_of_the_youtube_link_and_loads_only_on_play(): void
    {
        \Illuminate\Support\Facades\Storage::fake('s3');
        foreach (['kak-thern', 'khmer-traditional-wedding', 'admire', 'date-asking'] as $view) {
            $template = InvitationTemplate::factory()->create(['view' => 'invitations.templates.'.$view]);
            $invitation = \App\Models\Invitation::factory()->create([
                'invitation_template_id' => $template->id,
                'field_values' => [
                    'music_file' => 'invitations/1/song.mp3',
                    'music_url' => 'https://www.youtube.com/watch?v=lTRiuFIWV54',
                ],
            ]);
            $recipient = \App\Models\InvitationRecipient::factory()->create(['invitation_id' => $invitation->id]);

            $this->get(route('invitation.show', [$invitation, $recipient]))
                ->assertOk()
                ->assertSee('song.mp3', false)
                ->assertSee('preload="none"', false)
                ->assertDontSee('youtube.com/embed', false);
        }
    }

    public function test_an_inactive_template_demo_404s(): void
    {
        $template = InvitationTemplate::factory()->create([
            'slug' => 'archived-template',
            'is_active' => false,
        ]);

        $this->get("/templates/{$template->slug}/demo")->assertNotFound();
    }
}

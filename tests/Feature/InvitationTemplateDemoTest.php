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

    public function test_an_inactive_template_demo_404s(): void
    {
        $template = InvitationTemplate::factory()->create([
            'slug' => 'archived-template',
            'is_active' => false,
        ]);

        $this->get("/templates/{$template->slug}/demo")->assertNotFound();
    }
}

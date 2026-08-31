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

    public function test_an_inactive_template_demo_404s(): void
    {
        $template = InvitationTemplate::factory()->create([
            'slug' => 'archived-template',
            'is_active' => false,
        ]);

        $this->get("/templates/{$template->slug}/demo")->assertNotFound();
    }
}

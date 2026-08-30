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

    public function test_an_inactive_template_demo_404s(): void
    {
        $template = InvitationTemplate::factory()->create([
            'slug' => 'archived-template',
            'is_active' => false,
        ]);

        $this->get("/templates/{$template->slug}/demo")->assertNotFound();
    }
}

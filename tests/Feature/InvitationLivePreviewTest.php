<?php

namespace Tests\Feature;

use App\Livewire\Pages\Dashboard\InvitationManagePage;
use App\Models\Invitation;
use App\Models\InvitationTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The editor's live preview: a wireframe of each template built from its recipe (config/live-preview.php),
 * plus the «Latin names» switch.
 */
class InvitationLivePreviewTest extends TestCase
{
    use RefreshDatabase;

    private function invitation(array $fields, array $values = [], ?string $view = null): Invitation
    {
        $invitation = Invitation::factory()->create(['field_values' => $values]);
        // no ★ fields, so every field is in the plan (tests that need a locked field set premium_fields)
        $changes = ['fields' => $fields, 'premium_fields' => []];
        if ($view !== null) {
            $changes['view'] = $view;
        }
        $invitation->template->update($changes);

        return $invitation->fresh();
    }

    private function editor(Invitation $invitation)
    {
        return Livewire::actingAs($invitation->customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->call('showSection', 'details');
    }

    public function test_every_recipe_points_at_a_real_template_and_real_blocks(): void
    {
        $recipes = config('live-preview.recipes');
        $this->assertNotEmpty($recipes);

        $blockFolder = resource_path('views/livewire/pages/dashboard/previews/blocks');
        foreach ([...array_values($recipes), config('live-preview.default')] as $recipe) {
            foreach ($recipe as $block) {
                $this->assertFileExists($blockFolder.'/'.$block['block'].'.blade.php');
            }
        }

        foreach (array_keys($recipes) as $view) {
            $this->assertTrue(view()->exists($view), "No template file for the recipe {$view}");
        }
    }

    public function test_every_template_recipe_renders_with_every_field(): void
    {
        $allFields = array_keys(InvitationTemplate::FIELD_CATALOG);

        foreach (array_keys(config('live-preview.recipes')) as $view) {
            $invitation = $this->invitation($allFields, ['groom_name' => 'Sopha', 'event_date' => '2030-01-01T17:00'], $view);

            // every recipe draws sections with field slots (weddings, birthdays, love letters, ceremonies)
            $this->editor($invitation)
                ->assertSeeHtml('class="pv-section"')
                ->assertSeeHtml('data-preview-slot="');
        }
    }

    public function test_the_template_recipe_decides_the_layout(): void
    {
        // Kbach Meas: parents in 3 columns, arch photos; the default layout has neither
        $kbach = $this->invitation(['groom_name', 'bride_name', 'groom_father', 'bride_father', 'cover_image'], [], 'invitations.templates.wedding.kbach-meas');
        $this->editor($kbach)
            ->assertSeeHtml('pv-cols pv-cols--3')
            ->assertSeeHtml('pv-photo pv-photo--arch');

        // A template without a recipe falls back to the default layout
        $plain = $this->invitation(['sender_name', 'groom_name'], [], 'invitations.templates.date-asking-unknown');
        $this->editor($plain)
            ->assertSeeHtml('data-preview-slot="sender_name"')
            ->assertDontSeeHtml('pv-photo--arch');
    }

    public function test_a_field_shows_its_value_or_its_label_in_grey(): void
    {
        $invitation = $this->invitation(['groom_name', 'bride_name', 'venue_name'], ['groom_name' => 'Sopha'], 'invitations.templates.wedding.kbach-meas');

        $this->editor($invitation)
            ->assertSeeHtml('name="groom_name"')
            ->assertSeeHtmlInOrder(['data-preview-slot="groom_name"', 'data-preview-value="Sopha"', '>Sopha</p>'])
            ->assertSeeHtmlInOrder(['is-empty', 'data-preview-slot="bride_name"', '>Bride&#039;s name</p>']);
    }

    public function test_fields_the_template_or_plan_lacks_are_not_drawn(): void
    {
        $invitation = $this->invitation(['groom_name', 'venue_name', 'venue_address'], [], 'invitations.templates.wedding.kbach-meas');
        $invitation->template->update(['premium_fields' => ['venue_address']]);

        $this->editor($invitation->fresh())
            ->assertSeeHtml('data-preview-slot="venue_name"')
            ->assertDontSeeHtml('data-preview-slot="venue_address"')   // ★ field not in the plan
            ->assertDontSeeHtml('data-preview-slot="bride_name"')      // not used by this template
            ->assertDontSeeHtml('data-preview-slot="qr_code"');
    }

    public function test_countdown_and_rsvp_sections_follow_their_switch(): void
    {
        $invitation = $this->invitation(
            ['event_date', 'countdown_enabled', 'rsvp_enabled', 'rsvp_message'],
            ['event_date' => '2030-01-01T17:00', 'countdown_enabled' => true],
            'invitations.templates.wedding.kbach-meas',
        );

        $html = $this->editor($invitation)->html();

        $this->assertStringContainsString('data-preview-switch="countdown_enabled"', $html);
        $this->assertDoesNotMatchRegularExpression('/data-preview-switch="countdown_enabled"\s+hidden/', $html, 'countdown is on');
        $this->assertMatchesRegularExpression('/data-preview-switch="rsvp_enabled"\s+hidden/', $html, 'RSVP is off');
    }

    public function test_the_editor_has_the_live_preview_button_and_no_help_cards(): void
    {
        $invitation = $this->invitation(['groom_name']);

        $this->editor($invitation)
            ->assertSeeHtml('id="live-preview"')
            ->assertSeeHtml('data-live-preview-open')
            ->assertDontSee('Shows here')
            ->assertDontSee('View full screen');
    }

    public function test_the_latin_names_switch_shows_only_with_a_latin_name_field_and_starts_on(): void
    {
        $withLatin = $this->invitation(['groom_name', 'groom_name_latin'], [], 'invitations.templates.wedding.kbach-meas');
        $this->editor($withLatin)
            ->assertSet('fieldValues.show_latin_names', true)
            ->assertSeeHtml('name="show_latin_names"')
            ->assertSeeHtml('data-preview-switch="show_latin_names"');

        $withoutLatin = $this->invitation(['groom_name']);
        $this->editor($withoutLatin)->assertDontSeeHtml('name="show_latin_names"');
    }

    public function test_turning_the_latin_names_off_is_saved_and_hides_them(): void
    {
        $invitation = $this->invitation(['groom_name', 'groom_name_latin', 'bride_name_latin']);

        $this->editor($invitation)
            ->set('fieldValues.groom_name_latin', 'Sopha')
            ->set('fieldValues.show_latin_names', false)
            ->call('save')
            ->assertHasNoErrors();

        $saved = $invitation->fresh()->field_values;
        $this->assertFalse($saved['show_latin_names']);
        $this->assertFalse(InvitationTemplate::showsLatinNames($saved));
        $this->assertTrue(InvitationTemplate::showsLatinNames([]), 'missing = shown');
    }

    public function test_the_switch_is_not_stored_for_a_template_without_latin_names(): void
    {
        $invitation = $this->invitation(['groom_name']);

        $this->editor($invitation)
            ->set('fieldValues.show_latin_names', false)
            ->call('save');

        $this->assertArrayNotHasKey('show_latin_names', $invitation->fresh()->field_values);
    }
}

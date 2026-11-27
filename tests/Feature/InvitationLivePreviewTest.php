<?php

namespace Tests\Feature;

use App\Livewire\Pages\Dashboard\InvitationManagePage;
use App\Models\Invitation;
use App\Models\InvitationTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The editor's live preview: one invitation-shaped layout shared by every template
 * (partials/live-preview-page.blade.php),
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

    public function test_the_shared_layout_draws_every_field_a_guest_can_see(): void
    {
        // not drawn on purpose: settings, links and media that have no place in a wireframe
        $notDrawn = ['music_url', 'music_file', 'accent_color', 'cta_url', 'gallery_feed', 'title_image',
            'rsvp_enabled', 'countdown_enabled', 'event_schedule', 'story_chapters', 'photo_gallery'];
        $allFields = array_keys(InvitationTemplate::FIELD_CATALOG);
        $invitation = $this->invitation($allFields, ['groom_name' => 'Sopha', 'event_date' => '2030-01-01T17:00']);

        $html = $this->editor($invitation)->html();

        foreach (array_diff($allFields, $notDrawn) as $key) {
            $this->assertStringContainsString('data-preview-slot="'.$key.'"', $html, "The live preview doesn't draw {$key}");
        }
    }

    public function test_the_same_layout_fits_any_template_from_its_fields_alone(): void
    {
        // a wedding: the couple and their parents, no celebrant
        $wedding = $this->invitation(['groom_name', 'bride_name', 'groom_father', 'groom_photo'], [], 'invitations.templates.wedding.any-new-template');
        $this->editor($wedding)
            ->assertSeeHtml('data-preview-slot="bride_name"')
            ->assertSeeHtml('data-preview-slot="groom_father"')
            ->assertSeeHtml('data-preview-slot="groom_photo"')
            ->assertDontSeeHtml('data-preview-slot="celebrant_name"');

        // a birthday: the celebrant and the age, no couple, no empty sections
        $birthday = $this->invitation(['celebrant_name', 'turning_age'], [], 'invitations.templates.birthday-unknown');
        $this->editor($birthday)
            ->assertSeeHtml('data-preview-slot="celebrant_name"')
            ->assertSeeHtml('data-preview-slot="turning_age"')
            ->assertDontSeeHtml('data-preview-slot="bride_name"')
            ->assertDontSee('Gift')
            ->assertDontSee('Location');
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

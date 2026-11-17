<?php

namespace Tests\Feature;

use App\Livewire\Pages\Dashboard\InvitationManagePage;
use App\Models\Customer;
use App\Models\Invitation;
use App\Models\InvitationRecipient;
use App\Models\InvitationTemplate;
use App\Models\ServicePlan;
use App\Support\Invitations\ChristmasTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ChristmasTemplateTest extends TestCase
{
    use RefreshDatabase;

    private const FIELDS = [
        'headline', 'sender_name', 'event_date', 'end_time', 'venue_name', 'venue_address', 'map_url',
        'message', 'dress_code', 'what_to_bring', 'rsvp_deadline', 'contact_phone', 'rsvp_enabled', 'rsvp_message', 'music_url',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Date::setTestNow('2026-11-15 10:00:00');
    }

    private function render(array $fields, ?Invitation $invitation = null, string $to = 'Dara', string $page = 'details'): string
    {
        return view('invitations.templates.christmas', [
            'recipientName' => $to,
            'fields' => $fields,
            'invitation' => $invitation,
            ...ChristmasTemplate::pageData($page, '/cover', '/cover/details'),
        ])->render();
    }

    /** Khmer script (U+1780–U+17FF) anywhere in a string. */
    private function assertNoKhmer(string $html): void
    {
        $this->assertDoesNotMatchRegularExpression('/[\x{1780}-\x{17FF}\x{19E0}-\x{19FF}]/u', $html, 'Khmer text found');
    }

    public function test_the_file_demo_cover_is_its_own_page_with_the_gift_box(): void
    {
        $response = $this->get('/demo/christmas?to=Dara')
            ->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('Dear Dara,')
            ->assertSee('Christmas Party')
            ->assertSee('aria-label="Open invitation"', false)
            ->assertSee('Open me')
            ->assertSee('data-href="'.url('/demo/christmas/details').'"', false)
            ->assertSee('id="xm-snow"', false)
            ->assertSee('snowflake.svg', false)
            ->assertSee('Portrait-session1.mp4', false)
            ->assertSee('cistmas%20Text.png', false)
            // page 2 is not on this page
            ->assertDontSee('id="xm-details"', false)
            ->assertDontSee('Saturday, 19 December 2026');

        $this->assertNoKhmer($response->getContent());
    }

    public function test_the_file_demo_details_page_shows_every_section_in_english(): void
    {
        $response = $this->get('/demo/christmas/details')
            ->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('You are warmly invited to')
            ->assertSee('Christmas Party')
            ->assertSee('hosted by')
            ->assertSee('Saturday, 19 December 2026')
            ->assertSee('6:30 PM – 10:00 PM')
            ->assertSee('View map')
            ->assertSee('Red &amp; Green', false)
            ->assertSee('Saturday, 12 December 2026')
            ->assertSee('href="tel:012345678"', false)
            ->assertSee('Call to RSVP')
            ->assertSee('Back to cover')
            ->assertSee('href="'.url('/demo/christmas').'"', false)
            ->assertSee('Portrait-session2.mp4', false)
            ->assertSee('id="xm-snow"', false)
            ->assertDontSee('id="xm-cover"', false)
            ->assertDontSee('cistmas%20Text.png', false);

        $this->assertNoKhmer($response->getContent());
    }

    public function test_only_the_christmas_template_has_a_details_page(): void
    {
        $this->get('/demo/birthday/details')->assertNotFound();
    }

    public function test_guest_links_have_a_details_route_that_needs_saved_details(): void
    {
        $invitation = Invitation::factory()->create(['field_values' => ['headline' => 'Office Party', 'venue_name' => 'Riverside Hall']]);
        $invitation->template->update(['view' => ChristmasTemplate::VIEW]);
        $recipient = InvitationRecipient::factory()->create(['invitation_id' => $invitation->id, 'recipient_name' => 'Dara']);

        $this->get(route('invitation.show', $recipient))
            ->assertOk()
            ->assertSee('Dear Dara,')
            ->assertSee('data-href="'.route('invitation.show.details', $recipient).'"', false);

        $this->get(route('invitation.show.details', $recipient))
            ->assertOk()
            ->assertSee('Office Party')
            ->assertSee('Riverside Hall');

        // nothing to show on page 2 yet: back to the cover
        $invitation->update(['field_values' => []]);
        $this->get(route('invitation.show.details', $recipient))
            ->assertRedirect(route('invitation.show', $recipient));
    }

    public function test_curly_quotes_replace_straight_ones(): void
    {
        $this->assertSame('“Ho ho” it’s ‘fun’', ChristmasTemplate::typo('"Ho ho" it\'s \'fun\''));
        $this->assertStringContainsString('Bring your “best” sweater', $this->render(['what_to_bring' => 'Bring your "best" sweater']));
    }

    public function test_the_admin_demo_uses_the_english_sample_data(): void
    {
        $template = InvitationTemplate::factory()->create([
            'slug' => 'xmas-party', 'view' => ChristmasTemplate::VIEW, 'is_active' => true,
        ]);

        $response = $this->get("/templates/{$template->slug}/demo")
            ->assertOk()
            ->assertSee('Dear Sophea,');
        $this->assertNoKhmer($response->getContent());

        $response = $this->get("/templates/{$template->slug}/demo/details")
            ->assertOk()
            ->assertSee('The Sok Family')
            ->assertSee('href="'.url("/templates/{$template->slug}/demo").'"', false);
        $this->assertNoKhmer($response->getContent());
    }

    public function test_the_page_stays_english_when_the_app_locale_is_khmer(): void
    {
        App::setLocale('km');

        $html = $this->render(['event_date' => '2026-12-19T18:30', 'dress_code' => 'Formal']);

        $this->assertStringContainsString('Saturday, 19 December 2026', $html);
        $this->assertStringContainsString('6:30 PM', $html);
        $this->assertStringContainsString('Dress code', $html);
        $this->assertNoKhmer($html);
        $cover = $this->render([], page: 'cover');
        $this->assertStringContainsString('Open invitation', $cover);
        $this->assertNoKhmer($cover);
    }

    public function test_empty_optional_fields_hide_their_lines_and_the_title_has_a_default(): void
    {
        $html = $this->render(['event_date' => '2026-12-19T18:30'], to: '');
        $cover = $this->render(['event_date' => '2026-12-19T18:30'], to: '', page: 'cover');

        $this->assertStringContainsString('Christmas Party', $html);
        $this->assertStringContainsString('6:30 PM', $html);
        $this->assertStringNotContainsString(' – ', $html);
        $this->assertMatchesRegularExpression('/data-xm-line="dear"\s+hidden/', $cover);
        foreach (['host', 'venue', 'address', 'map', 'message', 'dress', 'bring', 'rsvpby', 'contact', 'call'] as $line) {
            $this->assertMatchesRegularExpression('/data-xm-line="'.$line.'"[^>]*\shidden/', $html, "[{$line}] should be hidden");
        }
        $this->assertStringNotContainsString('id="xm-music"', $html);
    }

    public function test_customer_content_comes_from_fields(): void
    {
        $html = $this->render([
            'headline' => 'Office Xmas Party',
            'sender_name' => 'Team Roumdoul',
            'dress_code' => 'Ugly Sweater',
            'what_to_bring' => 'Cookies',
            'venue_name' => 'Riverside Hall',
            'venue_address' => '12 Sisowath Quay',
        ]);

        $this->assertStringContainsString('Office Xmas Party', $html);
        $this->assertStringNotContainsString('>Christmas Party<', $html);
        $this->assertStringContainsString('Team Roumdoul', $html);
        $this->assertStringContainsString('Ugly Sweater', $html);
        $this->assertStringContainsString('Cookies', $html);
        $this->assertStringContainsString('maps.google.com/?q=12+Sisowath+Quay', $html);
    }

    public function test_premium_lines_stay_hidden_when_the_plan_does_not_unlock_them(): void
    {
        $invitation = Invitation::factory()->create();

        $html = $this->render([
            'venue_name' => 'Riverside Hall',
            'venue_address' => '12 Sisowath Quay',
            'map_url' => 'https://maps.google.com/?q=x',
            'rsvp_enabled' => true,
        ], $invitation);

        $this->assertStringContainsString('Riverside Hall', $html);
        $this->assertStringNotContainsString('12 Sisowath Quay', $html);
        $this->assertMatchesRegularExpression('/data-xm-line="map"[^>]*\shidden/', $html);
        $this->assertMatchesRegularExpression('/data-xm-line="rsvp"[^>]*\shidden/', $html);
    }

    public function test_a_non_http_map_link_is_never_rendered(): void
    {
        $html = $this->render(['map_url' => 'javascript:alert(1)']);

        $this->assertStringNotContainsString('javascript:', $html);
    }

    private function manage(array $fields = self::FIELDS, array $values = []): Testable
    {
        $customer = Customer::factory()->create();
        $plan = ServicePlan::factory()->create(['features' => ['venue_address', 'map_url', 'rsvp_enabled']]);
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id, 'field_values' => $values, 'service_plan_id' => $plan->id]);
        $invitation->template->update(['view' => ChristmasTemplate::VIEW, 'fields' => $fields]);

        return Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation->fresh()]);
    }

    private function validForm(Testable $page): Testable
    {
        return $page
            ->set('fieldValues.headline', 'Christmas Party')
            ->set('fieldValues.sender_name', 'The Sok Family')
            ->set('fieldValues.event_date', '2026-12-19T18:30')
            ->set('fieldValues.venue_name', 'Riverside Hall')
            ->set('fieldValues.venue_address', '12 Sisowath Quay');
    }

    public function test_the_editor_replaces_the_generic_form_with_a_live_preview(): void
    {
        $this->manage()
            ->call('showSection', 'details')
            ->assertSet('fieldValues.headline', 'Christmas Party')
            ->assertSeeHtml('x-data="xmEditor(')
            ->assertSeeHtml('editor=1')
            ->assertSee('Event title')
            ->assertSee('Add more details')
            ->assertSee('Download as PNG')
            ->assertSeeHtml('role="tablist"')
            ->assertSeeHtml('details?editor=1')
            ->assertSee('Reset')
            ->assertSee('Ugly Sweater');
    }

    public function test_the_editor_is_english_when_the_dashboard_is_khmer(): void
    {
        session(['locale' => 'km']);
        App::setLocale('km');

        $html = $this->manage()->call('showSection', 'details')->html();
        $start = strpos($html, 'x-data="xmEditor(');
        $form = substr($html, $start, strpos($html, '</form>', $start) - $start);

        $this->assertStringContainsString('Host name', $form);
        $this->assertNoKhmer($form);
    }

    public function test_a_valid_form_saves(): void
    {
        $this->validForm($this->manage())
            ->set('fieldValues.end_time', '22:00')
            ->set('fieldValues.rsvp_deadline', '2026-12-19')
            ->set('fieldValues.map_url', 'https://maps.app.goo.gl/abc')
            ->set('fieldValues.dress_code', 'Something sparkly')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_required_fields_can_not_be_empty_and_errors_are_english(): void
    {
        App::setLocale('km');

        $page = $this->manage()
            ->set('fieldValues.headline', '')
            ->call('save')
            ->assertHasErrors(['fieldValues.headline', 'fieldValues.sender_name', 'fieldValues.event_date', 'fieldValues.venue_name', 'fieldValues.venue_address']);

        $errors = $page->errors();
        $this->assertSame('Event title is required.', $errors->first('fieldValues.headline'));
        $this->assertSame('Please choose the date.', $errors->first('fieldValues.event_date'));
        $this->assertNoKhmer(implode(' ', $errors->all()));
    }

    public function test_a_premium_address_is_optional_when_the_plan_does_not_include_it(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['view' => ChristmasTemplate::VIEW, 'fields' => self::FIELDS]);

        $this->validForm(Livewire::actingAs($customer, 'customer')->test(InvitationManagePage::class, ['invitation' => $invitation->fresh()]))
            ->set('fieldValues.venue_address', '')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_limits_are_enforced(): void
    {
        $page = $this->validForm($this->manage())
            ->set('fieldValues.headline', str_repeat('a', 41))
            ->set('fieldValues.message', str_repeat('a', 151))
            ->call('save')
            ->assertHasErrors(['fieldValues.headline', 'fieldValues.message']);

        $this->assertSame('Message can be at most 150 characters.', $page->errors()->first('fieldValues.message'));
    }

    public function test_the_date_can_not_be_in_the_past_and_needs_a_start_time(): void
    {
        $page = $this->validForm($this->manage())->set('fieldValues.event_date', '2026-11-14T18:30')->call('save');
        $this->assertSame('The date can’t be in the past.', $page->errors()->first('fieldValues.event_date'));

        $page = $this->validForm($this->manage())->set('fieldValues.event_date', '2026-12-19T')->call('save');
        $this->assertSame('Please choose a start time.', $page->errors()->first('fieldValues.event_date'));

        $this->validForm($this->manage())->set('fieldValues.event_date', '2026-11-15T18:30')->call('save')->assertHasNoErrors();
    }

    public function test_the_end_time_must_be_after_the_start_time(): void
    {
        $page = $this->validForm($this->manage())->set('fieldValues.end_time', '18:30')->call('save');

        $this->assertSame('The end time must be after the start time (6:30 PM).', $page->errors()->first('fieldValues.end_time'));
    }

    public function test_the_rsvp_date_must_be_on_or_before_the_event(): void
    {
        $page = $this->validForm($this->manage())->set('fieldValues.rsvp_deadline', '2026-12-20')->call('save');

        $this->assertSame('The RSVP date must be on or before the event date.', $page->errors()->first('fieldValues.rsvp_deadline'));
    }

    public function test_the_map_link_must_be_a_valid_url(): void
    {
        $this->validForm($this->manage())->set('fieldValues.map_url', 'not a link')->call('save')
            ->assertHasErrors(['fieldValues.map_url']);
        $this->validForm($this->manage())->set('fieldValues.map_url', 'javascript:alert(1)')->call('save')
            ->assertHasErrors(['fieldValues.map_url']);
    }

    public function test_reset_clears_the_form_back_to_the_default_title(): void
    {
        $this->validForm($this->manage())
            ->set('fieldValues.message', 'Hello')
            ->call('resetChristmasForm')
            ->assertSet('fieldValues.headline', 'Christmas Party')
            ->assertSet('fieldValues.sender_name', '')
            ->assertSet('fieldValues.message', '')
            ->assertDispatched('xm-form-reset');
    }

    public function test_other_templates_keep_the_generic_form_and_rules(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['fields' => ['event_date', 'end_time']]);

        Livewire::actingAs($customer, 'customer')
            ->test(InvitationManagePage::class, ['invitation' => $invitation])
            ->call('showSection', 'details')
            ->assertDontSeeHtml('xmEditor(')
            ->assertSeeHtml('id="input-end_time" type="time"')
            ->set('fieldValues.event_date', '2026-12-19T18:30')
            ->set('fieldValues.end_time', '17:00')
            ->call('save')
            ->assertHasErrors(['fieldValues.end_time']);
    }

    public function test_the_owner_preview_in_editor_mode_has_no_placeholder_guest(): void
    {
        $customer = Customer::factory()->create();
        $invitation = Invitation::factory()->create(['customer_id' => $customer->id]);
        $invitation->template->update(['view' => ChristmasTemplate::VIEW]);

        $this->actingAs($customer, 'customer')
            ->get(route('dashboard.invitations.preview', ['invitation' => $invitation, 'editor' => 1]))
            ->assertOk()
            ->assertSee('&quot;editor&quot;:true', false)
            ->assertDontSee('Honored Guest');

        // page 2 in editor mode: the editor sends the (maybe unsaved) details, so no bounce back
        $this->actingAs($customer, 'customer')
            ->get(route('dashboard.invitations.preview.details', ['invitation' => $invitation, 'editor' => 1]))
            ->assertOk()
            ->assertSee('id="xm-details"', false);

        $this->actingAs($customer, 'customer')
            ->get(route('dashboard.invitations.preview', $invitation))
            ->assertOk()
            ->assertSee('Dear Honored Guest,');
    }
}

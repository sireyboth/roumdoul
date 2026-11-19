<?php

namespace Tests\Feature;

use App\Livewire\Pages\Dashboard\InvitationManagePage;
use App\Models\Invitation;
use App\Models\InvitationTemplate;
use App\Support\Invitations\EventDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Livewire\Livewire;
use Tests\TestCase;

/** River Love Sakura: sender name, Cambodia-time countdown, caption ↔ photo order on the strip. */
class RiverLoveTemplateTest extends TestCase
{
    use RefreshDatabase;

    private const VIEW = 'invitations.templates.anniversary-category.river-love';

    private const FIELDS = [
        'sender_name', 'event_date', 'countdown_enabled', 'headline', 'love_reasons', 'closing_line', 'song_title',
        'thanks_message', 'music_url', 'music_file', 'cover_image', 'background_image', 'photo_gallery', 'photo_captions',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Date::setTestNow('2026-11-18 18:41:00'); // 19 Nov 2026, 01:41 in Phnom Penh
    }

    private function render(array $fields): string
    {
        return view(self::VIEW, ['recipientName' => 'Anna', 'fields' => $fields, 'invitation' => null])->render();
    }

    public function test_the_event_date_is_cambodia_time_whatever_the_app_timezone(): void
    {
        $this->assertSame('UTC', config('app.timezone'));
        $date = EventDate::parse('2027-02-14T19:00');

        $this->assertSame('2027-02-14T19:00:00+07:00', $date->toIso8601String());
        $this->assertSame('2027-02-14T12:00:00+00:00', $date->copy()->utc()->toIso8601String());
        // a value that already carries an offset keeps it
        $this->assertSame('2027-02-14T19:00:00+00:00', EventDate::parse('2027-02-14T19:00:00+00:00')->toIso8601String());
        $this->assertNull(EventDate::parse(''));
        $this->assertNull(EventDate::parse('not a date'));
    }

    public function test_the_start_date_shows_a_live_count_up_and_no_countdown(): void
    {
        $html = $this->render(['together_since' => '2022-03-14', 'countdown_enabled' => true]);

        $this->assertStringContainsString('"since":"2022-03-14T00:00:00+07:00"', $html);
        $this->assertStringContainsString('Together since 14 March 2022', $html);
        $this->assertStringContainsString('id="rlTogether"', $html);
        $this->assertStringContainsString('<b>Since 2022</b>', $html);
        // 14 Mar 2022 → 19 Nov 2026 (Phnom Penh) = 1,711 days, shown before the script ticks
        $this->assertStringContainsString('<b data-u="d">1,711</b>', $html);
        $this->assertStringNotContainsString('id="rlCount"', $html);
        $this->assertStringNotContainsString('"countdown"', $html);
        $this->assertStringNotContainsString('Until ', $html);
    }

    public function test_a_future_event_date_shows_no_countdown(): void
    {
        $html = $this->render(['event_date' => '2027-02-14T19:00', 'countdown_enabled' => true]);

        $this->assertStringNotContainsString('id="rlTime"', $html);
        $this->assertStringNotContainsString('Until Sunday', $html);
        $this->assertStringContainsString('<b>With love</b>', $html);
    }

    public function test_an_older_invitation_with_a_past_event_date_still_counts_from_it(): void
    {
        $html = $this->render(['event_date' => '2022-03-14 19:00:00']);

        $this->assertStringContainsString('"since":"2022-03-14T19:00:00+07:00"', $html);
        $this->assertStringContainsString('Together since 14 March 2022', $html);
    }

    public function test_every_countdown_template_emits_the_cambodian_offset(): void
    {
        foreach (['khmer-traditional-wedding', 'khmer-traditional-2', 'kak-thern', 'wedding.vellum-garden'] as $view) {
            $html = view('invitations.templates.'.$view, [
                'recipientName' => 'ភ្ញៀវ', 'invitation' => null,
                'fields' => ['event_date' => '2027-02-14T19:00', 'countdown_enabled' => true],
            ])->render();

            $this->assertStringContainsString('data-target="2027-02-14T19:00:00+07:00"', $html, $view);
        }
    }

    public function test_the_sender_name_replaces_me_everywhere_and_is_escaped(): void
    {
        $html = $this->render(['sender_name' => 'Chetra <b>❤️</b>', 'photo_gallery' => ['https://example.test/1.jpg']]);

        $this->assertStringContainsString('from Chetra &lt;b&gt;❤️&lt;/b&gt;, for Anna', $html);
        $this->assertStringContainsString('Forever yours,<span>Chetra &lt;b&gt;', $html);
        $this->assertStringNotContainsString('<b>❤️</b>', $html);
        $this->assertStringNotContainsString('from Me,', $html);

        $this->assertStringContainsString('from Me, for Anna', $this->render([]));
    }

    public function test_a_caption_small_line_replaces_the_event_year_on_the_photo_strip(): void
    {
        $gallery = array_map(fn ($i) => "https://example.test/{$i}.jpg", range(1, 10));
        $html = $this->render([
            'event_date' => '2027-02-14T19:00',
            'photo_gallery' => $gallery,
            'photo_captions' => "one\ntwo\nthree\nfour\nThe day you said yes | 2022\nDrives | 2023\nSundays | 2024\nKep | 2025\nUs | 2026",
        ]);

        // captions 1–4 title the scratch cards, which show gallery photos 1–4
        preg_match_all('/class="rl-ph-btn" data-full="([^"]+)" data-cap="([^"]*)" aria-label="Open photo \d"/', explode('id="rlPhotos"', $html)[0], $m);
        $this->assertSame(array_slice($gallery, 0, 4), array_slice($m[1], 0, 4));
        $this->assertSame(['one', 'two', 'three', 'four'], array_slice($m[2], 0, 4));

        // on the strip, a photo whose caption has a small line never gets the event year
        preg_match_all('/data-full="([^"]+)" data-cap="[^"]*"[^>]*>\s*<img[^>]*>\s*<\/button>\s*<span class="rl-lab rl-lab-bottom rl-lab-(\w+)">([^<]+)</', explode('id="rlPhotos"', $html)[1], $cards, PREG_SET_ORDER);
        $this->assertNotEmpty($cards);
        foreach ($cards as [, $src, $kind, $label]) {
            $n = (int) basename($src, '.jpg');
            if ($n >= 5 && $n <= 9) {
                $this->assertSame((string) (2017 + $n), trim($label), "photo {$n}");
            }
        }
        $this->assertStringContainsString('<span class="rl-lab rl-lab-bottom rl-lab-tag">2022</span>', $html);
    }

    public function test_the_editor_shows_your_name_first_under_invitation_wording_and_saves_it_trimmed(): void
    {
        $template = InvitationTemplate::factory()->create(['view' => self::VIEW, 'fields' => self::FIELDS]);
        $invitation = Invitation::factory()->create(['invitation_template_id' => $template->id]);

        $page = new InvitationManagePage;
        $page->invitation = $invitation->load('template');
        $groups = $page->sectionGroups('details');
        $this->assertSame('sender_name', $groups['words']['fields'][0]);
        $this->assertArrayNotHasKey('people', $groups);

        $catalog = $template->localizedFields();
        $this->assertSame('Your name', $catalog['sender_name']['label']);
        $this->assertSame('Chetra', $catalog['sender_name']['example']);
        $this->assertSame(40, $catalog['sender_name']['max']);
        $this->assertSame('Every river finds its way back to you', $catalog['headline']['example']);
        // other templates keep the shared catalog
        $this->assertSame('សូមគោរពអញ្ជើញ', InvitationTemplate::localizedCatalog()['headline']['example']);

        $this->actingAs($invitation->customer, 'customer');
        Livewire::test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('fieldValues.sender_name', str_repeat('x', 41))
            ->call('save')
            ->assertHasErrors(['fieldValues.sender_name' => 'max'])
            ->set('fieldValues.sender_name', '  Chetra ❤️  ')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Chetra ❤️', $invitation->fresh()->field_values['sender_name']);
    }

    public function test_the_start_date_is_first_under_when_and_where_and_cant_be_in_the_future(): void
    {
        $template = InvitationTemplate::factory()->create(['view' => self::VIEW, 'fields' => [...self::FIELDS, 'together_since']]);
        $invitation = Invitation::factory()->create(['invitation_template_id' => $template->id]);

        $page = new InvitationManagePage;
        $page->invitation = $invitation->load('template');
        // River Love has no countdown: the event date and the countdown switch stay out of its editor
        $groups = $page->sectionGroups('details');
        $this->assertSame(['together_since'], $groups['when_where']['fields']);
        $this->assertArrayNotHasKey('options', $groups);
        $this->assertNotContains('event_date', $page->sectionFields('details'));
        $this->assertNotContains('countdown_enabled', $template->editorFields());

        $this->actingAs($invitation->customer, 'customer');
        Livewire::test(InvitationManagePage::class, ['invitation' => $invitation])
            ->set('fieldValues.together_since', '2026-11-20') // tomorrow in Phnom Penh
            ->call('save')
            ->assertHasErrors(['fieldValues.together_since'])
            ->set('fieldValues.together_since', '2026-11-19') // today in Phnom Penh, still the 18th in UTC
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('2026-11-19', $invitation->fresh()->field_values['together_since']);
    }

    public function test_rose_bloom_reads_the_start_date_with_the_old_event_date_as_fallback(): void
    {
        $render = fn (array $fields) => view('invitations.templates.anniversary-category.rose-bloom', ['recipientName' => 'Anna', 'fields' => $fields, 'invitation' => null])->render();

        $this->assertStringContainsString('1,711 days of us', $render(['together_since' => '2022-03-14']));
        $this->assertStringContainsString('1,711 days of us', $render(['event_date' => '2022-03-14 19:00:00']));
    }
}

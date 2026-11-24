<?php

namespace Tests\Feature;

use App\Support\Invitations\WeddingData;
use Tests\TestCase;

class WeddingDataTest extends TestCase
{
    private function sample(array $extra = []): array
    {
        return array_merge([
            'groom_name' => 'SOKHA', 'bride_name' => 'SREYNEANG',
            'groom_father' => 'ហេង ឌី', 'groom_mother' => 'លោកស្រី សុខ ជា',
            'event_date' => '2026-12-15 17:00:00', 'end_time' => '21:30',
            'venue_name' => 'The Grand Ballroom', 'map_url' => 'https://www.google.com/maps/@11.5564,104.9282,16z',
            'photo_gallery' => ['https://x.test/1.jpg', 'https://x.test/2.jpg'], 'photo_captions' => "one\ntwo",
            'story_chapters' => [['title' => 'ហាងកាហ្វេ | ២០២១', 'text' => 'hi'], ['title' => '', 'text' => '']],
            'event_schedule' => [['time' => '05:00 PM', 'label' => 'ទទួលភ្ញៀវ']],
            'dress_code' => 'ពណ៌ក្រែម ឬ sage', 'rsvp_deadline' => '2026-12-05',
            'qr_code' => 'https://x.test/qr.png', 'gift_account' => "ABA · SOKHA\n000 123 456",
            'groom_phone' => '+855 12 345 678', 'contact_phone' => '+855 12 345 678',
            'cta_label' => 'Telegram', 'cta_url' => 'https://t.me/x',
            'music_url' => 'https://youtu.be/dQw4w9WgXcQ', 'rsvp_enabled' => true, 'countdown_enabled' => true,
        ], $extra);
    }

    public function test_it_prepares_every_wedding_value_in_khmer(): void
    {
        $w = WeddingData::from($this->sample());

        $this->assertSame('Sokha & Sreyneang', $w->coupleTitle);
        $this->assertSame('S&S', $w->initials);
        $this->assertSame(['លោក ហេង ឌី', 'លោកស្រី សុខ ជា'], $w->groomParents);
        $this->assertSame('ថ្ងៃអង្គារ ទី១៥ ខែធ្នូ ឆ្នាំ២០២៦', $w->dateKhmer);
        $this->assertSame('វេលាម៉ោង ៥:០០ ល្ងាច – ៩:៣០ ល្ងាច', $w->timeKhmer);
        $this->assertStringEndsWith('+07:00', $w->isoDate); // Cambodia time, not UTC
        $this->assertSame(15, $w->calendar['day']);
        $this->assertTrue($w->showCountdown);
        $this->assertSame('https://www.google.com/maps/@11.5564,104.9282,16z', $w->mapUrl);
        $this->assertStringContainsString('11.5564%2C104.9282', $w->mapEmbed);
        $this->assertSame(['place' => 'ហាងកាហ្វេ', 'when' => '២០២១', 'text' => 'hi'], $w->story->first());
        $this->assertCount(1, $w->story); // empty chapters dropped
        $this->assertSame('៥:០០ ល្ងាច', $w->schedule->first()['time']);
        $this->assertSame(['ក្រែម', 'Sage'], array_column($w->dressColours, 'name'));
        $this->assertSame('ថ្ងៃសៅរ៍ ទី៥ ខែធ្នូ ឆ្នាំ២០២៦', $w->rsvpBy);
        $this->assertSame('000123456', $w->giftNumber);
        $this->assertCount(1, $w->contacts); // the same phone twice shows once
        $this->assertSame('+85512345678', $w->contacts->first()['tel']);
        $this->assertSame('Telegram', $w->ctaLabel);
        $this->assertSame('dQw4w9WgXcQ', $w->youtubeId);
        $this->assertSame('https://x.test/1.jpg', $w->cover); // no cover_image → first gallery photo
        $this->assertSame('two', $w->captions[1]);
        $this->assertNull($w->rsvpUrl); // demo page: nothing to post to
    }

    public function test_empty_fields_give_empty_values_and_template_defaults(): void
    {
        $w = WeddingData::from([], null, null, ['accent' => '#2f7a5a', 'closing_line' => 'សូមអរគុណ']);

        $this->assertSame('', $w->coupleTitle);
        $this->assertSame('♡', $w->initials);
        $this->assertSame('#2f7a5a', $w->accent);
        $this->assertSame('សូមអរគុណ', $w->closingLine);
        $this->assertNull($w->eventDate);
        $this->assertNull($w->giftQr);
        $this->assertFalse($w->showVenue);
        $this->assertFalse($w->showRsvp);
        $this->assertTrue($w->gallery->isEmpty());
        $this->assertSame([], $w->dressColours);
        $this->assertNotSame('', $w->receptionText); // classic wording when empty
    }

    public function test_khmer_helpers(): void
    {
        $this->assertSame('១២:០០ ថ្ងៃត្រង់', WeddingData::khTime(12, '00'));
        $this->assertSame('៦:០០ ព្រឹក', WeddingData::scheduleTime('06:00 AM'));
        $this->assertSame('ពេលល្ងាច', WeddingData::scheduleTime('ពេលល្ងាច')); // not a time: left as typed
        $this->assertSame('សុខា', WeddingData::pretty('សុខា'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\InvitationTemplate;

class InvitationTemplateDemoController extends Controller
{
    public function __invoke(InvitationTemplate $template)
    {
        abort_unless($template->is_active, 404);

        if ($template->slug === 'khmer-traditional-wedding') {
            return view('invitations.show', [
                'view' => $template->view,
                'recipientName' => 'ភ្ញៀវកិត្តិយស',
                'invitation' => null,
                'fields' => $this->khmerWeddingDemoFields(),
            ]);
        }

        return view('invitations.show', [
            'view' => $template->view,
            'recipientName' => 'Bella',
            'invitation' => null,
            'fields' => [
                'sender_name' => 'Alex',
                'message' => "I've been wanting to ask you this for a while... no pressure, just vibes ✨",
                'cover_image' => null,
                'event_date' => now()->addDays(9)->setTime(19, 0),
                'venue_name' => 'Rooftop Café, BKK1',
                'venue_address' => 'Rooftop Café, Street 51, Phnom Penh',
                'rsvp_enabled' => true,
                'countdown_enabled' => true,
                'music_url' => 'https://www.youtube.com/watch?v=lTRiuFIWV54',
                'cta_label' => 'See my playlist',
                'cta_url' => 'https://open.spotify.com',
                'accent_color' => '#e0709f',
                'photo_gallery' => collect(range(1, 8))
                    ->map(fn ($i) => "https://picsum.photos/seed/roumdoul{$i}/600/800")
                    ->all(),
            ],
        ]);
    }

    /**
     * Realistic sample content for the Khmer Traditional Wedding preview — the same field keys
     * a real customer fills in through the dashboard form, so the demo exercises every section.
     */
    private function khmerWeddingDemoFields(): array
    {
        return [
            'groom_name' => 'SOKHA',
            'bride_name' => 'SREYNEANG',
            'event_date' => '2026-12-15 17:00:00',
            'khmer_date' => 'ថ្ងៃអង្គារ ១៤កើត ខែបុស្ស ព.ស. ២៥៧០',
            'venue_name' => 'The Grand Ballroom',
            'venue_address' => 'The Grand Ballroom, Phnom Penh, Cambodia',
            'rsvp_enabled' => true,
            'countdown_enabled' => true,
            'groom_phone' => '+855 12 345 678',
            'bride_phone' => '+855 96 765 4321',
            'cover_image' => 'https://picsum.photos/seed/khmerwed-cover/900/1200',
            'groom_photo' => 'https://picsum.photos/seed/khmerwed-groom/600/800',
            'bride_photo' => 'https://picsum.photos/seed/khmerwed-bride/600/800',
            'story_chapters' => [
                ['title' => 'ការចាប់ផ្ដើម', 'text' => 'យើងបានជួបគ្នាជាលើកដំបូងនៅក្នុងថ្ងៃភ្លៀងមួយ ដែលគ្មាននរណាដឹងថាវានឹងក្លាយជាដំណើររឿងដ៏ស្រស់ស្អាត។'],
                ['title' => 'ការស្គាល់គ្នា', 'text' => 'ពីការផ្ញើសារតិចតួច ទៅជាការសន្ទនាយ៉ាងយូរ យើងចាប់ផ្ដើមស្គាល់ចិត្តគ្នាកាន់តែស៊ីជម្រៅ។'],
                ['title' => 'ការយល់ចិត្តគ្នា', 'text' => 'ក្នុងគ្រាសប្បាយ និងគ្រាលំបាក យើងបានរៀនពីតម្លៃនៃការជឿទុកចិត្ត និងការគាំទ្រគ្នាទៅវិញទៅមក។'],
                ['title' => 'ការសម្រេចចិត្ត', 'text' => 'នៅក្រោមពន្លឺព្រះច័ន្ទ គាត់បានសួរសំណួរដ៏សំខាន់ ហើយចម្លើយតែមួយគត់គឺ «យល់ព្រម»។'],
                ['title' => 'ថ្ងៃដ៏មានអត្ថន័យ', 'text' => 'ឥឡូវនេះ យើងរង់ចាំចែករំលែកថ្ងៃដ៏វិសេសនេះជាមួយក្រុមគ្រួសារ និងមិត្តជិតស្និទ្ធ។'],
            ],
            'event_schedule' => [
                ['time' => '06:00 AM', 'label' => 'ពិធីហែជំនូន'],
                ['time' => '07:00 AM', 'label' => 'ពិធីសែនក្រុងពាលី'],
                ['time' => '08:30 AM', 'label' => 'ពិធីចងដៃ'],
                ['time' => '10:00 AM', 'label' => 'ពិធីបង្វិលពពិល'],
                ['time' => '05:00 PM', 'label' => 'ពិធីទទួលភ្ញៀវ'],
            ],
            'photo_gallery' => collect(range(1, 6))
                ->map(fn ($i) => "https://picsum.photos/seed/khmerwed{$i}/700/".($i % 2 ? '900' : '700'))
                ->all(),
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\InvitationTemplate;
use App\Support\Invitations\ChristmasTemplate;

class InvitationTemplateDemoController extends Controller
{
    public function __invoke(InvitationTemplate $template)
    {
        abort_unless($template->is_active, 404);

        if (in_array($template->slug, ['khmer-traditional-wedding', 'khmer-traditional-2'], true)) {
            return view('invitations.show', [
                'view' => $template->view,
                'recipientName' => 'ភ្ញៀវកិត្តិយស',
                'invitation' => null,
                'fields' => $this->khmerWeddingDemoFields(),
            ]);
        }

        // matched by design file, not slug — admin picks the slug
        if (ChristmasTemplate::is($template->view)) {
            return $this->christmasTemplateDemo($template, 'cover');
        }

        if ($template->slug === 'kak-thern') {
            return view('invitations.show', [
                'view' => $template->view,
                'recipientName' => 'លោក លោកស្រី សុខ សុភា',
                'invitation' => null,
                'fields' => $this->kathinaDemoFields(),
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

    /** Page 2 (event details) of a two-page template's demo — only the Christmas template has one. */
    public function details(InvitationTemplate $template)
    {
        abort_unless($template->is_active && ChristmasTemplate::is($template->view), 404);

        return $this->christmasTemplateDemo($template, 'details');
    }

    private function christmasTemplateDemo(InvitationTemplate $template, string $page)
    {
        return view('invitations.show', [
            'view' => $template->view,
            'recipientName' => 'Sophea',
            'invitation' => null,
            'fields' => $this->christmasDemoFields(),
            ...ChristmasTemplate::pageData($page, route('templates.demo', $template), route('templates.demo.details', $template)),
        ]);
    }

    /** /demo/{path}/details — page 2 of a two-page template file (Christmas). */
    public function fileDetails(string $path)
    {
        $view = 'invitations.templates.'.str_replace('/', '.', trim($path, '/'));
        abort_unless(ChristmasTemplate::is($view), 404);

        return $this->file($path, 'details');
    }

    /**
     * Review link for any template file, straight from disk — no InvitationTemplate row needed.
     * /demo/{path} renders resources/views/invitations/templates/{path}.blade.php, e.g.
     * /demo/wedding/{name} or /demo/birthday. ?to=Name sets the guest name.
     * Sample data is picked by folder/name so every section of the design shows.
     */
    public function file(string $path, string $page = 'cover')
    {
        $view = 'invitations.templates.'.str_replace('/', '.', trim($path, '/'));
        abort_unless(view()->exists($view), 404);

        $kind = strtolower(strtok(trim($path, '/'), '/'));
        $fields = match (true) {
            str_contains($kind, 'wedding') || str_contains($kind, 'khmer-traditional') => $this->khmerWeddingDemoFields() + [
                'contact_phone' => '012 345 678',
                'host_names' => "លោក ហេង ឌី និងលោកស្រី សុខ ជា\nលោក ឈិន សារ៉ាត និងលោកស្រី ពិស្ណុ សារ៉ាត",
            ],
            str_contains($kind, 'kathin') || str_contains($kind, 'kak-thern') || str_contains($kind, 'ceremony') => $this->kathinaDemoFields(),
            str_contains($kind, 'christmas') => $this->christmasDemoFields(),
            str_contains($kind, 'anniversary') => $this->anniversaryDemoFields(),
            default => $this->khmerWeddingDemoFields() + $this->kathinaDemoFields() + [
                'sender_name' => 'Alex',
                'celebrant_name' => 'Bella',
                'turning_age' => '25',
                'headline' => 'You are invited',
                'message' => 'We would love to celebrate this special day with you.',
            ],
        };

        // the template's own pictures in public/images/invitations/{file name}/ replace the sample
        // photos: the first is the cover picture, all of them fill the gallery. Only for templates in
        // a subfolder (wedding/{name}) — older top-level ones keep design artwork in that folder
        $ownDir = 'images/invitations/'.basename($path);
        $ownImages = collect(str_contains(trim($path, '/'), '/') && is_dir(public_path($ownDir)) ? scandir(public_path($ownDir)) : [])
            ->filter(fn ($f) => preg_match('/\.(png|jpe?g|webp|gif)$/i', $f))
            ->sort()
            ->map(fn ($f) => asset($ownDir.'/'.rawurlencode($f)))
            ->values();
        if ($ownImages->isNotEmpty()) {
            $fields['cover_image'] = $ownImages->first();
            $fields['photo_gallery'] = $ownImages->all();
        }

        // the Christmas template is two pages; ?to= (the sample guest name) only matters on the cover
        $pages = ChristmasTemplate::is($view)
            ? ChristmasTemplate::pageData($page, route('templates.demo.file', ['path' => $path, 'to' => request('to')]), route('templates.demo.file.details', ['path' => $path]))
            : [];

        return view('invitations.show', [
            'view' => $view,
            // the Christmas template is English-only
            'recipientName' => request('to', match (true) {
                str_contains($kind, 'christmas') => 'Sophea',
                str_contains($kind, 'anniversary') => 'My love',
                default => 'ភ្ញៀវកិត្តិយស',
            }),
            'invitation' => null,
            'fields' => $fields,
            ...$pages,
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
            'couple_initials' => 'ស&ស',
            'groom_father' => 'ហេង ឌី',
            'groom_mother' => 'សុខ ជា',
            'bride_father' => 'ឈិន សារ៉ាត',
            'bride_mother' => 'ពិស្ណុ សារ៉ាត',
            'reception_message' => 'និងញាតិមិត្ត អញ្ជើញចូលរួមជាអធិបតី និងជាភ្ញៀវកិត្តិយសក្នុងពិធីអាពាហ៍ពិពាហ៍ វេលាម៉ោង ០៦:០០ ព្រឹក ដោយមេត្រីភាព ។',
            'thanks_message' => 'សូមថ្លែងអំណរគុណយ៉ាងជ្រាលជ្រៅ ចំពោះការអញ្ជើញចូលរួមជាភ្ញៀវកិត្តិយសក្នុងពិធីរៀបអាពាហ៍ពិពាហ៍ កូនប្រុស-កូនស្រី របស់យើងខ្ញុំ ។ យើងខ្ញុំសូមគោរពជូនពរដល់ភ្ញៀវកិត្តិយសទាំងអស់ សូមប្រកបតែភាពសុខដុម ចម្រើនរុងរឿង ដ៏សុភមង្គល ក្នុងក្រុមគ្រួសារជានិច្ចនិរន្តរ៍។',
            'apology_message' => 'យើងខ្ញុំជាមាតា បិតា កូនប្រុស-កូនស្រី សូមអភ័យទោសក្នុងករណីពុំបានជូនលិខិតអញ្ជើញដោយផ្ទាល់ ។ សូម ឯកឧត្តម លោកឧកញ៉ា លោកជំទាវ លោក លោកស្រី អ្នកនាង កញ្ញា អញ្ជើញចូលរួមជាអធិបតី និងជាភ្ញៀវកិត្តិយសក្នុងពិធីរៀបអាពាហ៍ពិពាហ៍ កូនប្រុស-កូនស្រី របស់យើងខ្ញុំ ដោយមេត្រីភាព ។',
            'event_date' => '2026-12-15 17:00:00',
            'khmer_date' => 'ថ្ងៃអង្គារ ១៤កើត ខែបុស្ស ព.ស. ២៥៧០',
            'venue_name' => 'The Grand Ballroom',
            'venue_address' => 'The Grand Ballroom, Phnom Penh, Cambodia',
            'map_url' => 'https://www.google.com/maps/@11.5564,104.9282,16z',
            'qr_code' => 'https://api.qrserver.com/v1/create-qr-code/?size=480x480&margin=10&data=ROUMDOUL-DEMO-GIFT',
            'gift_account' => "ABA · SOKHA & SREYNEANG\n000 123 456",
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
            'gallery_feed' => true,
            // ?photos=N (1–30) previews the feed with any number of photos, mixed portrait/landscape
            'photo_gallery' => collect(range(1, max(1, min(30, (int) request('photos', 6)))))
                ->map(fn ($i) => "https://picsum.photos/seed/khmerwed{$i}/".($i % 3 === 0 ? '1000/700' : ($i % 2 ? '700/900' : '700/700')))
                ->all(),
        ];
    }

    /**
     * Sample content for the Christmas party preview (English, like the template) — every
     * optional line filled in so the demo shows each one. The date is always next 19 December,
     * so the sample never sits in the past.
     */
    private function christmasDemoFields(): array
    {
        $day = now()->setDate(now()->year, 12, 19)->startOfDay();
        if (now()->toDateString() > $day->toDateString()) {
            $day->addYear();
        }

        return [
            'headline' => 'Christmas Party',
            'sender_name' => 'The Sok Family',
            'event_date' => $day->format('Y-m-d').'T18:30',
            'end_time' => '22:00',
            'venue_name' => 'Riverside Hall',
            'venue_address' => '12 Sisowath Quay, Phnom Penh',
            'map_url' => 'https://www.google.com/maps/@11.5682,104.9306,17z',
            'message' => 'Join us for dinner, carols and a gift exchange. We would love to celebrate with you!',
            'dress_code' => 'Red & Green',
            'what_to_bring' => 'A small gift for the gift exchange',
            'rsvp_deadline' => $day->copy()->subWeek()->format('Y-m-d'),
            'contact_phone' => '012 345 678',
            'rsvp_enabled' => true,
        ];
    }

    /**
     * Sample content for the anniversary / love-letter templates (Rose Bloom): a letter, a gallery
     * big enough for the stamps, the photo strip and every map memory, captions, reasons and a song.
     */
    private function anniversaryDemoFields(): array
    {
        return [
            'sender_name' => 'Dara',
            'headline' => 'You’re my favorite person',
            'message' => "Happy anniversary. I wanted to give you something you could open slowly, so I made you this.\n\n"
                .'Scratch the stamps, press play, open every little heart. Each part is something I love about you, and there were far too many to fit, so think of this as the short version.'."\n\n"
                .'Thank you for every ordinary day that somehow turned into my favorite one.',
            'photo_gallery' => collect(range(1, 13))
                ->map(fn ($i) => "https://picsum.photos/seed/rosebloom{$i}/700/700")
                ->all(),
            'photo_captions' => implode("\n", [
                'my favorite kind of day', 'right here, with you', 'that night we laughed', 'us, always',
                'First photo together | the one you hate, I love', 'Late night drives | windows down, our song on',
                'Sunday mornings | coffee and nowhere to be', 'Our trip to Kep | sandy, sunburnt, happy', 'Just us | my favorite place',
            ]),
            'story_chapters' => [
                ['title' => 'Where we met | Spring 2022', 'text' => 'I almost didn’t go. Best decision I ever made.'],
                ['title' => 'Our first date | A week later', 'text' => 'Two coffees, four hours, zero awkward silences.'],
                ['title' => 'The beach trip | Summer', 'text' => 'Sunburnt, sandy, completely happy.'],
                ['title' => 'Where you said it | That night', 'text' => 'You said it first. I’m still smiling about it.'],
            ],
            'love_reasons' => implode("\n", [
                'The way you say my name when you’re half asleep.',
                'You remember the small things I only mention once.',
                'Your laugh, especially the one you try to hold in.',
                'You make every plan feel like an adventure.',
                'You’re my calm on loud days.',
                'You still hold my hand like it’s the first time.',
                'Because you’re you. That one covers the rest.',
            ]),
            'song_title' => 'Our song',
            'closing_line' => 'I love you, always.',
            'thanks_message' => 'Today, tomorrow, and on every ordinary day in between.',
            'event_date' => '2022-03-14 19:00:00',
            'venue_name' => 'Where it all began: Phnom Penh',
            'accent_color' => '#d65f7f',
        ];
    }

    /**
     * Sample content for the Kathina ceremony preview — the same field keys a real customer
     * fills in through the dashboard form, so the demo exercises every section.
     */
    private function kathinaDemoFields(): array
    {
        return [
            'host_names' => "លោក សុខ ហេង និងលោកស្រី ចាន់ ធីតា\nព្រមទាំងកូនចៅ និងពុទ្ធបរិស័ទចំណុះជើងវត្ត",
            'event_date' => '2026-11-22 07:00:00',
            'khmer_date' => 'ត្រូវនឹងថ្ងៃអាទិត្យ ៣កើត ខែកត្តិក ឆ្នាំម្សាញ់ សប្តស័ក ព.ស. ២៥៧០',
            'venue_name' => 'វត្តឧណ្ណាលោម',
            'venue_address' => 'Wat Ounalom, Sisowath Quay, Phnom Penh',
            'map_url' => 'https://www.google.com/maps/@11.5747,104.9302,17z',
            'event_schedule' => [
                ['time' => 'ថ្ងៃសៅរ៍ ២កើត', 'label' => ''],
                ['time' => '05:00 PM', 'label' => 'ពិធីចូលរោងបុណ្យ និងសូត្រមន្តចម្រើនព្រះបរិត្ត'],
                ['time' => '07:00 PM', 'label' => 'ពិធីសម្តែងព្រះធម្មទេសនា'],
                ['time' => 'ថ្ងៃអាទិត្យ ៣កើត', 'label' => ''],
                ['time' => '07:00 AM', 'label' => 'ពិធីដង្ហែកឋិនជុំវិញព្រះវិហារ ៣ ជុំ'],
                ['time' => '09:00 AM', 'label' => 'ពិធីវេរកឋិនទានប្រគេនព្រះសង្ឃ'],
                ['time' => '11:00 AM', 'label' => 'ប្រគេនចង្ហាន់ថ្ងៃត្រង់ព្រះសង្ឃ'],
            ],
            'countdown_enabled' => true,
            'rsvp_enabled' => true,
            'qr_code' => 'https://api.qrserver.com/v1/create-qr-code/?size=480x480&margin=10&data=ROUMDOUL-DEMO-KATHINA',
            'gift_account' => "ABA · SOK HENG\n000 123 456",
            'contact_phone' => '012 345 678',
            'photo_gallery' => collect(range(1, 5))
                ->map(fn ($i) => "https://picsum.photos/seed/kathina{$i}/800/1000")
                ->all(),
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\InvitationTemplate;
use App\Services\ProductViews;
use App\Support\DemoPhotos;
use Illuminate\Support\Str;

class InvitationTemplateDemoController extends Controller
{
    public function __invoke(InvitationTemplate $template, ProductViews $views)
    {
        abort_unless($template->is_active, 404);
        // its design file was removed from the site (e.g. the old Christmas template)
        abort_unless(view()->exists((string) $template->view), 404);

        // admin dashboard: demos opened per product (once per visitor per day)
        $views->record($template->service_id, ProductViews::DEMO);

        // every wedding design gets the same sample as its /demo/{path} review link, matched by design file (the
        // admin picks the slug, e.g. Khmer Traditional II is «khmer-wedding-template»)
        $path = str_replace('.', '/', Str::after((string) $template->view, 'invitations.templates.'));
        if (str_starts_with($path, 'wedding/') || in_array($path, ['khmer-traditional-wedding', 'khmer-traditional-2'], true)) {
            return $this->file($path);
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

    /**
     * Review link for any template file, straight from disk — no InvitationTemplate row needed.
     * /demo/{path} renders resources/views/invitations/templates/{path}.blade.php, e.g.
     * /demo/wedding/{name} or /demo/birthday. ?to=Name sets the guest name.
     * Sample data is picked by folder/name so every section of the design shows.
     */
    public function file(string $path)
    {
        // /demo/starter previews the skeleton new wedding templates are copied from (not a sellable design)
        $isStarter = trim($path, '/') === 'starter';
        $view = $isStarter ? 'invitations.starters.wedding' : 'invitations.templates.'.str_replace('/', '.', trim($path, '/'));
        abort_unless(view()->exists($view), 404);

        $kind = $isStarter ? 'wedding' : strtolower(strtok(trim($path, '/'), '/'));
        $fields = match (true) {
            str_contains($kind, 'wedding') || str_contains($kind, 'khmer-traditional') => $this->khmerWeddingDemoFields() + [
                'contact_phone' => '012 345 678',
                'host_names' => "លោក ហេង ឌី និងលោកស្រី សុខ ជា\nលោក ឈិន សារ៉ាត និងលោកស្រី ពិស្ណុ សារ៉ាត",
            ],
            str_contains($kind, 'kathin') || str_contains($kind, 'kak-thern') || str_contains($kind, 'ceremony') => $this->kathinaDemoFields(),
            str_contains($kind, 'anniversary') => $this->anniversaryDemoFields(),
            str_contains($kind, 'birthday') => $this->birthdayDemoFields(),
            default => $this->khmerWeddingDemoFields() + $this->kathinaDemoFields() + [
                'sender_name' => 'Alex',
                'celebrant_name' => 'Bella',
                'turning_age' => '25',
                'headline' => 'You are invited',
                'message' => 'We would love to celebrate this special day with you.',
            ],
        };

        // love story for every wedding demo, in the order of the story photos (Admin › Catalog › Demo photos):
        // titled «Place | when» so the wedding templates can turn them into memory-map pins
        $weddingPlaces = [
            ['title' => 'ពិធីមង្គលការមិត្តភក្តិ | ឆ្នាំ ២០២២', 'text' => 'យើងបានជួបគ្នាជាលើកដំបូងនៅពិធីមង្គលការរបស់មិត្តភក្តិ។ នាងកាន់ភួងផ្កា ហើយគាត់ខ្មាសពេក មិនហ៊ាននិយាយមួយម៉ាត់។'],
            ['title' => 'មាត់ទន្លេចតុមុខ | ណាត់ជួបលើកដំបូង', 'text' => 'ការណាត់ជួបលើកដំបូងពោរពេញដោយសំណើច រហូតភ្លេចមើលម៉ោង។ ចាប់ពីល្ងាចនោះមក យើងមិនដែលឃ្លាតគ្នាទៀតឡើយ។'],
            ['title' => 'ផ្ទះក្រុមគ្រួសារ | ថ្ងៃសុំរៀបការ', 'text' => 'នៅចំពោះមុខក្រុមគ្រួសារទាំងសងខាង គាត់ចាប់ដៃនាង ហើយសុំរៀបការ។ ចម្លើយតែមួយគត់គឺ «ចាស យល់ព្រម»។'],
            ['title' => 'ស្ទូឌីយោថតរូប | មុនថ្ងៃមង្គល', 'text' => 'យើងថតរូបមុនរៀបការក្នុងសម្លៀកបំពាក់បីបែប គឺខ្មែរ ចិន និងរ៉ូបពណ៌ស ហើយស្ទើរតែគ្រប់រូបសុទ្ធតែសើច។'],
            ['title' => 'ភ្នំពេញ | ១៥ ធ្នូ ២០២៦', 'text' => 'ឥឡូវនេះ យើងត្រៀមចាប់ផ្ដើមជីវិតថ្មីជាមួយគ្នាជារៀងរហូត ហើយសូមគោរពអញ្ជើញលោកអ្នកមកធ្វើជាសាក្សី។'],
        ];

        // every optional wedding field filled (guest notes, end time, custom button, song name…); the photos and
        // captions come from the shared demo photo set below
        $fullWeddingSample = fn (array $extra = []) => array_merge([
            'headline' => 'Together with their families',
            'message' => 'ពីការជួបគ្នាដោយចៃដន្យ ក្លាយជាមិត្ត ហើយក្លាយជាគូជីវិត។ យើងទាំងពីរពិតជារីករាយណាស់ ដែលបានចែករំលែកថ្ងៃដ៏មានអត្ថន័យនេះជាមួយលោកអ្នក។',
            'story_chapters' => $weddingPlaces,
            'end_time' => '21:00',
            'dress_code' => 'ពណ៌ក្រែម មាស ឬ sage — សម្លៀកបំពាក់ផ្លូវការ',
            'what_to_bring' => 'ស្នាមញញឹម និងក្តីស្រឡាញ់របស់លោកអ្នក',
            'rsvp_deadline' => '2026-12-05',
            'rsvp_message' => 'តើលោកអ្នកនឹងអញ្ជើញមកចូលរួមអបអរជាមួយយើងខ្ញុំដែរឬទេ?',
            'gift_message' => 'វត្តមានរបស់លោកអ្នកគឺជាកាដូដ៏ធំបំផុត។ សម្រាប់អ្នកដែលចង់ផ្ញើចំណងដៃពីចម្ងាយ សូមស្កេន QR ខាងក្រោម។',
            'cta_label' => 'ចូលរួម Telegram Group',
            'cta_url' => 'https://t.me/',
            'closing_line' => 'Happily ever after',
            'song_title' => 'Soft Piano',
            'music_file' => asset('images/invitations/assets/music/royalty_free_music-soft-piano-100-bpm-121529.mp3'),
        ], $extra);

        // sample wording for one template file only, when the folder's shared sample doesn't fit it
        $fields = array_merge($fields, match (basename($path)) {
            'river-love' => [
                'headline' => 'The best part of my life | is in that letter',
                'message' => "Happy anniversary. Some days I still can’t believe I get to love you.\n\n"
                    .'You turned ordinary mornings into my favorite part of the day, and quiet evenings into a place I never want to leave. Like a river, I keep finding my way back to you.'."\n\n"
                    .'Thank you for choosing me, again and again. From this moment on, and every one after it, I will love you.',
            ],
            // Vellum Garden: the personal note, a terracotta wax seal and a sample song
            'vellum-garden' => [
                'message' => 'ពីការជួបគ្នាដោយចៃដន្យ ក្លាយជាមិត្ត ហើយក្លាយជាគូជីវិត។ យើងទាំងពីរពិតជារីករាយណាស់ ដែលបានចែករំលែកថ្ងៃដ៏មានអត្ថន័យនេះជាមួយលោកអ្នក។',
                'accent_color' => '#b0654b',
                'story_chapters' => $weddingPlaces,
                'dress_code' => 'សូមស្លៀកពាក់ពណ៌ក្រែម បៃតងខ្ចី ឬផ្កាឈូកខ្ចី — សម្លៀកបំពាក់ផ្លូវការ ឬប្រពៃណីខ្មែរ',
                'music_file' => asset('images/invitations/assets/music/royalty_free_music-soft-piano-100-bpm-121529.mp3'),
            ],
            // Front Page: pull quote, dress code, a sample song (12 photos = three full photo-booth strips)
            'front-page' => [
                'message' => 'ពីថ្ងៃនេះតទៅ យើងនឹងសរសេររឿងរ៉ាវតែមួយជាមួយគ្នា ហើយទំព័រដំបូងនៃរឿងនោះ យើងចង់ឲ្យមានវត្តមានលោកអ្នក។',
                'dress_code' => 'ពណ៌ក្រែម ខ្មៅ ឬក្រហមដិត',
                'story_chapters' => $weddingPlaces,
                'music_file' => asset('images/invitations/assets/music/royalty_free_music-soft-piano-100-bpm-121529.mp3'),
            ],
            'golden-hour' => $fullWeddingSample([
                'headline' => 'We are getting married', 'end_time' => '21:30', 'closing_line' => 'Forever starts today',
                'dress_code' => 'ពណ៌ក្រែម មាស ឬត្នោតខ្ចី — សម្លៀកបំពាក់ផ្លូវការ',
                'song_title' => 'Soft Piano · Golden Hour', 'accent_color' => '#d4a76a',
            ]),
            'starter' => $fullWeddingSample(),
            // the four templates built together (western, Gen Z, two Khmer kbach): every field filled
            'eternal-vows' => $fullWeddingSample(['accent_color' => '#b08d57', 'closing_line' => 'Forever & always', 'dress_code' => 'Black tie — ខ្មៅ ស ឬ champagne']),
            'love-bubble' => $fullWeddingSample(['accent_color' => '#ff7eb6', 'headline' => 'We said yes!', 'closing_line' => 'See you there bestie!', 'dress_code' => 'ពណ៌ផ្កាឈូកខ្ចី lavender ឬ sky blue — casual cute']),
            'kbach-meas' => $fullWeddingSample(['accent_color' => '#c9a14a', 'headline' => 'សិរីមង្គលអាពាហ៍ពិពាហ៍', 'closing_line' => 'សូមអរគុណ', 'dress_code' => 'សម្លៀកបំពាក់ប្រពៃណីខ្មែរ ពណ៌មាស ក្រហមដិត ឬក្រែម']),
            // Memory Hall (3D walk-through): every stop filled, ten photos so the gallery wing is a real corridor
            'memory-hall' => $fullWeddingSample([
                'accent_color' => '#c9a24d', 'headline' => 'Together with their families', 'closing_line' => 'Forever starts here',
                'song_title' => 'Soft Piano · Memory Hall',
            ]),
            'blossom-swing' => $fullWeddingSample(['accent_color' => '#d0668c', 'closing_line' => 'Happily ever after', 'dress_code' => 'ពណ៌ផ្កាឈូកខ្ចី ក្រែម ឬ sage — សម្លៀកបំពាក់ផ្លូវការ']),
            // Green Wedding (wisteria night garden): every field filled
            'green-wedding' => $fullWeddingSample(['accent_color' => '#e2c27d', 'closing_line' => 'Forever & always', 'dress_code' => 'ពណ៌បៃតងចាស់ ស ឬ មាស — សម្លៀកបំពាក់ផ្លូវការ']),
            'kbach-chhouk' => $fullWeddingSample(['accent_color' => '#2f7a5a', 'headline' => 'សិរីមង្គលអាពាហ៍ពិពាហ៍', 'closing_line' => 'សូមអរគុណ', 'dress_code' => 'សម្លៀកបំពាក់ប្រពៃណីខ្មែរ ពណ៌ក្រែម បៃតងខ្ចី ឬមាស']),
            'paper-bloom' => $fullWeddingSample([
                'dress_code' => 'ពណ៌ផ្កាឈូកខ្ចី ក្រែម បៃតងខ្ចី ឬ lavender — សម្លៀកបំពាក់ផ្លូវការ',
                'song_title' => 'Soft Piano · Paper Bloom', 'accent_color' => '#e58f8f',
            ]),
            default => [],
        });

        // every wedding demo shows the one shared couple photo set (App\Support\DemoPhotos, uploaded in Admin ›
        // Catalog › Demo photos), each template with the cover crop that fits its frame and as many gallery photos as
        // its design shows. Roles nobody uploaded stay empty, so that photo's section hides.
        $isWeddingDemo = str_contains($kind, 'wedding') || str_contains($kind, 'khmer-traditional');
        if ($isWeddingDemo) {
            $name = basename($path);
            $count = match ($name) {
                // ?photos=N (1–17) previews the Khmer feed with any number of photos
                'khmer-traditional-wedding', 'khmer-traditional-2' => max(1, min(17, (int) request('photos', 6))),
                'front-page' => 12,
                'memory-hall' => 10,
                'vellum-garden' => 6,
                'paper-bloom' => 8,
                default => 9,
            };
            $fields = array_merge($fields, [
                'cover_image' => DemoPhotos::url(match ($name) {
                    'kbach-meas', 'kbach-chhouk', 'vellum-garden', 'eternal-vows', 'paper-bloom', 'love-bubble' => 'cover_tall',
                    'golden-hour', 'memory-hall' => 'cover_wide',
                    default => 'cover',
                }),
                'groom_photo' => DemoPhotos::url('groom'),
                'bride_photo' => DemoPhotos::url('bride'),
                'background_image' => DemoPhotos::url('end'),
                'photo_gallery' => DemoPhotos::gallery($count),
                'photo_captions' => DemoPhotos::captions($count),
            ]);
        }

        return view('invitations.show', [
            'view' => $view,
            'recipientName' => request('to', str_contains($kind, 'anniversary') ? 'My love' : 'ភ្ញៀវកិត្តិយស'),
            'invitation' => null,
            'fields' => $fields,
        ]);
    }

    /**
     * Realistic sample content for the Khmer Traditional Wedding preview — the same field keys
     * a real customer fills in through the dashboard form, so the demo exercises every section.
     */
    private function khmerWeddingDemoFields(): array
    {
        return [
            'groom_name' => 'SOPHA',
            'bride_name' => 'MASY',
            'couple_initials' => 'ស&ម',
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
            'gift_account' => "ABA · SOPHA & MASY\n000 123 456",
            'rsvp_enabled' => true,
            'countdown_enabled' => true,
            'groom_phone' => '+855 12 345 678',
            'bride_phone' => '+855 96 765 4321',
            'cover_image' => DemoPhotos::url('cover'),
            'groom_photo' => DemoPhotos::url('groom'),
            'bride_photo' => DemoPhotos::url('bride'),
            // in the order of the story photos (Admin › Catalog › Demo photos)
            'story_chapters' => [
                ['title' => 'ជួបគ្នាលើកដំបូង', 'text' => 'យើងបានជួបគ្នាជាលើកដំបូងនៅពិធីមង្គលការរបស់មិត្តភក្តិ ក្នុងថ្ងៃដ៏រីករាយមួយ ដែលគ្មាននរណាដឹងថាវានឹងក្លាយជាដំណើររឿងរបស់យើង។'],
                ['title' => 'ណាត់ជួបលើកដំបូង', 'text' => 'ការជួបគ្នាលើកដំបូងពោរពេញដោយសំណើច ហើយពីពេលនោះមក យើងចាប់ផ្ដើមស្គាល់ចិត្តគ្នាកាន់តែស៊ីជម្រៅ។'],
                ['title' => 'ថ្ងៃសុំរៀបការ', 'text' => 'នៅចំពោះមុខក្រុមគ្រួសារទាំងសងខាង កូនប្រុសបានចាប់ដៃកូនស្រី ហើយសុំរៀបការ ដោយមានការឯកភាព និងពរជ័យពីមាតាបិតា។'],
                ['title' => 'ថតរូបមុនមង្គលការ', 'text' => 'យើងបានថតរូបអនុស្សាវរីយ៍ក្នុងសម្លៀកបំពាក់ប្រពៃណីខ្មែរ ចិន និងរ៉ូបពណ៌ស ទុកជាការចងចាំមុនថ្ងៃមង្គល។'],
                ['title' => 'ជារៀងរហូត', 'text' => 'ឥឡូវនេះ យើងខ្ញុំត្រៀមចាប់ផ្ដើមជីវិតថ្មីជាមួយគ្នា ហើយសូមគោរពអញ្ជើញលោកអ្នកមកធ្វើជាសាក្សីនៃថ្ងៃដ៏មានអត្ថន័យនេះ។'],
            ],
            'event_schedule' => [
                ['time' => '06:00 AM', 'label' => 'ពិធីហែជំនូន'],
                ['time' => '07:00 AM', 'label' => 'ពិធីសែនក្រុងពាលី'],
                ['time' => '08:30 AM', 'label' => 'ពិធីចងដៃ'],
                ['time' => '10:00 AM', 'label' => 'ពិធីបង្វិលពពិល'],
                ['time' => '05:00 PM', 'label' => 'ពិធីទទួលភ្ញៀវ'],
            ],
            'gallery_feed' => true,
            // ?photos=N (1–17) previews the feed with any number of photos
            'photo_gallery' => DemoPhotos::gallery(max(1, min(17, (int) request('photos', 6)))),
            'photo_captions' => DemoPhotos::captions(max(1, min(17, (int) request('photos', 6)))),
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
            // a local sample song so the music buttons (Rose Bloom card, River Love «Play our song») work in the demo
            'music_file' => asset('images/invitations/assets/music/royalty_free_music-soft-piano-100-bpm-121529.mp3'),
            'closing_line' => 'I love you, always.',
            'thanks_message' => 'Today, tomorrow, and on every ordinary day in between.',
            // the day it began: River Love counts up from it and down to the next anniversary, Rose Bloom shows «together since»
            'together_since' => '2022-03-14',
            'venue_name' => 'Where it all began: Phnom Penh',
            'accent_color' => '#d65f7f',
        ];
    }

    /**
     * Sample content for the birthday templates (Chrome Bow, /demo/birthday/...): every birthday field, every premium
     * extra and the guest notes filled, so each section of the design shows.
     */
    private function birthdayDemoFields(): array
    {
        return [
            'celebrant_name' => 'Sreypov',
            'turning_age' => '25',
            'sender_name' => 'Dara & friends',
            'headline' => "It's my birthday",
            'message' => 'មួយឆ្នាំទៀតហើយ! ខ្ញុំចង់អបអរថ្ងៃនេះជាមួយមនុស្សដែលខ្ញុំស្រឡាញ់បំផុត — សើច ញ៉ាំ រាំ ហើយថតរូបឲ្យច្រើន។ សូមមកចូលរួមជាមួយគ្នាណា!',
            'reception_message' => 'សូមគោរពអញ្ជើញមកចូលរួមអបអរខួបកំណើតដ៏រីករាយ ជប់លៀង សើចសប្បាយ និងបង្កើតអនុស្សាវរីយ៍ថ្មីៗជាមួយគ្នា។ វត្តមានរបស់អ្នក គឺជាកាដូដ៏ល្អបំផុត!',
            'event_date' => '2026-12-19 18:30:00',
            'end_time' => '22:30',
            'khmer_date' => 'ថ្ងៃសៅរ៍ ១០កើត ខែមិគសិរ ឆ្នាំម្សាញ់ ព.ស. ២៥៧០',
            'venue_name' => 'Pink Pearl Rooftop',
            'venue_address' => 'Street 240, Daun Penh, Phnom Penh',
            'map_url' => 'https://www.google.com/maps/@11.5631,104.9282,17z',
            'cover_image' => 'https://picsum.photos/seed/chromebow-cover/900/1200',
            'background_image' => 'https://picsum.photos/seed/chromebow-end/900/1100',
            'photo_gallery' => collect(range(1, 9))
                ->map(fn ($i) => "https://picsum.photos/seed/chromebow{$i}/700/".[900, 700, 1050][$i % 3])
                ->all(),
            'photo_captions' => "Golden hour 🌅\nBestie date\nCake first, always\nThat trip to Kep\nPink everything\nLate night karaoke\nSunday brunch\n24 → 25\nMain character energy",
            'story_chapters' => [
                ['title' => 'ថ្ងៃដំបូង | 2001', 'text' => 'ក្មេងស្រីតូចម្នាក់ដែលចូលចិត្តពណ៌ផ្កាឈូក និងនំខេកជាងគេ។'],
                ['title' => 'Sweet sixteen | 2017', 'text' => 'ឆ្នាំដែលរៀនបើកម៉ូតូ ហើយបានមិត្តភក្តិល្អបំផុតសម្រាប់មួយជីវិត។'],
                ['title' => 'Graduation | 2023', 'text' => 'បញ្ចប់ការសិក្សា ជាមួយក្តីសុបិនធំៗ និងទឹកភ្នែករីករាយ។'],
                ['title' => 'Twenty-five | 2026', 'text' => 'ជំពូកថ្មីចាប់ផ្ដើមហើយ — ហើយខ្ញុំចង់ឲ្យអ្នកនៅទីនោះ។'],
            ],
            'event_schedule' => [
                ['time' => '06:30 PM', 'label' => 'ទទួលភ្ញៀវ & welcome drinks'],
                ['time' => '07:00 PM', 'label' => 'អាហារពេលល្ងាច'],
                ['time' => '08:00 PM', 'label' => 'ផ្លុំទៀន & កាត់នំខេក'],
                ['time' => '08:30 PM', 'label' => 'Party, DJ & photo booth'],
                ['time' => '10:00 PM', 'label' => 'ថតរូបចុងក្រោយ'],
            ],
            'countdown_enabled' => true,
            'rsvp_enabled' => true,
            'rsvp_message' => 'តើអ្នកនឹងមកចូលរួមជប់លៀងជាមួយគ្នាដែរឬទេ?',
            'rsvp_deadline' => '2026-12-12',
            'qr_code' => 'https://api.qrserver.com/v1/create-qr-code/?size=480x480&margin=10&data=ROUMDOUL-DEMO-BIRTHDAY',
            'gift_account' => "ABA · SREYPOV\n000 456 789",
            'gift_message' => 'វត្តមានរបស់អ្នកគឺជាកាដូដ៏ល្អបំផុតហើយ។ បើចង់ផ្ញើកាដូពីចម្ងាយ សូមស្កេន QR ខាងក្រោម។',
            'contact_phone' => '012 345 678',
            'cta_label' => 'Party playlist',
            'cta_url' => 'https://open.spotify.com',
            'dress_code' => 'ពណ៌ផ្កាឈូកខ្ចី ស ឬប្រាក់ — chic & sparkly',
            'what_to_bring' => 'ស្នាមញញឹម និងថាមពលពេញ ✨',
            'thanks_message' => 'អរគុណដែលតែងតែនៅក្បែរ ក្នុងគ្រាសប្បាយ និងគ្រាលំបាក។ ឆ្នាំនេះ ខ្ញុំចង់អបអរវាជាមួយអ្នក។',
            'apology_message' => 'សូមអភ័យទោស ដែលមិនបានជូនលិខិតអញ្ជើញដោយផ្ទាល់ដៃ។ សូមទទួលយកលិខិតតូចមួយនេះជំនួសវិញណា ♥',
            'closing_line' => "Let's party!",
            'song_title' => 'Soft Piano · Birthday',
            'music_file' => asset('images/invitations/assets/music/royalty_free_music-soft-piano-100-bpm-121529.mp3'),
            'accent_color' => '#ff4fa0',
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

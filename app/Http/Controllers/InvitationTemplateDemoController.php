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
        $view = 'invitations.templates.'.str_replace('/', '.', trim($path, '/'));
        abort_unless(view()->exists($view), 404);

        $kind = strtolower(strtok(trim($path, '/'), '/'));
        $fields = match (true) {
            str_contains($kind, 'wedding') => $this->khmerWeddingDemoFields() + [
                'contact_phone' => '012 345 678',
                'host_names' => "លោក ហេង ឌី និងលោកស្រី សុខ ជា\nលោក ឈិន សារ៉ាត និងលោកស្រី ពិស្ណុ សារ៉ាត",
            ],
            str_contains($kind, 'kathin') || str_contains($kind, 'kak-thern') || str_contains($kind, 'ceremony') => $this->kathinaDemoFields(),
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

        return view('invitations.show', [
            'view' => $view,
            'recipientName' => request('to', 'ភ្ញៀវកិត្តិយស'),
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
            'groom_name' => 'SOKHA',
            'bride_name' => 'SREYNEANG',
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

<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * The one shared couple photo set every wedding template demo reads from, by role («cover», «gallery_3», «story_2»…).
 * Photos are uploaded in Admin › Catalog › Demo photos (App\Filament\Pages\DemoPhotos) to the s3 disk under
 * demo/couple/; the role → file path map (and each photo's caption) is stored as one Setting row. Swapping a
 * photo = upload a new file for that role there. Only demo pages use this — customer invitations never do.
 */
class DemoPhotos
{
    public const SETTING_KEY = 'demo_photos';

    public const DIRECTORY = 'demo/couple';

    /**
     * role => [admin label, crop / size hint, built-in caption]. The demo gallery order is gallery_1, story_1…5,
     * gallery_2…12: story chapter N shows gallery[N + 1] in the templates, so the story photos sit right after the first.
     */
    public const ROLES = [
        'cover' => ['Cover', 'Best portrait of the couple together, 2:3 (1067×1600)', null],
        'cover_tall' => ['Cover · tall', 'Cover cropped 3:4 for arch frames (1200×1600). Blank = Cover', null],
        'cover_wide' => ['Cover · wide', '16:9 for full-screen covers (1600×900). Blank = Cover', null],
        'groom' => ['Groom', 'Groom alone, 3:4 (600×800)', null],
        'bride' => ['Bride', 'Bride alone, 3:4 (600×800)', null],
        'end' => ['Closing photo', 'Last photo of the page, 3:4 (900×1200). Blank = Cover', null],

        'story_1' => ['Story 1 · how they met', '4:5 (720×900)', 'ជួបគ្នាលើកដំបូង · ឈុតអាវធំ និងរ៉ូបពណ៌ស'],
        'story_2' => ['Story 2 · first date', '4:5 (720×900)', 'វ៉ែនតាខ្មៅ និងសំណើចដំបូង'],
        'story_3' => ['Story 3 · the proposal', '4:5 (720×900)', 'ចាប់ដៃសន្យាក្នុងឈុតប្រពៃណីខ្មែរ'],
        'story_4' => ['Story 4 · pre-wedding shoot', '4:5 (720×900)', 'ផ្ទាំងពរពណ៌ក្រហម «百年好合 · 永结同心»'],
        'story_5' => ['Story 5 · forever', '4:5 (720×900)', 'ថ្ងាសប៉ះថ្ងាស · ជារៀងរហូត'],

        'gallery_1' => ['Gallery 1', '4:5 (720×900)', 'ឈុតប្រពៃណីខ្មែរ ពណ៌មាសផ្កាឈូក'],
        'gallery_2' => ['Gallery 2', '4:5 (720×900)', 'កៀកដៃគ្នាក្នុងឈុតតុក្ស៊ីដូ និងរ៉ូបពណ៌ស'],
        'gallery_3' => ['Gallery 3', '4:5 (720×900)', 'អក្សរ «囍» សុភមង្គលទ្វេដង'],
        'gallery_4' => ['Gallery 4', '4:5 (720×900)', 'ស្ទូឌីយោពណ៌ត្នោត · ឈុតខ្មែរបុរាណ'],
        'gallery_5' => ['Gallery 5', '4:5 (720×900)', 'ឱបគ្នា ញញឹមទាំងពីរនាក់'],
        'gallery_6' => ['Gallery 6', '4:5 (720×900)', 'ផ្លិត «夫» និង «妻» · ប្ដី និងប្រពន្ធ'],
        'gallery_7' => ['Gallery 7', '4:5 (720×900)', 'ក្រោមស្បៃពណ៌ស'],
        'gallery_8' => ['Gallery 8', '4:5 (720×900)', 'វ៉ែនតាខ្មៅ · ឈុតចិនបុរាណ'],
        'gallery_9' => ['Gallery 9', '4:5 (720×900)', 'ភួងផ្កាពណ៌ស និងស្នាមញញឹម'],
        'gallery_10' => ['Gallery 10', '4:5 (720×900)', 'ក្រោមក្លោងទ្វារពន្លឺ'],
        'gallery_11' => ['Gallery 11', '4:5 (720×900)', 'កូនក្រមុំក្នុងឈុតចិនបុរាណ'],
        'gallery_12' => ['Gallery 12', '4:5 (720×900)', 'កូនក្រមុំក្នុងរ៉ូបពណ៌ស'],
    ];

    /** A variant left blank shows this role instead. */
    private const FALLBACK = ['cover_tall' => 'cover', 'cover_wide' => 'cover', 'end' => 'cover'];

    /** @return array{photos: array<string, string|null>, captions: array<string, string|null>} */
    public static function all(): array
    {
        $stored = Setting::get(self::SETTING_KEY, []);
        $stored = is_array($stored) ? $stored : [];

        return [
            'photos' => array_intersect_key((array) ($stored['photos'] ?? []), self::ROLES),
            'captions' => array_intersect_key((array) ($stored['captions'] ?? []), self::ROLES),
        ];
    }

    /** @param array{photos?: array<string, mixed>, captions?: array<string, mixed>} $data */
    public static function save(array $data): void
    {
        $clean = fn ($list) => collect(array_intersect_key((array) $list, self::ROLES))
            ->map(fn ($v) => is_array($v) ? (array_values($v)[0] ?? null) : $v) // FileUpload state can be [uuid => path]
            ->map(fn ($v) => filled($v) ? trim((string) $v) : null)
            ->all();

        Setting::put(self::SETTING_KEY, ['photos' => $clean($data['photos'] ?? []), 'captions' => $clean($data['captions'] ?? [])]);
    }

    /** The photo's URL for a role, or null when nothing is uploaded for it (the template then hides that photo). */
    public static function url(string $role): ?string
    {
        $path = self::all()['photos'][$role] ?? null;

        if (blank($path)) {
            return isset(self::FALLBACK[$role]) ? self::url(self::FALLBACK[$role]) : null;
        }

        return preg_match('#^https?://#i', $path) ? $path : Storage::disk('s3')->url($path);
    }

    /** @return list<string> gallery roles in demo order: gallery_1, story_1…5, gallery_2…12 */
    public static function galleryRoles(): array
    {
        return ['gallery_1', ...array_map(fn ($i) => "story_{$i}", range(1, 5)), ...array_map(fn ($i) => "gallery_{$i}", range(2, 12))];
    }

    /** @return list<string> the uploaded gallery photo URLs in demo order (empty roles skipped), at most $max */
    public static function gallery(int $max = 12): array
    {
        return collect(self::filledGalleryRoles())->take($max)->map(fn ($r) => self::url($r))->values()->all();
    }

    /** One caption per line, lined up with gallery($max). */
    public static function captions(int $max = 12): string
    {
        $captions = self::all()['captions'];

        return collect(self::filledGalleryRoles())->take($max)
            ->map(fn ($r) => $captions[$r] ?? self::ROLES[$r][2] ?? '')
            ->implode("\n");
    }

    /** @return list<string> */
    private static function filledGalleryRoles(): array
    {
        $photos = self::all()['photos'];

        return array_values(array_filter(self::galleryRoles(), fn ($r) => filled($photos[$r] ?? null)));
    }
}

<?php

namespace App\Support\Invitations;

/**
 * Dress code → colour swatches. Every template shows the `dress_code` field as colours, never as plain
 * text alone: the colours the couple names (Khmer words like «ក្រែម», «បៃតងខ្ចី», English like «sage»,
 * «dusty pink», or #hex codes) become swatches, in the order they're written (max 6), and the text
 * itself still shows under them for anything that isn't a colour («formal», «Khmer traditional»…).
 *
 *     DressCode::colours('សូមស្លៀកពាក់ពណ៌ក្រែម ឬ sage #c9a14a')
 *     // [['hex' => '#efe4cc', 'name' => 'ក្រែម'], ['hex' => '#a9b98f', 'name' => 'Sage'], ['hex' => '#c9a14a', 'name' => '#C9A14A']]
 */
class DressCode
{
    /** Colour words → hex. Khmer longest first, so «ក្រហមដិត» wins over «ក្រហម». */
    public const PALETTE = [
        // Khmer (longest first, so «ក្រហមដិត» wins over «ក្រហម»)
        'ក្រហមដិត' => '#7d1f2b', 'ក្រហមចាស់' => '#7d1f2b', 'ក្រហម' => '#b3262e', 'ផ្កាឈូកខ្ចី' => '#efc7c2', 'ផ្កាឈូក' => '#e39aa6',
        'ទឹកក្រូច' => '#e0873d', 'លឿងខ្ចី' => '#f1dc98', 'លឿង' => '#e9c75b', 'មាស' => '#c9a14a', 'ប្រាក់' => '#c0c2c4',
        'បៃតងចាស់' => '#3f5a3a', 'បៃតងខ្ចី' => '#b5c99a', 'បៃតងស្រាល' => '#a9b98f', 'បៃតង' => '#5f7f4e',
        'ខៀវចាស់' => '#23345c', 'ខៀវខ្ចី' => '#a9c6e0', 'ខៀវ' => '#3d64a8', 'ស្វាយខ្ចី' => '#c9b6dc', 'ស្វាយ' => '#6d4a8c',
        'ត្នោតខ្ចី' => '#b08d6e', 'ត្នោត' => '#7a5338', 'ប្រផេះ' => '#9a9a96', 'ក្រែម' => '#efe4cc', 'បេហ្ស' => '#dbc8a8',
        'សំប៉ាញ' => '#e8d6b0', 'ខ្មៅ' => '#1d1c1a', 'ពណ៌ស' => '#fbfaf6',
        // English
        'dusty pink' => '#d9a5a5', 'dusty blue' => '#8fa9c0', 'sky blue' => '#a9c6e0', 'light blue' => '#a9c6e0', 'navy blue' => '#23345c',
        'burgundy' => '#7d1f2b', 'maroon' => '#6e1f26', 'wine' => '#722f37', 'red' => '#b3262e', 'blush' => '#efc7c2',
        'pink' => '#e39aa6', 'rose' => '#d58d93', 'peach' => '#f2c1a0', 'coral' => '#ee8a72', 'terracotta' => '#c0694e',
        'orange' => '#e0873d', 'mustard' => '#d0a53a', 'yellow' => '#e9c75b', 'gold' => '#c9a14a', 'golden' => '#c9a14a',
        'champagne' => '#e8d6b0', 'silver' => '#c0c2c4', 'sage' => '#a9b98f', 'olive' => '#77804a', 'emerald' => '#2f7a5a',
        'green' => '#5f7f4e', 'navy' => '#23345c', 'teal' => '#2f7c80', 'blue' => '#3d64a8', 'lavender' => '#c9b6dc',
        'lilac' => '#c8a8d3', 'purple' => '#6d4a8c', 'brown' => '#7a5338', 'beige' => '#dbc8a8', 'nude' => '#d9b99b',
        'taupe' => '#a39382', 'cream' => '#efe4cc', 'ivory' => '#f6f0e1', 'white' => '#fbfaf6', 'black' => '#1d1c1a',
        'charcoal' => '#3b3b3b', 'grey' => '#9a9a96', 'gray' => '#9a9a96',
    ];

    /** @return list<array{hex: string, name: string}> */
    public static function colours(?string $text, int $max = 6): array
    {
        $text = trim((string) $text);
        if ($text === '') {
            return [];
        }

        $scan = mb_strtolower($text);
        $found = [];
        $take = function (string $pattern, callable $hex, callable $name) use (&$scan, &$found) {
            if (preg_match_all($pattern, $scan, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[0] as [$match, $at]) {
                    $found[] = ['at' => $at, 'hex' => $hex($match), 'name' => $name($match)];
                    $scan = substr_replace($scan, str_repeat(' ', strlen($match)), $at, strlen($match)); // matched once only
                }
            }
        };
        $take('/#[0-9a-f]{6}\b/', fn ($t) => $t, fn ($t) => mb_strtoupper($t));
        foreach (self::PALETTE as $word => $hex) {
            $isLatin = ! preg_match('/\p{Khmer}/u', $word);
            $take($isLatin ? '/\b'.preg_quote($word, '/').'\b/u' : '/'.preg_quote($word, '/').'/u', fn () => $hex, fn ($t) => $isLatin ? ucfirst($t) : $t);
        }
        // «ស» (white) on its own, between spaces / commas / «ឬ» / «និង»
        $take('/(?<=^|[\s,\/&+]|ឬ|និង)ស(?=$|[\s,\/&+។]|ឬ|និង)/u', fn () => '#fbfaf6', fn () => 'ស');

        return collect($found)->sortBy('at')->unique('hex')->take($max)
            ->map(fn ($c) => ['hex' => $c['hex'], 'name' => $c['name']])->values()->all();
    }
}

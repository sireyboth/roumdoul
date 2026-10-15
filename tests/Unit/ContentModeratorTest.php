<?php

namespace Tests\Unit;

use App\Services\ContentModerator;
use Tests\TestCase;

class ContentModeratorTest extends TestCase
{
    private function allowed(string $text): bool
    {
        return app(ContentModerator::class)->evaluate($text)['allowed'];
    }

    /** @dataProvider legitimateComments */
    public function test_legitimate_comments_are_allowed(string $text): void
    {
        $this->assertTrue($this->allowed($text), "Should allow: {$text}");
    }

    public static function legitimateComments(): array
    {
        return [
            'khmer congratulations' => ['សូមអរគុណច្រើន សេវាកម្មល្អណាស់ ខ្ញុំពេញចិត្តខ្លាំងណាស់'],
            'khmer wedding' => ['សូមជូនពរឱ្យអ្នកទាំងពីរមានក្តីសុខ ជួបតែសេចក្តីសុខសាន្ត'],
            'english praise' => ['Beautiful invitation, congratulations to both of you!'],
            'mixed' => ['Thank you! ពិតជាស្អាតណាស់ ❤️'],
            'place name containing banned substring' => ['We will be at Sussex Road, class of assassins is not here'],
            'scunthorpe style' => ['Dickinson and Cockburn say hello'],
            'time and date' => ['See you at 5:30 PM on 15/12/2026'],
            'phone number' => ['Call me on 012 345 678 if you are lost'],
        ];
    }

    /** @dataProvider disguisedAbuse */
    public function test_disguised_abuse_is_blocked(string $text): void
    {
        $this->assertFalse($this->allowed($text), "Should block: {$text}");
    }

    public static function disguisedAbuse(): array
    {
        return [
            'plain' => ['this is shit'],
            'leetspeak' => ['what a sh1t service'],
            'symbols' => ['you are a b!tch'],
            'spaced letters' => ['f u c k this'],
            'dotted letters' => ['p.o.r.n here'],
            'repeated letters' => ['fuuuuck'],
            'uppercase' => ['PORN'],
            'zero width characters' => ["sh\u{200B}it"],
            'fullwidth' => ['ｓｈｉｔ'],
        ];
    }

    /** @dataProvider spamAndInjection */
    public function test_spam_links_and_markup_are_blocked(string $text): void
    {
        $this->assertFalse($this->allowed($text), "Should block: {$text}");
    }

    public static function spamAndInjection(): array
    {
        return [
            'url' => ['visit https://scam.example.com now'],
            'www' => ['go to www.scam.com'],
            'bare domain' => ['promo at bestdeals.xyz'],
            'obfuscated dot' => ['bestdeals [dot] com'],
            'telegram link' => ['join t.me/scamgroup'],
            'script tag' => ['<script>alert(1)</script>'],
            'img onerror' => ['<img src=x onerror=alert(1)>'],
            'javascript uri' => ['javascript:alert(1)'],
            'spam phrase' => ['Earn money from home with crypto'],
            'character flood' => [str_repeat('a', 40)],
        ];
    }

    public function test_sanitize_strips_tags_and_invisible_characters(): void
    {
        $m = app(ContentModerator::class);

        $this->assertSame('hello world', $m->sanitize("  <b>hello</b>\u{200B}\n\n world "));
        $this->assertSame(5, mb_strlen($m->sanitize('abcdefghij', 5)));
    }

    public function test_fingerprint_ignores_case_spacing_and_invisible_characters(): void
    {
        $m = app(ContentModerator::class);

        $this->assertSame($m->fingerprint('Hello  World'), $m->fingerprint("hello\u{200B} world"));
        $this->assertNotSame($m->fingerprint('hello'), $m->fingerprint('goodbye'));
    }
}

<?php

namespace App\Services;

/**
 * Backend text moderation for guest/customer-submitted text (reviews, RSVP notes).
 *
 * Designed to catch disguised words (leetspeak, spaced-out letters, repeated letters,
 * zero-width characters, full-width forms) while protecting legitimate Khmer and English
 * text: matching is whole-word (Unicode-aware, Khmer combining marks count as part of a
 * word), and Khmer text is never leet-mapped or letter-collapsed. Word lists, link and spam
 * rules live in config/moderation.php.
 */
class ContentModerator
{
    public const REASON_BANNED = 'banned_word';

    public const REASON_LINK = 'link';

    public const REASON_SPAM = 'spam_phrase';

    public const REASON_MARKUP = 'markup';

    public const REASON_REPEATED = 'repeated_characters';

    /** @return array{allowed: bool, reasons: list<string>} */
    public function evaluate(string $text): array
    {
        $reasons = [];

        if (preg_match('/<\s*\/?\s*[a-z!?]|javascript\s*:|data\s*:\s*text\/html/iu', $text)) {
            $reasons[] = self::REASON_MARKUP;
        }

        $views = $this->views($text);

        if ($this->containsBanned($views)) {
            $reasons[] = self::REASON_BANNED;
        }

        if ($this->containsLink($text, $views)) {
            $reasons[] = self::REASON_LINK;
        }

        if ($this->containsSpamPhrase($views)) {
            $reasons[] = self::REASON_SPAM;
        }

        $max = max(3, (int) config('moderation.max_repeated_chars', 12));
        if (preg_match('/(\X)\1{'.($max - 1).',}/u', $text)) {
            $reasons[] = self::REASON_REPEATED;
        }

        return ['allowed' => $reasons === [], 'reasons' => array_values(array_unique($reasons))];
    }

    /**
     * Strips tags, control and zero-width characters and collapses whitespace. Output is still
     * escaped by Blade when rendered — this just keeps junk out of the database.
     */
    public function sanitize(string $text, int $maxLength = 1000): string
    {
        $text = preg_replace('~</?[a-z][^>]*>~iu', '', $text) ?? $text;
        $text = preg_replace('/[\p{Cc}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2064}\x{FEFF}]/u', ' ', $text) ?? $text;
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        return mb_substr($text, 0, $maxLength);
    }

    /** Stable, keyed hash of the normalised text — safe to log, usable for duplicate detection. */
    public function fingerprint(string $text): string
    {
        return hash_hmac('sha256', $this->normalize($text), (string) config('app.key'));
    }

    /**
     * Whether an identical (after normalisation) review comment was already submitted inside
     * the configured window — catches copy-paste spam without storing extra data.
     */
    public function isRecentDuplicateReview(string $text): bool
    {
        $hash = $this->fingerprint($text);
        $since = now()->subHours((int) config('moderation.duplicate_window_hours', 24));

        return \App\Models\Review::where('created_at', '>=', $since)
            ->whereNotNull('comment')
            ->latest()
            ->limit(200)
            ->pluck('comment')
            ->contains(fn ($c) => hash_equals($hash, $this->fingerprint($c)));
    }

    /** Lowercased, NFKC-normalised text without invisible characters or repeated whitespace. */
    public function normalize(string $text): string
    {
        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_KC) ?: $text;
        }

        $text = preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2064}\x{FEFF}\x{00AD}]/u', '', $text) ?? $text;
        $text = mb_strtolower($text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /**
     * The same text in several comparable shapes: plain, leetspeak-mapped, letter-spaced
     * collapsed ("f u c k") and repeated-letter collapsed ("fuuuck"). Banned terms are
     * collapsed the same way, so "shiit" and "shit" meet at the same form.
     *
     * @return list<string>
     */
    private function views(string $text): array
    {
        $plain = $this->normalize($text);

        $leet = strtr($plain, ['0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '@' => 'a', '$' => 's', '!' => 'i', '+' => 't']);

        // single Latin letters separated by spaces/punctuation: "f.u.c.k", "p o r n"
        $joined = preg_replace_callback(
            '/(?<![\p{L}\p{N}])[a-z](?:[\s.\-_*,]+[a-z](?![\p{L}\p{N}])){2,}/u',
            fn ($m) => preg_replace('/[\s.\-_*,]+/u', '', $m[0]),
            $leet
        ) ?? $leet;

        $collapse = fn (string $s) => preg_replace('/([a-z])\1+/u', '$1', $s) ?? $s;

        return array_values(array_unique([$plain, $leet, $joined, $collapse($leet), $collapse($joined)]));
    }

    private function containsBanned(array $views): bool
    {
        $words = array_merge(
            (array) config('moderation.banned_words', []),
            (array) config('moderation.banned_words_km', []),
        );

        foreach ($words as $word) {
            $word = $this->normalize((string) $word);
            if ($word === '') {
                continue;
            }

            // Latin terms are also tried in letter-collapsed form; Khmer terms only literally.
            $candidates = [$word];
            if (preg_match('/^[a-z0-9 ]+$/', $word)) {
                $candidates[] = preg_replace('/([a-z])\1+/u', '$1', $word);
            }

            foreach ($candidates as $candidate) {
                $pattern = '/(?<![\p{L}\p{N}\p{M}])'.preg_quote($candidate, '/').'(?![\p{L}\p{N}\p{M}])/u';
                foreach ($views as $view) {
                    if (preg_match($pattern, $view) === 1) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function containsLink(string $raw, array $views): bool
    {
        if (! config('moderation.block_links', true)) {
            return false;
        }

        $tlds = implode('|', array_map('preg_quote', (array) config('moderation.link_tlds', [])));
        $pattern = '/(?:https?:\/\/|www\.|(?<![\p{L}\p{N}])(?:t\.me|wa\.me|bit\.ly|tinyurl\.com)\/|[\p{L}\p{N}-]+\.(?:'.$tlds.')(?![\p{L}\p{N}]))/iu';

        foreach (array_merge([$raw], $views) as $candidate) {
            // "example [dot] com" evasions
            $candidate = preg_replace('/\s*(?:\[dot\]|\(dot\))\s*/iu', '.', $candidate) ?? $candidate;
            if (preg_match($pattern, $candidate) === 1) {
                return true;
            }
        }

        return false;
    }

    private function containsSpamPhrase(array $views): bool
    {
        foreach ((array) config('moderation.spam_phrases', []) as $phrase) {
            $phrase = $this->normalize((string) $phrase);
            if ($phrase === '') {
                continue;
            }
            $pattern = '/(?<![\p{L}\p{N}\p{M}])'.preg_quote($phrase, '/').'(?![\p{L}\p{N}\p{M}])/u';
            foreach ($views as $view) {
                if (preg_match($pattern, $view) === 1) {
                    return true;
                }
            }
        }

        return false;
    }
}

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Banned words
    |--------------------------------------------------------------------------
    |
    | Any customer-submitted review comment containing one of these words
    | (case-insensitive, whole-word match) is rejected before it's saved —
    | it never reaches the database or the public homepage.
    |
    | This is a starter list of common English profanity/sexual-content
    | terms. Add your own — including Khmer slang/profanity — as plain
    | lowercase strings. Multi-word phrases work too (matched as a whole
    | phrase, not word-by-word).
    |
    */

    'banned_words' => [
        // Profanity
        'fuck', 'shit', 'bitch', 'asshole', 'bastard', 'dick', 'piss', 'cunt',
        'douche', 'wanker', 'motherfucker', 'fucker', 'dumbass', 'jackass',

        // Sexual / nudity related
        'porn', 'pornhub', 'nude', 'nudes', 'naked', 'sex', 'sexual', 'xxx',
        'onlyfans', 'nsfw', 'boobs', 'penis', 'vagina', 'orgasm', 'masturbate',
        'rape', 'incest',

        // Slurs / hate speech (generic placeholders — extend as needed)
        'retard', 'retarded',
    ],

    /*
    |--------------------------------------------------------------------------
    | Khmer banned words
    |--------------------------------------------------------------------------
    |
    | Intentionally empty: add vetted Khmer profanity / sexual / threat terms here,
    | one per entry. Matching is whole-word and Khmer-aware (combining vowel signs
    | count as part of the word), so a short term will not fire inside a longer,
    | innocent word. Have a native speaker review additions to avoid false positives.
    |
    */

    'banned_words_km' => [],

    /*
    |--------------------------------------------------------------------------
    | Spam phrases, links and repetition
    |--------------------------------------------------------------------------
    */

    'spam_phrases' => [
        'buy now', 'click here', 'free money', 'earn money', 'make money', 'work from home',
        'crypto', 'bitcoin', 'forex', 'investment opportunity', 'casino', 'betting', 'loan offer',
        'whatsapp me', 'telegram me', 'dm me', 'promo code', 'limited offer', 'viagra',
    ],

    // Guests and customers have no reason to post links in a review or RSVP note.
    'block_links' => true,

    'link_tlds' => ['com', 'net', 'org', 'info', 'biz', 'xyz', 'top', 'site', 'online', 'club', 'shop', 'link', 'click', 'ru', 'cn', 'io', 'co', 'me', 'ly', 'gg', 'tk', 'cc', 'kh', 'vip', 'bet', 'win'],

    'max_repeated_chars' => 12,

    // Cooldowns / limits (see InvitationRsvpController, PublicFeedbackForm)
    'rsvp_updates_per_window' => 6,
    'rsvp_window_seconds' => 600,
    'feedback_per_hour_per_ip' => 5,
    'duplicate_window_hours' => 24,

];

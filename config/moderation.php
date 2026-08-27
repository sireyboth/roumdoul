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

];

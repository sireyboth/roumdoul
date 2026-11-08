<?php

namespace App\Portfolio;

use Illuminate\Support\Str;

/**
 * Markdown for portfolio copy. Content is written by the portfolio owner,
 * so inline HTML is allowed, but javascript:/data: links are still blocked.
 */
class Markdown
{
    public static function render(?string $markdown): string
    {
        if ($markdown === null || trim($markdown) === '') {
            return '';
        }

        return Str::markdown($markdown, [
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);
    }

    /** Single-line Markdown (emphasis, links) without the wrapping <p>. */
    public static function inline(?string $markdown): string
    {
        if ($markdown === null || $markdown === '') {
            return '';
        }

        return Str::inlineMarkdown($markdown, ['html_input' => 'allow', 'allow_unsafe_links' => false]);
    }
}

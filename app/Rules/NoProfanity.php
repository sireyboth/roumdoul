<?php

namespace App\Rules;

use App\Services\ContentModerator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects text that the backend moderator flags: banned words (including disguised ones),
 * links, spam phrases and markup. Kept under its original name so existing forms keep working.
 */
class NoProfanity implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $result = app(ContentModerator::class)->evaluate($value);

        if ($result['allowed']) {
            return;
        }

        $fail(match (true) {
            in_array(ContentModerator::REASON_LINK, $result['reasons'], true) => __("Please don't include links in your message."),
            in_array(ContentModerator::REASON_BANNED, $result['reasons'], true) => __('Please revise your comment — it contains inappropriate language.'),
            default => __('Please revise your message — it could not be accepted.'),
        });
    }
}

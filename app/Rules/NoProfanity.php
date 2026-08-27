<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoProfanity implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $normalized = mb_strtolower($value);

        foreach (config('moderation.banned_words', []) as $word) {
            $pattern = '/(?<![\p{L}\p{N}])'.preg_quote(mb_strtolower($word), '/').'(?![\p{L}\p{N}])/u';

            if (preg_match($pattern, $normalized) === 1) {
                $fail('សូមកែសម្រួលមតិយោបល់របស់អ្នក — មានពាក្យមិនសមរម្យ។ / Please revise your comment — it contains inappropriate language.');

                return;
            }
        }
    }
}

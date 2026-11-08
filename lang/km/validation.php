<?php

// Khmer validation messages for the rules the portfolio contact form uses.
// Anything not listed here falls back to Laravel's English messages.

return [
    'required' => 'សូមបំពេញ :attribute។',
    'string' => ':attribute ត្រូវតែជាអក្សរ។',
    'email' => 'សូមបញ្ចូល :attribute ឱ្យបានត្រឹមត្រូវ។',
    'prohibited' => 'មិនអាចទទួល :attribute បានទេ។',
    'max' => [
        'string' => ':attribute មិនអាចលើសពី :max តួអក្សរទេ។',
    ],
    'min' => [
        'string' => ':attribute ត្រូវមានយ៉ាងហោចណាស់ :min តួអក្សរ។',
    ],
];

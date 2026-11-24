<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Key → JSON value store for admin-editable settings (see App\Support\InvitationDefaults).
 * Reads are memoised for the request; a missing table (before `migrate`) reads as the default.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'array'];

    /** @var array<string, mixed> */
    protected static array $memo = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, static::$memo)) {
            try {
                static::$memo[$key] = Schema::hasTable('settings')
                    ? static::query()->where('key', $key)->value('value')
                    : null;
            } catch (Throwable) {
                static::$memo[$key] = null;
            }

            if (is_string(static::$memo[$key])) {
                static::$memo[$key] = json_decode(static::$memo[$key], true);
            }
        }

        return static::$memo[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        static::$memo[$key] = $value;
    }

    public static function forgetMemo(): void
    {
        static::$memo = [];
    }
}

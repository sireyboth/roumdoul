<?php

namespace App\Console\Commands;

use App\Http\Middleware\SetLocale;
use App\Models\InvitationTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\Finder\Finder;

/**
 * Lists interface strings used in code but missing from a language's dictionary:
 *   - JSON keys: __('…'), trans('…'), @lang('…') in app/ and resources/views, and t('…') in resources/js
 *   - group keys such as __('validation.required') against lang/{locale}/*.php
 *   - strings translated dynamically (FIELD_CATALOG labels/hints, see dynamicKeys())
 * plus keys present in one JSON file but not the other. Exits non-zero when anything is missing.
 */
class LangMissing extends Command
{
    protected $signature = 'lang:missing';

    protected $description = 'List translation keys used in code that are missing from lang/km.json or lang/en.json';

    private const PHP_CALL = '/(?:\b__|\btrans|\btrans_choice|@lang|Lang::get)\(\s*(\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*")/u';

    private const JS_CALL = '/\bt\(\s*(\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*")/u';

    public function handle(): int
    {
        $used = $this->usedKeys();
        $dictionaries = [];
        $problems = 0;

        foreach (SetLocale::SUPPORTED as $locale) {
            $path = lang_path("{$locale}.json");
            $dictionaries[$locale] = is_file($path) ? json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) : [];
        }

        foreach (SetLocale::SUPPORTED as $locale) {
            $missing = [];

            foreach ($used as $key => $where) {
                $found = $this->isGroupKey($key)
                    ? Lang::hasForLocale($key, $locale)
                    : array_key_exists($key, $dictionaries[$locale]);

                if (! $found) {
                    $missing[$key] = $where;
                }
            }

            foreach (SetLocale::SUPPORTED as $other) {
                foreach (array_diff_key($dictionaries[$other], $dictionaries[$locale]) as $key => $_) {
                    $missing[$key] ??= "only in {$other}.json";
                }

                foreach (array_diff_key($this->groupKeys($other), $this->groupKeys($locale)) as $key => $_) {
                    $missing[$key] ??= "only in lang/{$other}/";
                }
            }

            if ($missing === []) {
                $this->info("{$locale}: all ".count($used).' used keys present.');

                continue;
            }

            $problems += count($missing);
            $this->error("{$locale}: ".count($missing).' missing');
            foreach ($missing as $key => $where) {
                $this->line("  - {$key}  <fg=gray>({$where})</>");
            }
        }

        return $problems === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @return array<string, string> key => first file it was found in */
    private function usedKeys(): array
    {
        $keys = [];

        $scan = function (Finder $files, string $pattern) use (&$keys) {
            foreach ($files as $file) {
                preg_match_all($pattern, $file->getContents(), $matches);
                foreach ($matches[1] as $literal) {
                    $keys[$this->unquote($literal)] ??= str_replace('\\', '/', $file->getRelativePathname());
                }
            }
        };

        $scan(Finder::create()->files()->in([app_path(), resource_path('views')])->name('*.php')->notName('LangMissing.php'), self::PHP_CALL);
        $scan(Finder::create()->files()->in(resource_path('js'))->name('*.js'), self::JS_CALL);

        foreach ($this->dynamicKeys() as $key) {
            $keys[$key] ??= 'InvitationTemplate::FIELD_CATALOG';
        }

        return $keys;
    }

    /** Strings passed to __() through a variable, which the scan above can't see. */
    private function dynamicKeys(): array
    {
        return collect(InvitationTemplate::FIELD_CATALOG)
            ->flatMap(fn (array $field) => array_filter([$field['label'] ?? null, $field['hint'] ?? null]))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Every key in lang/{locale}/*.php as "file.dotted.key". Customer-facing attribute names
     * are optional per language (Laravel falls back to the field name), so they're skipped.
     *
     * @return array<string, true>
     */
    private function groupKeys(string $locale): array
    {
        $keys = [];

        foreach (glob(lang_path("{$locale}/*.php")) ?: [] as $file) {
            $group = basename($file, '.php');
            $lines = require $file;
            unset($lines['attributes']);

            foreach (array_keys(\Illuminate\Support\Arr::dot($lines)) as $key) {
                $keys["{$group}.{$key}"] = true;
            }
        }

        return $keys;
    }

    private function isGroupKey(string $key): bool
    {
        return (bool) preg_match('/^[a-z_]+\.[A-Za-z0-9_.\-]+$/', $key);
    }

    private function unquote(string $literal): string
    {
        $quote = $literal[0];
        $body = substr($literal, 1, -1);

        return $quote === "'"
            ? str_replace(["\\'", '\\\\'], ["'", '\\'], $body)
            : stripcslashes($body);
    }
}

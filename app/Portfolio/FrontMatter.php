<?php

namespace App\Portfolio;

/**
 * Deliberately tiny front-matter reader: one "key: value" per line between
 * "---" fences. Enough for title/excerpt, without pulling in a YAML parser.
 */
class FrontMatter
{
    /** @return array{meta: array<string, string>, body: string} */
    public static function parse(string $source): array
    {
        $source = preg_replace('/^\xEF\xBB\xBF/', '', str_replace("\r\n", "\n", $source));

        if (! preg_match('/^---\n(.*?)\n---\n?(.*)$/s', $source, $m)) {
            return ['meta' => [], 'body' => trim($source)];
        }

        $meta = [];

        foreach (explode("\n", $m[1]) as $line) {
            if (preg_match('/^\s*([A-Za-z_][\w-]*)\s*:\s*(.*)$/u', $line, $kv)) {
                $meta[$kv[1]] = trim($kv[2], " \t\"'");
            }
        }

        return ['meta' => $meta, 'body' => trim($m[2])];
    }
}

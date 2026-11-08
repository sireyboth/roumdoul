<?php

namespace App\Portfolio;

use Illuminate\Support\Carbon;

/**
 * A project folder: project.php (language-neutral facts) plus one {locale}.php
 * per language with the written content. A language file may be partial —
 * missing fields fall back to the base language and are flagged as such.
 */
class Project
{
    /** Case-study sections, in reading order. Numbering on the page follows this. */
    public const SECTIONS = ['overview', 'challenge', 'idea', 'approach', 'solution', 'features', 'technical', 'outcome', 'reflection'];

    public const STATUSES = ['live', 'in_development', 'ongoing', 'exploring', 'concept', 'completed', 'paused', 'archived'];

    /** @var array<string, array> */
    protected array $text = [];

    public function __construct(
        public readonly Portfolio $portfolio,
        public readonly string $slug,
        public readonly string $path,
        protected array $meta,
    ) {}

    public static function fromDirectory(Portfolio $portfolio, string $dir): self
    {
        return new self($portfolio, basename($dir), $dir, require $dir.'/project.php');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->meta, $key, $default);
    }

    public function isHidden(): bool
    {
        return (bool) ($this->meta['hidden'] ?? false);
    }

    public function order(): int
    {
        return (int) ($this->meta['order'] ?? 999);
    }

    public function category(): string
    {
        return (string) ($this->meta['category'] ?? '');
    }

    public function status(): string
    {
        $status = (string) ($this->meta['status'] ?? 'concept');

        return in_array($status, self::STATUSES, true) ? $status : 'concept';
    }

    /** Layout family for the case study: system | creative | editorial. */
    public function template(): string
    {
        $template = $this->meta['template'] ?? 'editorial';

        return in_array($template, ['system', 'creative', 'editorial'], true) ? $template : 'editorial';
    }

    public function isFeatured(): bool
    {
        return in_array($this->slug, $this->portfolio->get('featured', []), true);
    }

    /** Human date label: "2025", "Mar 2025", "2023 — 2025", "2024 — present". */
    public function dateLabel(string $locale): ?string
    {
        $start = $this->formatDate($this->meta['date'] ?? null, $locale);
        $end = $this->meta['date_end'] ?? null;

        if ($start === null) {
            return null;
        }

        if ($end === 'present') {
            return $start.' — '.__('portfolio.date.present', [], $locale);
        }

        $end = $this->formatDate($end, $locale);

        return $end ? "{$start} — {$end}" : $start;
    }

    public function year(): ?string
    {
        $date = $this->meta['date'] ?? null;

        return $date ? substr((string) $date, 0, 4) : null;
    }

    protected function formatDate(?string $value, string $locale): ?string
    {
        if (! $value) {
            return null;
        }

        if (preg_match('/^\d{4}$/', $value)) {
            return $value;
        }

        try {
            return Carbon::parse($value.(strlen($value) === 7 ? '-01' : ''))->locale($locale)->translatedFormat('M Y');
        } catch (\Throwable) {
            return $value;
        }
    }

    /* ----------------------------------------------------------------- */
    /* Written content */
    /* ----------------------------------------------------------------- */

    public function textFile(string $locale): array
    {
        if (! array_key_exists($locale, $this->text)) {
            $file = $this->path."/{$locale}.php";
            $this->text[$locale] = is_file($file) ? require $file : [];
        }

        return $this->text[$locale];
    }

    /**
     * @return array{value: mixed, locale: string}|null The value and the language it is written in.
     */
    public function localized(string $locale, string $key): ?array
    {
        $value = data_get($this->textFile($locale), $key);

        if (! self::isEmpty($value)) {
            return ['value' => $value, 'locale' => $locale];
        }

        $base = $this->portfolio->baseLocale();
        $value = $locale === $base ? null : data_get($this->textFile($base), $key);

        return self::isEmpty($value) ? null : ['value' => $value, 'locale' => $base];
    }

    public function text(string $locale, string $key, mixed $default = null): mixed
    {
        return $this->localized($locale, $key)['value'] ?? $default;
    }

    public function title(string $locale): string
    {
        return (string) $this->text($locale, 'title', $this->slug);
    }

    public function summary(string $locale): ?string
    {
        return $this->text($locale, 'summary');
    }

    /** True when the language file has its own case-study body (not just a title). */
    public function hasCaseStudyIn(string $locale): bool
    {
        $file = $this->textFile($locale);

        foreach (self::SECTIONS as $section) {
            if (! self::isEmpty($file[$section] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The case-study sections that have content, each with the language it is in.
     *
     * @return array<string, array{value: mixed, locale: string}>
     */
    public function sections(string $locale): array
    {
        // A section is all-or-nothing per language: if this language has no body
        // at all, take every section from the base language rather than mixing.
        $source = $this->hasCaseStudyIn($locale) ? $locale : $this->portfolio->baseLocale();
        $out = [];

        foreach (self::SECTIONS as $section) {
            $value = data_get($this->textFile($source), $section);

            if (! self::isEmpty($value)) {
                $out[$section] = ['value' => $value, 'locale' => $source];
            }
        }

        return $out;
    }

    /** Sections not yet written in any language — used for editor hints only. */
    public function missingSections(string $locale): array
    {
        return array_values(array_diff(self::SECTIONS, array_keys($this->sections($locale))));
    }

    public function cover(): ?array
    {
        return $this->meta['cover'] ?? null;
    }

    /** @return list<array{src: string, ratio?: string, caption?: string|null}> */
    public function gallery(string $locale): array
    {
        $captions = (array) $this->text($locale, 'gallery_captions', []);

        return collect($this->meta['gallery'] ?? [])
            ->values()
            ->map(fn (array $item, int $i) => $item + ['caption' => $captions[$i] ?? null])
            ->all();
    }

    public function alt(string $locale, string $key = 'cover'): string
    {
        return (string) $this->text($locale, "alt.{$key}", $this->title($locale));
    }

    public static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}

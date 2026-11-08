<?php

namespace App\Portfolio;

use Illuminate\Support\Carbon;

/**
 * A note/article folder: note.php (date, type, cover, related projects, draft)
 * plus one {locale}.md per language. Each Markdown file starts with a small
 * front-matter block:
 *
 *   ---
 *   title: Why spreadsheets should teach system thinking
 *   excerpt: One or two sentences for listings.
 *   ---
 *   Body in Markdown…
 */
class Note
{
    public const TYPES = ['article', 'note', 'experiment', 'reflection', 'discovery', 'observation'];

    /** @var array<string, array{meta: array<string,string>, body: string}|null> */
    protected array $files = [];

    public function __construct(
        public readonly Portfolio $portfolio,
        public readonly string $slug,
        public readonly string $path,
        protected array $meta,
    ) {}

    public static function fromDirectory(Portfolio $portfolio, string $dir): self
    {
        return new self($portfolio, basename($dir), $dir, require $dir.'/note.php');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->meta, $key, $default);
    }

    public function isDraft(): bool
    {
        return (bool) ($this->meta['draft'] ?? false);
    }

    public function type(): string
    {
        $type = $this->meta['type'] ?? 'note';

        return in_array($type, self::TYPES, true) ? $type : 'note';
    }

    public function date(): ?Carbon
    {
        return isset($this->meta['date']) ? Carbon::parse($this->meta['date']) : null;
    }

    public function availableIn(string $locale): bool
    {
        return $this->file($locale) !== null;
    }

    /** The language this note will actually be shown in for a requested locale. */
    public function resolvedLocale(string $locale): string
    {
        return $this->availableIn($locale) ? $locale : $this->portfolio->baseLocale();
    }

    public function title(string $locale): string
    {
        return $this->file($this->resolvedLocale($locale))['meta']['title'] ?? $this->slug;
    }

    public function excerpt(string $locale): ?string
    {
        return $this->file($this->resolvedLocale($locale))['meta']['excerpt'] ?? null;
    }

    public function body(string $locale): string
    {
        return $this->file($this->resolvedLocale($locale))['body'] ?? '';
    }

    public function html(string $locale): string
    {
        return Markdown::render($this->body($locale));
    }

    /** Minutes, rounded up. Khmer is written without spaces, so it is measured in characters. */
    public function readingTime(string $locale): int
    {
        $text = strip_tags($this->body($locale));

        if ($this->resolvedLocale($locale) === 'km') {
            return max(1, (int) ceil(mb_strlen(preg_replace('/\s+/u', '', $text)) / 700));
        }

        return max(1, (int) ceil(str_word_count($text) / 220));
    }

    /** @return array{meta: array<string,string>, body: string}|null */
    protected function file(string $locale): ?array
    {
        if (array_key_exists($locale, $this->files)) {
            return $this->files[$locale];
        }

        $file = $this->path."/{$locale}.md";

        return $this->files[$locale] = is_file($file) ? FrontMatter::parse(file_get_contents($file)) : null;
    }
}

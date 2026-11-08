<?php

namespace App\Portfolio;

use Illuminate\Support\Collection;

/**
 * Discovers portfolio folders on disk (config('portfolio.path')/{slug}/portfolio.php)
 * and hands out Portfolio objects. Content is plain PHP/Markdown files, so OPcache
 * keeps reads cheap; objects are memoised for the rest of the request.
 */
class PortfolioRepository
{
    /** @var array<string, Portfolio|null> */
    protected array $loaded = [];

    public function __construct(protected string $basePath) {}

    public function basePath(): string
    {
        return $this->basePath;
    }

    /** @return list<string> */
    public function slugs(): array
    {
        if (! is_dir($this->basePath)) {
            return [];
        }

        $slugs = [];

        foreach (scandir($this->basePath) ?: [] as $entry) {
            if ($entry[0] === '.' || $entry[0] === '_') {
                continue;
            }

            if (is_file($this->basePath.DIRECTORY_SEPARATOR.$entry.DIRECTORY_SEPARATOR.'portfolio.php')) {
                $slugs[] = $entry;
            }
        }

        sort($slugs);

        return $slugs;
    }

    /** @return Collection<int, Portfolio> */
    public function all(): Collection
    {
        return collect($this->slugs())->map(fn (string $slug) => $this->find($slug))->filter()->values();
    }

    public function find(string $slug): ?Portfolio
    {
        if (array_key_exists($slug, $this->loaded)) {
            return $this->loaded[$slug];
        }

        // Slugs come from the URL: only allow plain folder names.
        if (! preg_match('/^[a-z0-9][a-z0-9-]*$/', $slug) || ! in_array($slug, $this->slugs(), true)) {
            return $this->loaded[$slug] = null;
        }

        $path = $this->basePath.DIRECTORY_SEPARATOR.$slug;

        return $this->loaded[$slug] = new Portfolio($slug, $path, require $path.DIRECTORY_SEPARATOR.'portfolio.php');
    }
}

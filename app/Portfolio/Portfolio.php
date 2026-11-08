<?php

namespace App\Portfolio;

use Illuminate\Support\Collection;

/**
 * One person's portfolio, backed by a folder:
 *
 *   portfolio.php          identity, contact, socials, locales, featured order
 *   content/{locale}.php   page copy for that language
 *   projects/{slug}/       project.php (meta) + {locale}.php (text)
 *   notes/{slug}/          note.php (meta) + {locale}.md (text)
 *   media/                 source images (optimized by `php artisan portfolio:media`)
 */
class Portfolio
{
    /** @var array<string, array> */
    protected array $copy = [];

    /** @var Collection<int, Project>|null */
    protected ?Collection $projects = null;

    /** @var Collection<int, Note>|null */
    protected ?Collection $notes = null;

    protected ?Media $media = null;

    public function __construct(
        public readonly string $slug,
        public readonly string $path,
        protected array $config,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /* ----------------------------------------------------------------- */
    /* Languages */
    /* ----------------------------------------------------------------- */

    /** @return list<string> */
    public function locales(): array
    {
        $known = array_keys(config('portfolio.locales'));

        return array_values(array_intersect($this->config['locales'] ?? ['en'], $known));
    }

    public function supports(string $locale): bool
    {
        return in_array($locale, $this->locales(), true);
    }

    public function defaultLocale(): string
    {
        $default = $this->config['default_locale'] ?? 'en';

        return $this->supports($default) ? $default : $this->locales()[0];
    }

    /** The language every other language falls back to. */
    public function baseLocale(): string
    {
        return $this->defaultLocale();
    }

    /* ----------------------------------------------------------------- */
    /* Page copy */
    /* ----------------------------------------------------------------- */

    public function copy(string $locale): array
    {
        if (! array_key_exists($locale, $this->copy)) {
            $file = $this->path."/content/{$locale}.php";
            $this->copy[$locale] = is_file($file) ? require $file : [];
        }

        return $this->copy[$locale];
    }

    /**
     * Page copy for a key. Falls back to the base language when a translation
     * is missing, so a page never renders a hole — Text::lang tells the view
     * which language it actually got, for a correct lang="" attribute.
     */
    public function text(string $locale, string $key, mixed $default = null): mixed
    {
        $value = data_get($this->copy($locale), $key);

        if (($value === null || $value === '' || $value === []) && $locale !== $this->baseLocale()) {
            $value = data_get($this->copy($this->baseLocale()), $key);
        }

        return $value ?? $default;
    }

    /** Which language text() actually resolved to for this key. */
    public function textLocale(string $locale, string $key): string
    {
        $value = data_get($this->copy($locale), $key);

        return ($value === null || $value === '' || $value === []) ? $this->baseLocale() : $locale;
    }

    public function name(string $locale): string
    {
        $name = $this->config['name'] ?? $this->slug;

        return is_array($name) ? ($name[$locale] ?? $name[$this->baseLocale()] ?? reset($name)) : $name;
    }

    /* ----------------------------------------------------------------- */
    /* Disciplines (portfolio-specific work categories) */
    /* ----------------------------------------------------------------- */

    /** @return list<string> */
    public function disciplineKeys(): array
    {
        return $this->config['disciplines'] ?? [];
    }

    public function discipline(string $locale, string $key): array
    {
        return (array) $this->text($locale, "disciplines.{$key}", ['title' => $key]);
    }

    /* ----------------------------------------------------------------- */
    /* Projects */
    /* ----------------------------------------------------------------- */

    /** @return Collection<int, Project> */
    public function projects(): Collection
    {
        if ($this->projects !== null) {
            return $this->projects;
        }

        $dirs = glob($this->path.'/projects/*', GLOB_ONLYDIR) ?: [];

        return $this->projects = collect($dirs)
            ->reject(fn (string $dir) => in_array(basename($dir)[0], ['_', '.'], true))
            ->filter(fn (string $dir) => is_file($dir.'/project.php'))
            ->map(fn (string $dir) => Project::fromDirectory($this, $dir))
            ->reject(fn (Project $p) => $p->isHidden())
            ->sortBy(fn (Project $p) => [$p->order(), $p->slug])
            ->values();
    }

    public function project(string $slug): ?Project
    {
        return $this->projects()->first(fn (Project $p) => $p->slug === $slug);
    }

    /** @return Collection<int, Project> */
    public function featuredProjects(): Collection
    {
        $order = $this->config['featured'] ?? [];

        return collect($order)->map(fn (string $slug) => $this->project($slug))->filter()->values();
    }

    /** @return Collection<int, Project> */
    public function relatedProjects(Project $project, int $limit = 3): Collection
    {
        $explicit = collect($project->get('related', []))->map(fn ($slug) => $this->project($slug))->filter();

        $sameDiscipline = $this->projects()
            ->filter(fn (Project $p) => $p->slug !== $project->slug && $p->category() === $project->category());

        $others = $this->projects()->filter(fn (Project $p) => $p->slug !== $project->slug);

        return $explicit->concat($sameDiscipline)->concat($others)
            ->unique(fn (Project $p) => $p->slug)
            ->take($limit)
            ->values();
    }

    /* ----------------------------------------------------------------- */
    /* Notes */
    /* ----------------------------------------------------------------- */

    /** @return Collection<int, Note> All notes, newest first. Drafts only with editor hints on. */
    public function notes(): Collection
    {
        if ($this->notes !== null) {
            return $this->notes;
        }

        $dirs = glob($this->path.'/notes/*', GLOB_ONLYDIR) ?: [];

        return $this->notes = collect($dirs)
            ->reject(fn (string $dir) => in_array(basename($dir)[0], ['_', '.'], true))
            ->filter(fn (string $dir) => is_file($dir.'/note.php'))
            ->map(fn (string $dir) => Note::fromDirectory($this, $dir))
            ->reject(fn (Note $n) => $n->isDraft() && ! config('portfolio.editor_hints'))
            ->sortByDesc(fn (Note $n) => $n->date()?->timestamp ?? 0)
            ->values();
    }

    public function note(string $slug): ?Note
    {
        return $this->notes()->first(fn (Note $n) => $n->slug === $slug);
    }

    /* ----------------------------------------------------------------- */
    /* Media */
    /* ----------------------------------------------------------------- */

    public function media(): Media
    {
        return $this->media ??= new Media($this);
    }

    public function mediaPath(): string
    {
        return $this->path.DIRECTORY_SEPARATOR.'media';
    }
}

<?php

namespace App\Portfolio;

/**
 * Resolves image paths written in content (relative to the portfolio's media/
 * folder, e.g. "projects/roumdoul/cover.jpg") into something a <img> can use.
 *
 * 1. If `php artisan portfolio:media` has run, use the optimized WebP widths
 *    it published to public/portfolios/{slug}/ (via manifest.json).
 * 2. Otherwise, if the source file exists, serve it as-is through the media
 *    route — works immediately after dropping a file in, just not optimized.
 * 3. Otherwise return null and the view draws a "image required" placeholder.
 */
class Media
{
    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif', 'svg'];

    protected ?array $manifest = null;

    public function __construct(protected Portfolio $portfolio) {}

    public function publicDir(): string
    {
        return public_path(config('portfolio.public_dir').DIRECTORY_SEPARATOR.$this->portfolio->slug);
    }

    public function publicUrl(string $relative): string
    {
        return asset(config('portfolio.public_dir').'/'.$this->portfolio->slug.'/'.$relative);
    }

    public function manifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $file = $this->publicDir().DIRECTORY_SEPARATOR.'manifest.json';

        return $this->manifest = is_file($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
    }

    /** Absolute path of a source file inside media/, or null if missing / outside the folder. */
    public function sourcePath(?string $path): ?string
    {
        if (! $path || str_contains($path, '..') || str_contains($path, "\0")) {
            return null;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! in_array($ext, self::EXTENSIONS, true)) {
            return null;
        }

        $root = realpath($this->portfolio->mediaPath());
        $full = realpath($this->portfolio->mediaPath().DIRECTORY_SEPARATOR.$path);

        if (! $root || ! $full || ! str_starts_with($full, $root.DIRECTORY_SEPARATOR) || ! is_file($full)) {
            return null;
        }

        return $full;
    }

    /**
     * @return array{src: string, srcset: string|null, width: int|null, height: int|null, svg: bool}|null
     */
    public function image(?string $path): ?array
    {
        if (! $path) {
            return null;
        }

        $entry = $this->manifest()[$path] ?? null;
        $source = $this->sourcePath($path);

        // Manifest wins only while it still matches the file on disk.
        if ($entry && $source && ($entry['mtime'] ?? null) === filemtime($source)) {
            if (! empty($entry['svg'])) {
                return ['src' => $this->publicUrl($entry['file']), 'srcset' => null, 'width' => $entry['w'] ?? null, 'height' => $entry['h'] ?? null, 'svg' => true];
            }

            $variants = $entry['variants'] ?? [];
            ksort($variants);

            $srcset = collect($variants)->map(fn (string $file, $w) => $this->publicUrl($file)." {$w}w")->implode(', ');
            $largest = end($variants);

            return [
                'src' => $this->publicUrl($variants[min(1600, max(array_keys($variants)))] ?? $largest),
                'srcset' => $srcset ?: null,
                'width' => $entry['w'],
                'height' => $entry['h'],
                'svg' => false,
            ];
        }

        if (! $source) {
            return null;
        }

        $size = @getimagesize($source);

        return [
            'src' => route('portfolio.media', ['portfolio' => $this->portfolio->slug, 'path' => $path]),
            'srcset' => null,
            'width' => $size[0] ?? null,
            'height' => $size[1] ?? null,
            'svg' => str_ends_with(strtolower($path), '.svg'),
        ];
    }

    /** Best single absolute URL for an image (used for Open Graph). */
    public function url(?string $path): ?string
    {
        $image = $this->image($path);

        return $image ? url($image['src']) : null;
    }
}

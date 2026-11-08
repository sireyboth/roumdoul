<?php

namespace App\Console\Commands;

use App\Portfolio\Media;
use App\Portfolio\Portfolio;
use App\Portfolio\PortfolioRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Turns the source images in portfolios/{slug}/media into responsive WebP
 * files in public/portfolios/{slug}/ and writes manifest.json describing them.
 * Re-run after adding or replacing images; unchanged files are skipped.
 */
class PortfolioMediaCommand extends Command
{
    protected $signature = 'portfolio:media {portfolio? : Folder name; all portfolios when omitted} {--force : Rebuild every image} {--quality=82 : WebP quality 1-100}';

    protected $description = 'Generate optimized responsive images for personal portfolios';

    public function handle(PortfolioRepository $repository): int
    {
        if (! function_exists('imagewebp')) {
            $this->error('The GD extension with WebP support is required.');

            return self::FAILURE;
        }

        $slug = $this->argument('portfolio');
        $portfolios = $slug ? collect([$repository->find($slug)])->filter() : $repository->all();

        if ($portfolios->isEmpty()) {
            $this->error($slug ? "No portfolio folder named \"{$slug}\"." : 'No portfolios found.');

            return self::FAILURE;
        }

        foreach ($portfolios as $portfolio) {
            $this->process($portfolio);
        }

        return self::SUCCESS;
    }

    protected function process(Portfolio $portfolio): void
    {
        $this->components->info("Portfolio: {$portfolio->slug}");

        $mediaRoot = $portfolio->mediaPath();
        $outRoot = $portfolio->media()->publicDir();
        $manifestFile = $outRoot.DIRECTORY_SEPARATOR.'manifest.json';
        $old = is_file($manifestFile) ? (json_decode(file_get_contents($manifestFile), true) ?: []) : [];
        $manifest = [];

        if (! is_dir($mediaRoot)) {
            $this->line('  no media/ folder — nothing to do');

            return;
        }

        File::ensureDirectoryExists($outRoot);

        foreach (File::allFiles($mediaRoot) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $ext = strtolower($file->getExtension());

            if (! in_array($ext, Media::EXTENSIONS, true)) {
                continue;
            }

            $mtime = $file->getMTime();

            if (! $this->option('force') && ($old[$relative]['mtime'] ?? null) === $mtime && $this->outputsExist($outRoot, $old[$relative])) {
                $manifest[$relative] = $old[$relative];
                $this->line("  <fg=gray>skip</>  {$relative}");

                continue;
            }

            try {
                $manifest[$relative] = $ext === 'svg'
                    ? $this->copySvg($file->getPathname(), $relative, $outRoot, $mtime)
                    : $this->resize($file->getPathname(), $relative, $outRoot, $mtime);
                $this->line("  <fg=green>done</>  {$relative}");
            } catch (\Throwable $e) {
                $this->line("  <fg=red>fail</>  {$relative}: {$e->getMessage()}");
            }
        }

        ksort($manifest);
        file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->components->info(count($manifest).' image(s) in manifest.');
    }

    protected function outputsExist(string $outRoot, array $entry): bool
    {
        $files = isset($entry['file']) ? [$entry['file']] : array_values($entry['variants'] ?? []);

        foreach ($files as $f) {
            if (! is_file($outRoot.DIRECTORY_SEPARATOR.$f)) {
                return false;
            }
        }

        return $files !== [];
    }

    protected function copySvg(string $source, string $relative, string $outRoot, int $mtime): array
    {
        File::ensureDirectoryExists(dirname($outRoot.'/'.$relative));
        copy($source, $outRoot.'/'.$relative);

        return ['svg' => true, 'file' => $relative, 'mtime' => $mtime];
    }

    protected function resize(string $source, string $relative, string $outRoot, int $mtime): array
    {
        $image = @imagecreatefromstring(file_get_contents($source));

        if (! $image) {
            throw new \RuntimeException('not a readable image');
        }

        $image = $this->applyExifOrientation($image, $source);
        $w = imagesx($image);
        $h = imagesy($image);
        $base = preg_replace('/\.[^.]+$/', '', $relative);
        $quality = max(1, min(100, (int) $this->option('quality')));

        $widths = array_filter(config('portfolio.image_widths'), fn (int $width) => $width < $w);
        $widths[] = min($w, max(config('portfolio.image_widths')));
        $widths = array_values(array_unique($widths));
        sort($widths);

        $variants = [];

        foreach ($widths as $width) {
            $height = (int) round($h * $width / $w);
            $resized = imagecreatetruecolor($width, $height);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $w, $h);

            $out = "{$base}-{$width}.webp";
            File::ensureDirectoryExists(dirname($outRoot.'/'.$out));
            imagewebp($resized, $outRoot.'/'.$out, $quality);
            imagedestroy($resized);

            $variants[$width] = $out;
        }

        imagedestroy($image);

        $largest = max(array_keys($variants));

        return ['w' => $largest, 'h' => (int) round($h * $largest / $w), 'variants' => $variants, 'mtime' => $mtime];
    }

    /** Phone photos are often stored sideways with an EXIF rotation flag. */
    protected function applyExifOrientation(\GdImage $image, string $source): \GdImage
    {
        if (! function_exists('exif_read_data') || ! preg_match('/\.jpe?g$/i', $source)) {
            return $image;
        }

        $orientation = @exif_read_data($source)['Orientation'] ?? 1;

        return match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }
}

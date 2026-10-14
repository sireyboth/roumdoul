<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Shrinks customer photo uploads before they are stored, so a full-resolution phone photo
 * (often 5–15 MB) is served to every invitation guest as a ~200–500 KB image instead.
 * Photos are scaled down to fit MAX_EDGE, rotated upright from their EXIF orientation (GD
 * ignores it, so portrait phone shots would otherwise appear sideways) and re-encoded: JPEG
 * stays JPEG, PNG/WebP become WebP so transparency survives. GIFs are stored untouched to
 * keep their animation. If GD can't decode a file, the original is stored as-is.
 */
class ImageOptimizer
{
    public const MAX_EDGE = 1600;

    public const QUALITY = 82;

    /** Stores the optimized image under $directory on $disk and returns its path. */
    public function store(UploadedFile $file, string $directory, string $disk = 's3'): string
    {
        $mime = $file->getMimeType();

        if ($mime === 'image/gif' || ! ($image = $this->load($file->getRealPath(), $mime))) {
            return $file->store($directory, $disk);
        }

        if ($mime !== 'image/jpeg') {
            imagepalettetotruecolor($image);   // scaling a palette PNG would band its colours and drop alpha
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        $image = $this->orient($image, $file->getRealPath(), $mime);
        $image = $this->fit($image);

        ob_start();
        if ($mime === 'image/jpeg') {
            imagejpeg($image, null, self::QUALITY);
            $ext = 'jpg';
        } else {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagewebp($image, null, self::QUALITY);
            $ext = 'webp';
        }
        $bytes = ob_get_clean();

        $path = trim($directory, '/').'/'.Str::random(40).'.'.$ext;
        Storage::disk($disk)->put($path, $bytes);

        return $path;
    }

    private function load(string $path, ?string $mime): \GdImage|false
    {
        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };
    }

    private function orient(\GdImage $image, string $path, string $mime): \GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($path)['Orientation'] ?? 1);
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        return $rotated ?: $image;
    }

    private function fit(\GdImage $image): \GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $scale = self::MAX_EDGE / max($w, $h);

        if ($scale >= 1) {
            return $image;
        }

        return imagescale($image, (int) round($w * $scale), (int) round($h * $scale), IMG_BICUBIC) ?: $image;
    }
}

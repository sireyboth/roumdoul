<?php

namespace App\Console\Commands;

use App\Models\Service;
use Illuminate\Console\Command;

/**
 * One-off: re-encodes product cover + gallery images uploaded before Service::optimizeImages()
 * ran on save (heavy PNGs slow the homepage on mobile). Safe to run again: small files are skipped.
 */
class OptimizeServiceImages extends Command
{
    protected $signature = 'services:optimize-images';

    protected $description = 'Shrink heavy product images already stored on S3/R2';

    public function handle(): int
    {
        $total = 0;

        Service::query()->each(function (Service $service) use (&$total) {
            $count = $service->optimizeImages();
            $total += $count;

            if ($count) {
                $this->line("{$service->name_en}: {$count} image(s) optimized");
            }
        });

        $this->info("Done — {$total} image(s) optimized.");

        return self::SUCCESS;
    }
}

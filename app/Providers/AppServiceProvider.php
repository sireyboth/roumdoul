<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->seedEphemeralSqliteDatabase();
    }

    /**
     * On read-only serverless deployments (e.g. Vercel), DB_DATABASE is pointed
     * at a path under the OS temp dir so SQLite writes succeed within a single
     * warm instance. The database file itself isn't part of the deployment
     * (it's gitignored), so on cold start we create it fresh and migrate it —
     * that way writes fail with a normal empty result instead of "no such
     * table". Data does not persist across cold starts — this is a
     * best-effort fallback, not durable storage.
     */
    private function seedEphemeralSqliteDatabase(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $path = config('database.connections.sqlite.database');

        if (! $path || ! str_starts_with($path, sys_get_temp_dir())) {
            return;
        }

        if (file_exists($path)) {
            return;
        }

        touch($path);
        Artisan::call('migrate', ['--force' => true]);
    }
}

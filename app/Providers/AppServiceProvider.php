<?php

namespace App\Providers;

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
        if (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']) || getenv('VERCEL') || app()->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        \Illuminate\Support\Facades\Storage::extend('vercel_blob', function ($app, $config) {
            $token = $config['token'] ?? env('BLOB_READ_WRITE_TOKEN');
            $storeId = $config['store_id'] ?? env('BLOB_STORE_ID');

            if (empty($token)) {
                return \Illuminate\Support\Facades\Storage::disk('local');
            }

            $adapter = new \App\Services\Storage\VercelBlobAdapter($token, $storeId);

            return new \Illuminate\Filesystem\FilesystemAdapter(
                new \League\Flysystem\Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }
}

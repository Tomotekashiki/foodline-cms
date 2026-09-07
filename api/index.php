<?php

// Forward Vercel Serverless Function requests to Laravel
if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL']) || getenv('VERCEL')) {
    // Set storage path and logging before Laravel boots
    $_ENV['LARAVEL_STORAGE_PATH'] = '/tmp/storage';
    $_SERVER['LARAVEL_STORAGE_PATH'] = '/tmp/storage';
    putenv('LARAVEL_STORAGE_PATH=/tmp/storage');

    $_ENV['LOG_CHANNEL'] = 'stderr';
    $_SERVER['LOG_CHANNEL'] = 'stderr';
    putenv('LOG_CHANNEL=stderr');

    $_ENV['APP_MAINTENANCE_DRIVER'] = 'file';
    $_SERVER['APP_MAINTENANCE_DRIVER'] = 'file';
    putenv('APP_MAINTENANCE_DRIVER=file');

    // 1. Create writable storage directories in /tmp
    $storageDirs = [
        '/tmp/storage/app/public',
        '/tmp/storage/app/private',
        '/tmp/storage/framework/cache/data',
        '/tmp/storage/framework/sessions',
        '/tmp/storage/framework/testing',
        '/tmp/storage/framework/views',
        '/tmp/storage/logs',
        '/tmp/bootstrap/cache',
    ];
    foreach ($storageDirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    // 2. Setup SQLite database in writable /tmp
    $sourceDb = __DIR__ . '/../database/database.sqlite';
    $targetDb = '/tmp/database.sqlite';
    if (file_exists($sourceDb)) {
        if (!file_exists($targetDb) || filesize($targetDb) < 1000) {
            copy($sourceDb, $targetDb);
        }
    } elseif (!file_exists($targetDb)) {
        touch($targetDb);
    }
    $_ENV['DB_DATABASE'] = $targetDb;
    $_SERVER['DB_DATABASE'] = $targetDb;
    putenv("DB_DATABASE={$targetDb}");

    // 3. Fix script/path for Laravel routing on Vercel
    // vercel-php executes /api/index.php which sets SCRIPT_NAME to /api/index.php,
    // causing Laravel's Request::getBaseUrl() to strip /api from /api/menu-items.
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
}

require __DIR__ . '/../public/index.php';

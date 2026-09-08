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

    if (empty($_ENV['BCRYPT_ROUNDS'])) {
        $_ENV['BCRYPT_ROUNDS'] = '12';
        $_SERVER['BCRYPT_ROUNDS'] = '12';
        putenv('BCRYPT_ROUNDS=12');
    }

    $requestHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? null;
    if ($requestHost && !empty($requestHost)) {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'https';
        $currentAppUrl = "{$scheme}://{$requestHost}";
        $_ENV['APP_URL'] = $currentAppUrl;
        $_SERVER['APP_URL'] = $currentAppUrl;
        putenv("APP_URL={$currentAppUrl}");
    } elseif (empty($_ENV['APP_URL']) || $_ENV['APP_URL'] === 'http://localhost' || $_ENV['APP_URL'] === 'http://127.0.0.1:8000') {
        $_ENV['APP_URL'] = 'https://foodline-cms.vercel.app';
        $_SERVER['APP_URL'] = 'https://foodline-cms.vercel.app';
        putenv('APP_URL=https://foodline-cms.vercel.app');
    }

    if (empty($_ENV['FILESYSTEM_DISK'])) {
        $_ENV['FILESYSTEM_DISK'] = 'local';
        $_SERVER['FILESYSTEM_DISK'] = 'local';
        putenv('FILESYSTEM_DISK=local');
    }

    // 1. Create writable storage directories in /tmp
    $storageDirs = [
        '/tmp/storage/app/public',
        '/tmp/storage/app/private',
        '/tmp/storage/app/private/livewire-tmp',
        '/tmp/storage/app/public/livewire-tmp',
        '/tmp/storage/app/livewire-tmp',
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

    // 2. Setup Database (PostgreSQL if POSTGRES_URL / POSTGRES_HOST / pgsql, otherwise SQLite in /tmp)
    $hasPostgres = !empty($_ENV['POSTGRES_URL']) || !empty(getenv('POSTGRES_URL')) ||
                   !empty($_ENV['POSTGRES_HOST']) || !empty(getenv('POSTGRES_HOST')) ||
                   (isset($_ENV['DB_CONNECTION']) && $_ENV['DB_CONNECTION'] === 'pgsql');

    if ($hasPostgres) {
        $_ENV['DB_CONNECTION'] = 'pgsql';
        $_SERVER['DB_CONNECTION'] = 'pgsql';
        putenv('DB_CONNECTION=pgsql');
    } else {
        $sourceDb = __DIR__ . '/../database/database.sqlite';
        $sourceB64 = __DIR__ . '/../database/database.sqlite.base64';
        $targetDb = '/tmp/database.sqlite';
        if (file_exists($sourceDb) && filesize($sourceDb) > 1000) {
            if (!file_exists($targetDb) || filesize($targetDb) < 1000) {
                copy($sourceDb, $targetDb);
            }
        } elseif (file_exists($sourceB64)) {
            if (!file_exists($targetDb) || filesize($targetDb) < 1000) {
                file_put_contents($targetDb, base64_decode(file_get_contents($sourceB64)));
            }
        } elseif (!file_exists($targetDb)) {
            touch($targetDb);
        }
        $_ENV['DB_DATABASE'] = $targetDb;
        $_SERVER['DB_DATABASE'] = $targetDb;
        putenv("DB_DATABASE={$targetDb}");
    }

    // 3. Fix script/path, Host and HTTPS scheme for Laravel on Vercel
    // vercel-php executes /api/index.php which sets SCRIPT_NAME to /api/index.php,
    // causing Laravel's Request::getBaseUrl() to strip /api from /api/menu-items.
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = '443';
    $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
    $_SERVER['HTTP_X_FORWARDED_PORT'] = '443';

    if ($requestHost) {
        $_SERVER['HTTP_HOST'] = $requestHost;
        $_SERVER['SERVER_NAME'] = explode(':', $requestHost)[0];
    }
}

require __DIR__ . '/../public/index.php';

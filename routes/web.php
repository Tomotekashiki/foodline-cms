<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/debug-env', function () {
    $sourceDb = base_path('database/database.sqlite');
    $sourceB64 = base_path('database/database.sqlite.base64');
    $targetDb = '/tmp/database.sqlite';
    return response()->json([
        'base_path' => base_path(),
        'source_exists' => file_exists($sourceDb),
        'source_size' => file_exists($sourceDb) ? filesize($sourceDb) : null,
        'source_b64_exists' => file_exists($sourceB64),
        'source_b64_size' => file_exists($sourceB64) ? filesize($sourceB64) : null,
        'target_exists' => file_exists($targetDb),
        'target_size' => file_exists($targetDb) ? filesize($targetDb) : null,
        'db_config' => config('database.connections.sqlite'),
        'dir_database' => is_dir(base_path('database')) ? scandir(base_path('database')) : null,
    ]);
});


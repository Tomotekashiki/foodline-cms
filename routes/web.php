<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/debug-env', function () {
    $sourceDb = base_path('database/database.sqlite');
    $targetDb = '/tmp/database.sqlite';
    return response()->json([
        'base_path' => base_path(),
        'source_exists' => file_exists($sourceDb),
        'source_size' => file_exists($sourceDb) ? filesize($sourceDb) : null,
        'target_exists' => file_exists($targetDb),
        'target_size' => file_exists($targetDb) ? filesize($targetDb) : null,
        'db_config' => config('database.connections.sqlite'),
        'dir_database' => is_dir(base_path('database')) ? scandir(base_path('database')) : null,
    ]);
});


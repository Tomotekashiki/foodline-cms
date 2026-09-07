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
        'bcrypt_defined' => defined('PASSWORD_BCRYPT'),
        'argon2i_defined' => defined('PASSWORD_ARGON2I'),
        'argon2id_defined' => defined('PASSWORD_ARGON2ID'),
        'test_bcrypt' => (function() {
            try {
                return password_hash('secret', PASSWORD_BCRYPT);
            } catch (\Throwable $e) {
                return 'Error: ' . $e->getMessage();
            }
        })(),
        'hashing_config' => config('hashing'),
        'test_cost_int' => (function() {
            try {
                return password_hash('secret', PASSWORD_BCRYPT, ['cost' => 12]);
            } catch (\Throwable $e) {
                return 'Error: ' . $e->getMessage() . ' (' . get_class($e) . ')';
            }
        })(),
        'test_cost_string' => (function() {
            try {
                return password_hash('secret', PASSWORD_BCRYPT, ['cost' => '12']);
            } catch (\Throwable $e) {
                return 'Error: ' . $e->getMessage() . ' (' . get_class($e) . ')';
            }
        })(),
        'test_cost_from_config' => (function() {
            try {
                $rounds = config('hashing.bcrypt.rounds');
                return [
                    'rounds_val' => $rounds,
                    'rounds_type' => gettype($rounds),
                    'result' => password_hash('secret', PASSWORD_BCRYPT, ['cost' => $rounds]),
                ];
            } catch (\Throwable $e) {
                return 'Error: ' . $e->getMessage() . ' (' . get_class($e) . ')';
            }
        })(),
        'test_argon2id' => (function() {
            try {
                return password_hash('secret', PASSWORD_ARGON2ID);
            } catch (\Throwable $e) {
                return 'Error: ' . $e->getMessage();
            }
        })(),
        'test_hash_argon2id_make' => (function() {
            try {
                return app('hash')->driver('argon2id')->make('secret');
            } catch (\Throwable $e) {
                return 'Error: ' . $e->getMessage() . ' (' . get_class($e) . ')';
            }
        })(),
        'test_hash_make' => (function() {
            try {
                return \Illuminate\Support\Facades\Hash::make('secret');
            } catch (\Throwable $e) {
                return 'Error: ' . $e->getMessage() . ' (' . get_class($e) . ') in ' . $e->getFile() . ':' . $e->getLine();
            }
        })(),
    ]);
});


<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | This option determines the default session driver that is utilized for
    | incoming requests. Laravel supports a variety of storage options to
    | persist session data. Database storage is a great default choice.
    |
    | Supported: "file", "cookie", "database", "memcached",
    |            "redis", "dynamodb", "array"
    |
    */

    'driver' => !empty(env('SESSION_DRIVER')) ? env('SESSION_DRIVER') : 'database',

    'lifetime' => !empty(env('SESSION_LIFETIME')) && (int) env('SESSION_LIFETIME') > 0 ? (int) env('SESSION_LIFETIME') : 10080,

    'expire_on_close' => !empty(env('SESSION_EXPIRE_ON_CLOSE')) ? filter_var(env('SESSION_EXPIRE_ON_CLOSE'), FILTER_VALIDATE_BOOLEAN) : false,

    'encrypt' => !empty(env('SESSION_ENCRYPT')) ? filter_var(env('SESSION_ENCRYPT'), FILTER_VALIDATE_BOOLEAN) : false,

    'files' => storage_path('framework/sessions'),

    'connection' => !empty(env('SESSION_CONNECTION')) ? env('SESSION_CONNECTION') : 'pgsql_admin',

    'table' => !empty(env('SESSION_TABLE')) ? env('SESSION_TABLE') : 'sessions',

    'store' => !empty(env('SESSION_STORE')) ? env('SESSION_STORE') : null,

    'lottery' => [2, 100],

    'cookie' => !empty(env('SESSION_COOKIE')) ? env('SESSION_COOKIE') : 'foodline_session',

    'path' => !empty(env('SESSION_PATH')) ? env('SESSION_PATH') : '/',

    'domain' => !empty(env('SESSION_DOMAIN')) ? env('SESSION_DOMAIN') : null,

    'secure' => !empty(env('SESSION_SECURE_COOKIE')) ? filter_var(env('SESSION_SECURE_COOKIE'), FILTER_VALIDATE_BOOLEAN) : true,

    'http_only' => true,

    'same_site' => !empty(env('SESSION_SAME_SITE')) ? env('SESSION_SAME_SITE') : 'lax',

    'partitioned' => false,

    'serialization' => 'json',

];

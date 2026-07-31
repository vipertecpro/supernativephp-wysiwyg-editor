<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | Published purely to drop the `realpath()` the framework default wraps
    | this in. NativePHP stages a copy of the app and runs `composer install`
    | inside it, and that copy deliberately excludes `storage/framework` — so
    | the directory does not exist yet when `package:discover` boots the app.
    |
    | `realpath()` answers FALSE for a path that is not there, the view
    | compiler rejects an empty cache path, and the build dies with "Please
    | provide a valid cache path" long before anything is compiled.
    |
    | Handing over the path as a plain string lets the compiler create the
    | directory itself, which is what it does everywhere else.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        storage_path('framework/views'),
    ),

];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    | IMPORTANT: Do NOT use PHP ternary/conditional expressions here to select
    | between disk configs. config:cache evaluates this file once and bakes the
    | result — any env() call inside a ternary would be evaluated at cache time
    | and the chosen array would be frozen. Instead, always define all disks
    | statically and control which one is used via FILESYSTEM_DISK (default)
    | or by explicitly passing the disk name to Storage::disk().
    |
    | Production (Railway): set FILESYSTEM_DISK=s3 (lowercase!) so that
    | config('filesystems.default') returns 's3'.  All controllers call
    | Storage::disk(config('filesystems.default', 'public')) which resolves
    | to the 's3' disk in production and the local 'public' disk in dev.
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        // Local development disk — served via public/storage symlink.
        // In production, set FILESYSTEM_DISK=s3 to use the 's3' disk instead.
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

        // Cloudflare R2 / AWS S3 disk.
        // Set FILESYSTEM_DISK=s3 (lowercase) in Railway to make this
        // the default disk used by all file uploads.
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'auto'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            // Cloudflare R2 requires path-style endpoints.
            'use_path_style_endpoint' => true,
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];

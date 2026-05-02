<?php

declare(strict_types=1);

return [
    'log_dir'    => storage_path('logs'),
    'min_level'  => env('LOG_LEVEL', 'warning'),
    'date_format' => 'Y-m-d H:i:s',
    'timezone'   => env('APP_TIMEZONE', ''),

    'file' => [
        'max_file_size'  => 1048576,
        'max_files'      => 5,
        'prefix'         => '',
        'suffix'         => '',
        'date_structure' => 'Y/m',
    ],

    'stderr' => [
        'enabled'      => env('PHP_LOGGER_STDERR', true),
        'skip_in_test' => true,
    ],
];

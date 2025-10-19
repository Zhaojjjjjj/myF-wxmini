<?php
return [
    // 默认日志记录通道
    'default'      => env('LOG_CHANNEL', 'file'),

    // 日志记录方式
    'channels'     => [
        'file' => [
            'type'           => 'File',
            'path'           => runtime_path() . 'log',
            'level'          => ['error', 'warning', 'info', 'sql'],
            'single'         => false,
            'apart_level'    => [],
            'max_files'      => 30,
            'json'           => false,
        ],
    ],
];

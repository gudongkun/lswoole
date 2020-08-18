<?php
/**
 *
 */
return [
    'host' => '0.0.0.0',
    'port' => 8080,
    'worker_num' => 4,
    'max_request' => 5000,
    'pid_file' => storage_path('logs/swoole.pid'),
    'enableGlobals'=>false
];
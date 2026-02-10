<?php

namespace App\Core;

class Logger
{
    protected static string $logPath = __DIR__ . '/../../writable/logs/app.log';

    public static function log(string $level, string $message, array $context = []): void
    {
        $date = date('Y-m-d H:i:s');
        $ctx  = empty($context) ? '' : json_encode($context);

        $line = "[$date] $level: $message $ctx" . PHP_EOL;
        file_put_contents(self::$logPath, $line, FILE_APPEND);
    }

    public static function info(string $msg, array $ctx = [])
    {
        self::log('INFO', $msg, $ctx);
    }

    public static function error(string $msg, array $ctx = [])
    {
        self::log('ERROR', $msg, $ctx);
    }
}

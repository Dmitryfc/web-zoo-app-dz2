<?php

namespace App;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;

class Logger
{
    private static ?MonologLogger $logger = null;

    public static function getInstance(): MonologLogger
    {
        if (self::$logger === null) {
            self::$logger = new MonologLogger('http');

            // Пишем в файл app.log внутри app/ и в php://stdout для Docker логов
            $logPath = __DIR__ . '/../app.log';
            self::$logger->pushHandler(new StreamHandler($logPath, MonologLogger::INFO));
            self::$logger->pushHandler(new StreamHandler('php://stdout', MonologLogger::INFO));
        }

        return self::$logger;
    }
}
<?php

declare(strict_types=1);

namespace App;

use App\Config\Config;
use PDO;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

final class Database
{
    private static bool $booted = false;

    /**
     * Инициализация Eloquent Capsule ORM с получением параметров из Config
     */
    public static function init(): void
    {
        if (self::$booted) {
            return;
        }

        $config = null;
        try {
            $config = Config::load();
        } catch (\Throwable) {
            // fallback to getenv
        }

        $host = $config?->get('db.host') ?: (getenv('DB_HOST') ?: (getenv('POSTGRES_HOST') ?: 'postgres'));
        $port = $config?->get('db.port') ?: (getenv('DB_PORT') ?: (getenv('POSTGRES_PORT') ?: 5432));
        $database = $config?->get('db.database') ?: (getenv('DB_NAME') ?: (getenv('POSTGRES_DB') ?: 'app'));
        $username = $config?->get('db.username') ?: (getenv('DB_USER') ?: (getenv('POSTGRES_USER') ?: 'app'));
        $password = $config?->get('db.password') ?: (getenv('DB_PASSWORD') ?: (getenv('POSTGRES_PASSWORD') ?: ''));
        $driver = $config?->get('db.driver') ?: 'pgsql';
        $charset = $config?->get('db.charset') ?: 'utf8';
        $prefix = $config?->get('db.prefix') ?: '';
        $schema = $config?->get('db.schema') ?: 'public';

        $capsule = new Capsule();

        $capsule->addConnection([
            'driver'    => (string) $driver,
            'host'      => (string) $host,
            'port'      => (int) $port,
            'database'  => (string) $database,
            'username'  => (string) $username,
            'password'  => (string) $password,
            'charset'   => (string) $charset,
            'prefix'    => (string) $prefix,
            'schema'    => (string) $schema,
        ]);

        $capsule->setEventDispatcher(new Dispatcher(new Container()));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        self::$booted = true;
    }

    /**
     * Исходный метод для проверки здоровья (health.php)
     */
    public static function connect(): PDO
    {
        $host = getenv('DB_HOST') ?: 'postgres';
        $port = getenv('DB_PORT') ?: '5432';
        $name = getenv('DB_NAME') ?: 'app';
        $user = getenv('DB_USER') ?: 'app';
        $password = getenv('DB_PASSWORD') ?: '';

        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $name);

        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 3,
        ]);
    }
}
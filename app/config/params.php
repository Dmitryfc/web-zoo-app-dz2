<?php

declare(strict_types=1);

/**
 * Статическая конфигурация приложения по окружениям.
 *
 * Секции:
 *   - 'common'     — общие параметры для всех окружений
 *   - 'local'      — параметры для локальной разработки (APP_ENV=local)
 *   - 'production' — параметры для продакшена (APP_ENV=production)
 *   - 'test'       — параметры для автоматических тестов (APP_ENV=test)
 *
 * Секретные значения (пароли, токены) зашифрованы ключом APP_CONFIG_KEY
 * и имеют префикс "enc:v1:". В открытом виде пароли в git не хранятся.
 */
return [
    'common' => [
        'app' => [
            'name' => 'web-zoo-app/dz2',
            'version' => '1.0.0',
        ],
        'db' => [
            'driver' => 'pgsql',
            'port' => 5432,
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
        ],
        'logging' => [
            'channel' => 'app',
            'level' => 'debug',
        ],
    ],

    'local' => [
        'debug' => true,
        'db' => [
            'host' => 'postgres',
            'database' => 'app',
            'username' => 'app',
            // Зашифрованный пароль 'app_dev_password' (генерируется через php bin/config encrypt)
            'password' => 'enc:v1:ahR3zqrZq2sVPeWn9b5MQ4FEIt1Y8F2z4TXZDu8HT1vGpxtaAPL+d5rVAiE=',
        ],
    ],

    'production' => [
        'debug' => false,
        'db' => [
            'host' => 'prod-postgres.internal',
            'database' => 'trello_prod',
            'username' => 'trello_prod_user',
            // Зашифрованный пароль для продакшена
            'password' => 'enc:v1:x9K1m4P7v0L2q5Z8t1B4dA==',
        ],
    ],

    'test' => [
        'debug' => true,
        'db' => [
            'host' => 'postgres',
            'database' => 'app_test',
            'username' => 'app',
            'password' => 'test_password',
        ],
    ],
];

<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;
use Illuminate\Database\Capsule\Manager as Capsule;

Database::init();

echo "Starting migration..." . PHP_EOL;

if (!Capsule::schema()->hasTable('demo')) {
    Capsule::schema()->create('demo', function ($table) {
        $table->increments('id');
        $table->timestampTz('created_at')->useCurrent();
        $table->text('note');
    });
    echo "Table 'demo' successfully created via Eloquent Migrator." . PHP_EOL;
} else {
    echo "Table 'demo' already exists." . PHP_EOL;
}

// Тестовая миграция: создаем таблицу logs для демонстрации
if (!Capsule::schema()->hasTable('request_logs')) {
    Capsule::schema()->create('request_logs', function ($table) {
        $table->increments('id');
        $table->string('method', 10);
        $table->text('uri');
        $table->timestampTz('created_at')->useCurrent();
    });
    echo "Table 'request_logs' created successfully." . PHP_EOL;
} else {
    echo "Table 'request_logs' already exists." . PHP_EOL;
}

echo "All migrations finished." . PHP_EOL;
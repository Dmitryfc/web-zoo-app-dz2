<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

Database::init();

$schema = Capsule::schema();

echo "Starting migrations..." . PHP_EOL;

// 1. Таблица demo (из 1-го задания)
if (!$schema->hasTable('demo')) {
    $schema->create('demo', function (Blueprint $table) {
        $table->increments('id');
        $table->timestampTz('created_at')->useCurrent();
        $table->text('note');
    });
    echo "[OK] Table 'demo' created." . PHP_EOL;
} else {
    echo "[SKIP] Table 'demo' already exists." . PHP_EOL;
}

// 2. Таблица request_logs (логгер HTTP-запросов из 1-го задания)
if (!$schema->hasTable('request_logs')) {
    $schema->create('request_logs', function (Blueprint $table) {
        $table->increments('id');
        $table->string('method', 10);
        $table->text('uri');
        $table->timestampTz('created_at')->useCurrent();
    });
    echo "[OK] Table 'request_logs' created." . PHP_EOL;
} else {
    echo "[SKIP] Table 'request_logs' already exists." . PHP_EOL;
}

// 3. Таблица users (Лекция 5, слайд 6)
if (!$schema->hasTable('users')) {
    $schema->create('users', function (Blueprint $table) {
        $table->increments('user_id');
        $table->string('email', 255);
        $table->string('password_hash', 255);
        $table->string('name', 255);
        $table->timestamp('created_datetime')->useCurrent();
        $table->timestamp('updated_datetime')->nullable();

        $table->unique('email', 'idx_email');
    });
    echo "[OK] Table 'users' created with unique index 'idx_email'." . PHP_EOL;
} else {
    echo "[SKIP] Table 'users' already exists." . PHP_EOL;
}

// 4. Таблица boards (Лекция 5, слайд 7)
if (!$schema->hasTable('boards')) {
    $schema->create('boards', function (Blueprint $table) {
        $table->increments('board_id');
        $table->string('title', 255);
        $table->integer('owner_user_id');
        $table->boolean('is_public')->default(false);
        $table->timestamp('created_datetime')->useCurrent();
        $table->timestamp('updated_datetime')->nullable();

        $table->foreign('owner_user_id', 'fk_boards_owner')->references('user_id')->on('users')->onDelete('cascade');
        $table->index('owner_user_id', 'idx_boards_owner');
    });
    echo "[OK] Table 'boards' created with owner relation." . PHP_EOL;
} else {
    echo "[SKIP] Table 'boards' already exists." . PHP_EOL;
}

// 5. Таблица board_columns (Лекция 5, слайд 8)
if (!$schema->hasTable('board_columns')) {
    $schema->create('board_columns', function (Blueprint $table) {
        $table->increments('column_id');
        $table->integer('board_id');
        $table->smallInteger('ordinal_position');
        $table->string('title', 255);
        $table->string('color', 6)->nullable();

        $table->foreign('board_id', 'fk_columns_board')->references('board_id')->on('boards')->onDelete('cascade');
        $table->index('board_id', 'idx_board_id');
    });
    echo "[OK] Table 'board_columns' created with index 'idx_board_id'." . PHP_EOL;
} else {
    echo "[SKIP] Table 'board_columns' already exists." . PHP_EOL;
}

// 6. Таблица cards (Лекция 5, слайд 9)
if (!$schema->hasTable('cards')) {
    $schema->create('cards', function (Blueprint $table) {
        $table->increments('card_id');
        $table->integer('board_id');
        $table->integer('column_id');
        $table->smallInteger('sort_index');
        $table->string('title', 255);
        $table->text('content')->nullable();
        $table->string('color', 6)->nullable();
        $table->boolean('is_archived')->default(false);
        $table->timestamp('created_datetime')->useCurrent();
        $table->timestamp('updated_datetime')->nullable();

        $table->foreign('board_id', 'fk_cards_board')->references('board_id')->on('boards')->onDelete('cascade');
        $table->foreign('column_id', 'fk_cards_column')->references('column_id')->on('board_columns')->onDelete('cascade');
    });

    try {
        Capsule::statement('CREATE INDEX IF NOT EXISTS idx_active_board_id ON cards (board_id) WHERE is_archived = false;');
    } catch (\Throwable) {
        // Fallback
    }

    echo "[OK] Table 'cards' created with partial index 'idx_active_board_id'." . PHP_EOL;
} else {
    echo "[SKIP] Table 'cards' already exists." . PHP_EOL;
}

echo "All migrations completed successfully." . PHP_EOL;
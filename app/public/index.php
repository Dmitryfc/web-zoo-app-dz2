<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Config;
use App\Database;
use App\Logger;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Card;
use App\Models\Demo;
use App\Models\User;

Database::init();
$logger = Logger::getInstance();

$config = null;
try {
    $config = Config::load();
} catch (\Throwable $e) {
    //
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$requestBody = file_get_contents('php://input');

// Обработка отправки формы демо-записи через Eloquent
if ($method === 'POST' && isset($_POST['note'])) {
    $note = trim((string)$_POST['note']);
    if ($note !== '') {
        Demo::create(['note' => $note]);
    }
    header('Location: /');
    exit;
}

// Получение данных
$demoRows = [];
$counts = ['users' => 0, 'boards' => 0, 'columns' => 0, 'cards' => 0];

try {
    $demoRows = Demo::orderBy('id', 'asc')->get();
} catch (\Throwable) {}

try {
    $counts['users'] = User::count();
    $counts['boards'] = Board::count();
    $counts['columns'] = BoardColumn::count();
    $counts['cards'] = Card::count();
} catch (\Throwable) {}

ob_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Web Trello App — Лекция 5: Конфиги и схемы БД</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 900px; margin: 30px auto; padding: 0 20px; color: #333; line-height: 1.5; }
        h1, h2, h3 { color: #1a202c; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 13px; font-weight: bold; background: #e2e8f0; color: #4a5568; }
        .badge-env { background: #c6f6d5; color: #22543d; }
        .card-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 20px; }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 12px; }
        .stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; text-align: center; }
        .stat-num { font-size: 24px; font-weight: bold; color: #2b6cb0; }
        table { border-collapse: collapse; width: 100%; margin-top: 12px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px 12px; text-align: left; }
        th { background: #edf2f7; }
        form { margin-top: 16px; display: flex; gap: 8px; }
        input[type="text"] { flex: 1; padding: 8px 12px; border: 1px solid #cbd5e0; border-radius: 4px; }
        button { padding: 8px 16px; background: #3182ce; color: #fff; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; }
        button:hover { background: #2b6cb0; }
        code { background: #edf2f7; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
    </style>
</head>
<body>
    <h1>Trello Web App <span class="badge badge-env">ENV: <?= htmlspecialchars($config?->env() ?? 'unknown') ?></span></h1>
    <p>Архитектурный фундамент: <strong>Статический конфиг с шифрованием параметров</strong> + <strong>Схема БД Trello на 4 таблицы</strong>.</p>

    <div class="card-box">
        <h3>Параметры конфигурации (Config)</h3>
        <ul>
            <li><strong>Имя приложения:</strong> <?= htmlspecialchars((string)($config?->get('app.name') ?? '—')) ?></li>
            <li><strong>Версия:</strong> <?= htmlspecialchars((string)($config?->get('app.version') ?? '—')) ?></li>
            <li><strong>БД Хост / База:</strong> <code><?= htmlspecialchars((string)($config?->get('db.host') ?? '—')) ?>:<?= htmlspecialchars((string)($config?->get('db.port') ?? '5432')) ?> / <?= htmlspecialchars((string)($config?->get('db.database') ?? '—')) ?></code></li>
            <li><strong>Шифрование пароля БД:</strong> 
                <?php if ($config?->isEncrypted('db.password')): ?>
                    <span class="badge" style="background:#feebc8; color:#744210;">Шифрование активно (AES-256-GCM)</span>
                <?php else: ?>
                    <span class="badge">Открытый текст</span>
                <?php endif; ?>
            </li>
        </ul>
    </div>

    <div class="card-box">
        <h3>Схема приложения (4 основные таблицы из Лекции 5)</h3>
        <div class="grid">
            <div class="stat-card">
                <div class="stat-num"><?= $counts['users'] ?></div>
                <div><code>users</code></div>
            </div>
            <div class="stat-card">
                <div class="stat-num"><?= $counts['boards'] ?></div>
                <div><code>boards</code></div>
            </div>
            <div class="stat-card">
                <div class="stat-num"><?= $counts['columns'] ?></div>
                <div><code>board_columns</code></div>
            </div>
            <div class="stat-card">
                <div class="stat-num"><?= $counts['cards'] ?></div>
                <div><code>cards</code></div>
            </div>
        </div>
    </div>

    <div class="card-box">
        <h3>Таблица demo (Eloquent ORM)</h3>
        <table>
            <tr>
                <th style="width: 80px;">id</th>
                <th>note</th>
            </tr>
            <?php if (empty($demoRows) || count($demoRows) === 0): ?>
            <tr><td colspan="2" style="text-align: center; color: #a0aec0;">Записей пока нет</td></tr>
            <?php else: ?>
                <?php foreach ($demoRows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$row->id) ?></td>
                    <td><?= htmlspecialchars((string)$row->note) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>

        <form method="post" action="/">
            <input type="text" name="note" placeholder="Введите заметку для проверки Eloquent" required>
            <button type="submit">Добавить запись</button>
        </form>
    </div>
</body>
</html>
<?php
$responseBody = ob_get_flush();

$logger->info('HTTP Request processed', [
    'method'        => $method,
    'uri'           => $uri,
    'request_body'  => $requestBody ?: $_POST,
    'response_body' => $responseBody,
]);
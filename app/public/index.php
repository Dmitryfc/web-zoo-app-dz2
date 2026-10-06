<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\Logger;
use App\Models\Demo;

Database::init();
$logger = Logger::getInstance();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$requestBody = file_get_contents('php://input');

// Обработка отправки формы через Eloquent
if ($method === 'POST' && isset($_POST['note'])) {
    $note = trim((string)$_POST['note']);
    if ($note !== '') {
        Demo::create(['note' => $note]);
    }
    header('Location: /');
    exit;
}

// Получение списка записей через Eloquent ORM
$rows = Demo::orderBy('id', 'asc')->get();

// Начинаем перехват ответа для логирования тела
ob_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>nginx + php-fpm + PostgreSQL (Eloquent & Monolog)</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 40px auto; padding: 0 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px 12px; text-align: left; }
        th { background: #f4f4f4; }
        form { margin-top: 20px; display: flex; gap: 8px; }
        input[type="text"] { flex: 1; padding: 8px; }
        button { padding: 8px 16px; background: #0066cc; color: #fff; border: none; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>
    <h2>nginx + php-fpm + PostgreSQL (Eloquent ORM)</h2>
    <p>Данные получены через модель <code>App\Models\Demo::all()</code>.</p>

    <h3>Таблица demo</h3>
    <table>
        <tr>
            <th>id</th>
            <th>note</th>
        </tr>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= htmlspecialchars((string)$row->id) ?></td>
            <td><?= htmlspecialchars((string)$row->note) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <form method="post" action="/">
        <input type="text" name="note" placeholder="Новая запись" required autofocus>
        <button type="submit">Добавить</button>
    </form>
</body>
</html>
<?php
$responseBody = ob_get_flush();

// Логируем входящий HTTP-запрос и тело ответа с помощью Monolog
$logger->info('HTTP Request processed', [
    'method'        => $method,
    'uri'           => $uri,
    'request_body'  => $requestBody ?: $_POST,
    'response_body' => $responseBody,
]);
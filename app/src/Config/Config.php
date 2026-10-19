<?php

declare(strict_types=1);

namespace App\Config;

use App\Security\Encryptor;
use InvalidArgumentException;
use RuntimeException;

/**
 * Класс статического конфигуратора приложения по окружениям.
 *
 * Файл конфигурации (по умолчанию config/params.php) возвращает массив:
 * [
 *     'common'     => [...], // общие настройки
 *     'local'      => [...], // настройки локальной разработки
 *     'production' => [...], // настройки продакшена
 *     'test'       => [...], // настройки тестов
 * ]
 *
 * Итоговые параметры формируются объединением: common + [окружение].
 * Зашифрованные значения вида "enc:v1:..." автоматически расшифровываются
 * при вызове get().
 */
final class Config
{
    public const COMMON = 'common';

    /**
     * @param string $env Текущее окружение (local, production, ...)
     * @param array<string, mixed> $params Параметры (common + env) в исходном виде
     * @param list<string> $environments Список доступных окружений
     * @param Encryptor|null $encryptor Объект шифратора
     */
    public function __construct(
        private readonly string $env,
        private readonly array $params,
        private readonly array $environments,
        private readonly ?Encryptor $encryptor = null,
    ) {
    }

    /**
     * Загружает конфигурацию из файла для указанного окружения.
     */
    public static function load(
        ?string $env = null,
        ?string $filePath = null,
        ?Encryptor $encryptor = null,
    ): self {
        $filePath ??= dirname(__DIR__, 2) . '/config/params.php';

        if (!is_file($filePath)) {
            throw new RuntimeException(sprintf('Configuration file "%s" not found.', $filePath));
        }

        $allConfig = (static function () use ($filePath) {
            return require $filePath;
        })();

        if (!is_array($allConfig)) {
            throw new RuntimeException(sprintf('Config file "%s" must return an array.', $filePath));
        }

        $environments = array_values(array_filter(
            array_keys($allConfig),
            static fn ($key) => $key !== self::COMMON
        ));

        $env ??= getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'local');

        if (!in_array($env, $environments, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown environment "%s". Available environments: %s',
                $env,
                implode(', ', $environments)
            ));
        }

        $common = $allConfig[self::COMMON] ?? [];
        $envConfig = $allConfig[$env] ?? [];

        if (!is_array($common) || !is_array($envConfig)) {
            throw new RuntimeException('Environment configuration sections must be arrays.');
        }

        $merged = array_replace_recursive($common, $envConfig);

        if ($encryptor === null) {
            try {
                $encryptor = Encryptor::fromEnv();
            } catch (\Throwable) {
                // Если ключ не задан в окружении, объект шифратора останется null.
                // Ошибка возникнет только при попытке расшифровать значение.
                $encryptor = null;
            }
        }

        return new self($env, $merged, array_map('strval', $environments), $encryptor);
    }

    public function env(): string
    {
        return $this->env;
    }

    /**
     * @return list<string>
     */
    public function environments(): array
    {
        return $this->environments;
    }

    public function hasEncryptor(): bool
    {
        return $this->encryptor !== null;
    }

    /**
     * Получить параметр по ключу с точечной нотацией (например "db.host", "app.name").
     * Если значение зашифровано, оно автоматически расшифровывается.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->find($key, $found);

        if (!$found) {
            return $default;
        }

        return $this->reveal($value, $key);
    }

    /**
     * Проверяет наличие ключа.
     */
    public function has(string $key): bool
    {
        $this->find($key, $found);

        return $found;
    }

    /**
     * Получить сырое значение без расшифровки (например, зашифрованную строку enc:v1:...).
     */
    public function raw(string $key, mixed $default = null): mixed
    {
        $value = $this->find($key, $found);

        return $found ? $value : $default;
    }

    /**
     * Возвращает все параметры текущего окружения как есть (сырой массив).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->params;
    }

    /**
     * Возвращает все параметры с расшифровкой всех зашифрованных полей.
     *
     * @return array<string, mixed>
     */
    public function allDecrypted(): array
    {
        return $this->reveal($this->params, '');
    }

    /**
     * Проверяет, является ли значение по ключу зашифрованным.
     */
    public function isEncrypted(string $key): bool
    {
        $val = $this->raw($key);

        return $this->containsEncrypted($val);
    }

    /**
     * Поиск значения по ключу с точками.
     */
    private function find(string $key, ?bool &$found): mixed
    {
        $current = $this->params;
        $segments = explode('.', $key);

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                $found = false;
                return null;
            }
            $current = $current[$segment];
        }

        $found = true;
        return $current;
    }

    /**
     * Рекурсивная расшифровка значений.
     */
    private function reveal(mixed $value, string $path): mixed
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $k => $v) {
                $subPath = $path === '' ? (string) $k : $path . '.' . $k;
                $result[$k] = $this->reveal($v, $subPath);
            }
            return $result;
        }

        if (!Encryptor::isEncrypted($value)) {
            return $value;
        }

        if ($this->encryptor === null) {
            throw new RuntimeException(sprintf(
                'Parameter "%s" is encrypted, but encryption key (APP_CONFIG_KEY) is not set.',
                $path
            ));
        }

        return $this->encryptor->decrypt($value);
    }

    private function containsEncrypted(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->containsEncrypted($item)) {
                    return true;
                }
            }
            return false;
        }

        return Encryptor::isEncrypted($value);
    }
}

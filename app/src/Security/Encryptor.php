<?php

declare(strict_types=1);

namespace App\Security;

use InvalidArgumentException;
use RuntimeException;

/**
 * Класс для симметричного шифрования и дешифрования конфиденциальных параметров.
 *
 * Алгоритм: AES-256-GCM (аутентифицированное шифрование).
 * Формат зашифрованного значения: enc:v1:<base64(iv[12] . tag[16] . ciphertext)>
 */
final class Encryptor
{
    public const PREFIX = 'enc:v1:';
    public const CIPHER = 'aes-256-gcm';
    public const KEY_BYTES = 32;
    public const IV_BYTES = 12;
    public const TAG_BYTES = 16;

    public function __construct(private readonly string $key)
    {
        if (!extension_loaded('openssl')) {
            throw new RuntimeException('PHP extension "openssl" is required.');
        }

        if (strlen($this->key) !== self::KEY_BYTES) {
            throw new InvalidArgumentException(sprintf(
                'Encryption key must be exactly %d bytes, got %d.',
                self::KEY_BYTES,
                strlen($this->key)
            ));
        }
    }

    /**
     * Создает экземпляр Encryptor из строки вида "base64:<base64-encoded key>".
     */
    public static function fromString(string $keyString): self
    {
        $trimmed = trim($keyString);
        if (str_starts_with($trimmed, 'base64:')) {
            $trimmed = substr($trimmed, 7);
        }

        $rawKey = base64_decode($trimmed, true);
        if ($rawKey === false || strlen($rawKey) !== self::KEY_BYTES) {
            throw new InvalidArgumentException(sprintf(
                'Invalid base64 key: decoded key must be exactly %d bytes.',
                self::KEY_BYTES
            ));
        }

        return new self($rawKey);
    }

    /**
     * Создает экземпляр Encryptor из переменной окружения (по умолчанию APP_CONFIG_KEY).
     */
    public static function fromEnv(string $envName = 'APP_CONFIG_KEY'): self
    {
        $val = getenv($envName) ?: ($_ENV[$envName] ?? null);
        if (empty($val) || !is_string($val)) {
            throw new RuntimeException(sprintf(
                'Environment variable "%s" is not set. Generate one using: php bin/config key:generate',
                $envName
            ));
        }

        return self::fromString($val);
    }

    /**
     * Генерирует криптографически стойкий случайный 32-байтный ключ в формате base64.
     */
    public static function generateKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(self::KEY_BYTES));
    }

    /**
     * Проверяет, зашифровано ли переданное значение.
     */
    public static function isEncrypted(mixed $value): bool
    {
        return is_string($value) && str_starts_with($value, self::PREFIX);
    }

    /**
     * Шифрует строку в формат "enc:v1:<base64...>".
     */
    public function encrypt(string $plainText): string
    {
        $iv = random_bytes(self::IV_BYTES);
        $tag = '';

        $cipherText = openssl_encrypt(
            $plainText,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_BYTES
        );

        if ($cipherText === false) {
            throw new RuntimeException('openssl_encrypt failed: ' . (openssl_error_string() ?: 'unknown error'));
        }

        return self::PREFIX . base64_encode($iv . $tag . $cipherText);
    }

    /**
     * Расшифровывает строку формата "enc:v1:<base64...>".
     */
    public function decrypt(string $payload): string
    {
        if (!self::isEncrypted($payload)) {
            throw new InvalidArgumentException(sprintf(
                'Value must start with prefix "%s".',
                self::PREFIX
            ));
        }

        $raw = base64_decode(substr($payload, strlen(self::PREFIX)), true);
        if ($raw === false || strlen($raw) < self::IV_BYTES + self::TAG_BYTES) {
            throw new RuntimeException('Malformed encrypted payload.');
        }

        $iv = substr($raw, 0, self::IV_BYTES);
        $tag = substr($raw, self::IV_BYTES, self::TAG_BYTES);
        $cipherText = substr($raw, self::IV_BYTES + self::TAG_BYTES);

        $plainText = openssl_decrypt(
            $cipherText,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plainText === false) {
            throw new RuntimeException('Decryption failed: invalid key or corrupted ciphertext.');
        }

        return $plainText;
    }
}

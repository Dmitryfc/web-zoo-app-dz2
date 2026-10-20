# web-zoo-app/dz2 — Архитектура Backend

Стек: **Nginx + PHP-FPM 8.5 + PostgreSQL + Eloquent ORM + Monolog + OpenSSL AES-256-GCM**.

Проект реализует требования:
1. **Статический конфигуратор по окружениям** (`common`, `local`, `production`, `test`).
2. **Шифрование чувствительных параметров** (паролей, токенов) с использованием расширения PHP `openssl` (AES-256-GCM) и префиксом `enc:v1:`.
3. **Консольная утилита CLI (`bin/config`)** для генерации ключа шифрования, шифрования и дешифрования.
4. **Схема базы данных на 4 основные таблицы** (`users`, `boards`, `board_columns`, `cards`) с индексами и связями.
5. Безопасность: пароли и мастер-ключи **не коммитятся в git** (`.env` в `.gitignore`).

---

## Быстрый старт

```bash
# 1. Инициализация .env (если ещё не создан)
make init

# 2. Сборка и запуск контейнеров
make build
make up

# 3. Установка зависимостей через Composer
make composer

# 4. Генерация ключа шифрования конфига (запишите его в .env -> APP_CONFIG_KEY=...)
make keygen

# 5. Применение миграций БД (создание 4 таблиц и индексов)
make migrate
```

После этого:
- **Веб-интерфейс:** [http://127.0.0.1:8080/](http://127.0.0.1:8080/)
- **Health-check:** [http://127.0.0.1:8080/health.php](http://127.0.0.1:8080/health.php)
- **PostgreSQL:** `127.0.0.1:5432` (`make psql`)

---

## 1. Конфигурация и окружения (`App\Config\Config`)

Файл `app/config/params.php` возвращает ассоциативный массив параметров по секциям:
- `common` — общие параметры приложения (имя, версия, порт БД и т.д.);
- `local` — параметры для локальной разработки (`APP_ENV=local`);
- `production` — параметры для продакшена (`APP_ENV=production`);
- `test` — параметры для тестов (`APP_ENV=test`).

Класс `App\Config\Config`:
- Читает активное окружение из переменной `APP_ENV`;
- Объединяет секцию `common` с выбранным окружением (`array_replace_recursive`);
- Поддерживает получение через точечную нотацию: `$config->get('db.host')`;
- **Автоматически расшифровывает зашифрованные параметры** при обращении через `get()` с помощью `Encryptor`.

---

## 2. Шифрование параметров (`App\Security\Encryptor`)

- **Алгоритм:** `aes-256-gcm` (аутентифицированное симметричное шифрование).
- **Формат шифртекста:** `enc:v1:<base64(iv[12] . tag[16] . ciphertext)>`.
- **Мастер-ключ:** 32 криптостойких байта в формате `base64:...`, передается через переменную окружения `APP_CONFIG_KEY`.

### Консольные команды (`bin/config`):

```bash
# Сгенерировать новый мастер-ключ:
docker compose exec php php bin/config key:generate

# Зашифровать значение (например пароль БД):
docker compose exec php php bin/config encrypt "my-super-secret-password"

# Расшифровать значение:
docker compose exec php php bin/config decrypt "enc:v1:..."

# Показать текущую конфигурацию (скрытые значения):
docker compose exec php php bin/config show

# Показать конфигурацию с расшифровкой секретов:
docker compose exec php php bin/config show --decrypt
```

---

## 3. Схема базы данных (Миграции и Eloquent)

Миграции запускаются командой `make migrate` (`php bin/migrate`). Создаются 4 основные таблицы:

1. **`users`**
   - `user_id` SERIAL PRIMARY KEY
   - `email` VARCHAR(255) NOT NULL
   - `password_hash` VARCHAR(255) NOT NULL
   - `name` VARCHAR(255) NOT NULL
   - `created_datetime` TIMESTAMP NOT NULL
   - `updated_datetime` TIMESTAMP NULL
   - Индекс: `idx_email` (UNIQUE)

2. **`boards`**
   - `board_id` SERIAL PRIMARY KEY
   - `title` VARCHAR(255) NOT NULL
   - `owner_user_id` INT NOT NULL (связь с `users.user_id`)
   - `is_public` BOOLEAN NOT NULL DEFAULT false
   - `created_datetime` TIMESTAMP NOT NULL
   - `updated_datetime` TIMESTAMP NULL

3. **`board_columns`**
   - `column_id` SERIAL PRIMARY KEY
   - `board_id` INT NOT NULL (связь с `boards.board_id`)
   - `ordinal_position` SMALLINT NOT NULL
   - `title` VARCHAR(255) NOT NULL
   - `color` VARCHAR(6) NULL
   - Индекс: `idx_board_id`

4. **`cards`**
   - `card_id` SERIAL PRIMARY KEY
   - `board_id` INT NOT NULL
   - `column_id` INT NOT NULL
   - `sort_index` SMALLINT NOT NULL
   - `title` VARCHAR(255) NOT NULL
   - `content` TEXT NULL
   - `color` VARCHAR(6) NULL
   - `is_archived` BOOLEAN NOT NULL DEFAULT false
   - `created_datetime` TIMESTAMP NOT NULL
   - `updated_datetime` TIMESTAMP NULL
   - Частичный индекс: `idx_active_board_id ON cards (board_id) WHERE is_archived = false;`

Eloquent-модели находятся в каталоге `app/src/Models/`:
- `User` (`app/src/Models/User.php`)
- `Board` (`app/src/Models/Board.php`)
- `BoardColumn` (`app/src/Models/BoardColumn.php`)
- `Card` (`app/src/Models/Card.php`)

---

## Структура каталогов

```
compose.yaml, Makefile, .env.example, .gitignore
docker/
├── nginx/
├── php/ (Dockerfile с ext-openssl, pdo_pgsql, intl, zip)
└── postgres/
app/
├── bin/
│   ├── config                  # CLI утилита шифрования и просмотра конфигов
│   └── migrate                 # CLI раннер миграций базы данных
├── config/
│   └── params.php              # Статический конфиг (common, local, production, test)
├── database/
│   └── migrate.php             # Определение схемы 4 таблиц и индексов
├── public/
│   ├── index.php               # Веб-страница с информацией о Config и таблицах
│   └── health.php              # Healthcheck эндпоинт
├── src/
│   ├── Config/
│   │   └── Config.php          # Класс конфигуратора
│   ├── Security/
│   │   └── Encryptor.php       # Класс шифрования AES-256-GCM
│   ├── Models/
│   │   ├── User.php
│   │   ├── Board.php
│   │   ├── BoardColumn.php
│   │   ├── Card.php
│   │   └── Demo.php
│   ├── Database.php            # Подключение Eloquent с параметрами из Config
│   └── Logger.php              # Monolog логгер HTTP-запросов
└── composer.json
```

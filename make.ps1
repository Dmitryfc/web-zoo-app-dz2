param(
    [Parameter(Position=0)]
    [string]$Target = "help",
    [Parameter(ValueFromRemainingArguments=$true)]
    [string[]]$ArgsList
)

switch ($Target) {
    "up" {
        docker compose up -d
    }
    "down" {
        docker compose down
    }
    "build" {
        docker compose build
    }
    "ps" {
        docker compose ps
    }
    "logs" {
        docker compose logs -f
    }
    "clean" {
        docker compose down -v
    }
    "composer" {
        docker compose exec -u www-data php composer install
    }
    "migrate" {
        docker compose exec -u www-data php php bin/migrate
    }
    "keygen" {
        docker compose exec php php bin/config key:generate
    }
    "config" {
        docker compose exec php php bin/config show
    }
    "config-decrypted" {
        docker compose exec php php bin/config show --decrypt
    }
    "sh" {
        docker compose exec php sh
    }
    "psql" {
        docker compose exec postgres psql -U app -d app
    }
    default {
        Write-Host "Использование в PowerShell: .\make <команда>" -ForegroundColor Cyan
        Write-Host "  .\make up       - поднять контейнеры в фоне"
        Write-Host "  .\make down     - остановить контейнеры"
        Write-Host "  .\make build    - пересобрать образы"
        Write-Host "  .\make ps       - статус контейнеров"
        Write-Host "  .\make logs     - логи всех сервисов"
        Write-Host "  .\make composer - установить зависимости composer"
        Write-Host "  .\make keygen   - сгенерировать ключ шифрования (APP_CONFIG_KEY)"
        Write-Host "  .\make migrate  - применить миграции базы данных"
        Write-Host "  .\make config   - показать параметры конфигурации"
        Write-Host "  .\make psql     - консоль PostgreSQL"
    }
}

# Сквош-лига

Веб-приложение таблицы сквош-лиги: 3 команды × 2 дивизиона (красный / жёлтый).

```
new-league/
  front/   # Vue 3 + Pinia + TypeScript (Vite)
  back/    # PHP 8 + MySQL API
```

## Быстрый старт (Docker, локально)

```bash
docker compose up --build
```

| Сервис | URL |
|--------|-----|
| Приложение (UI) | http://localhost:3000 |
| Форма ввода результата | http://localhost:3000/entry |
| API напрямую | http://localhost:8080/api/standings |
| MySQL | `localhost:3306` (user `root`, пароль пустой) |

Остановка: `docker compose down`  
Сброс БД: `docker compose down -v`

## Deploy на сервер (Docker + nginx)

Прод-стек: [`docker-compose.prod.yml`](docker-compose.prod.yml) + [`.env.example`](.env.example) + [`deploy/nginx.conf`](deploy/nginx.conf).

Автодеплой: пуш в `main` → Actions собирает образы в GHCR → SSH на сервер → `pull` + `up -d` (без сборки на сервере).  
Secrets и настройка: [`deploy/README.md`](deploy/README.md) (нужен `GHCR_TOKEN` с `read:packages`, если пакеты private).

Кратко вручную на сервере (образы уже в GHCR):

```bash
cp .env.example .env          # задать пароли MySQL
docker compose -f docker-compose.prod.yml --env-file .env pull
docker compose -f docker-compose.prod.yml --env-file .env up -d
sudo cp /opt/new-league/deploy/nginx.conf /etc/nginx/sites-available/squash-league
```

В проде наружу слушает только `127.0.0.1:3000` (front). MySQL и API закрыты; host nginx проксирует домен на front.

## Локальный запуск без Docker

Требования: Node.js 20+, PHP 8.1+ (`pdo_mysql`), MySQL 8+.

### База данных

```bash
mysql -u root -p --default-character-set=utf8mb4 < back/schema.sql
```

При необходимости поправьте доступ в [`back/config/database.php`](back/config/database.php) или через переменные окружения:

- `DB_HOST` (по умолчанию `127.0.0.1`)
- `DB_PORT` (`3306`)
- `DB_NAME` (`squash_league`)
- `DB_USER` (`root`)
- `DB_PASSWORD` (пустая)

Сидер создаёт 3 команды и по 6 игроков в каждом дивизионе (placeholder-имена).

## Константы очков

Все очки и лимиты матчей — в [`back/config/scoring.php`](back/config/scoring.php):

- `same_division` — матч внутри одного дивизиона
- `higher_vs_lower` — красный побеждает жёлтого
- `lower_vs_higher` — жёлтый побеждает красного
- `max_matches_same_division` (2)
- `max_matches_cross_division` (1)

## Запуск backend

```bash
cd back
php -S 127.0.0.1:8080 router.php
```

API доступен по префиксу `/api/...`, например:

- `GET  /api/standings`
- `GET  /api/players`
- `POST /api/players`
- `PUT  /api/players/{id}`
- `DELETE /api/players/{id}`
- `GET  /api/opponents?player_id=1`
- `POST /api/matches`
- `GET  /api/matches`

### Пример: добавить игрока

```bash
curl -X POST http://127.0.0.1:8080/api/players \
  -H 'Content-Type: application/json' \
  -d '{"team_id":1,"division":"red","name":"Иванов"}'
```

### Пример: заменить / переименовать

```bash
curl -X PUT http://127.0.0.1:8080/api/players/1 \
  -H 'Content-Type: application/json' \
  -d '{"name":"Петров"}'
```

### Пример: удалить (soft-delete)

```bash
curl -X DELETE http://127.0.0.1:8080/api/players/1
```

### Пример: сохранить матч

```bash
curl -X POST http://127.0.0.1:8080/api/matches \
  -H 'Content-Type: application/json' \
  -d '{"player1_id":1,"player2_id":13,"winner_id":1,"score":"3-1"}'
```

Очки считаются сразу на бэкенде и пишутся в таблицу `matches`.

## Запуск frontend

```bash
cd front
npm install
npm run dev
```

Откройте http://localhost:5173/

Vite проксирует `/api` на `http://127.0.0.1:8080`.

### Экраны

| URL | Назначение |
|-----|------------|
| `/` | Таблица очков и сыгранных матчей по 3 командам |
| `/entry` | Форма ввода результата (только по прямой ссылке, в меню не выводится) |

В форме `/entry` после выбора первого игрока подгружаются только соперники из других команд, с которыми ещё не сыграны все положенные матчи.

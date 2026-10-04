#!/bin/bash
# Apply / reset DB schema + seed players.
# Usage on server:
#   cd /opt/new-league
#   ./deploy/apply-schema.sh
#
# WARNING: drops matches/players/teams and re-seeds.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

if [ ! -f .env ]; then
  echo "ERROR: .env not found in $ROOT_DIR"
  exit 1
fi

set -a
# shellcheck disable=SC1091
source .env
set +a

COMPOSE=(docker compose -f docker-compose.prod.yml --env-file .env)

echo "Applying schema to database '${DB_NAME:-squash_league}'..."
"${COMPOSE[@]}" exec -T db \
  mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" --default-character-set=utf8mb4 \
  < back/schema.sql

echo "Tables:"
"${COMPOSE[@]}" exec -T db \
  mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" --default-character-set=utf8mb4 \
  -e "USE ${DB_NAME:-squash_league}; SHOW TABLES; SELECT COUNT(*) AS players FROM players;"

echo "Done. Check: curl -s http://127.0.0.1:3000/api/standings | head"

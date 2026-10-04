#!/bin/bash
set -euo pipefail

echo "[init-db] Applying /schema.sql ..."
mysql -uroot --default-character-set=utf8mb4 < /schema.sql
mysql -uroot --default-character-set=utf8mb4 -e "USE squash_league; SHOW TABLES;"
echo "[init-db] Schema applied."

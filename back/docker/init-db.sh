#!/bin/bash
set -euo pipefail

mysql -uroot --default-character-set=utf8mb4 < /schema.sql

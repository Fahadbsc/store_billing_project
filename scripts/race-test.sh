#!/usr/bin/env bash
# Real concurrency check. Needs MySQL/Postgres (SQLite serialises writes anyway)
# and a running server:  php artisan serve --port=8000
# Usage: scripts/race-test.sh <product_id>   (set that product's stock to 1 first)
set -euo pipefail
ID="${1:?product id}"
BASE="${BASE:-http://127.0.0.1:8000}"
body() { echo "{\"customer\":{\"email\":\"race$1@example.com\",\"name\":\"Race $1\"},\"items\":[{\"product_id\":$ID,\"quantity\":1}]}"; }
export -f body; export ID BASE
seq 1 10 | xargs -P 10 -I{} bash -c \
  'curl -s -o /dev/null -w "request {}: HTTP %{http_code}\n" -X POST "$BASE/api/orders" -H "Content-Type: application/json" -H "Accept: application/json" -d "$(body {})"'
echo "Expect exactly one 201 and nine 422."

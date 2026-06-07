#!/usr/bin/env bash
# Shared helpers for curl integration tests. Targets the TEST database only.
set -uo pipefail
BASE="${BASE:-http://localhost:8000}"
DB_TEST_NAME="${DB_TEST_NAME:-employee_manager_test}"

eval "$(php -r '$c = is_file("includes/config.php") ? "includes/config.php" : "includes/config.example.php"; require $c; printf("DB_HOST=%s\nDB_USER=%s\nDB_PASS=%s\n", escapeshellarg(DB_HOST), escapeshellarg(DB_USER), escapeshellarg(DB_PASS));')"

JAR="/tmp/turnos_jar.txt"; CSRF=""; LAST=""; CODE=""

reset_db() {
  mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_TEST_NAME" < sql/schema.sql 2>/dev/null
  mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_TEST_NAME" < sql/seed.sql 2>/dev/null
  : > "$JAR"; CSRF=""
}

# seed_date <offset> -> fecha (YYYY-MM-DD) del lunes de la semana actual + offset días.
# Coincide con la lógica del seed dinámico (turnos 1..5 en offsets 0..4).
seed_date() {
  mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -N -e \
    "SELECT DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL $1 DAY)" 2>/dev/null
}

# login <correo> <password>
login() {
  : > "$JAR"
  local resp; resp="$(curl -s -c "$JAR" -X POST "$BASE/api/login" \
     -H 'Content-Type: application/json' -d "{\"correo\":\"$1\",\"password\":\"$2\"}")"
  CSRF="$(printf '%s' "$resp" | sed -n 's/.*"csrf":"\([^"]*\)".*/\1/p')"
}

# req <METHOD> <path> [json-body]
req() {
  local m="$1" p="$2" body="${3:-}"
  local out
  out="$(curl -s -b "$JAR" -c "$JAR" -w '\n%{http_code}' -X "$m" "$BASE$p" \
        -H 'Content-Type: application/json' -H "X-CSRF-Token: $CSRF" \
        ${body:+-d "$body"})"
  CODE="$(printf '%s' "$out" | tail -n1)"
  LAST="$(printf '%s' "$out" | sed '$d')"
}

assert_status() {
  if [ "$CODE" = "$1" ]; then echo "  ok status $1";
  else echo "  FAIL expected $1 got $CODE :: $LAST"; exit 1; fi
}
assert_contains() {
  if printf '%s' "$LAST" | grep -q "$1"; then echo "  ok contains '$1'";
  else echo "  FAIL '$1' not in :: $LAST"; exit 1; fi
}

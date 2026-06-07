#!/usr/bin/env bash
# Runner único: unit (funciones puras) + integración curl contra la BD de test.
# No toca la BD de desarrollo. Devuelve exit != 0 si algo falla.
set -uo pipefail
cd "$(dirname "$0")/.."

PORT="${PORT:-8000}"
DB_TEST_NAME="${DB_TEST_NAME:-employee_manager_test}"

echo "== Unit (funciones puras) =="
php tests/unit.php || { echo "UNIT FAILED"; exit 1; }

echo
echo "== Integración (curl, BD de test: $DB_TEST_NAME) =="
lsof -ti tcp:"$PORT" | xargs kill -9 2>/dev/null
DB_NAME="$DB_TEST_NAME" \
  php -S "localhost:$PORT" -t public public/router.php >/tmp/run-all-srv.log 2>&1 &
SRV=$!
for _ in $(seq 1 25); do curl -s -o /dev/null "http://localhost:$PORT/login" && break; sleep 0.2; done

RC=0
for t in tests/[0-9]*.sh; do
  echo "-- $(basename "$t") --"
  if bash "$t" >/tmp/run-all-t.log 2>&1; then
    echo "   PASS"
  else
    echo "   FAIL"; tail -5 /tmp/run-all-t.log; RC=1
  fi
done
kill "$SRV" 2>/dev/null

echo
[ $RC -eq 0 ] && echo "== TODO VERDE ==" || echo "== HAY FALLOS =="
exit $RC

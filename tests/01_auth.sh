#!/usr/bin/env bash
source "$(dirname "$0")/lib.sh"
reset_db

echo "T1 sin sesión -> 401"
req GET /api/employees; assert_status 401

echo "T2 login password mala -> 401 (sin csrf)"
login admin@turnos.local WRONGPASS
req GET /api/employees; assert_status 401

echo "T3 login válido -> csrf recibido"
login admin@turnos.local Admin1234
[ -n "$CSRF" ] && echo "  ok csrf recibido" || { echo "  FAIL sin csrf"; exit 1; }

echo "T4 usuario inactivo -> login rechazado"
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_TEST_NAME" -e "UPDATE empleados SET activo=0 WHERE correo='emp.ventas@turnos.local';" 2>/dev/null
login emp.ventas@turnos.local Empl1234
[ -z "$CSRF" ] && echo "  ok login rechazado" || { echo "  FAIL inactivo logueó"; exit 1; }

echo "T5 mutación sin CSRF -> 403"
login admin@turnos.local Admin1234
CSRF=""
req POST /api/employees '{}'; assert_status 403

echo "OK Phase 1"

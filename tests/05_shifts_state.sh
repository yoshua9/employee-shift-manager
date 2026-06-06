#!/usr/bin/env bash
source "$(dirname "$0")/lib.sh"
reset_db
login admin@turnos.local Admin1234

echo "programado -> confirmado (turno 1)"
req PUT /api/shifts/1 '{"estado":"confirmado"}'; assert_status 200; assert_contains '"estado":"confirmado"'

echo "editar campos de un confirmado -> 409"
req PUT /api/shifts/1 '{"hora_inicio":"08:00","hora_fin":"12:00"}'; assert_status 409

echo "campos + cambio de estado sobre confirmado -> 409 (corrección 3)"
req PUT /api/shifts/1 '{"estado":"ausente","hora_inicio":"08:00"}'; assert_status 409

echo "confirmado -> ausente"
req PUT /api/shifts/1 '{"estado":"ausente"}'; assert_status 200; assert_contains '"estado":"ausente"'

echo "asignar sustituto a ausente -> cubierto (turno 1 titular id3; sustituto id2 mismo dept Soporte)"
req PUT /api/shifts/1 '{"sustituto_id":2,"motivo_ausencia":"Permiso"}'; assert_status 200; assert_contains '"estado":"cubierto"'

echo "sustituto == ausente -> 422 (turno 3 ausente, titular id5)"
req PUT /api/shifts/3 '{"sustituto_id":5}'; assert_status 422

echo "sustituto inactivo -> 422"
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_TEST_NAME" -e "UPDATE empleados SET activo=0 WHERE id=4;" 2>/dev/null
req PUT /api/shifts/3 '{"sustituto_id":4}'; assert_status 422
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_TEST_NAME" -e "UPDATE empleados SET activo=1 WHERE id=4;" 2>/dev/null

echo "asignar sustituto sobre turno NO ausente -> 409 (turno 5 programado)"
req PUT /api/shifts/5 '{"sustituto_id":6}'; assert_status 409

echo "reabrir a programado: responsable -> 403 (corrección 1)"
login resp.soporte@turnos.local Resp1234
req PUT /api/shifts/1 '{"estado":"programado"}'; assert_status 403

echo "reabrir a programado: admin -> 200 y coherencia (turno 1 estaba cubierto)"
login admin@turnos.local Admin1234
req PUT /api/shifts/1 '{"estado":"programado"}'; assert_status 200; assert_contains '"estado":"programado"'
assert_contains '"sustituto_id":null'
assert_contains '"motivo_ausencia":null'

echo "DELETE programado -> 200 ; DELETE confirmado -> 409"
req DELETE /api/shifts/1; assert_status 200
req PUT /api/shifts/5 '{"estado":"confirmado"}'; assert_status 200
req DELETE /api/shifts/5; assert_status 409

echo "own-id exclusion: reabrir 5 y reeditar con sus MISMAS horas sin falso solape"
req PUT /api/shifts/5 '{"estado":"programado"}'; assert_status 200
req PUT /api/shifts/5 '{"hora_inicio":"08:00","hora_fin":"14:00","tipo":"manana"}'; assert_status 200

echo "motivo_ausencia oculto a empleado"
reset_db
login emp.oper@turnos.local Empl1234
req GET /api/shifts/4; assert_status 200
if printf '%s' "$LAST" | grep -q 'motivo_ausencia'; then echo "  FAIL motivo visible"; exit 1; else echo "  ok oculto"; fi

echo "regla 8: BORRAR empleado sustituto de un 'cubierto' devuelve el turno a 'ausente'"
reset_db
login admin@turnos.local Admin1234
req DELETE /api/employees/6; assert_status 200
req GET /api/shifts/4; assert_status 200; assert_contains '"estado":"ausente"'
assert_contains '"sustituto_id":null'

echo "regla 8 bis: DESACTIVAR sustituto también revierte"
reset_db
login admin@turnos.local Admin1234
req PUT /api/employees/6 '{"activo":false}'; assert_status 200
req GET /api/shifts/4; assert_status 200; assert_contains '"estado":"ausente"'

echo "OK Phase 5"

#!/usr/bin/env bash
source "$(dirname "$0")/lib.sh"
reset_db
login admin@turnos.local Admin1234

echo "crear válido -> 201"
req POST /api/shifts '{"empleado_id":3,"fecha":"2027-06-08","hora_inicio":"09:00","hora_fin":"13:00","tipo":"manana"}'
assert_status 201

echo "solapado (cualquier estado) -> 409"
req POST /api/shifts '{"empleado_id":3,"fecha":"2027-06-08","hora_inicio":"12:00","hora_fin":"14:00","tipo":"tarde"}'
assert_status 409

echo "adyacente exacto NO solapa -> 201"
req POST /api/shifts '{"empleado_id":3,"fecha":"2027-06-08","hora_inicio":"13:00","hora_fin":"15:00","tipo":"tarde"}'
assert_status 201

echo "sustituto ocupado: crear titular que solapa con un turno que ya cubre -> 409"
DCUB=$(seed_date 3)
req POST /api/shifts "{\"empleado_id\":6,\"fecha\":\"$DCUB\",\"hora_inicio\":\"22:30\",\"hora_fin\":\"23:30\",\"tipo\":\"noche\"}"
assert_status 409

echo "hora_fin<=hora_inicio -> 422"
req POST /api/shifts '{"empleado_id":3,"fecha":"2027-06-09","hora_inicio":"13:00","hora_fin":"09:00","tipo":"manana"}'
assert_status 422

echo "empleado inactivo -> 422"
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_TEST_NAME" -e "UPDATE empleados SET activo=0 WHERE id=3;" 2>/dev/null
req POST /api/shifts '{"empleado_id":3,"fecha":"2027-06-10","hora_inicio":"09:00","hora_fin":"13:00","tipo":"manana"}'
assert_status 422
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_TEST_NAME" -e "UPDATE empleados SET activo=1 WHERE id=3;" 2>/dev/null

echo "responsable crea para otro dept -> 403"
login resp.soporte@turnos.local Resp1234
req POST /api/shifts '{"empleado_id":5,"fecha":"2027-06-11","hora_inicio":"09:00","hora_fin":"13:00","tipo":"manana"}'
assert_status 403

echo "empleado crea -> 403"
login emp.soporte@turnos.local Empl1234
req POST /api/shifts '{"empleado_id":3,"fecha":"2027-06-12","hora_inicio":"09:00","hora_fin":"13:00","tipo":"manana"}'
assert_status 403

echo "OK Phase 4"

#!/usr/bin/env bash
source "$(dirname "$0")/lib.sh"
reset_db

echo "admin ve todos los departamentos"
login admin@turnos.local Admin1234
req GET /api/shifts; assert_status 200
assert_contains '"departamento":"Ventas"'
assert_contains '"departamento":"Operaciones"'

echo "responsable Ventas: solo Ventas"
login resp.ventas@turnos.local Resp1234
req GET /api/shifts; assert_status 200
if printf '%s' "$LAST" | grep -q '"departamento":"Operaciones"'; then echo "  FAIL ve otro dept"; exit 1; else echo "  ok aislado"; fi

echo "responsable no puede ampliar con ?department=Operaciones"
req GET '/api/shifts?department=Operaciones'; assert_status 200
if printf '%s' "$LAST" | grep -q '"departamento":"Operaciones"'; then echo "  FAIL amplió scope"; exit 1; else echo "  ok no amplía"; fi

echo "empleado: solo los suyos (titular o sustituto), sin motivo"
login emp.oper@turnos.local Empl1234
req GET /api/shifts; assert_status 200
assert_contains '"empleado_id":7'
if printf '%s' "$LAST" | grep -q 'motivo_ausencia'; then echo "  FAIL ve motivo"; exit 1; else echo "  ok sin motivo"; fi

echo "filtro status dentro del scope"
login admin@turnos.local Admin1234
req GET '/api/shifts?status=cubierto'; assert_status 200; assert_contains '"estado":"cubierto"'

echo "GET /shifts/{id} fuera de scope -> 404"
login resp.soporte@turnos.local Resp1234
req GET /api/shifts/3; assert_status 404

echo "OK Phase 3"

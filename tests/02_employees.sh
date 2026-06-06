#!/usr/bin/env bash
source "$(dirname "$0")/lib.sh"
reset_db

echo "admin lista todo"
login admin@turnos.local Admin1234
req GET /api/employees; assert_status 200; assert_contains 'resp.ventas@turnos.local'

echo "responsable Soporte: solo su departamento"
login resp.soporte@turnos.local Resp1234
req GET /api/employees; assert_status 200
assert_contains 'emp.soporte@turnos.local'
if printf '%s' "$LAST" | grep -q 'resp.ventas@turnos.local'; then echo "  FAIL ve otro dept"; exit 1; else echo "  ok aislado"; fi

echo "empleado: solo a sí mismo"
login emp.ventas@turnos.local Empl1234
req GET /api/employees; assert_status 200; assert_contains 'emp.ventas@turnos.local'
if printf '%s' "$LAST" | grep -q 'resp.ventas@turnos.local'; then echo "  FAIL ve a otro"; exit 1; else echo "  ok solo self"; fi

echo "GET /employees/{id} fuera de scope -> 404"
login resp.soporte@turnos.local Resp1234
req GET /api/employees/5; assert_status 404
login emp.ventas@turnos.local Empl1234
req GET /api/employees/4; assert_status 404

echo "admin GET cualquiera -> 200"
login admin@turnos.local Admin1234
req GET /api/employees/5; assert_status 200

echo "admin crea válido -> 201"
req POST /api/employees '{"nombre":"Nuevo","apellidos":"Empleado","correo":"nuevo@turnos.local","contrasena":"Clave1234","departamento":"Ventas","rol":"empleado"}'
assert_status 201

echo "correo duplicado -> 409"
req POST /api/employees '{"nombre":"X","apellidos":"Y","correo":"nuevo@turnos.local","contrasena":"Clave1234","departamento":"Ventas","rol":"empleado"}'
assert_status 409

echo "password corta -> 422"
req POST /api/employees '{"nombre":"X","apellidos":"Y","correo":"corta@turnos.local","contrasena":"123","departamento":"Ventas","rol":"empleado"}'
assert_status 422

echo "campo faltante -> 422"
req POST /api/employees '{"nombre":"X","correo":"falta@turnos.local","contrasena":"Clave1234","departamento":"Ventas","rol":"empleado"}'
assert_status 422

echo "departamento inexistente -> 422"
req POST /api/employees '{"nombre":"X","apellidos":"Y","correo":"dep@turnos.local","contrasena":"Clave1234","departamento":"NoExiste","rol":"empleado"}'
assert_status 422

echo "responsable POST -> 403"
login resp.soporte@turnos.local Resp1234
req POST /api/employees '{"nombre":"X","apellidos":"Y","correo":"z@turnos.local","contrasena":"Clave1234","departamento":"Soporte","rol":"empleado"}'
assert_status 403

echo "empleado PUT -> 403"
login emp.ventas@turnos.local Empl1234
req PUT /api/employees/5 '{"nombre":"Z"}'; assert_status 403

echo "OK Phase 2"

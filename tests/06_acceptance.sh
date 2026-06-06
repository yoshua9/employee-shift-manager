#!/usr/bin/env bash
# Acceptance checklist (API). Cada sección mapea un punto del criterio de aceptación.
source "$(dirname "$0")/lib.sh"
reset_db

echo "== 1) POST /api/login funciona y devuelve el usuario =="
login admin@turnos.local Admin1234
req POST /api/login '{"correo":"admin@turnos.local","password":"Admin1234"}'
assert_status 200; assert_contains '"rol":"administrador"'; assert_contains '"correo":"admin@turnos.local"'
login admin@turnos.local Admin1234   # re-login para CSRF/cookie de la sesión de pruebas

echo "== 6) Sin sesión, cualquier endpoint (salvo login) -> 401 =="
CSRF=""; : > "$JAR"
req GET    /api/employees;   assert_status 401
req GET    /api/shifts;      assert_status 401
req GET    /api/shifts/1;    assert_status 401
req POST   /api/shifts '{}'; assert_status 401
req DELETE /api/employees/1; assert_status 401

echo "== 2) Employees: lecturas y mutaciones según rol =="
login admin@turnos.local Admin1234
req GET /api/employees;        assert_status 200
req GET /api/employees/3;      assert_status 200
req POST /api/employees '{"nombre":"Test","apellidos":"Acc","correo":"acc@turnos.local","contrasena":"Clave1234","departamento":"Ventas","rol":"empleado"}'
assert_status 201
NEWID=$(printf '%s' "$LAST" | sed -n 's/.*"id":\([0-9]*\).*/\1/p')
req PUT "/api/employees/$NEWID" '{"nombre":"TestEdit"}'; assert_status 200
req DELETE "/api/employees/$NEWID";                       assert_status 200
echo "  -- mutaciones solo admin: responsable/empleado -> 403 --"
login resp.soporte@turnos.local Resp1234
req POST /api/employees '{"nombre":"X","apellidos":"Y","correo":"no@turnos.local","contrasena":"Clave1234","departamento":"Soporte","rol":"empleado"}'; assert_status 403
req PUT /api/employees/3 '{"nombre":"Z"}'; assert_status 403
req DELETE /api/employees/3;               assert_status 403
echo "  -- responsable lee su dept; empleado solo a sí mismo --"
req GET /api/employees;   assert_status 200; assert_contains 'emp.soporte@turnos.local'
login emp.ventas@turnos.local Empl1234
req GET /api/employees;   assert_status 200; assert_contains 'emp.ventas@turnos.local'

echo "== 3) Shifts: CRUD + validaciones =="
login admin@turnos.local Admin1234
req POST /api/shifts '{"empleado_id":3,"fecha":"2027-07-01","hora_inicio":"09:00","hora_fin":"13:00","tipo":"manana"}'; assert_status 201
SID=$(printf '%s' "$LAST" | sed -n 's/.*"id":\([0-9]*\).*/\1/p')
req GET "/api/shifts/$SID";                       assert_status 200
req PUT "/api/shifts/$SID" '{"estado":"confirmado"}'; assert_status 200; assert_contains '"estado":"confirmado"'
echo "  -- validaciones --"
req POST /api/shifts '{"empleado_id":3,"fecha":"2027-07-01","hora_inicio":"12:00","hora_fin":"14:00","tipo":"tarde"}'; assert_status 409   # solapamiento
req POST /api/shifts '{"empleado_id":3,"fecha":"2027-07-02","hora_inicio":"13:00","hora_fin":"09:00","tipo":"manana"}'; assert_status 422   # horas
req PUT "/api/shifts/$SID" '{"hora_inicio":"08:00"}'; assert_status 409   # editar confirmado
req DELETE "/api/shifts/$SID";                        assert_status 409   # borrar no-programado
req PUT /api/shifts/5 '{"sustituto_id":2}';           assert_status 409   # sustituto sobre no-ausente
req DELETE /api/shifts/1;                              assert_status 200   # borrar programado

echo "== 4) Filtros de GET /api/shifts (individuales y combinados) =="
reset_db; login admin@turnos.local Admin1234
D3=$(seed_date 2)   # turno 3 (ausente) cae en lunes+2
req GET "/api/shifts?date=$D3";               assert_status 200; assert_contains '"id":3'
req GET '/api/shifts?employee_id=3';          assert_status 200; assert_contains '"empleado_id":3'
req GET '/api/shifts?department=Ventas';      assert_status 200; assert_contains '"departamento":"Ventas"'
req GET '/api/shifts?status=cubierto';        assert_status 200; assert_contains '"estado":"cubierto"'
echo "  -- combinados --"
req GET '/api/shifts?department=Operaciones&status=cubierto'; assert_status 200; assert_contains '"id":4'
if printf '%s' "$LAST" | grep -q '"id":1'; then echo "  FAIL combinado trajo de más"; exit 1; else echo "  ok combinado department+status acota"; fi
D1=$(seed_date 0)   # turno 1 (programado) cae en lunes+0
req GET "/api/shifts?employee_id=3&date=$D1"; assert_status 200; assert_contains '"id":1'
req GET '/api/shifts?employee_id=3&status=ausente';  assert_status 200
if printf '%s' "$LAST" | grep -q '"id":'; then echo "  FAIL emp3 no tiene ausentes"; exit 1; else echo "  ok combinado employee_id+status (vacío correcto)"; fi

echo "== 5) Mapa de códigos HTTP coherentes =="
login admin@turnos.local Admin1234
req GET /api/shifts;                                  assert_status 200   # 200
req POST /api/shifts '{"empleado_id":7,"fecha":"2027-07-10","hora_inicio":"09:00","hora_fin":"13:00","tipo":"manana"}'; assert_status 201   # 201
CSRF=""; : > "$JAR"; req GET /api/shifts;             assert_status 401   # 401 sin sesión
login emp.ventas@turnos.local Empl1234
req POST /api/shifts '{"empleado_id":5,"fecha":"2027-07-11","hora_inicio":"09:00","hora_fin":"13:00","tipo":"manana"}'; assert_status 403   # 403 sin permiso
req GET /api/shifts/999;                              assert_status 404   # 404 no existe
login resp.soporte@turnos.local Resp1234
req GET /api/shifts/3;                                assert_status 404   # 404 fuera de scope (Ventas)
login admin@turnos.local Admin1234
DSEED=$(seed_date 0)   # solapa con el turno 1 del seed (emp3, lunes 09:00-13:00)
req POST /api/shifts "{\"empleado_id\":3,\"fecha\":\"$DSEED\",\"hora_inicio\":\"10:00\",\"hora_fin\":\"12:00\",\"tipo\":\"manana\"}"; assert_status 409   # 409 conflicto (solape con seed)
req POST /api/shifts '{"empleado_id":3,"fecha":"2027-06-09"}'; assert_status 422   # 422 validación

echo "OK acceptance"

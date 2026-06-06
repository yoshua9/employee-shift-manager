<?php
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/repositories/ShiftRepository.php';
require_once __DIR__ . '/../../includes/repositories/EmployeeRepository.php';

class ShiftController {
    public static function index(array $user): void {
        $f = [
            'date'        => $_GET['date']        ?? null,
            'employee_id' => $_GET['employee_id'] ?? null,
            'department'  => $_GET['department']  ?? null,
            'status'      => $_GET['status']      ?? null,
        ];
        json_response(['data' => ShiftRepository::list($user, $f)], 200);
    }

    public static function show(array $user, int $id): void {
        $s = ShiftRepository::find($user, $id);
        if (!$s) { json_error('El turno no existe o no es accesible.', 404); }
        json_response(['data' => $s], 200);
    }

    public static function create(array $user, array $body): void {
        $err = self::validateInput($body);
        if ($err) { json_error($err, 422); }
        $empId = (int)$body['empleado_id'];
        $emp = EmployeeRepository::findById($empId);
        if (!$emp) { json_error('El empleado seleccionado no existe.', 422); }
        if ($user['rol'] === 'responsable' && $emp['departamento'] !== $user['departamento']) {
            json_error('No autorizado', 403);
        }
        if ((int)$emp['activo'] !== 1) { json_error('No se puede asignar un turno a un empleado inactivo.', 422); }
        if (ShiftRepository::hasOverlap($empId, $body['fecha'], $body['hora_inicio'], $body['hora_fin'])) {
            json_error('El turno se solapa con otro turno del mismo empleado.', 409);
        }
        $id = ShiftRepository::create([
            'empleado_id' => $empId,
            'fecha'       => $body['fecha'],
            'hora_inicio' => $body['hora_inicio'],
            'hora_fin'    => $body['hora_fin'],
            'tipo'        => $body['tipo'],
        ]);
        json_response(['data' => ShiftRepository::find($user, $id)], 201);
    }

    public static function update(array $user, int $id, array $body): void {
        $shift = ShiftRepository::findById($id);
        if (!$shift || !shift_in_scope($user, $shift)) { json_error('El turno no existe o no es accesible.', 404); }

        // Assign substitute
        if (array_key_exists('sustituto_id', $body) && $body['sustituto_id'] !== null && $body['sustituto_id'] !== '') {
            self::assignSubstitute($user, $shift, $body);
            return;
        }

        $current = $shift['estado'];
        $target  = $body['estado'] ?? $current;

        // State change
        if ($target !== $current) {
            if (!in_array($target, SHIFT_STATES, true)) { json_error('El estado indicado no es válido.', 422); }

            // Reopening to 'programado' is an admin-only authorization concern -> 403, not 409.
            if ($target === 'programado' && $current !== 'programado' && $user['rol'] !== 'administrador') {
                json_error('Solo el administrador puede devolver un turno a programado', 403);
            }
            if (!can_transition($current, $target, $user['rol'])) {
                json_error('Ese cambio de estado no está permitido.', 409);
            }
            // A 'confirmado' shift is not editable; field edits sent with the transition are rejected.
            if ($current === 'confirmado' && self::hasFieldEdits($body)) {
                json_error('Un turno confirmado no se puede editar; primero hay que reabrirlo.', 409);
            }
            if ($target === 'programado') {
                ShiftRepository::reopen($id);
            } else {
                ShiftRepository::update($id, ['estado' => $target]);
            }
            json_response(['data' => ShiftRepository::find($user, $id)], 200);
        }

        // Field edits (no state change)
        if (self::hasFieldEdits($body)) {
            if ($current === 'confirmado') { json_error('Un turno confirmado no se puede editar; primero hay que reabrirlo.', 409); }
            if (in_array($current, ['ausente', 'cubierto'], true)) {
                json_error('Solo se pueden editar turnos en estado programado.', 409);
            }
            $merged = [
                'fecha'       => $body['fecha']       ?? $shift['fecha'],
                'hora_inicio' => $body['hora_inicio'] ?? $shift['hora_inicio'],
                'hora_fin'    => $body['hora_fin']    ?? $shift['hora_fin'],
            ];
            if (isset($body['tipo']) && !in_array($body['tipo'], SHIFT_TYPES, true)) { json_error('El tipo de turno no es válido.', 422); }
            if (!valid_time_order($merged['hora_inicio'], $merged['hora_fin'])) {
                json_error('La hora de fin debe ser posterior a la de inicio.', 422);
            }
            if (ShiftRepository::hasOverlap((int)$shift['empleado_id'], $merged['fecha'], $merged['hora_inicio'], $merged['hora_fin'], $id)) {
                json_error('El turno se solapa con otro turno del mismo empleado.', 409);
            }
            ShiftRepository::update($id, array_intersect_key($body, array_flip(['fecha', 'hora_inicio', 'hora_fin', 'tipo'])));
            json_response(['data' => ShiftRepository::find($user, $id)], 200);
        }

        json_response(['data' => ShiftRepository::find($user, $id)], 200);
    }

    public static function destroy(array $user, int $id): void {
        $shift = ShiftRepository::findById($id);
        if (!$shift || !shift_in_scope($user, $shift)) { json_error('El turno no existe o no es accesible.', 404); }
        if ($shift['estado'] !== 'programado') { json_error('Solo se pueden eliminar turnos en estado programado.', 409); }
        ShiftRepository::delete($id);
        json_response(['ok' => true], 200);
    }

    private static function assignSubstitute(array $user, array $shift, array $body): void {
        if ($shift['estado'] !== 'ausente') { json_error('Solo se puede asignar un sustituto a turnos en estado ausente.', 409); }
        $subId = (int)$body['sustituto_id'];
        if ($subId === (int)$shift['empleado_id']) { json_error('El sustituto no puede ser el propio empleado ausente.', 422); }
        $sub = EmployeeRepository::findById($subId);
        if (!$sub) { json_error('El sustituto seleccionado no existe.', 422); }
        if ($user['rol'] === 'responsable' && $sub['departamento'] !== $user['departamento']) { json_error('No autorizado', 403); }
        if ((int)$sub['activo'] !== 1) { json_error('El sustituto está inactivo.', 422); }
        if (ShiftRepository::hasOverlap($subId, $shift['fecha'], $shift['hora_inicio'], $shift['hora_fin'])) {
            json_error('El sustituto ya tiene un turno solapado en ese horario.', 409);
        }
        ShiftRepository::assignSubstitute((int)$shift['id'], $subId, $body['motivo_ausencia'] ?? null);
        json_response(['data' => ShiftRepository::find($user, (int)$shift['id'])], 200);
    }

    private static function validateInput(array $b): ?string {
        $labels = [
            'empleado_id' => 'Indica el empleado.', 'fecha' => 'Indica la fecha.',
            'hora_inicio' => 'Indica la hora de inicio.', 'hora_fin' => 'Indica la hora de fin.',
            'tipo' => 'Indica el tipo de turno.',
        ];
        foreach (['empleado_id', 'fecha', 'hora_inicio', 'hora_fin', 'tipo'] as $f) {
            if (!isset($b[$f]) || $b[$f] === '') { return $labels[$f]; }
        }
        if (!in_array($b['tipo'], SHIFT_TYPES, true)) { return 'El tipo de turno no es válido.'; }
        if (!valid_time_order($b['hora_inicio'], $b['hora_fin'])) { return 'La hora de fin debe ser posterior a la de inicio.'; }
        return null;
    }

    private static function hasFieldEdits(array $b): bool {
        foreach (['fecha', 'hora_inicio', 'hora_fin', 'tipo'] as $f) {
            if (array_key_exists($f, $b)) { return true; }
        }
        return false;
    }
}

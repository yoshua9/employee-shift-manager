<?php
// Scope = derived from the SESSION user, ANDed onto queries. Params only narrow, never widen.

function emp_scope_sql(array $user, string $alias = 'e'): array {
    switch ($user['rol']) {
        case 'administrador': return ['1=1', []];
        case 'responsable':   return ["$alias.departamento = ?", [$user['departamento']]];
        default:              return ["$alias.id = ?", [(int)$user['id']]];
    }
}

function shift_scope_sql(array $user): array {
    switch ($user['rol']) {
        case 'administrador': return ['1=1', []];
        case 'responsable':   return ['e.departamento = ?', [$user['departamento']]];
        default:              return ['(t.empleado_id = ? OR t.sustituto_id = ?)', [(int)$user['id'], (int)$user['id']]];
    }
}

function shift_in_scope(array $user, array $shift): bool {
    switch ($user['rol']) {
        case 'administrador': return true;
        case 'responsable':   return $shift['departamento'] === $user['departamento'];
        default:
            return (int)$shift['empleado_id'] === (int)$user['id']
                || (int)($shift['sustituto_id'] ?? 0) === (int)$user['id'];
    }
}

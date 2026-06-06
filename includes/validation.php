<?php
const SHIFT_TYPES  = ['manana', 'tarde', 'noche', 'partido'];
const SHIFT_STATES = ['programado', 'confirmado', 'ausente', 'cubierto'];
const ROLES        = ['administrador', 'responsable', 'empleado'];

// Two ranges on the same day overlap iff each starts before the other ends.
// Exact adjacency (a.end == b.start) is NOT an overlap.
function ranges_overlap(string $startA, string $endA, string $startB, string $endB): bool {
    return $startA < $endB && $endA > $startB;
}

function valid_time_order(string $start, string $end): bool {
    return $end > $start;
}

// State machine. Forward edges allowed for responsable/administrador.
// 'cubierto' is reachable ONLY via substitute assignment, never as a bare estado change.
// Reopening to 'programado' is admin-only and only from a non-programado state.
function can_transition(string $from, string $to, string $role): bool {
    if ($to === 'programado') {
        return $role === 'administrador' && $from !== 'programado';
    }
    if ($role === 'empleado') {
        return false;
    }
    $forward = [
        'programado' => ['confirmado'],
        'confirmado' => ['ausente'],
        'ausente'    => [],   // -> cubierto solo vía sustituto
        'cubierto'   => [],
    ];
    return in_array($to, $forward[$from] ?? [], true);
}

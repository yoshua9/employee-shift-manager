<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../permissions.php';

class ShiftRepository {
    private const SELECT =
        "SELECT t.*, e.nombre AS emp_nombre, e.apellidos AS emp_apellidos, e.departamento AS departamento,
                s.nombre AS sust_nombre, s.apellidos AS sust_apellidos
         FROM turnos t
         JOIN empleados e ON e.id = t.empleado_id
         LEFT JOIN empleados s ON s.id = t.sustituto_id";

    public static function list(array $user, array $f): array {
        [$where, $params] = shift_scope_sql($user);
        $sql = self::SELECT . " WHERE $where";
        if (!empty($f['date']))        { $sql .= " AND t.fecha = ?";        $params[] = $f['date']; }
        if (!empty($f['employee_id'])) { $sql .= " AND t.empleado_id = ?";  $params[] = (int)$f['employee_id']; }
        if (!empty($f['department']))  { $sql .= " AND e.departamento = ?"; $params[] = $f['department']; }
        if (!empty($f['status']))      { $sql .= " AND t.estado = ?";       $params[] = $f['status']; }
        $sql .= " ORDER BY t.fecha, t.hora_inicio";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return array_map(fn($r) => self::serialize($r, $user), $stmt->fetchAll());
    }

    public static function find(array $user, int $id): ?array {
        [$where, $params] = shift_scope_sql($user);
        $stmt = db()->prepare(self::SELECT . " WHERE $where AND t.id = ?");
        $stmt->execute([...$params, $id]);
        $row = $stmt->fetch();
        return $row ? self::serialize($row, $user) : null;
    }

    public static function findById(int $id): ?array {
        $stmt = db()->prepare(
            "SELECT t.*, e.departamento FROM turnos t JOIN empleados e ON e.id = t.empleado_id WHERE t.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function hasOverlap(int $empId, string $fecha, string $hi, string $hf, ?int $exceptId = null): bool {
        $sql = "SELECT 1 FROM turnos WHERE empleado_id = ? AND fecha = ? AND hora_inicio < ? AND hora_fin > ?";
        $p = [$empId, $fecha, $hf, $hi];
        if ($exceptId !== null) { $sql .= " AND id != ?"; $p[] = $exceptId; }
        $sql .= " LIMIT 1";
        $s = db()->prepare($sql); $s->execute($p);
        return (bool)$s->fetchColumn();
    }

    public static function create(array $d): int {
        $s = db()->prepare(
            "INSERT INTO turnos (empleado_id, fecha, hora_inicio, hora_fin, tipo, estado)
             VALUES (?, ?, ?, ?, ?, 'programado')"
        );
        $s->execute([(int)$d['empleado_id'], $d['fecha'], $d['hora_inicio'], $d['hora_fin'], $d['tipo']]);
        return (int)db()->lastInsertId();
    }

    public static function update(int $id, array $d): void {
        $fields = []; $p = [];
        foreach (['fecha', 'hora_inicio', 'hora_fin', 'tipo', 'estado'] as $f) {
            if (array_key_exists($f, $d)) { $fields[] = "$f = ?"; $p[] = $d[$f]; }
        }
        if (!$fields) { return; }
        $p[] = $id;
        db()->prepare("UPDATE turnos SET " . implode(', ', $fields) . " WHERE id = ?")->execute($p);
    }

    // Reabrir a 'programado': limpia sustituto y motivo para dejar el turno coherente.
    public static function reopen(int $id): void {
        db()->prepare("UPDATE turnos SET estado = 'programado', sustituto_id = NULL, motivo_ausencia = NULL WHERE id = ?")
            ->execute([$id]);
    }

    public static function assignSubstitute(int $id, int $sustitutoId, ?string $motivo): void {
        db()->prepare(
            "UPDATE turnos SET sustituto_id = ?, estado = 'cubierto',
                    motivo_ausencia = COALESCE(?, motivo_ausencia) WHERE id = ?"
        )->execute([$sustitutoId, $motivo, $id]);
    }

    public static function delete(int $id): void {
        db()->prepare("DELETE FROM turnos WHERE id = ?")->execute([$id]);
    }

    private static function serialize(array $r, array $user): array {
        $r['id']           = (int)$r['id'];
        $r['empleado_id']  = (int)$r['empleado_id'];
        $r['sustituto_id'] = $r['sustituto_id'] !== null ? (int)$r['sustituto_id'] : null;
        if ($user['rol'] === 'empleado') { unset($r['motivo_ausencia']); }
        return $r;
    }
}

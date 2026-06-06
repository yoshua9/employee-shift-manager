<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../permissions.php';

class EmployeeRepository {
    private const COLS = 'id, nombre, apellidos, correo, departamento, rol, activo';

    public static function list(array $user): array {
        [$where, $params] = emp_scope_sql($user);
        $stmt = db()->prepare("SELECT " . self::COLS . " FROM empleados e WHERE $where ORDER BY apellidos, nombre");
        $stmt->execute($params);
        return array_map([self::class, 'cast'], $stmt->fetchAll());
    }

    public static function find(array $user, int $id): ?array {
        [$where, $params] = emp_scope_sql($user);
        $stmt = db()->prepare("SELECT " . self::COLS . " FROM empleados e WHERE $where AND e.id = ?");
        $stmt->execute([...$params, $id]);
        $row = $stmt->fetch();
        return $row ? self::cast($row) : null;
    }

    public static function findById(int $id): ?array {
        $stmt = db()->prepare("SELECT id, departamento, rol, activo FROM empleados WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::cast($row) : null;
    }

    public static function create(array $d): int {
        $stmt = db()->prepare(
            "INSERT INTO empleados (nombre, apellidos, correo, contrasena, departamento, rol, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $d['nombre'], $d['apellidos'], $d['correo'],
            password_hash($d['contrasena'], PASSWORD_BCRYPT),
            $d['departamento'], $d['rol'], (int)$d['activo'],
        ]);
        return (int)db()->lastInsertId();
    }

    public static function update(int $id, array $d): void {
        $pdo = db();
        $pdo->beginTransaction();
        $fields = []; $params = [];
        foreach (['nombre', 'apellidos', 'correo', 'departamento', 'rol'] as $f) {
            if (array_key_exists($f, $d)) { $fields[] = "$f = ?"; $params[] = $d[$f]; }
        }
        if (array_key_exists('contrasena', $d)) {
            $fields[] = "contrasena = ?"; $params[] = password_hash($d['contrasena'], PASSWORD_BCRYPT);
        }
        if (array_key_exists('activo', $d)) { $fields[] = "activo = ?"; $params[] = (int)$d['activo']; }
        if ($fields) {
            $params[] = $id;
            $pdo->prepare("UPDATE empleados SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);
        }
        if (array_key_exists('activo', $d) && (int)$d['activo'] === 0) {
            self::revertCoveredSubstitutions($id);
        }
        $pdo->commit();
    }

    public static function delete(int $id): void {
        $pdo = db();
        $pdo->beginTransaction();
        self::revertCoveredSubstitutions($id);          // before CASCADE/SET NULL fires
        $pdo->prepare("DELETE FROM empleados WHERE id = ?")->execute([$id]);
        $pdo->commit();
    }

    // Shifts where this employee was substitute and state was 'cubierto' revert to 'ausente'.
    private static function revertCoveredSubstitutions(int $employeeId): void {
        db()->prepare("UPDATE turnos SET estado = 'ausente', sustituto_id = NULL
                       WHERE sustituto_id = ? AND estado = 'cubierto'")->execute([$employeeId]);
    }

    public static function emailExists(string $correo, ?int $exceptId = null): bool {
        $sql = "SELECT 1 FROM empleados WHERE correo = ?"; $p = [$correo];
        if ($exceptId !== null) { $sql .= " AND id != ?"; $p[] = $exceptId; }
        $s = db()->prepare($sql); $s->execute($p);
        return (bool)$s->fetchColumn();
    }

    public static function departmentExists(string $name): bool {
        $s = db()->prepare("SELECT 1 FROM departamentos WHERE nombre = ?");
        $s->execute([$name]);
        return (bool)$s->fetchColumn();
    }

    private static function cast(array $r): array {
        $r['id'] = (int)$r['id'];
        if (isset($r['activo'])) { $r['activo'] = (int)$r['activo']; }
        return $r;
    }
}

<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/response.php';

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_auth(): array {
    $u = current_user();
    if (!$u) { json_error('No autenticado', 401); }
    return $u;
}

function require_role(string ...$roles): array {
    $u = require_auth();
    if (!in_array($u['rol'], $roles, true)) { json_error('No autorizado', 403); }
    return $u;
}

function attempt_login(string $correo, string $password): ?array {
    $stmt = db()->prepare(
        'SELECT id, nombre, apellidos, correo, contrasena, departamento, rol, activo
         FROM empleados WHERE correo = ?'
    );
    $stmt->execute([$correo]);
    $row = $stmt->fetch();
    if (!$row || (int)$row['activo'] !== 1 || !password_verify($password, $row['contrasena'])) {
        return null;
    }
    unset($row['contrasena']);
    $row['id'] = (int)$row['id'];
    return $row;
}

function login_user(array $user): void {
    session_regenerate_id(true);
    unset($user['contrasena']);
    $_SESSION['user'] = $user;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function logout_user(): void {
    $_SESSION = [];
    session_destroy();
}

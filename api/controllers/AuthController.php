<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

class AuthController {
    public static function login(array $body): void {
        $correo   = trim((string)($body['correo'] ?? ''));
        $password = (string)($body['password'] ?? '');
        if ($correo === '' || $password === '') {
            json_error('Correo y contraseña son obligatorios', 422);
        }
        $user = attempt_login($correo, $password);
        if (!$user) { json_error('Credenciales inválidas', 401); }
        login_user($user);
        json_response(['user' => $user, 'csrf' => csrf_token()], 200);
    }

    public static function logout(): void {
        logout_user();
        json_response(['ok' => true], 200);
    }
}

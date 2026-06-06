<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/repositories/EmployeeRepository.php';
require_once __DIR__ . '/../includes/repositories/ShiftRepository.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/EmployeeController.php';
require_once __DIR__ . '/controllers/ShiftController.php';

function api_dispatch(string $method, string $path): void {
    $body = [];
    if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        $raw  = file_get_contents('php://input');
        $body = $raw ? (json_decode($raw, true) ?? []) : [];
    }

    // Public route
    if ($method === 'POST' && $path === '/login') { AuthController::login($body); }

    // Auth gate for everything else
    $user = require_auth();

    if ($method === 'POST' && $path === '/logout') { AuthController::logout(); }

    // CSRF for mutations
    if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) { verify_csrf(); }

    // /employees
    if ($path === '/employees') {
        if ($method === 'GET')  { EmployeeController::index($user); }
        if ($method === 'POST') { require_role('administrador'); EmployeeController::create($user, $body); }
        json_error('Método no permitido', 405);
    }
    if (preg_match('#^/employees/(\d+)$#', $path, $m)) {
        $id = (int)$m[1];
        if ($method === 'GET')    { EmployeeController::show($user, $id); }
        if ($method === 'PUT')    { require_role('administrador'); EmployeeController::update($user, $id, $body); }
        if ($method === 'DELETE') { require_role('administrador'); EmployeeController::destroy($user, $id); }
        json_error('Método no permitido', 405);
    }

    // /shifts
    if ($path === '/shifts') {
        if ($method === 'GET')  { ShiftController::index($user); }
        if ($method === 'POST') { require_role('administrador', 'responsable'); ShiftController::create($user, $body); }
        json_error('Método no permitido', 405);
    }
    if (preg_match('#^/shifts/(\d+)$#', $path, $m)) {
        $id = (int)$m[1];
        if ($method === 'GET')    { ShiftController::show($user, $id); }
        if ($method === 'PUT')    { require_role('administrador', 'responsable'); ShiftController::update($user, $id, $body); }
        if ($method === 'DELETE') { require_role('administrador', 'responsable'); ShiftController::destroy($user, $id); }
        json_error('Método no permitido', 405);
    }

    json_error('Recurso no encontrado', 404);
}

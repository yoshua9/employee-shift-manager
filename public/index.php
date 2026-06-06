<?php
session_start();

$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// --- API delegation ---
if ($path === '/api' || strpos($path, '/api/') === 0) {
    $apiPath = substr($path, 4);            // strip leading "/api"
    if ($apiPath === '') { $apiPath = '/'; }
    require __DIR__ . '/../api/router.php';
    api_dispatch($method, $apiPath);
    exit;
}

// --- Root: redirect by role if logged in, else to login ---
if ($path === '/') {
    $u = $_SESSION['user'] ?? null;
    if ($u) {
        header('Location: ' . ($u['rol'] === 'empleado' ? '/turnos' : '/planificacion'));
    } else {
        header('Location: /login');
    }
    exit;
}

// --- Web views ---
$views = [
    '/login'         => 'login.php',
    '/planificacion' => 'planning.php',
    '/turnos'        => 'shifts.php',
    '/empleados'     => 'employees.php',
];
if (isset($views[$path]) && is_file(__DIR__ . '/views/' . $views[$path])) {
    require __DIR__ . '/views/' . $views[$path];
    exit;
}
http_response_code(404);
echo 'Página no encontrada';

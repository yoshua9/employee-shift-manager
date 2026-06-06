<?php
// Real config (gitignored) if present; otherwise the versioned example. In both cases the
// values come from getenv() with defaults, so Docker can inject DB_* via the environment.
require_once __DIR__ . '/' . (is_file(__DIR__ . '/config.php') ? 'config.php' : 'config.example.php');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

<?php
function csrf_token(): string {
    return $_SESSION['csrf'] ?? '';
}

function verify_csrf(): void {
    $sent     = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $expected = $_SESSION['csrf'] ?? '';
    if ($expected === '' || !hash_equals($expected, $sent)) {
        json_error('Token CSRF inválido', 403);
    }
}

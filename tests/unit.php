<?php
// Zero-dependency unit runner for pure functions in includes/validation.php
require_once __DIR__ . '/../includes/validation.php';

$failed = 0;
function check(string $name, bool $cond): void {
    global $failed;
    if ($cond) { echo "  ok  $name\n"; }
    else       { echo "  FAIL $name\n"; $GLOBALS['failed'] = 1; }
}

require __DIR__ . '/unit_cases.php';

exit($failed ? 1 : 0);

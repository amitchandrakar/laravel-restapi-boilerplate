#!/usr/bin/env php
<?php
declare(strict_types=1);

// Consume stdin so Cursor does not block. Decision uses git status, not the payload.
stream_get_contents(STDIN);

$root = dirname(__DIR__, 2);
$status = shell_exec(
    'cd ' . escapeshellarg($root) . ' && git status --porcelain -- app tests routes database 2>/dev/null'
);
$status = is_string($status) ? $status : '';

$needsPest = false;
$needsStan = false;

foreach (preg_split('/\R/', $status) ?: [] as $line) {
    if (!preg_match('/\.php$/', $line)) {
        continue;
    }

    if (preg_match('#(^|\\s)(app|tests|routes|database)/#', $line)) {
        $needsPest = true;
    }

    if (preg_match('#(^|\\s)app/#', $line)) {
        $needsStan = true;
    }
}

if (!$needsPest) {
    echo "{}\n";
    exit(0);
}

$steps =
    'Run vendor/bin/pint --dirty, vendor/bin/phpcs on changed PHP, and php artisan test --filter= for the area you changed.';

if ($needsStan) {
    $steps .= ' app/ changed, so also run vendor/bin/phpstan analyse.';
}
$steps .= ' Fix failures before treating the task as done.';

echo json_encode(['followup_message' => $steps], JSON_UNESCAPED_SLASHES) . "\n";
exit(0);


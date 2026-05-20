<?php

declare(strict_types=1);

/**
 * Removes a private instance method definition from PHPUnit test classes.
 * Brace-counting handles nested arrays/closures in the method body.
 *
 * Usage: php scripts/strip-test-helpers.php [--dry-run]
 */
$dryRun = in_array('--dry-run', $argv, true);

$featureDir = dirname(__DIR__) . '/tests/Feature';

$methodNames = ['createUserWithRole'];

foreach (glob($featureDir . '/*.php') ?: [] as $path) {
    $src = file_get_contents($path);
    if ($src === false) {
        fwrite(STDERR, "Cannot read: {$path}\n");
        exit(1);
    }
    foreach ($methodNames as $method) {
        $needle = 'private function ' . $method . '(';
        $pos = strpos($src, $needle);
        if ($pos === false) {
            continue;
        }
        $braceStart = strpos($src, '{', $pos);
        if ($braceStart === false) {
            continue;
        }
        $depth = 0;
        $len = strlen($src);
        for ($i = $braceStart; $i < $len; $i++) {
            $c = $src[$i];
            if ($c === '{') {
                $depth++;
            } elseif ($c === '}') {
                $depth--;
                if ($depth === 0) {
                    $end = $i;
                    break;
                }
            }
        }
        if (!isset($end)) {
            fwrite(STDERR, "Unbalanced braces in {$path} for {$method}\n");
            exit(1);
        }
        $before = substr($src, 0, $pos);
        $after = substr($src, $end + 1);
        $trimmedBefore = preg_replace('/[ \t]*$/', '', $before) ?? $before;
        $trimmedAfter = preg_replace('/^\s*\r?\n/', '', $after) ?? $after;
        $src = $trimmedBefore . "\n\n" . ltrim((string) $trimmedAfter, "\n");

        fwrite(STDOUT, $dryRun ? "[dry-run] would strip {$method} from {$path}\n" : "Stripped {$method} from {$path}\n");
    }
    if (!$dryRun) {
        file_put_contents($path, $src);
    }
}

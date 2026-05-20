<?php

declare(strict_types=1);

/**
 * Queue names for worker priority (app-plan §14).
 * Run workers per queue, e.g. `php artisan queue:work redis --queue=critical,high,default,low`
 */
return [
    'critical' => env('QUEUE_CRITICAL', 'critical'),
    'high' => env('QUEUE_HIGH', 'high'),
    'default' => env('QUEUE_DEFAULT', 'default'),
    'low' => env('QUEUE_LOW', 'low'),
];

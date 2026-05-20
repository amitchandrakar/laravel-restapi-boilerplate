<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Feature (Laravel application + HTTP)
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Unit (plain PHPUnit, no Laravel application)
|--------------------------------------------------------------------------
*/

pest()->extend(PhpUnitTestCase::class)->in('Unit');

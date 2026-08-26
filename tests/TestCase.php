<?php

declare(strict_types=1);

namespace Sifrious\Menard\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Sifrious\Menard\MenardServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [MenardServiceProvider::class];
    }
}

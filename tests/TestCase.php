<?php

namespace Kraftdo\Shared\Tests;

use Kraftdo\Shared\KraftdoSharedServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [KraftdoSharedServiceProvider::class];
    }
}

<?php

declare(strict_types=1);

namespace IllumaLaw\HealthCheckTypesense\Tests;

use IllumaLaw\HealthCheckTypesense\HealthCheckTypesenseServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Health\HealthServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            HealthServiceProvider::class,
            HealthCheckTypesenseServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('scout.driver', 'typesense');
    }
}

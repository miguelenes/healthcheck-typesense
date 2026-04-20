<?php

declare(strict_types=1);

namespace IllumaLaw\HealthCheckTypesense\Tests;

use IllumaLaw\HealthCheckTypesense\TypesensePulseServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Health\HealthServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            HealthServiceProvider::class,
            TypesensePulseServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('scout.driver', 'typesense');
    }
}

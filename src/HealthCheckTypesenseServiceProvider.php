<?php

declare(strict_types=1);

namespace IllumaLaw\HealthCheckTypesense;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class HealthCheckTypesenseServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('healthcheck-typesense')
            ->hasConfigFile()
            ->hasTranslations();
    }
}

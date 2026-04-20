# Typesense Pulse

[![Tests](https://github.com/illuma-law/healthcheck-typesense/actions/workflows/run-tests.yml/badge.svg)](https://github.com/illuma-law/healthcheck-typesense/actions)
[![Packagist License](https://img.shields.io/badge/Licence-MIT-blue)](http://choosealicense.com/licenses/mit/)
[![Latest Stable Version](https://img.shields.io/packagist/v/illuma-law/healthcheck-typesense?label=Version)](https://packagist.org/packages/illuma-law/healthcheck-typesense)

**Focused Typesense health check for Spatie's Laravel Health package**

This package provides a robust health check for Typesense, monitoring connection health, collection counts, and node availability.

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Fluent API](#fluent-api)
- [Testing](#testing)
- [Credits](#credits)
- [License](#license)

## Installation

You can install the package via composer:

```bash
composer require illuma-law/healthcheck-typesense
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="healthcheck-typesense-config"
```

## Configuration

The configuration file allows you to define default client settings and timeout thresholds:

```php
return [
    'client_settings' => [
        'api_key' => env('TYPESENSE_API_KEY'),
        'nodes' => [
            [
                'host' => env('TYPESENSE_HOST', 'localhost'),
                'port' => env('TYPESENSE_PORT', '8108'),
                'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
            ],
        ],
    ],
    'timeout_seconds' => 5,
    'expected_nodes' => 1,
];
```

## Usage

Register the check in your `HealthServiceProvider` or wherever you configure Spatie Health:

```php
use IllumaLaw\HealthCheckTypesense\TypesenseCheck;
use Spatie\Health\Facades\Health;

Health::checks([
    TypesenseCheck::new()
        ->timeout(3)
        ->expectNodes(1),
]);
```

### Fluent API

You can also fluently configure the client settings directly on the check:

```php
TypesenseCheck::new()
    ->clientSettings([
        'api_key' => 'custom-key',
        'nodes' => [['host' => 'typesense.example.com', 'port' => '443', 'protocol' => 'https']]
    ])
    ->timeout(5)
    ->expectNodes(3);
```

## Testing

The package includes a comprehensive test suite using Pest.

```bash
composer test
```

## Credits

- [illuma-law](https://github.com/illuma-law)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

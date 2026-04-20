<?php

declare(strict_types=1);

use IllumaLaw\HealthCheckTypesense\Tests\TestCase;
use IllumaLaw\HealthCheckTypesense\TypesenseCheck;
use Spatie\Health\Enums\Status;
use Typesense\Client;
use Typesense\Collections;
use Typesense\Exceptions\TypesenseClientError;
use Typesense\Health;

uses(TestCase::class);

it('skips when scout driver is not typesense', function () {
    config()->set('scout.driver', 'algolia');

    $result = TypesenseCheck::new()->run();

    expect($result->status)->toEqual(Status::skipped());
});

it('can perform a successful check', function () {
    $health = Mockery::mock(Health::class);
    $health->shouldReceive('retrieve')->andReturn(['ok' => true]);

    $collections = Mockery::mock(Collections::class);
    $collections->shouldReceive('retrieve')->andReturn([
        ['name' => 'test', 'num_documents' => 10],
    ]);

    $client = Mockery::mock(Client::class);
    $client->health = $health;
    $client->collections = $collections;

    $result = TypesenseCheck::new()
        ->useClient($client)
        ->run();

    expect($result->status)->toEqual(Status::ok())
        ->and($result->meta['collection_count'])->toBe(1)
        ->and($result->meta['num_documents_total'])->toBe(10);
});

it('fails when typesense throws an exception', function () {
    $health = Mockery::mock(Health::class);
    $health->shouldReceive('retrieve')->andThrow(new Exception('Connection failed'));

    $client = Mockery::mock(Client::class);
    $client->health = $health;

    $result = TypesenseCheck::new()
        ->useClient($client)
        ->run();

    expect($result->status)->toEqual(Status::failed())
        ->and($result->notificationMessage)->toContain('Connection failed');
});

it('warns when response time is slow', function () {
    $health = Mockery::mock(Health::class);
    $health->shouldReceive('retrieve')->andReturnUsing(function () {
        usleep(100000);

        return ['ok' => true];
    });

    $collections = Mockery::mock(Collections::class);
    $collections->shouldReceive('retrieve')->andReturn([]);

    $client = Mockery::mock(Client::class);
    $client->health = $health;
    $client->collections = $collections;

    $result = TypesenseCheck::new()
        ->useClient($client)
        ->timeout(0)
        ->run();

    expect($result->status)->toEqual(Status::warning())
        ->and($result->notificationMessage)->toContain('Typesense responded slowly');
});

it('fails when typesense throws a client error', function () {
    $health = Mockery::mock(Health::class);
    $health->shouldReceive('retrieve')->andThrow(new TypesenseClientError('Client error'));

    $client = Mockery::mock(Client::class);
    $client->health = $health;

    $result = TypesenseCheck::new()
        ->useClient($client)
        ->run();

    expect($result->status)->toEqual(Status::failed())
        ->and($result->notificationMessage)->toContain('Client error');
});

it('can be configured with fluent methods', function () {
    config()->set('scout.driver', 'typesense');
    config()->set('healthcheck-typesense.client_settings', [
        'nodes' => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
        'api_key' => 'xyz',
    ]);

    $check = TypesenseCheck::new()
        ->clientSettings(['nodes' => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']], 'api_key' => 'xyz'])
        ->expectNodes(3)
        ->timeout(10);

    expect($check)->toBeInstanceOf(TypesenseCheck::class);

    // This will try to instantiate the client and fail reaching it
    $result = $check->run();
    expect($result->status)->toEqual(Status::failed());
});

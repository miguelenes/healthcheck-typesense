<?php

declare(strict_types=1);

use IllumaLaw\HealthCheckTypesense\TypesenseCheck;
use Mockery\MockInterface;
use Spatie\Health\Enums\Status;
use Typesense\Client;
use Typesense\Collections;
use Typesense\Exceptions\TypesenseClientError;
use Typesense\Health;

it('can perform a successful check', function () {
    /** @var Health|MockInterface $health */
    $health = Mockery::mock(Health::class);
    $health->shouldReceive('retrieve')->andReturn(['ok' => true]);

    /** @var Collections|MockInterface $collections */
    $collections = Mockery::mock(Collections::class);
    $collections->shouldReceive('retrieve')->andReturn([
        ['name' => 'test', 'num_documents' => 10],
    ]);

    /** @var Client|MockInterface $client */
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
    /** @var Health|MockInterface $health */
    $health = Mockery::mock(Health::class);
    $health->shouldReceive('retrieve')->andThrow(new Exception('Connection failed'));

    /** @var Client|MockInterface $client */
    $client = Mockery::mock(Client::class);
    $client->health = $health;

    $result = TypesenseCheck::new()
        ->useClient($client)
        ->run();

    expect($result->status)->toEqual(Status::failed())
        ->and($result->notificationMessage)->toContain('Connection failed');
});

it('warns when response time is slow', function () {
    /** @var Health|MockInterface $health */
    $health = Mockery::mock(Health::class);
    $health->shouldReceive('retrieve')->andReturnUsing(function () {
        usleep(100000);

        return ['ok' => true];
    });

    /** @var Collections|MockInterface $collections */
    $collections = Mockery::mock(Collections::class);
    $collections->shouldReceive('retrieve')->andReturn([]);

    /** @var Client|MockInterface $client */
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
    /** @var Health|MockInterface $health */
    $health = Mockery::mock(Health::class);
    $health->shouldReceive('retrieve')->andThrow(new TypesenseClientError('Client error'));

    /** @var Client|MockInterface $client */
    $client = Mockery::mock(Client::class);
    $client->health = $health;

    $result = TypesenseCheck::new()
        ->useClient($client)
        ->run();

    expect($result->status)->toEqual(Status::failed())
        ->and($result->notificationMessage)->toContain('Client error');
});

it('can be configured with fluent methods', function () {
    config()->set('healthcheck-typesense.client_settings', [
        'nodes' => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
        'api_key' => 'xyz',
    ]);

    $check = TypesenseCheck::new()
        ->clientSettings(['nodes' => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']], 'api_key' => 'xyz'])
        ->expectNodes(3)
        ->timeout(10);

    expect($check)->toBeInstanceOf(TypesenseCheck::class);

    // This should fail because port 8109 is not listening
    $result = $check->run();
    expect($result->status)->toEqual(Status::failed());
});

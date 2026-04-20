<?php

declare(strict_types=1);

namespace IllumaLaw\HealthCheckTypesense;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Spatie\Health\Enums\Status;
use Throwable;
use Typesense\Client;
use Typesense\Exceptions\TypesenseClientError;

final class TypesenseCheck extends Check
{
    private ?array $clientSettings = null;
    private ?int $timeoutSeconds = null;
    private ?int $expectedNodes = null;
    private ?Client $client = null;

    public function useClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function clientSettings(array $settings): self
    {
        $this->clientSettings = $settings;

        return $this;
    }

    public function timeout(int $seconds): self
    {
        $this->timeoutSeconds = $seconds;

        return $this;
    }

    public function expectNodes(int $count): self
    {
        $this->expectedNodes = $count;

        return $this;
    }

    public function run(): Result
    {
        if (config('scout.driver') !== 'typesense') {
            return (new Result(Status::skipped(), __('healthcheck-typesense::messages.skipped')))
                ->meta(['driver' => (string) config('scout.driver', 'database')])
                ->shortSummary(__('healthcheck-typesense::messages.skipped'));
        }

        $timeoutSeconds = $this->timeoutSeconds ?? (int) config('healthcheck-typesense.timeout_seconds', 5);
        $clientSettings = $this->clientSettings ?? (array) config('healthcheck-typesense.client_settings', []);
        $expectedNodes = $this->expectedNodes ?? (int) config('healthcheck-typesense.expected_nodes', 1);

        $started = microtime(true);

        try {
            $client = $this->client ?? new Client($clientSettings);
            $health = $client->health->retrieve();
            $collections = $client->collections->retrieve();
        } catch (TypesenseClientError $e) {
            return Result::make()
                ->failed(__('healthcheck-typesense::messages.unreachable', ['message' => $e->getMessage()]))
                ->shortSummary('Failed');
        } catch (Throwable $e) {
            return Result::make()
                ->failed(__('healthcheck-typesense::messages.unreachable', ['message' => $e->getMessage()]))
                ->shortSummary('Failed');
        }

        $responseTimeMs = (int) round((microtime(true) - $started) * 1000);
        $collectionCount = count($collections);
        $numDocs = 0;

        foreach ($collections as $row) {
            $numDocs += (int) ($row['num_documents'] ?? 0);
        }

        $host = (string) ($clientSettings['nodes'][0]['host'] ?? '');

        $result = Result::make()
            ->meta([
                'health'              => $health,
                'collection_count'    => $collectionCount,
                'num_documents_total' => $numDocs,
                'host'                => $host,
                'response_time_ms'    => $responseTimeMs,
            ])
            ->shortSummary("{$responseTimeMs}ms");

        if ($responseTimeMs > ($timeoutSeconds * 1000)) {
            return $result->warning("Typesense responded slowly ({$responseTimeMs}ms)");
        }

        return $result->ok(__('healthcheck-typesense::messages.ok'));
    }
}

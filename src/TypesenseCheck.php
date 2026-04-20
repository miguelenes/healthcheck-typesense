<?php

declare(strict_types=1);

namespace IllumaLaw\HealthCheckTypesense;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Throwable;
use Typesense\Client;
use Typesense\Exceptions\TypesenseClientError;

final class TypesenseCheck extends Check
{
    /** @var array<string, mixed>|null */
    private ?array $clientSettings = null;

    private ?int $timeoutSeconds = null;

    private ?int $expectedNodes = null;

    private ?Client $client = null;

    public function useClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /** @param array<string, mixed> $settings */
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
        $configTimeout = config('healthcheck-typesense.timeout_seconds');
        $timeoutSeconds = $this->timeoutSeconds ?? (is_int($configTimeout) ? $configTimeout : 5);

        $configSettings = config('healthcheck-typesense.client_settings');
        /** @var array<string, mixed> $clientSettings */
        $clientSettings = $this->clientSettings ?? (is_array($configSettings) ? $configSettings : []);

        $configNodes = config('healthcheck-typesense.expected_nodes');
        $expectedNodes = $this->expectedNodes ?? (is_int($configNodes) ? $configNodes : 1);

        $started = microtime(true);

        try {
            $client = $this->client ?? new Client($clientSettings);
            $health = $client->health->retrieve();
            /** @var array<int, array<string, mixed>> $collections */
            $collections = $client->collections->retrieve();
        } catch (TypesenseClientError $e) {
            $message = __('healthcheck-typesense::messages.unreachable', ['message' => $e->getMessage()]);

            return Result::make()
                ->failed(is_string($message) ? $message : 'Typesense request failed')
                ->shortSummary('Failed');
        } catch (Throwable $e) {
            $message = __('healthcheck-typesense::messages.unreachable', ['message' => $e->getMessage()]);

            return Result::make()
                ->failed(is_string($message) ? $message : 'Typesense request failed')
                ->shortSummary('Failed');
        }

        $responseTimeMs = (int) round((microtime(true) - $started) * 1000);
        $collectionCount = count($collections);
        $numDocs = 0;

        foreach ($collections as $row) {
            $numDocs += is_int($row['num_documents'] ?? null) ? (int) $row['num_documents'] : 0;
        }

        /** @var array<int, array<string, mixed>> $nodes */
        $nodes = is_array($clientSettings['nodes'] ?? null) ? $clientSettings['nodes'] : [];
        $node = $nodes[0] ?? [];
        $host = is_string($node['host'] ?? null) ? (string) $node['host'] : '';

        $result = Result::make()
            ->meta([
                'health' => $health,
                'collection_count' => $collectionCount,
                'num_documents_total' => $numDocs,
                'host' => $host,
                'response_time_ms' => $responseTimeMs,
            ])
            ->shortSummary("{$responseTimeMs}ms");

        if ($responseTimeMs > ($timeoutSeconds * 1000)) {
            return $result->warning("Typesense responded slowly ({$responseTimeMs}ms)");
        }

        $okMessage = __('healthcheck-typesense::messages.ok');

        return $result->ok(is_string($okMessage) ? $okMessage : 'Typesense is healthy');
    }
}

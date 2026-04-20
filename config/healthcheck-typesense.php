<?php

declare(strict_types=1);

return [
    /*
     * The client settings for the Typesense client.
     */
    'client_settings' => [
        'api_key' => env('TYPESENSE_API_KEY', 'xyz'),
        'nodes'   => [
            [
                'host'     => env('TYPESENSE_HOST', 'localhost'),
                'port'     => env('TYPESENSE_PORT', '8108'),
                'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
            ],
        ],
        'nearest_node' => [
            'host'     => env('TYPESENSE_HOST', 'localhost'),
            'port'     => env('TYPESENSE_PORT', '8108'),
            'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
        ],
        'connection_timeout_seconds'   => env('TYPESENSE_CONNECTION_TIMEOUT_SECONDS', 2),
        'healthcheck_interval_seconds' => env('TYPESENSE_HEALTHCHECK_INTERVAL_SECONDS', 60),
        'num_retries'                  => env('TYPESENSE_NUM_RETRIES', 3),
        'retry_interval_seconds'       => env('TYPESENSE_RETRY_INTERVAL_SECONDS', 1),
    ],

    /*
     * The default timeout for the health check in seconds.
     */
    'timeout_seconds' => 5,

    /*
     * The number of nodes expected to be healthy.
     */
    'expected_nodes' => 1,
];

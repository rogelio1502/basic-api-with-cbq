<?php

return [
    'name' => 'basic-api-with-cbq',
    'prefix' => 'ef-admin',
    'domain' => '',
    'subdomain' => 'mty01',
    'cbq' => [
        'controllerClass' => \Uxmal\Backend\Controllers\CBQToBrokerController::class,
        'broker' => [
            'default' => [
                'driver' => 'kafka',
                'receive_wait_timeout_ms' => env('UXMAL_BACKEND_CBQ_RECEIVE_WAIT_TIMEOUT', 5000),
                'sync_timeout_ms' => env('UXMAL_BACKEND_CBQ_SYNC_TIMEOUT', 5000),
                'kafka' => [
                    'brokers' => env('UXMAL_BACKEND_CBQ_DEFAULT_KAFKA_BROKERS', 'localhost:9092'),
                    'group_id' => env('UXMAL_BACKEND_CBQ_DEFAULT_KAFKA_GROUP_ID', 'uxmal-backend'),
                    'librdkafka-config' => [
                        'enable.idempotence' => 'true',
                        'socket.timeout.ms' => '50',
                    ],
                    'topics' => explode('|',env('UXMAL_BACKEND_CBQ_DEFAULT_KAFKA_TOPICS', 'rogelio1502')),
                    'security' => [
                        'protocol' => env('UXMAL_BACKEND_CBQ_DEFAULT_KAFKA_SECURITY_PROTOCOL', 'SASL_SSL'),
                        'sasl_username' => env('UXMAL_BACKEND_CBQ_DEFAULT_KAFKA_SECURITY_SASL_USERNAME', ''),
                        'sasl_password' => env('UXMAL_BACKEND_CBQ_DEFAULT_KAFKA_SECURITY_SASL_PASSWORD', ''),
                        /*
                            * AWS MSK IAM SASL
                            * 'protocol' => 'MSK_IAM_SASL',
                            * 'aws_region' => env('AWS_REGION', ''),
                            * 'aws_key' => env('AWS_ACCESS_KEY_ID', ''),
                            * 'aws_secret' => env('AWS_SECRET_ACCESS_KEY', ''),
                            *
                            * PAINTEXT
                            * 'protocol' => 'PLAINTEXT',
                            * 'sasl_username' => env('UXMAL_BACKEND_CMD_BROKER_MSK_SASL_USERNAME', ''),
                            * 'sasl_password' => env('UXMAL_BACKEND_CMD_BROKER_MSK_SASL_PASSWORD', ''),
                            *
                        */
                    ],
                ],
                'handles' => [
                    'cmd'
                ]
            ],
        ],
    ]
];

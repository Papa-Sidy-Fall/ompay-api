<?php

return [
    'api' => [
        'title' => 'OmPay API',
    ],

    'routes' => [
        'api' => 'api/documentation',
    ],

    'paths' => [
        'use_absolute_path' => env('SWAGGER_LUME_USE_ABSOLUTE_PATH', true),
        'swagger_ui_asset_path' => env('SWAGGER_LUME_SWAGGER_UI_ASSET_PATH', null),
        'docs' => env('SWAGGER_LUME_DOCS_PATH', 'docs'),
        'docs_json' => 'api-docs.json',
        'format_to_use_for_docs' => env('SWAGGER_LUME_FORMAT_TO_USE_FOR_DOCS', 'json'),
        'annotations' => [
            base_path('app'),
        ],
    ],

    'scan' => [
        'directories' => [
            app_path(),
        ],
        'files' => [
            base_path('routes/api.php'),
        ],
    ],

    'security' => [
        'bearer' => [
            'type' => 'apiKey',
            'name' => 'Authorization',
            'in' => 'header',
        ],
    ],

    'generate_always' => env('SWAGGER_LUME_GENERATE_ALWAYS', false),
    'generate_yaml_copy' => env('SWAGGER_LUME_GENERATE_YAML_COPY', false),
    'proxy' => false,
    'additional_config_url' => null,
    'operations_sort' => env('SWAGGER_LUME_OPERATIONS_SORT', null),
    'validator_url' => null,

    'ui' => [
        'display' => [
            'doc_expansion' => env('SWAGGER_LUME_DOC_EXPANSION', 'none'),
            'filter' => env('SWAGGER_LUME_FILTER', true),
        ],

        'authorization' => [
            'persist_authorization' => env('SWAGGER_LUME_PERSIST_AUTHORIZATION', false),
            'oauth2_redirect_url' => env('SWAGGER_LUME_OAUTH2_REDIRECT_URL', null),
        ],
    ],
];
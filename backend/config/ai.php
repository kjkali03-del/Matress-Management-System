<?php

return [
    'enabled' => env('AI_ENABLED', false),
    'provider' => env('AI_PROVIDER', 'openai'),
    'api_key' => env('AI_API_KEY'),
    'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),
    'model' => env('AI_MODEL', 'gpt-4.1-mini'),
    'timeout' => (int) env('AI_TIMEOUT', 30),
    'max_tool_rounds' => (int) env('AI_MAX_TOOL_ROUNDS', 4),
    'max_tokens' => (int) env('AI_MAX_TOKENS', 700),
    'timezone' => env('AI_TIMEZONE', 'Africa/Dar_es_Salaam'),
];

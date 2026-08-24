<?php

return [
    /*
     * Active AI provider identifier (controlled application value, recorded in
     * analysis metadata). Must match a configured adapter behind the
     * provider-neutral port (TD-002 / TD-009).
     */
    'provider' => env('AI_PROVIDER', 'openai'),

    /*
     * Provider registry: each entry defines an OpenAI-compatible endpoint.
     * The 'provider' key above selects which one is active.
     *
     * Supported keys per provider:
     *  - base_url   : OpenAI-compatible API base URL (e.g., https://api.openai.com/v1, http://localhost:1234/v1)
     *  - api_key    : API key (empty string for keyless local servers like LM Studio)
     *  - model      : Model identifier passed to the provider
     *  - timeout    : Request timeout in seconds (default 120)
     */
    'providers' => [
        'openai' => [
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'api_key' => env('OPENAI_API_KEY', ''),
            'model' => env('OPENAI_MODEL', 'gpt-5.6-luna'),
            'timeout' => (int) env('OPENAI_TIMEOUT', 120),
        ],
        'lmstudio' => [
            'base_url' => env('LMSTUDIO_BASE_URL', 'http://localhost:1234/v1'),
            'api_key' => env('LMSTUDIO_API_KEY', ''), // LM Studio typically runs keyless
            'model' => env('LMSTUDIO_MODEL', 'qwen/qwen3.5-9b'),
            'timeout' => (int) env('LMSTUDIO_TIMEOUT', 300),
        ],
        'google' => [
            'base_url' => env('GOOGLE_AI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/openai'),
            'api_key' => env('GOOGLE_AI_API_KEY', ''),
            'model' => env('GOOGLE_AI_MODEL', 'gemini-3.7-flash'),
            'timeout' => (int) env('GOOGLE_AI_TIMEOUT', 120),
        ],
    ],

    /*
     * Approved finite request timeout (TD-003): 120 seconds.
     * Enforced on the real provider HTTP request; never relies on
     * PHP max_execution_time alone.
     */
    'timeout' => (int) env('AI_TIMEOUT', 120),

    /*
     * Schema/prompt version identifier recorded in analysis metadata and used
     * as the FK target for analysis_logs.prompt_version.
     */
    'schema_version' => 'meeting-analysis-v1',

    /*
     * Development-only adapter selector.
     *  - 'llm'   (default): real LLMAdapter (production behavior).
     *  - 'fake'  : FakeAnalysisProvider, used locally/testing to deterministically
     *              demonstrate FR-005 failure handling WITHOUT calling any LLM.
     * Never set this to 'fake' in production.
     */
    'driver' => env('AI_DRIVER', 'llm'),

    /*
     * Fake provider outcome when ai.driver = 'fake'. One of:
     * valid | config | dependency | timeout | rate_limit | invalid_response | internal
     * Controls which deterministic result/failure the fake simulates.
     */
    'fake_outcome' => env('AI_FAKE_OUTCOME', 'valid'),
];

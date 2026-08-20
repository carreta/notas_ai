<?php

return [
    /*
     * Active AI provider identifier (controlled application value, recorded in
     * analysis metadata). Must match a configured adapter behind the
     * provider-neutral port (TD-002 / TD-009).
     */
    'provider' => env('AI_PROVIDER', 'openai'),

    /*
     * Development-only adapter selector.
     *  - 'openai' (default): real OpenAIAnalysisProvider (production behavior).
     *  - 'fake'  : FakeAnalysisProvider, used locally/testing to deterministically
     *              demonstrate FR-005 failure handling WITHOUT calling OpenAI.
     * Never set this to 'fake' in production.
     */
    'driver' => env('AI_DRIVER', 'openai'),

    /*
     * Fake provider outcome when ai.driver = 'fake'. One of:
     * valid | config | dependency | timeout | rate_limit | invalid_response | internal
     * Controls which deterministic result/failure the fake simulates.
     */
    'fake_outcome' => env('AI_FAKE_OUTCOME', 'valid'),

    /*
     * Model/config key passed to the provider adapter. Not a secret.
     */
    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),

    /*
     * Provider API key. Read from the environment only; never persisted,
     * never logged, never placed in application-owned DTOs.
     */
    'api_key' => env('OPENAI_API_KEY', ''),

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
];

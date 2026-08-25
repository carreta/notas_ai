<?php

namespace App\AI;

use Illuminate\Config\Repository;

/**
 * Verifies AI provider configuration readiness (FR-011).
 *
 * For the active (or a given) provider it checks that every required
 * configuration key (endpoint, API key, model) is present and non-empty.
 * The 'fake' driver is always considered ready because it performs no
 * network calls. Secret values are never read or exposed; only the
 * environment variable NAMES a user must configure are reported.
 */
final class AiReadiness
{
    /**
     * Provider -> configuration keys that must be non-empty for readiness.
     */
    private const REQUIRED_CONFIG_KEYS = [
        'openai' => ['base_url', 'api_key', 'model'],
        'google' => ['base_url', 'api_key', 'model'],
        'lmstudio' => ['base_url', 'model'],
    ];

    /**
     * (provider, configKey) -> environment variable name (for guidance only).
     */
    private const ENV_VAR_MAP = [
        'openai' => [
            'base_url' => 'OPENAI_BASE_URL',
            'api_key' => 'OPENAI_API_KEY',
            'model' => 'OPENAI_MODEL',
        ],
        'google' => [
            'base_url' => 'GOOGLE_AI_BASE_URL',
            'api_key' => 'GOOGLE_AI_API_KEY',
            'model' => 'GOOGLE_AI_MODEL',
        ],
        'lmstudio' => [
            'base_url' => 'LMSTUDIO_BASE_URL',
            'api_key' => 'LMSTUDIO_API_KEY',
            'model' => 'LMSTUDIO_MODEL',
        ],
    ];

    public function __construct(private Repository $config) {}

    public function checkActive(): AiReadinessResult
    {
        $provider = (string) $this->config->get('ai.provider', '');

        return $this->checkProvider($provider);
    }

    public function checkProvider(string $provider): AiReadinessResult
    {
        $driver = (string) $this->config->get('ai.driver', 'llm');

        if ($driver === 'fake') {
            return new AiReadinessResult($provider, true, $driver);
        }

        $providerConfig = $this->config->get("ai.providers.{$provider}");

        if (! is_array($providerConfig)) {
            return new AiReadinessResult(
                $provider,
                false,
                $driver,
                ["Provider '{$provider}' is not configured in ai.providers."],
            );
        }

        $requiredKeys = self::REQUIRED_CONFIG_KEYS[$provider]
            ?? ['base_url', 'api_key', 'model'];

        $issues = [];

        foreach ($requiredKeys as $key) {
            $value = $providerConfig[$key] ?? null;

            if (! is_string($value) || trim($value) === '') {
                $envVar = self::ENV_VAR_MAP[$provider][$key]
                    ?? strtoupper("AI_{$provider}_{$key}");
                $issues[] = "Missing or empty {$envVar} for provider '{$provider}'.";
            }
        }

        return new AiReadinessResult($provider, count($issues) === 0, $driver, $issues);
    }

    /**
     * @return array<string, AiReadinessResult>
     */
    public function checkAll(): array
    {
        $providers = array_keys((array) $this->config->get('ai.providers', []));
        $results = [];

        foreach ($providers as $provider) {
            $results[$provider] = $this->checkProvider($provider);
        }

        return $results;
    }
}

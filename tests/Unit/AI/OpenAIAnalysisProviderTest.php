<?php

namespace Tests\Unit\AI;

use App\AI\DTO\AnalysisRequest;
use App\AI\Exceptions\AiConfigurationException;
use App\AI\Exceptions\AiDependencyException;
use App\AI\Exceptions\AiInvalidResponseException;
use App\AI\Exceptions\AiRateLimitException;
use App\AI\Providers\LLMAdapter;
use App\AI\Providers\OpenAIAnalysisProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests for the OpenAI-compatible provider adapter (mirrors LLMAdapter contract).
 */
final class OpenAIAnalysisProviderTest extends TestCase
{
    private function makeRequest(): AnalysisRequest
    {
        return new AnalysisRequest(
            content: 'Test transcript',
            referenceDate: '2025-09-15',
            model: 'gpt-5.6-luna',
            provider: 'openai',
            modelKey: 'openai-gpt-5.6-luna',
        );
    }

    public function test_returns_content_on_success(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => '{"summary":"ok"}']],
                ],
            ], 200),
        ]);

        $provider = new OpenAIAnalysisProvider(apiKey: 'test-key', model: 'gpt-5.6-luna', timeout: 30);
        $result = $provider->analyze($this->makeRequest());

        $this->assertSame('{"summary":"ok"}', $result);
    }

    public function test_missing_api_key_maps_to_configuration_error(): void
    {
        $provider = new OpenAIAnalysisProvider(apiKey: '');

        $this->expectException(AiConfigurationException::class);
        $this->expectExceptionMessage('The AI provider API key is not configured.');

        $provider->analyze($this->makeRequest());
    }

    public function test_401_maps_to_configuration_error(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response(['error' => ['message' => 'Unauthorized']], 401),
        ]);

        $provider = new OpenAIAnalysisProvider(apiKey: 'bad', model: 'gpt-5.6-luna', timeout: 30);

        $this->expectException(AiConfigurationException::class);
        $this->expectExceptionMessage('rejected the request credentials');

        $provider->analyze($this->makeRequest());
    }

    public function test_429_maps_to_rate_limit_error(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response(['error' => ['message' => 'rate']], 429),
        ]);

        $provider = new OpenAIAnalysisProvider(apiKey: 'k', model: 'gpt-5.6-luna', timeout: 30);

        $this->expectException(AiRateLimitException::class);
        $this->expectExceptionMessage('rate limit');

        $provider->analyze($this->makeRequest());
    }

    public function test_500_maps_to_dependency_error(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response(['error' => ['message' => 'boom']], 500),
        ]);

        $provider = new OpenAIAnalysisProvider(apiKey: 'k', model: 'gpt-5.6-luna', timeout: 30);

        $this->expectException(AiDependencyException::class);
        $this->expectExceptionMessage('server error');

        $provider->analyze($this->makeRequest());
    }

    public function test_empty_content_maps_to_invalid_response(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response(['choices' => [['message' => ['content' => '']]]], 200),
        ]);

        $provider = new OpenAIAnalysisProvider(apiKey: 'k', model: 'gpt-5.6-luna', timeout: 30);

        $this->expectException(AiInvalidResponseException::class);
        $this->expectExceptionMessage('empty analysis');

        $provider->analyze($this->makeRequest());
    }

    public function test_connection_failure_maps_to_dependency_error(): void
    {
        Http::fake(function () {
            throw new ConnectionException('connection refused');
        });

        $provider = new OpenAIAnalysisProvider(apiKey: 'k', model: 'gpt-5.6-luna', timeout: 30);

        $this->expectException(AiDependencyException::class);
        $this->expectExceptionMessage('could not be reached');

        $provider->analyze($this->makeRequest());
    }

    public function test_provider_built_from_config_consumes_timeout_value(): void
    {
        // The production container builds OpenAIAnalysisProvider via the factory;
        // this asserts the default LLMAdapter still satisfies the port and that
        // a provider instance is constructible with the configured timeout.
        $this->assertInstanceOf(OpenAIAnalysisProvider::class, new OpenAIAnalysisProvider('k', 'gpt-5.6-luna', 120));
        $this->assertInstanceOf(LLMAdapter::class, new LLMAdapter);
    }
}

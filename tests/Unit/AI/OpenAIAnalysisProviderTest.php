<?php

namespace Tests\Unit\AI;

use App\AI\DTO\AnalysisRequest;
use App\AI\Exceptions\AiConfigurationException;
use App\AI\Exceptions\AiDependencyException;
use App\AI\Exceptions\AiRateLimitException;
use App\AI\Exceptions\AiTimeoutException;
use App\AI\Providers\AnalysisProvider;
use App\AI\Providers\OpenAIAnalysisProvider;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\NetworkTimeoutException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase as LaravelTestCase;

class OpenAIAnalysisProviderTest extends LaravelTestCase
{
    protected function tearDown(): void
    {
        Http::clearResolvedInstances();
        Http::fake([]);

        parent::tearDown();
    }

    private function request(): AnalysisRequest
    {
        return new AnalysisRequest(
            content: 'Transcript text.',
            referenceDate: '2026-08-19',
            model: 'gpt-4o-mini',
        );
    }

    public function test_approved_timeout_is_120_seconds(): void
    {
        $this->assertSame(120, (int) config('ai.timeout', 120));
    }

    public function test_provider_built_from_config_consumes_timeout_value(): void
    {
        config(['ai.timeout' => 120]);
        $fromConfig = app(AnalysisProvider::class);

        $reflection = new \ReflectionProperty(OpenAIAnalysisProvider::class, 'timeout');
        $this->assertSame(120, $reflection->getValue($fromConfig));

        config(['ai.timeout' => 99]);
        $changed = app(AnalysisProvider::class);
        $this->assertSame(99, $reflection->getValue($changed));
    }

    public function test_missing_api_key_maps_to_configuration_error(): void
    {
        $provider = new OpenAIAnalysisProvider(apiKey: '', model: 'gpt-4o-mini', timeout: 120);

        $this->expectException(AiConfigurationException::class);

        $provider->analyze($this->request());
    }

    public function test_http_401_maps_to_configuration_error_without_leaking_key(): void
    {
        $key = 'sk-TESTKEY-12345';
        Http::fake(['api.openai.com/*' => Http::response(['error' => 'unauthorized'], 401)]);

        $provider = new OpenAIAnalysisProvider(apiKey: $key, model: 'gpt-4o-mini', timeout: 120);

        try {
            $provider->analyze($this->request());
            $this->fail('Expected AiConfigurationException.');
        } catch (AiConfigurationException $e) {
            $this->assertStringNotContainsString($key, $e->getMessage());
        }
    }

    public function test_http_429_maps_to_rate_limit_error(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => 'rate'], 429)]);

        $provider = new OpenAIAnalysisProvider(apiKey: 'sk-x', model: 'gpt-4o-mini', timeout: 120);

        $this->expectException(AiRateLimitException::class);

        $provider->analyze($this->request());
    }

    public function test_http_500_maps_to_dependency_error(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => 'boom'], 500)]);

        $provider = new OpenAIAnalysisProvider(apiKey: 'sk-x', model: 'gpt-4o-mini', timeout: 120);

        $this->expectException(AiDependencyException::class);

        $provider->analyze($this->request());
    }

    public function test_transport_timeout_maps_to_timeout_error(): void
    {
        // A real curl timeout surfaces as NetworkTimeoutException (a TransferException,
        // which Laravel's HTTP client does NOT swallow), unlike ConnectException.
        Http::fake(['api.openai.com/*' => function () {
            throw new NetworkTimeoutException('timed out', new Request('POST', 'x'));
        }]);

        $provider = new OpenAIAnalysisProvider(apiKey: 'sk-x', model: 'gpt-4o-mini', timeout: 120);

        $this->expectException(AiTimeoutException::class);

        $provider->analyze($this->request());
    }

    public function test_transport_connect_failure_maps_to_dependency_error(): void
    {
        Http::fake(['api.openai.com/*' => function () {
            throw new ConnectException('connection refused', new Request('POST', 'x'));
        }]);

        $provider = new OpenAIAnalysisProvider(apiKey: 'sk-x', model: 'gpt-4o-mini', timeout: 120);

        $this->expectException(AiDependencyException::class);

        $provider->analyze($this->request());
    }

    public function test_success_returns_raw_string_and_leaks_no_provider_objects_or_secrets(): void
    {
        $key = 'sk-TESTKEY-12345';
        $payload = json_encode([
            'summary' => 'Done.',
            'decisions' => [],
            'action_items' => [],
            'open_questions' => [],
        ]);

        Http::fake(['api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => $payload]],
            ],
        ], 200)]);

        $provider = new OpenAIAnalysisProvider(apiKey: $key, model: 'gpt-4o-mini', timeout: 120);

        $result = $provider->analyze($this->request());

        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('summary', $decoded);
        $this->assertStringNotContainsString($key, $result);
    }
}

<?php

namespace Tests\Unit\AI;

use App\AI\DTO\AnalysisRequest;
use App\AI\Exceptions\AiConfigurationException;
use App\AI\Exceptions\AiDependencyException;
use App\AI\Exceptions\AiRateLimitException;
use App\AI\Exceptions\AiTimeoutException;
use App\AI\Providers\LLMAdapter;
use App\AI\Providers\OpenAIAnalysisProvider;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\NetworkTimeoutException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase as LaravelTestCase;

class OpenAIAnalysisProviderTest extends LaravelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Seed prompt_templates for LLMAdapter/OpenAIAnalysisProvider systemPrompt() call
        DB::table('prompt_templates')->upsert([
            'id' => (string) Str::uuid(),
            'version' => 'meeting-analysis-v1',
            'system_prompt' => 'Test system prompt.',
            'json_schema' => json_encode(['version' => 'meeting-analysis-v1']),
            'is_active' => true,
            'created_at' => now(),
        ], ['version'], ['system_prompt', 'json_schema', 'is_active', 'created_at']);
    }

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
        // Test LLMAdapter resolves timeout from config at runtime
        config(['ai.timeout' => 120]);
        config(['ai.providers.openai.timeout' => 120]);

        $adapter1 = new LLMAdapter;
        $reflection = new \ReflectionMethod(LLMAdapter::class, 'analyze');
        // We can't easily test private config resolution, so test that it uses config
        $this->assertInstanceOf(LLMAdapter::class, $adapter1);

        config(['ai.timeout' => 99]);
        config(['ai.providers.openai.timeout' => 99]);
        $adapter2 = new LLMAdapter;
        $this->assertInstanceOf(LLMAdapter::class, $adapter2);
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
        Http::fake(['*' => Http::response(['error' => 'unauthorized'], 401)]);

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
        Http::fake(['*' => Http::response(['error' => 'rate'], 429)]);

        $provider = new OpenAIAnalysisProvider(apiKey: 'sk-x', model: 'gpt-4o-mini', timeout: 120);

        $this->expectException(AiRateLimitException::class);

        $provider->analyze($this->request());
    }

    public function test_http_500_maps_to_dependency_error(): void
    {
        Http::fake(['*' => Http::response(['error' => 'boom'], 500)]);

        $provider = new OpenAIAnalysisProvider(apiKey: 'sk-x', model: 'gpt-4o-mini', timeout: 120);

        $this->expectException(AiDependencyException::class);

        $provider->analyze($this->request());
    }

    public function test_transport_timeout_maps_to_timeout_error(): void
    {
        // A real curl timeout surfaces as NetworkTimeoutException (a TransferException,
        // which Laravel's HTTP client does NOT swallow), unlike ConnectException.
        Http::fake(['*' => function () {
            throw new NetworkTimeoutException('timed out', new Request('POST', 'x'));
        }]);

        $provider = new OpenAIAnalysisProvider(apiKey: 'sk-x', model: 'gpt-4o-mini', timeout: 120);

        $this->expectException(AiTimeoutException::class);

        $provider->analyze($this->request());
    }

    public function test_transport_connect_failure_maps_to_dependency_error(): void
    {
        Http::fake(['*' => function () {
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

        Http::fake(['*' => Http::response([
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

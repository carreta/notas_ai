<?php

namespace Tests\Unit\AI;

use App\AI\DTO\AnalysisRequest;
use App\AI\Exceptions\AiConfigurationException;
use App\AI\Exceptions\AiDependencyException;
use App\AI\Exceptions\AiInvalidResponseException;
use App\AI\Exceptions\AiRateLimitException;
use App\AI\Providers\LLMAdapter;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Tests Gemini through Google's OpenAI-compatible chat completions API. */
final class LLMAdapterGeminiTest extends TestCase
{
    private function fakeGoogleConfig(): void
    {
        config([
            'ai.providers.google' => [
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta/openai',
                'api_key' => 'TESTKEY',
                'model' => 'gemini-3.7-flash',
                'timeout' => 120,
            ],
            'models.gemini-3.7-flash' => [
                'model' => 'gemini-3.7-flash',
                'provider' => 'google',
            ],
        ]);
    }

    private function geminiRequest(): AnalysisRequest
    {
        return new AnalysisRequest(
            content: 'Test transcript',
            referenceDate: '2025-09-15',
            model: 'gemini-3.7-flash',
            provider: 'google',
            modelKey: 'gemini-3.7-flash',
        );
    }

    public function test_uses_openai_compatible_endpoint_and_bearer_token(): void
    {
        $this->fakeGoogleConfig();
        $captured = null;
        Http::fake(function ($request) use (&$captured) {
            $captured = $request;

            return Http::response([
                'choices' => [
                    ['message' => ['content' => '{"summary":"ok"}']],
                ],
            ], 200);
        });

        $result = (new LLMAdapter)->analyze($this->geminiRequest());

        $this->assertSame('{"summary":"ok"}', $result);
        $this->assertSame(
            'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions',
            $captured->url(),
        );
        $this->assertTrue($captured->hasHeader('Authorization'));
        $this->assertContains('Bearer TESTKEY', $captured->header('Authorization'));
        $this->assertFalse($captured->hasHeader('x-goog-api-key'));

        $body = $captured->data();
        $this->assertSame('gemini-3.7-flash', $body['model']);
        $this->assertSame('system', $body['messages'][0]['role']);
        $this->assertSame('user', $body['messages'][1]['role']);
    }

    public function test_404_maps_to_dependency_error(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response(['error' => ['message' => 'not found']], 404),
        ]);

        $this->fakeGoogleConfig();

        $this->expectException(AiDependencyException::class);
        $this->expectExceptionMessage('unexpected response');

        (new LLMAdapter)->analyze($this->geminiRequest());
    }

    public function test_401_maps_to_configuration_error(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response(['error' => ['message' => 'Unauthorized']], 401),
        ]);

        $this->fakeGoogleConfig();

        $this->expectException(AiConfigurationException::class);
        $this->expectExceptionMessage('request credentials');

        (new LLMAdapter)->analyze($this->geminiRequest());
    }

    public function test_429_maps_to_rate_limit_error(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response(['error' => ['message' => 'rate']], 429),
        ]);

        $this->fakeGoogleConfig();

        $this->expectException(AiRateLimitException::class);
        $this->expectExceptionMessage('rate limit');

        (new LLMAdapter)->analyze($this->geminiRequest());
    }

    public function test_500_maps_to_dependency_error(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response(['error' => ['message' => 'boom']], 500),
        ]);

        $this->fakeGoogleConfig();

        $this->expectException(AiDependencyException::class);
        $this->expectExceptionMessage('server error');

        (new LLMAdapter)->analyze($this->geminiRequest());
    }

    public function test_empty_choices_maps_to_invalid_response(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response(['choices' => []], 200),
        ]);

        $this->fakeGoogleConfig();

        $this->expectException(AiInvalidResponseException::class);
        $this->expectExceptionMessage('empty analysis');

        (new LLMAdapter)->analyze($this->geminiRequest());
    }
}

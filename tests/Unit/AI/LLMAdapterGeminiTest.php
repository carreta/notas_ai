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

/**
 * Tests that LLMAdapter routes Google Gemini to the native generateContent
 * endpoint (x-goog-api-key, candidates.0.content.parts.0.text) while leaving
 * the OpenAI/LM Studio path untouched.
 */
final class LLMAdapterGeminiTest extends TestCase
{
    private function fakeGoogleConfig(): void
    {
        config([
            'ai.providers.google' => [
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
                'api_key' => 'TESTKEY',
                'model' => 'gemini-3.6-flash',
                'timeout' => 120,
            ],
        ]);
    }

    private function geminiRequest(): AnalysisRequest
    {
        return new AnalysisRequest(
            content: 'Test transcript',
            referenceDate: '2025-09-15',
            model: 'gemini-3.6-flash',
            provider: 'google',
            modelKey: 'google',
        );
    }

    public function test_uses_native_gemini_endpoint_and_header(): void
    {
        $this->fakeGoogleConfig();
        $captured = null;
        Http::fake(function ($request) use (&$captured) {
            $captured = $request;

            return Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '{"summary":"ok"}']]]],
                ],
            ], 200);
        });

        $result = (new LLMAdapter)->analyze($this->geminiRequest());

        $this->assertSame('{"summary":"ok"}', $result);
        $this->assertStringContainsString('/models/gemini-3.6-flash:generateContent', $captured->url());
        $this->assertTrue($captured->hasHeader('x-goog-api-key'));
        $this->assertContains('TESTKEY', $captured->header('x-goog-api-key'));
        $this->assertFalse($captured->hasHeader('Authorization'));
        $this->assertStringNotContainsString('/openai/', $captured->url());

        $body = $captured->data();
        $this->assertArrayHasKey('systemInstruction', $body);
        $this->assertArrayHasKey('contents', $body);
        $this->assertSame('application/json', $body['generationConfig']['responseMimeType']);
    }

    public function test_404_maps_to_configuration_error(): void
    {
        Http::fake([
            '*/models/*:generateContent' => Http::response(['error' => ['message' => 'not found']], 404),
        ]);

        $this->fakeGoogleConfig();

        $this->expectException(AiConfigurationException::class);
        $this->expectExceptionMessage('request configuration');

        (new LLMAdapter)->analyze($this->geminiRequest());
    }

    public function test_401_maps_to_configuration_error(): void
    {
        Http::fake([
            '*/models/*:generateContent' => Http::response(['error' => ['message' => 'Unauthorized']], 401),
        ]);

        $this->fakeGoogleConfig();

        $this->expectException(AiConfigurationException::class);
        $this->expectExceptionMessage('request credentials');

        (new LLMAdapter)->analyze($this->geminiRequest());
    }

    public function test_429_maps_to_rate_limit_error(): void
    {
        Http::fake([
            '*/models/*:generateContent' => Http::response(['error' => ['message' => 'rate']], 429),
        ]);

        $this->fakeGoogleConfig();

        $this->expectException(AiRateLimitException::class);
        $this->expectExceptionMessage('rate limit');

        (new LLMAdapter)->analyze($this->geminiRequest());
    }

    public function test_500_maps_to_dependency_error(): void
    {
        Http::fake([
            '*/models/*:generateContent' => Http::response(['error' => ['message' => 'boom']], 500),
        ]);

        $this->fakeGoogleConfig();

        $this->expectException(AiDependencyException::class);
        $this->expectExceptionMessage('server error');

        (new LLMAdapter)->analyze($this->geminiRequest());
    }

    public function test_empty_candidates_maps_to_invalid_response(): void
    {
        Http::fake([
            '*/models/*:generateContent' => Http::response(['candidates' => []], 200),
        ]);

        $this->fakeGoogleConfig();

        $this->expectException(AiInvalidResponseException::class);
        $this->expectExceptionMessage('empty analysis');

        (new LLMAdapter)->analyze($this->geminiRequest());
    }
}

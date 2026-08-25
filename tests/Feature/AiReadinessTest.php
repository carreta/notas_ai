<?php

namespace Tests\Feature;

use App\AI\AiReadiness;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AiReadinessTest extends TestCase
{
    public function test_fake_driver_is_always_ready(): void
    {
        Config::set('ai.driver', 'fake');

        $result = app(AiReadiness::class)->checkActive();

        $this->assertTrue($result->ready);
        $this->assertSame('fake', $result->driver);
        $this->assertSame([], $result->issues);
    }

    public function test_openai_missing_api_key_is_not_ready(): void
    {
        Config::set('ai.driver', 'llm');
        Config::set('ai.provider', 'openai');
        Config::set('ai.providers.openai.api_key', '');

        $result = app(AiReadiness::class)->checkProvider('openai');

        $this->assertFalse($result->ready);
        $this->assertStringContainsString('OPENAI_API_KEY', $result->issues[0]);
    }

    public function test_openai_with_key_and_model_is_ready(): void
    {
        Config::set('ai.driver', 'llm');
        Config::set('ai.providers.openai.api_key', 'sk-test');
        Config::set('ai.providers.openai.model', 'gpt-x');
        Config::set('ai.providers.openai.base_url', 'https://api.openai.com/v1');

        $result = app(AiReadiness::class)->checkProvider('openai');

        $this->assertTrue($result->ready);
        $this->assertSame([], $result->issues);
    }

    public function test_google_gemini_missing_key_is_not_ready(): void
    {
        Config::set('ai.driver', 'llm');
        Config::set('ai.providers.google.api_key', '');

        $result = app(AiReadiness::class)->checkProvider('google');

        $this->assertFalse($result->ready);
        $this->assertStringContainsString('GOOGLE_AI_API_KEY', $result->issues[0]);
    }

    public function test_google_gemini_with_key_is_ready(): void
    {
        Config::set('ai.driver', 'llm');
        Config::set('ai.providers.google.api_key', 'gemini-key');
        Config::set('ai.providers.google.model', 'gemini-x');
        Config::set('ai.providers.google.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai');

        $result = app(AiReadiness::class)->checkProvider('google');

        $this->assertTrue($result->ready);
    }

    public function test_lmstudio_keyless_is_ready_with_defaults(): void
    {
        Config::set('ai.driver', 'llm');

        // LM Studio is keyless; only base_url and model are required (both defaulted).
        $result = app(AiReadiness::class)->checkProvider('lmstudio');

        $this->assertTrue($result->ready);
    }

    public function test_unknown_provider_is_not_ready(): void
    {
        Config::set('ai.driver', 'llm');

        $result = app(AiReadiness::class)->checkProvider('does-not-exist');

        $this->assertFalse($result->ready);
        $this->assertStringContainsString("Provider 'does-not-exist' is not configured", $result->issues[0]);
    }

    public function test_command_reports_ready_and_exits_zero(): void
    {
        Config::set('ai.driver', 'fake');
        Config::set('ai.provider', 'openai');

        $exit = Artisan::call('ai:status');
        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('READY', $output);
    }

    public function test_command_reports_not_ready_and_exits_one_without_leaking_secret(): void
    {
        Config::set('ai.driver', 'llm');
        Config::set('ai.provider', 'openai');
        Config::set('ai.providers.openai.api_key', '');

        // A deliberately fake secret value; the command must never print it.
        Config::set('ai.providers.openai.model', 'super-secret-model-value-xyz');

        $exit = Artisan::call('ai:status');
        $output = Artisan::output();

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('NOT READY', $output);
        $this->assertStringContainsString('OPENAI_API_KEY', $output);
        $this->assertStringNotContainsString('super-secret-model-value-xyz', $output);
    }
}

<?php

namespace Tests\Unit\AI;

use App\AI\DTO\AnalysisRequest;
use App\AI\DTO\AnalysisResult;
use App\AI\Exceptions\AiConfigurationException;
use App\AI\Exceptions\AiDependencyException;
use App\AI\Exceptions\AiInvalidResponseException;
use App\AI\Exceptions\AiRateLimitException;
use App\AI\Exceptions\AiTimeoutException;
use App\AI\Providers\FakeAnalysisProvider;
use App\AI\StructuredAnalysisProcessor;
use PHPUnit\Framework\TestCase;

class FakeAnalysisProviderTest extends TestCase
{
    public function test_valid_outcome_returns_accepted_structured_response(): void
    {
        $provider = new FakeAnalysisProvider;
        $provider->setOutcome('valid');

        $raw = $provider->analyze(new AnalysisRequest('transcript'));

        $result = (new StructuredAnalysisProcessor)->process($raw);

        $this->assertInstanceOf(AnalysisResult::class, $result);
        $this->assertSame('Fake analysis summary.', $result->summary);
    }

    public function test_invalid_response_outcome_triggers_ai_invalid_response(): void
    {
        $provider = new FakeAnalysisProvider;
        $provider->setOutcome('invalid_response');

        $this->expectException(AiInvalidResponseException::class);

        (new StructuredAnalysisProcessor)->process($provider->analyze(new AnalysisRequest('x')));
    }

    public function test_configuration_outcome_throws_configuration_exception(): void
    {
        $provider = new FakeAnalysisProvider;
        $provider->setOutcome('config');

        $this->expectException(AiConfigurationException::class);

        $provider->analyze(new AnalysisRequest('x'));
    }

    public function test_dependency_outcome_throws_dependency_exception(): void
    {
        $provider = new FakeAnalysisProvider;
        $provider->setOutcome('dependency');

        $this->expectException(AiDependencyException::class);

        $provider->analyze(new AnalysisRequest('x'));
    }

    public function test_timeout_outcome_throws_timeout_exception(): void
    {
        $provider = new FakeAnalysisProvider;
        $provider->setOutcome('timeout');

        $this->expectException(AiTimeoutException::class);

        $provider->analyze(new AnalysisRequest('x'));
    }

    public function test_rate_limit_outcome_throws_rate_limit_exception(): void
    {
        $provider = new FakeAnalysisProvider;
        $provider->setOutcome('rate_limit');

        $this->expectException(AiRateLimitException::class);

        $provider->analyze(new AnalysisRequest('x'));
    }

    public function test_internal_outcome_throws_generic_throwable(): void
    {
        $provider = new FakeAnalysisProvider;
        $provider->setOutcome('internal');

        $this->expectException(\RuntimeException::class);

        $provider->analyze(new AnalysisRequest('x'));
    }

    public function test_fake_never_performs_network_calls(): void
    {
        $provider = new FakeAnalysisProvider;
        $provider->setOutcome('valid');

        $start = microtime(true);
        $provider->analyze(new AnalysisRequest('x'));
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(1.0, $elapsed, 'Fake provider must be deterministic and offline.');
    }
}

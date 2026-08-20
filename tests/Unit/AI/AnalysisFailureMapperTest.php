<?php

namespace Tests\Unit\AI;

use App\AI\Exceptions\AiConfigurationException;
use App\AI\Exceptions\AiDependencyException;
use App\AI\Exceptions\AiInvalidResponseException;
use App\AI\Exceptions\AiRateLimitException;
use App\AI\Exceptions\AiTimeoutException;
use App\AI\Failure\AnalysisFailure;
use App\AI\Failure\AnalysisFailureMapper;
use App\AI\Failure\FailureCategory;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\TestCase;

class AnalysisFailureMapperTest extends TestCase
{
    public function test_configuration_maps_to_ai_configuration_error(): void
    {
        $failure = AnalysisFailureMapper::map(new AiConfigurationException('raw detail'));

        $this->assertSame(FailureCategory::AI_CONFIGURATION_ERROR, $failure->category);
        $this->assertSame('AI analysis is temporarily unavailable.', $failure->userMessage);
    }

    public function test_dependency_maps_to_ai_dependency_error(): void
    {
        $failure = AnalysisFailureMapper::map(new AiDependencyException('raw detail'));

        $this->assertSame(FailureCategory::AI_DEPENDENCY_ERROR, $failure->category);
        $this->assertSame('The AI service is temporarily unavailable.', $failure->userMessage);
    }

    public function test_timeout_maps_to_ai_timeout(): void
    {
        $failure = AnalysisFailureMapper::map(new AiTimeoutException('raw detail'));

        $this->assertSame(FailureCategory::AI_TIMEOUT, $failure->category);
        $this->assertSame('The analysis took too long. Please try again.', $failure->userMessage);
    }

    public function test_rate_limit_maps_to_ai_rate_limit(): void
    {
        $failure = AnalysisFailureMapper::map(new AiRateLimitException('raw detail'));

        $this->assertSame(FailureCategory::AI_RATE_LIMIT, $failure->category);
        $this->assertSame('The AI service is temporarily busy. Please try again later.', $failure->userMessage);
    }

    public function test_invalid_response_maps_to_ai_invalid_response(): void
    {
        $failure = AnalysisFailureMapper::map(new AiInvalidResponseException('raw detail'));

        $this->assertSame(FailureCategory::AI_INVALID_RESPONSE, $failure->category);
        $this->assertSame('The analysis could not be processed safely.', $failure->userMessage);
    }

    public function test_persistence_exception_maps_to_persistence_error(): void
    {
        $exception = new QueryException('pgsql', 'SELECT 1', [], new \Exception('SQLSTATE[08006]'));

        $failure = AnalysisFailureMapper::map($exception);

        $this->assertSame(FailureCategory::PERSISTENCE_ERROR, $failure->category);
        $this->assertSame('The analysis could not be saved.', $failure->userMessage);
    }

    public function test_unexpected_throwable_maps_to_internal_error(): void
    {
        $failure = AnalysisFailureMapper::map(new \RuntimeException('boom'));

        $this->assertSame(FailureCategory::INTERNAL_ERROR, $failure->category);
        $this->assertSame('The analysis could not be completed.', $failure->userMessage);
    }

    public function test_every_category_is_an_approved_value(): void
    {
        $cases = [
            new AiConfigurationException,
            new AiDependencyException,
            new AiTimeoutException,
            new AiRateLimitException,
            new AiInvalidResponseException,
            new QueryException('pgsql', 'SELECT 1', [], new \Exception),
            new \RuntimeException,
        ];

        foreach ($cases as $case) {
            $category = AnalysisFailureMapper::map($case)->category;

            $this->assertContains($category, FailureCategory::all(), (string) $case::class);
        }
    }

    public function test_user_message_never_exposes_internal_details(): void
    {
        $secret = 'sk-secret-value';
        $exception = new AiDependencyException($secret);

        $message = AnalysisFailureMapper::map($exception)->userMessage;

        $this->assertStringNotContainsString($secret, $message);
        $this->assertStringNotContainsString('Exception', $message);
        $this->assertStringNotContainsString('SQLSTATE', $message);
        $this->assertStringNotContainsString('stack', $message);
        $this->assertStringNotContainsString('Bearer', $message);
    }

    public function test_returned_failure_is_a_value_object(): void
    {
        $this->assertInstanceOf(AnalysisFailure::class, AnalysisFailureMapper::map(new \RuntimeException));
    }
}

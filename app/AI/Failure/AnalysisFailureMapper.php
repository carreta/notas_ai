<?php

namespace App\AI\Failure;

use App\AI\Exceptions\AiConfigurationException;
use App\AI\Exceptions\AiDependencyException;
use App\AI\Exceptions\AiInvalidResponseException;
use App\AI\Exceptions\AiRateLimitException;
use App\AI\Exceptions\AiTimeoutException;
use Illuminate\Database\QueryException;
use PDOException;
use Throwable;

/**
 * Centralized, single-source-of-truth mapping from any Throwable raised during
 * analysis into an application-owned, safe {@see AnalysisFailure}.
 *
 * No other controller, service, or adapter may translate errors into categories.
 * The mapping rules are:
 *
 *  - AiConfigurationException  -> AI_CONFIGURATION_ERROR
 *  - AiDependencyException     -> AI_DEPENDENCY_ERROR
 *  - AiTimeoutException        -> AI_TIMEOUT
 *  - AiRateLimitException      -> AI_RATE_LIMIT
 *  - AiInvalidResponseException-> AI_INVALID_RESPONSE
 *  - QueryException / PDOException (persistence) -> PERSISTENCE_ERROR
 *  - any other Throwable       -> INTERNAL_ERROR
 */
final class AnalysisFailureMapper
{
    public static function map(Throwable $e): AnalysisFailure
    {
        if ($e instanceof AiConfigurationException) {
            return new AnalysisFailure(
                FailureCategory::AI_CONFIGURATION_ERROR,
                'AI analysis is temporarily unavailable.',
            );
        }

        if ($e instanceof AiRateLimitException) {
            return new AnalysisFailure(
                FailureCategory::AI_RATE_LIMIT,
                'The AI service is temporarily busy. Please try again later.',
            );
        }

        if ($e instanceof AiTimeoutException) {
            return new AnalysisFailure(
                FailureCategory::AI_TIMEOUT,
                'The analysis took too long. Please try again.',
            );
        }

        if ($e instanceof AiDependencyException) {
            return new AnalysisFailure(
                FailureCategory::AI_DEPENDENCY_ERROR,
                'The AI service is temporarily unavailable.',
            );
        }

        if ($e instanceof AiInvalidResponseException) {
            return new AnalysisFailure(
                FailureCategory::AI_INVALID_RESPONSE,
                'The analysis could not be processed safely.',
            );
        }

        if ($e instanceof QueryException || $e instanceof PDOException) {
            return new AnalysisFailure(
                FailureCategory::PERSISTENCE_ERROR,
                'The analysis could not be saved.',
            );
        }

        return new AnalysisFailure(
            FailureCategory::INTERNAL_ERROR,
            'The analysis could not be completed.',
        );
    }
}

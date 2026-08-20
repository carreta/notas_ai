<?php

namespace App\AI\Providers;

use App\AI\DTO\AnalysisRequest;
use App\AI\Exceptions\AiInvalidResponseException;
use App\AI\Exceptions\AiProviderException;

/**
 * Provider-neutral AI analysis port (TD-002 / TD-009).
 *
 * The application depends only on this contract. Provider SDK request/response
 * structures must never leak through it: {@see analyze()} returns the raw,
 * provider-independent analysis content (a JSON string) for the FR-004
 * pipeline to validate, and raises only application-owned exceptions on failure.
 */
interface AnalysisProvider
{
    /**
     * Analyze a meeting transcript and return the raw analysis content.
     *
     * @throws AiProviderException
     * @throws AiInvalidResponseException
     */
    public function analyze(AnalysisRequest $request): string;
}

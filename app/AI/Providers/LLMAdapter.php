<?php

namespace App\AI\Providers;

use App\AI\DTO\AnalysisRequest;
use App\AI\Exceptions\AiConfigurationException;
use App\AI\Exceptions\AiDependencyException;
use App\AI\Exceptions\AiInvalidResponseException;
use App\AI\Exceptions\AiRateLimitException;
use App\AI\Exceptions\AiTimeoutException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ConnectTimeoutException;
use GuzzleHttp\Exception\NetworkTimeoutException;
use GuzzleHttp\Exception\ResponseTimeoutException;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // Remove after logging is no longer needed
use Throwable;

/**
 * Provider-neutral LLM adapter behind the provider-neutral port (TD-002 / TD-009).
 *
 * Supports any OpenAI-compatible provider (OpenAI, LM Studio, local models via
 * Ollama/vLLM, etc.) via configurable base_url, api_key, and model.
 *
 * Provider-specific concerns stay inside this class:
 *  - request construction uses the active prompt template + application input;
 *  - the approved fixed 120-second timeout is enforced on the HTTP request;
 *  - provider/transport errors are converted to application-owned exceptions;
 *  - the API key is read only from configuration and is never persisted/logged.
 *
 * No OpenAI SDK type leaves this adapter.
 */
final class LLMAdapter implements AnalysisProvider
{
    {
        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][LLMAdapter] analyze() started', [
            'request_provider' => $request->provider,
            'request_model' => $request->model,
            'request_model_key' => $request->modelKey,
            'request_schema_version' => $request->schemaVersion,
            'content_length' => mb_strlen($request->content),
        ]);

        // Resolve provider config at RUNTIME from the request's provider/modelKey
        // This allows per-request provider selection (user chooses model in UI).
        $provider = $request->provider ?? Config::get('ai.provider', 'openai');
        $modelKey = $request->modelKey ?? Config::get('ai.model', 'gpt-5.6-luna');

        $providerConfig = Config::get("ai.providers.{$provider}") ?? [];
        $modelConfig = Config::get("models.{$modelKey}") ?? [];

        $baseUrl = $providerConfig['base_url'] ?? Config::get('ai.providers.openai.base_url', 'https://api.openai.com/v1');
        $apiKey = $providerConfig['api_key'] ?? Config::get('ai.providers.openai.api_key', '');
        $model = $modelConfig['model'] ?? $providerConfig['model'] ?? Config::get('ai.model', 'gpt-5.6-luna');
        $timeout = (int) ($providerConfig['timeout'] ?? Config::get('ai.timeout', 120));

        // Read temperature from model config (optional, null = omit from request)
        $temperature = $modelConfig['temperature'] ?? $providerConfig['temperature'] ?? null;

        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][LLMAdapter] Resolved configuration', [
            'provider' => $provider,
            'model_key' => $modelKey,
            'model_config' => $modelConfig,
            'base_url' => $baseUrl,
            'api_key_present' => ! empty($apiKey),
            'api_key_length' => mb_strlen($apiKey),
            'model' => $model,
            'timeout' => $timeout,
            'temperature' => $temperature,
        ]);

        if ($apiKey === '') {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][LLMAdapter] API key is empty', [
                'provider' => $provider,
            ]);
            throw new AiConfigurationException('The AI provider API key is not configured.');
        }
        
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user', 'content' => $this->buildUserPrompt($request)],
            ],
            'temperature' => 0,
        ];
        
        // Only add temperature if explicitly configured (not null)
        if ($temperature !== null) {
            $payload['temperature'] = $temperature;
        }

        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][LLMAdapter] Request payload built', [
            'model' => $payload['model'],
            'system_prompt_length' => mb_strlen($payload['messages'][0]['content']),
            'user_prompt_length' => mb_strlen($payload['messages'][1]['content']),
            'temperature' => $payload['temperature'] ?? 'not_set',
        ]);

        try {
            // Extend PHP max execution time to cover the HTTP timeout + buffer
            // This prevents "Maximum execution time of 30 seconds exceeded" fatal errors
            // when local models take longer than PHP's default limit.

            @set_time_limit($timeout + 30);
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][LLMAdapter] Making HTTP request', [
                'url' => $baseUrl.'/chat/completions',
                'timeout' => $timeout,
            ]);
            $response = Http::withToken($apiKey)
                ->timeout($timeout)
                ->post($baseUrl.'/chat/completions', $payload);
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][LLMAdapter] HTTP request completed', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);
        } catch (ConnectionException|RequestException $e) {
            $this->logTransportFailure($provider, $model, $baseUrl, $e);

            // Laravel wraps transport failures (timeouts, connection refused) into
            // a ConnectionException whose previous exception is the original
            // Guzzle exception. Inspect it to classify timeouts vs dependencies.
            $previous = $e->getPrevious();
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::warning('[TEMP][LLMAdapter] ConnectionException/RequestException caught', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'previous_exception' => $previous ? $previous::class : null,
                'previous_message' => $previous ? $previous->getMessage() : null,
            ]);
            if ($this->isTimeout($previous)) {
                // TODO: Revert once testing is sufficient - remove temporary logging
                Log::error('[TEMP][LLMAdapter] Classified as timeout');
                throw new AiTimeoutException('The AI provider request timed out.', $e);
            }

            if ($previous instanceof ConnectException) {
                // TODO: Revert once testing is sufficient - remove temporary logging
                Log::error('[TEMP][LLMAdapter] Classified as connection failure (ConnectException)');
                throw new AiDependencyException('The AI provider could not be reached.', $e);
            }

            // Other transport/HTTP failures: the AI service is unreachable.
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][LLMAdapter] Classified as dependency error');
            throw new AiDependencyException('The AI provider could not be reached.', $e);
        } catch (TransferException $e) {
            $this->logTransportFailure($provider, $model, $baseUrl, $e);

            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::warning('[TEMP][LLMAdapter] TransferException caught', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            if ($this->isTimeout($e)) {
                // TODO: Revert once testing is sufficient - remove temporary logging
                Log::error('[TEMP][LLMAdapter] Classified as timeout');
                throw new AiTimeoutException('The AI provider request timed out.', $e);
            }

            if ($e instanceof ConnectException) {
                // TODO: Revert once testing is sufficient - remove temporary logging
                Log::error('[TEMP][LLMAdapter] Classified as connection failure (ConnectException)');
                throw new AiDependencyException('The AI provider could not be reached.', $e);
            }

            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][LLMAdapter] Classified as dependency error');
            throw new AiDependencyException('The AI provider could not be reached.', $e);
        } catch (Throwable $e) {
            $this->logTransportFailure($provider, $model, $baseUrl, $e);

            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][LLMAdapter] Unexpected Throwable caught', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new AiDependencyException('The AI provider could not be reached.', $e);
        }

        $status = $response->status();

        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][LLMAdapter] Response received', [
            'status' => $status,
            'successful' => $response->successful(),
            'headers' => $response->headers(),
        ]);

        if (! $response->successful()) {
            $error = $response->json('error');
            $this->logHttpFailure(
                $provider,
                $model,
                $baseUrl,
                $status,
                is_array($error) ? $error : null,
            );
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::warning('[TEMP][LLMAdapter] Unsuccessful response', [
                'status' => $status,
                'error' => $error,
            ]);
        }

        if ($status === 401 || $status === 403) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][LLMAdapter] Authentication failed (401/403)');
            throw new AiConfigurationException('The AI provider rejected the request credentials.');
        }

        if ($status === 429) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][LLMAdapter] Rate limit exceeded (429)');
            throw new AiRateLimitException('The AI provider rate limit was exceeded.');
        }

        if ($status >= 500) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][LLMAdapter] Server error (5xx)');
            throw new AiDependencyException('The AI provider returned a server error.');
        }

        if (! $response->successful()) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][LLMAdapter] Unexpected unsuccessful response');
            throw new AiDependencyException('The AI provider returned an unexpected response.');
        }
        $content = $response->json('choices.0.message.content');

        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][LLMAdapter] Response content extracted', [
            'content_length' => is_string($content) ? mb_strlen($content) : 0,
            'content_preview' => is_string($content) ? substr($content, 0, 200) : 'null',
        ]);

        if (! is_string($content) || $content === '') {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][LLMAdapter] Empty or invalid content from provider');
            throw new AiInvalidResponseException('The AI provider returned an empty analysis.');
        }

        // Extract token usage from response (best-effort; never break the primary flow)
        $promptTokens = null;
        $completionTokens = null;
        $totalTokens = null;

        try {
            $usage = $response->json('usage');
            if (is_array($usage)) {
                $promptTokens = $usage['prompt_tokens'] ?? null;
                $completionTokens = $usage['completion_tokens'] ?? null;
                $totalTokens = $usage['total_tokens'] ?? null;
            }
        } catch (Throwable) {
            // Intentionally ignored: token extraction is "nice to have" and must not
            // fail the analysis. Log at debug level if needed.
        }

        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][LLMAdapter] analyze() completed successfully', [
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
        ]);

        return new AnalysisProviderResult(
            content: $content,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            totalTokens: $totalTokens,
        );
    }

    private function isTimeout(?Throwable $e): bool
    {
        return $e instanceof ConnectTimeoutException
            || $e instanceof NetworkTimeoutException
            || $e instanceof ResponseTimeoutException;
    }

    /**
     * Log diagnostics required to investigate provider failures without
     * recording credentials, request headers, prompts, or transcripts.
     *
     * @param  array<string, mixed>|null  $providerError
     */
    private function logHttpFailure(string $provider, string $model, string $baseUrl, int $status, ?array $providerError): void
    {
        Log::warning('AI provider returned an unsuccessful HTTP response.', [
            'provider' => $provider,
            'model' => $model,
            'base_url' => rtrim($baseUrl, '/'),
            'status' => $status,
            'provider_error_code' => $providerError['code'] ?? null,
            'provider_error_status' => $providerError['status'] ?? null,
            'provider_error_message' => isset($providerError['message'])
                ? str($providerError['message'])->limit(500)->toString()
                : null,
        ]);
    }

    private function logTransportFailure(string $provider, string $model, string $baseUrl, Throwable $exception): void
    {
        $previous = $exception->getPrevious();

        Log::warning('AI provider transport request failed.', [
            'provider' => $provider,
            'model' => $model,
            'base_url' => rtrim($baseUrl, '/'),
            'exception' => $exception::class,
            'exception_message' => str($exception->getMessage())->limit(500)->toString(),
            'previous_exception' => $previous ? $previous::class : null,
            'previous_exception_message' => $previous
                ? str($previous->getMessage())->limit(500)->toString()
                : null,
        ]);
    }

    private function systemPrompt(): string
    {
        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][LLMAdapter] Fetching system prompt from database');
        /** @var mixed $row */
        $row = DB::table('prompt_templates')
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->value('system_prompt');

        // original: return is_string($row)
        $prompt = is_string($row)
            ? $row
            : 'You are an assistant that analyzes meeting transcripts and returns strict JSON matching the meeting-analysis schema.';

        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][LLMAdapter] System prompt retrieved', [
            'from_database' => is_string($row),
            'length' => mb_strlen($prompt),
        ]);

        return $prompt;
    }

    private function buildUserPrompt(AnalysisRequest $request): string
    {
        $prompt = "Analyze the following meeting transcript and return structured JSON.\n\n";
        $prompt .= 'Transcript:'."\n".$request->content;

        if ($request->referenceDate !== null) {
            $prompt .= "\n\nMeeting date: ".$request->referenceDate;
        }

        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][LLMAdapter] User prompt built', [
            'content_length' => mb_strlen($request->content),
            'reference_date' => $request->referenceDate,
            'total_length' => mb_strlen($prompt),
        ]);

        return $prompt;
    }
}

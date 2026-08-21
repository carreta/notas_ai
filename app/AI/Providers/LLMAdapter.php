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
    public function analyze(AnalysisRequest $request): string
    {
        // Resolve provider config at RUNTIME from the request's provider/modelKey
        // This allows per-request provider selection (user chooses model in UI).
        $provider = $request->provider ?? Config::get('ai.provider', 'openai');
        $modelKey = $request->modelKey ?? Config::get('ai.provider', 'openai');

        $providerConfig = Config::get("ai.providers.{$provider}") ?? [];
        $modelConfig = Config::get("models.{$modelKey}") ?? [];

        $baseUrl = $providerConfig['base_url'] ?? Config::get('ai.providers.openai.base_url', 'https://api.openai.com/v1');
        $apiKey = $providerConfig['api_key'] ?? Config::get('ai.providers.openai.api_key', '');
        $model = $modelConfig['model'] ?? $providerConfig['model'] ?? Config::get('ai.model', 'gpt-4o-mini');
        $timeout = (int) ($providerConfig['timeout'] ?? Config::get('ai.timeout', 120));

        if ($apiKey === '') {
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

        try {
            // Extend PHP max execution time to cover the HTTP timeout + buffer
            // This prevents "Maximum execution time of 30 seconds exceeded" fatal errors
            // when local models take longer than PHP's default limit.
            @set_time_limit($timeout + 30);

            $response = Http::withToken($apiKey)
                ->timeout($timeout)
                ->post($baseUrl.'/chat/completions', $payload);
        } catch (ConnectionException|RequestException $e) {
            // Laravel wraps transport failures (timeouts, connection refused) into
            // a ConnectionException whose previous exception is the original
            // Guzzle exception. Inspect it to classify timeouts vs dependencies.
            $previous = $e->getPrevious();

            if ($this->isTimeout($previous)) {
                throw new AiTimeoutException('The AI provider request timed out.', $e);
            }

            if ($previous instanceof ConnectException) {
                throw new AiDependencyException('The AI provider could not be reached.', $e);
            }

            // Other transport/HTTP failures: the AI service is unreachable.
            throw new AiDependencyException('The AI provider could not be reached.', $e);
        } catch (TransferException $e) {
            if ($this->isTimeout($e)) {
                throw new AiTimeoutException('The AI provider request timed out.', $e);
            }

            if ($e instanceof ConnectException) {
                throw new AiDependencyException('The AI provider could not be reached.', $e);
            }

            throw new AiDependencyException('The AI provider could not be reached.', $e);
        } catch (Throwable $e) {
            throw new AiDependencyException('The AI provider could not be reached.', $e);
        }

        $status = $response->status();

        if ($status === 401 || $status === 403) {
            throw new AiConfigurationException('The AI provider rejected the request credentials.');
        }

        if ($status === 429) {
            throw new AiRateLimitException('The AI provider rate limit was exceeded.');
        }

        if ($status >= 500) {
            throw new AiDependencyException('The AI provider returned a server error.');
        }

        if (! $response->successful()) {
            throw new AiDependencyException('The AI provider returned an unexpected response.');
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || $content === '') {
            throw new AiInvalidResponseException('The AI provider returned an empty analysis.');
        }

        return $content;
    }

    private function isTimeout(?Throwable $e): bool
    {
        return $e instanceof ConnectTimeoutException
            || $e instanceof NetworkTimeoutException
            || $e instanceof ResponseTimeoutException;
    }

    private function systemPrompt(): string
    {
        /** @var mixed $row */
        $row = DB::table('prompt_templates')
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->value('system_prompt');

        return is_string($row)
            ? $row
            : 'You are an assistant that analyzes meeting transcripts and returns strict JSON matching the meeting-analysis schema.';
    }

    private function buildUserPrompt(AnalysisRequest $request): string
    {
        $prompt = "Analyze the following meeting transcript and return structured JSON.\n\n";
        $prompt .= 'Transcript:'."\n".$request->content;

        if ($request->referenceDate !== null) {
            $prompt .= "\n\nMeeting date: ".$request->referenceDate;
        }

        return $prompt;
    }
}

<?php

namespace App\Providers;

use App\AI\Providers\AnalysisProvider;
use App\AI\Providers\FakeAnalysisProvider;
use App\AI\Providers\OpenAIAnalysisProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the provider-neutral AI port to a concrete adapter.
        // Default is the production OpenAI adapter. A development-only 'fake'
        // driver (AI_DRIVER=env) selects FakeAnalysisProvider, which never calls
        // OpenAI and is used to deterministically demonstrate FR-005 failure
        // handling. Production behavior is unchanged unless AI_DRIVER is set.
        $this->app->bind(AnalysisProvider::class, function (): AnalysisProvider {
            if (config('ai.driver', 'openai') === 'fake') {
                $fake = new FakeAnalysisProvider;
                $fake->setOutcome((string) config('ai.fake_outcome', 'valid'));

                return $fake;
            }

            return new OpenAIAnalysisProvider(
                apiKey: (string) config('ai.api_key', ''),
                model: (string) config('ai.model', 'gpt-4o-mini'),
                timeout: (int) config('ai.timeout', 120),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

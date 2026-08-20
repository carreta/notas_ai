<?php

namespace App\Providers;

use App\AI\Providers\AnalysisProvider;
use App\AI\Providers\OpenAIAnalysisProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the provider-neutral AI port to the production OpenAI adapter.
        // Tests supply their own provider (e.g. FakeAnalysisProvider) directly
        // to the orchestrator, so this binding does not affect them.
        $this->app->bind(AnalysisProvider::class, function (): OpenAIAnalysisProvider {
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

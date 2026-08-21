<?php

namespace App\Providers;

use App\AI\Providers\AnalysisProvider;
use App\AI\Providers\FakeAnalysisProvider;
use App\AI\Providers\LLMAdapter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the provider-neutral AI port to a concrete adapter.
        // Default is the production LLMAdapter using the configured provider registry.
        // A development-only 'fake' driver (AI_DRIVER=fake) selects FakeAnalysisProvider,
        // which never calls any LLM and is used to deterministically demonstrate
        // FR-005 failure handling. Production behavior is unchanged unless AI_DRIVER is set.
        $this->app->bind(AnalysisProvider::class, function (): AnalysisProvider {
            if (config('ai.driver', 'llm') === 'fake') {
                $fake = new FakeAnalysisProvider;
                $fake->setOutcome((string) config('ai.fake_outcome', 'valid'));

                return $fake;
            }

            $activeProvider = config('ai.provider', 'openai');
            $providerConfig = config("ai.providers.{$activeProvider}");

            if (! $providerConfig) {
                throw new \InvalidArgumentException("AI provider '{$activeProvider}' is not configured in config/ai.php");
            }

            return new LLMAdapter;
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

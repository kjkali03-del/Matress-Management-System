<?php

namespace App\Providers;

use App\Services\AI\AiProviderInterface;
use App\Services\AI\OpenAiChatCompletionsProvider;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiProviderInterface::class, function (): AiProviderInterface {
            return match ((string) config('ai.provider')) {
                'openai' => new OpenAiChatCompletionsProvider(),
                default => throw new RuntimeException('Unsupported AI provider configured.'),
            };
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

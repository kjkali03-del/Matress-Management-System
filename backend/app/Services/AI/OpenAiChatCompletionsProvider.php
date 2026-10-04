<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiChatCompletionsProvider implements AiProviderInterface
{
    public function isConfigured(): bool
    {
        return filled(config('ai.api_key'))
            && filled(config('ai.model'))
            && filter_var(config('ai.enabled'), FILTER_VALIDATE_BOOL);
    }

    public function complete(array $messages, array $tools): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('The configured AI provider is not enabled or is missing credentials.');
        }

        $response = Http::withToken((string) config('ai.api_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(max(5, min(30, (int) config('ai.timeout', 30))))
            ->post(rtrim((string) config('ai.base_url'), '/') . '/chat/completions', [
                'model' => (string) config('ai.model'),
                'messages' => $messages,
                'tools' => $tools,
                'tool_choice' => 'auto',
                'parallel_tool_calls' => false,
                'temperature' => 0.3,
                'max_tokens' => max(128, min(1200, (int) config('ai.max_tokens', 700))),
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'AI provider returned HTTP ' . $response->status() . '.'
            );
        }

        $choice = data_get($response->json(), 'choices.0');
        $message = data_get($choice, 'message');
        if (! is_array($message)) {
            throw new RuntimeException('AI provider returned an invalid completion.');
        }

        $toolCalls = [];
        foreach (($message['tool_calls'] ?? []) as $call) {
            $function = $call['function'] ?? null;
            if (! is_array($function) || ! is_string($function['name'] ?? null)) {
                throw new RuntimeException('AI provider returned an invalid tool call.');
            }

            try {
                $arguments = json_decode(
                    (string) ($function['arguments'] ?? '{}'),
                    true,
                    32,
                    JSON_THROW_ON_ERROR,
                );
            } catch (\JsonException $exception) {
                throw new RuntimeException('AI provider returned malformed tool arguments.', previous: $exception);
            }

            if (! is_array($arguments)) {
                throw new RuntimeException('AI provider returned invalid tool arguments.');
            }

            $toolCalls[] = [
                'id' => (string) ($call['id'] ?? ''),
                'name' => $function['name'],
                'arguments' => $arguments,
            ];
        }

        return [
            'content' => is_string($message['content'] ?? null)
                ? $message['content']
                : null,
            'tool_calls' => $toolCalls,
            'finish_reason' => is_string($choice['finish_reason'] ?? null)
                ? $choice['finish_reason']
                : null,
        ];
    }
}

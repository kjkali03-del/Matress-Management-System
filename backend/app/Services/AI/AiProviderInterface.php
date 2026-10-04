<?php

namespace App\Services\AI;

interface AiProviderInterface
{
    /**
     * @param array<int, array<string, mixed>> $messages
     * @param array<int, array<string, mixed>> $tools
     * @return array{content: ?string, tool_calls: array<int, array<string, mixed>>, finish_reason: ?string}
     */
    public function complete(array $messages, array $tools): array;

    public function isConfigured(): bool;
}

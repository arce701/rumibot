<?php

namespace App\Ai\Middleware;

use App\Models\Conversation;
use Closure;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;

class TrackTokenUsage
{
    public function __construct(
        private Conversation $conversation,
    ) {}

    public function handle(AgentPrompt $prompt, Closure $next): mixed
    {
        return $next($prompt)->then(function (AgentResponse $response): void {
            $usage = $response->usage;

            if ($usage) {
                $this->conversation->withoutGlobalScopes()->increment('total_input_tokens', $usage->promptTokens ?? 0);
                $this->conversation->withoutGlobalScopes()->increment('total_output_tokens', $usage->completionTokens ?? 0);
            }

            Log::debug('Agent token usage', [
                'conversation_id' => $this->conversation->id,
                'input_tokens' => $usage->promptTokens ?? 0,
                'output_tokens' => $usage->completionTokens ?? 0,
                'model' => $response->model ?? 'unknown',
            ]);
        });
    }
}

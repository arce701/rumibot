<?php

namespace App\Console\Commands;

use App\Ai\Agents\TenantChatAgent;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Conversation;
use App\Models\Enums\ConversationStatus;
use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Exceptions\RateLimitedException;

class RetryUnansweredConversations extends Command
{
    protected $signature = 'app:retry-unanswered';

    protected $description = 'Find active conversations with unanswered user messages and generate AI responses';

    public function handle(): int
    {
        $unanswered = $this->findUnansweredConversations();

        if ($unanswered->isEmpty()) {
            $this->info(__('No unanswered conversations found.'));

            return self::SUCCESS;
        }

        $this->table(
            [__('Conversation'), __('Contact'), __('Phone'), __('Last Message'), __('Time')],
            $unanswered->map(fn ($row) => [
                $row['conversation']->id,
                $row['conversation']->contact_name,
                $row['conversation']->contact_phone,
                str($row['lastUserMessage']->content)->limit(50),
                $row['lastUserMessage']->created_at->diffForHumans(),
            ]),
        );

        $succeeded = 0;
        $failed = 0;

        foreach ($unanswered as $row) {
            $conversation = $row['conversation'];
            $lastUserMessage = $row['lastUserMessage'];

            $this->line("  → {$conversation->contact_name} ({$conversation->contact_phone})...");

            $result = $this->generateAndSendResponse($conversation, $lastUserMessage);

            if ($result) {
                $succeeded++;
                $this->info('    ✓ '.__('Response sent'));
            } else {
                $failed++;
                $this->error('    ✗ '.__('Failed'));
            }
        }

        $this->newLine();
        $this->info(__(':succeeded succeeded, :failed failed.', [
            'succeeded' => $succeeded,
            'failed' => $failed,
        ]));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{conversation: Conversation, lastUserMessage: Message}>
     */
    private function findUnansweredConversations(): \Illuminate\Support\Collection
    {
        return Conversation::withoutGlobalScopes()
            ->where('status', ConversationStatus::Active)
            ->where(function ($q) {
                $q->whereNull('ai_paused_until')
                    ->orWhere('ai_paused_until', '<', now());
            })
            ->get()
            ->map(function (Conversation $conversation) {
                $lastUserMessage = Message::withoutGlobalScopes()
                    ->where('conversation_id', $conversation->id)
                    ->where('role', 'user')
                    ->latest('created_at')
                    ->first();

                if (! $lastUserMessage) {
                    return null;
                }

                $hasReply = Message::withoutGlobalScopes()
                    ->where('conversation_id', $conversation->id)
                    ->where('role', 'assistant')
                    ->where('created_at', '>', $lastUserMessage->created_at)
                    ->exists();

                if ($hasReply) {
                    return null;
                }

                return ['conversation' => $conversation, 'lastUserMessage' => $lastUserMessage];
            })
            ->filter()
            ->values();
    }

    private function generateAndSendResponse(Conversation $conversation, Message $lastUserMessage): bool
    {
        $channel = $conversation->channel;
        $tenant = $channel->tenant;

        $credential = $tenant->defaultLlmCredential;
        if (! $credential) {
            $this->warn('    '.__('No LLM credential for tenant :name', ['name' => $tenant->name]));

            return false;
        }

        $model = $channel->ai_model_override ?? $tenant->default_ai_model;
        if (! $model) {
            $this->warn('    '.__('No AI model configured'));

            return false;
        }

        $agent = new TenantChatAgent($tenant, $channel, $conversation);
        $provider = $credential->provider->value;

        config()->set("ai.providers.{$provider}.key", $credential->api_key);

        try {
            $response = $agent->prompt(
                $lastUserMessage->content,
                provider: $provider,
                model: $model,
            );
        } catch (RateLimitedException $e) {
            $cooldown = $credential->provider->rateLimitCooldownSeconds();
            $this->warn('    '.__('Rate limited by :provider — skip and try later', ['provider' => $provider])." ({$cooldown}s cooldown)");
            Log::warning('Retry unanswered: rate limited', [
                'conversation_id' => $conversation->id,
                'provider' => $provider,
                'cooldown_seconds' => $cooldown,
            ]);

            return false;
        }

        $responseText = (string) $response;

        $assistantMessage = Message::create([
            'conversation_id' => $conversation->id,
            'tenant_id' => $tenant->id,
            'role' => 'assistant',
            'content' => $responseText,
            'model_used' => $model,
            'tokens_input' => $response->usage->promptTokens ?? null,
            'tokens_output' => $response->usage->completionTokens ?? null,
            'metadata' => [
                'provider' => $provider,
                'retry_source' => 'app:retry-unanswered',
            ],
        ]);

        $conversation->increment('messages_count');
        $conversation->update(['last_message_at' => now()]);

        SendWhatsAppMessage::dispatch($conversation, $responseText, $assistantMessage->id);

        return true;
    }
}

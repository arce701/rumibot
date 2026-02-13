<?php

namespace App\Http\Controllers\Api;

use App\Events\ConversationClosed;
use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Conversation;
use App\Models\Enums\ConversationStatus;
use App\Models\Escalation;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class AutomationApiController extends Controller
{
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['required', 'string', 'exists:conversations,id'],
            'text' => ['required', 'string', 'max:4096'],
        ]);

        $conversation = Conversation::findOrFail($validated['conversation_id']);

        SendWhatsAppMessage::dispatch($conversation, $validated['text']);

        return response()->json(['status' => 'queued'], 202);
    }

    public function updateLead(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', 'in:new,contacted,converted,lost'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'qualification_score' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $lead->update($validated);

        return response()->json(['data' => $lead->fresh()]);
    }

    public function closeConversation(Conversation $conversation): JsonResponse
    {
        $conversation->update(['status' => ConversationStatus::Closed]);

        event(new ConversationClosed($conversation));

        return response()->json(['data' => $conversation->fresh()]);
    }

    public function addEscalationNote(Request $request, Escalation $escalation): JsonResponse
    {
        $validated = $request->validate([
            'resolution_note' => ['required', 'string', 'max:5000'],
        ]);

        $escalation->update([
            'resolution_note' => $validated['resolution_note'],
            'resolved_at' => now(),
        ]);

        return response()->json(['data' => $escalation->fresh()]);
    }

    public function listConversations(): JsonResponse
    {
        $conversations = QueryBuilder::for(Conversation::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('channel_id'),
            ])
            ->allowedSorts([
                AllowedSort::field('created_at'),
                AllowedSort::field('last_message_at'),
            ])
            ->defaultSort('-last_message_at')
            ->paginate(20);

        return response()->json($conversations);
    }

    public function listLeads(): JsonResponse
    {
        $leads = QueryBuilder::for(Lead::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('email'),
            ])
            ->allowedSorts([
                AllowedSort::field('created_at'),
                AllowedSort::field('qualification_score'),
            ])
            ->defaultSort('-created_at')
            ->paginate(20);

        return response()->json($leads);
    }
}

<?php

namespace App\Ai\Tools;

use App\Events\LeadCaptured;
use App\Models\Conversation;
use App\Models\Lead;
use App\Support\PhoneHelper;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CaptureLead implements Tool
{
    public function __construct(
        private Conversation $conversation,
    ) {}

    public function description(): Stringable|string
    {
        return 'Capture lead information from the prospect during the conversation. Use this when the user provides their name, email, company, country, or expresses interest in a product/service. You can call this multiple times as you gather more data.';
    }

    public function handle(Request $request): Stringable|string
    {
        $lead = Lead::withoutGlobalScopes()
            ->where('conversation_id', $this->conversation->id)
            ->first();

        $country = $request['country']
            ?? PhoneHelper::detectCountryName($this->conversation->contact_phone);

        $data = array_filter([
            'full_name' => $request['full_name'] ?? null,
            'email' => $request['email'] ?? null,
            'company_name' => $request['company_name'] ?? null,
            'country' => $country,
            'phone' => $request['phone'] ?? $this->conversation->contact_phone,
        ]);

        if ($lead) {
            $lead->update($data);

            if ($request['interests'] ?? null) {
                $interests = array_unique(array_merge($lead->interests ?? [], $request['interests']));
                $lead->update(['interests' => $interests]);
            }
        } else {
            $lead = Lead::create([
                'tenant_id' => $this->conversation->tenant_id,
                'conversation_id' => $this->conversation->id,
                'phone' => $this->conversation->contact_phone,
                ...$data,
                'interests' => $request['interests'] ?? [],
            ]);

            event(new LeadCaptured($lead));
        }

        return "Lead captured successfully: {$lead->full_name} ({$lead->phone})";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'full_name' => $schema->string(),
            'email' => $schema->string(),
            'company_name' => $schema->string(),
            'country' => $schema->string(),
            'phone' => $schema->string(),
            'interests' => $schema->array()->items($schema->string()),
        ];
    }
}

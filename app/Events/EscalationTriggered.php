<?php

namespace App\Events;

use App\Models\Escalation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EscalationTriggered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Escalation $escalation,
    ) {}
}

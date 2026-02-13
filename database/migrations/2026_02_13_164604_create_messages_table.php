<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->text('content');
            $table->unsignedInteger('tokens_input')->nullable();
            $table->unsignedInteger('tokens_output')->nullable();
            $table->string('model_used', 100)->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['conversation_id', 'created_at']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

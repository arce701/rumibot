<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('channel_id')->constrained()->cascadeOnDelete();
            $table->string('contact_phone', 20);
            $table->string('contact_name', 100)->nullable();
            $table->string('status', 20)->default('active');
            $table->string('current_intent', 50)->nullable();
            $table->jsonb('metadata')->nullable();
            $table->unsignedInteger('messages_count')->default(0);
            $table->unsignedInteger('total_input_tokens')->default(0);
            $table->unsignedInteger('total_output_tokens')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'channel_id', 'contact_phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};

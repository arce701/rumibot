<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->string('type', 20);
            $table->string('provider_type', 20)->default('ycloud');
            $table->text('provider_api_key')->nullable();
            $table->string('provider_phone_number_id', 50)->nullable();
            $table->string('provider_webhook_verify_token', 100)->nullable();
            $table->text('system_prompt_override')->nullable();
            $table->string('ai_model_override', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};

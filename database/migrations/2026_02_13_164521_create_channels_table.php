<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->text('provider_api_key')->nullable();
            $table->string('provider_phone_number_id', 50)->nullable();
            $table->text('system_prompt_override')->nullable();
            $table->string('ai_model_override', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
        });

        // Partial unique index — PostgreSQL only
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX channels_tenant_phone_unique
            ON channels (tenant_id, provider_phone_number_id)
            WHERE provider_phone_number_id IS NOT NULL AND deleted_at IS NULL
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};

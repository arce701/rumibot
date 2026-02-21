<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignUuid('default_llm_credential_id')
                ->nullable()
                ->after('default_ai_model')
                ->constrained('llm_credentials')
                ->nullOnDelete();
            $table->decimal('ai_temperature', 3, 2)->default(0.70)->after('default_llm_credential_id');
            $table->unsignedInteger('ai_max_tokens')->default(500)->after('ai_temperature');
            $table->unsignedInteger('ai_context_window')->default(50)->after('ai_max_tokens');
            $table->boolean('ai_streaming')->default(false)->after('ai_context_window');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_llm_credential_id');
            $table->dropColumn(['ai_temperature', 'ai_max_tokens', 'ai_context_window', 'ai_streaming']);
        });
    }
};

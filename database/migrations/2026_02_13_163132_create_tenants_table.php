<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 200);
            $table->string('slug', 100)->unique();
            $table->text('system_prompt')->nullable();
            $table->string('default_ai_provider', 20)->default('openai');
            $table->string('default_ai_model', 100)->nullable();
            $table->string('timezone', 50)->default('America/Lima');
            $table->string('locale', 10)->default('es');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_platform_owner')->default(false);
            $table->jsonb('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('tenant_member');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('tenants');
    }
};

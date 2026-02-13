<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('provider');
            $table->string('url');
            $table->string('secret')->nullable();
            $table->jsonb('events')->default('[]');
            $table->string('status')->default('active');
            $table->boolean('is_primary')->default(false);
            $table->timestamp('last_triggered_at')->nullable();
            $table->unsignedInteger('failure_count')->default(0);
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_integrations');
    }
};

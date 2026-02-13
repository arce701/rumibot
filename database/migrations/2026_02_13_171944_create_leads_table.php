<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('full_name', 100)->nullable();
            $table->string('country', 50)->nullable();
            $table->string('phone', 20);
            $table->string('email', 100)->nullable();
            $table->string('company_name', 150)->nullable();
            $table->jsonb('interests')->default('[]');
            $table->unsignedTinyInteger('qualification_score')->nullable();
            $table->string('status', 20)->default('new');
            $table->text('notes')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};

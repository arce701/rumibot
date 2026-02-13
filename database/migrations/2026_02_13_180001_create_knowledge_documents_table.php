<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->unsignedInteger('file_size');
            $table->string('mime_type', 100);
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('total_chunks')->default(0);
            $table->jsonb('channel_scope')->default('[]');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_documents');
    }
};

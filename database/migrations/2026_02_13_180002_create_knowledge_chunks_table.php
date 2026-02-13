<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('document_id')->constrained('knowledge_documents')->cascadeOnDelete();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->text('content');
            $table->unsignedInteger('token_count');
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            if (DB::connection()->getDriverName() === 'pgsql') {
                $table->vector('embedding', dimensions: 1536);
                $table->vectorIndex('embedding');
            } else {
                $table->text('embedding')->nullable();
            }

            $table->index(['tenant_id', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};

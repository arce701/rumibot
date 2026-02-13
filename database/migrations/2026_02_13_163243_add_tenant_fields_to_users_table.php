<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('email');
            $table->foreignUuid('current_tenant_id')
                ->nullable()
                ->after('is_super_admin')
                ->constrained('tenants')
                ->nullOnDelete();
            $table->string('locale', 10)->default('es')->after('current_tenant_id');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_tenant_id']);
            $table->dropColumn(['is_super_admin', 'current_tenant_id', 'locale', 'deleted_at']);
        });
    }
};

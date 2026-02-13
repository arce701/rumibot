<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('billing_interval', 20);
            $table->string('currency', 3)->default('PEN');
            $table->unsignedInteger('price_amount');
            $table->timestamps();

            $table->unique(['plan_id', 'billing_interval', 'currency']);
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('feature_slug', 100);
            $table->string('value', 50);
            $table->timestamps();

            $table->unique(['plan_id', 'feature_slug']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->foreignId('plan_price_id')->constrained();
            $table->string('status', 20);
            $table->string('payment_provider', 30);
            $table->string('external_subscription_id')->nullable();
            $table->string('external_customer_id')->nullable();
            $table->timestamp('trial_starts_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_starts_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('grace_period_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('subscription_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('feature_slug', 100);
            $table->unsignedInteger('used')->default(0);
            $table->timestamp('period_starts_at');
            $table->timestamp('period_ends_at');
            $table->timestamps();

            $table->unique(['subscription_id', 'feature_slug', 'period_starts_at'], 'sub_usage_unique');
        });

        Schema::create('payment_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_provider', 30);
            $table->string('external_payment_id')->nullable();
            $table->string('status', 20);
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('PEN');
            $table->text('description')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_histories');
        Schema::dropIfExists('subscription_usages');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plan_prices');
        Schema::dropIfExists('plans');
    }
};

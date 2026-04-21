<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // subscription_usage has a FK to subscriptions — drop first
        Schema::dropIfExists('subscription_usage');
        Schema::dropIfExists('subscriptions');

        Schema::create('enterprise_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->foreignId('product_id')->constrained(); // denormalized for unique constraint
            $table->string('status'); // active | trialing | past_due | canceled | expired
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('provider')->nullable();
            $table->string('provider_customer_id')->nullable();
            $table->string('provider_subscription_id')->nullable();
            $table->json('override_json')->nullable();
            $table->unsignedInteger('amount_paid_cents')->nullable();
            $table->char('currency', 3)->nullable();
            $table->unsignedTinyInteger('discount_pct')->nullable(); // 0-100
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['enterprise_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enterprise_products');

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->string('status');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('provider')->nullable();
            $table->string('provider_customer_id')->nullable();
            $table->string('provider_subscription_id')->nullable();
            $table->json('override_json')->nullable();
            $table->unsignedInteger('amount_paid_cents')->nullable();
            $table->char('currency', 3)->nullable();
            $table->unsignedTinyInteger('discount_pct')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pre-aggregated daily rollup by model/provider/type
        Schema::create('token_usage_daily', function (Blueprint $table) {
            $table->date('date');
            $table->string('model', 80);
            $table->string('provider', 30);
            $table->string('type', 20);
            $table->unsignedBigInteger('input_tokens')->default(0);
            $table->unsignedBigInteger('output_tokens')->default(0);
            $table->unsignedInteger('record_count')->default(0);
            $table->primary(['date', 'model', 'provider', 'type']);
            // Slice by individual dimension
            $table->index('date');
            $table->index('model');
            $table->index('provider');
            $table->index('type');
        });

        // Pre-aggregated daily rollup per user
        Schema::create('token_usage_user_daily', function (Blueprint $table) {
            $table->date('date');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('input_tokens')->default(0);
            $table->unsignedBigInteger('output_tokens')->default(0);
            $table->unsignedInteger('record_count')->default(0);
            $table->primary(['date', 'user_id']);
            $table->index('user_id');
        });

        // Watermark — single row that tracks sweep progress
        Schema::create('token_usage_rollup_state', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('last_verified_id')->default(0);
            $table->timestamp('last_run_at')->nullable();
        });

        DB::table('token_usage_rollup_state')->insert([
            'last_verified_id' => 0,
            'last_run_at'      => null,
        ]);

        // Standalone created_at index on token_usages for raw queries (conversations endpoint)
        Schema::table('token_usages', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('token_usages', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::dropIfExists('token_usage_rollup_state');
        Schema::dropIfExists('token_usage_user_daily');
        Schema::dropIfExists('token_usage_daily');
    }
};

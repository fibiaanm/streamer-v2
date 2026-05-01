<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_usages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('assistant_conversations')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('assistant_sessions')->nullOnDelete();

            // What kind of AI operation was performed
            // text | image | embedding | memory | audio
            $table->string('type', 20);

            // Provider and model that served the request
            $table->string('provider', 30);  // openai | anthropic | gemini | grok
            $table->string('model', 80);     // e.g. openai/gpt-5-nano

            // Token counts — nullable because some types (image) don't use tokens
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();

            // Generic unit count for non-token usage (e.g. number of images generated)
            $table->unsignedSmallInteger('units')->nullable();

            // Which iteration of the tool loop produced this call (1-based)
            $table->unsignedTinyInteger('iteration')->default(1);

            $table->char('request_id', 36)->nullable();
            $table->json('metadata_json')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Primary query patterns: per-user, per-type, per-model, per-provider — all time-ranged
            $table->index(['user_id',   'created_at']);
            $table->index(['type',      'created_at']);
            $table->index(['model',     'created_at']);
            $table->index(['provider',  'created_at']);

            // Totals per conversation / tracing
            $table->index('conversation_id');
            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_usages');
    }
};

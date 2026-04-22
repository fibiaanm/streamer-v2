<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('assistant_conversations')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('assistant_sessions')->cascadeOnDelete();
            $table->string('role');   // user | assistant | system | tool_call | tool_result
            $table->string('channel')->default('web');  // web | whatsapp
            $table->text('content');
            $table->json('actions_json')->nullable();
            $table->json('metadata_json')->nullable();
            $table->boolean('memory_processed')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['conversation_id', 'created_at']);
            $table->index('memory_processed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_messages');
    }
};

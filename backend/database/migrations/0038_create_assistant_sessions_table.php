<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('assistant_conversations')->cascadeOnDelete();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('last_message_at')->useCurrent();

            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_sessions');
    }
};

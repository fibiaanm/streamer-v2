<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->string('content');
            $table->timestamp('suggested_event_at')->nullable();
            $table->string('status')->default('pending');  // pending | accepted | declined
            $table->foreignId('event_id')->nullable()->constrained('assistant_events')->nullOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('assistant_messages')->nullOnDelete();
            $table->timestamps();

            $table->index(['receiver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_requests');
    }
};

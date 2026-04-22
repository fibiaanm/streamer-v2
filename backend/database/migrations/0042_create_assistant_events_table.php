<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('content');
            $table->timestamp('event_at');
            $table->timestamp('event_end')->nullable();
            $table->string('type');
            $table->string('recurrence_rule')->nullable();
            $table->string('status')->default('active');  // active | cancelled | completed
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'event_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_events');
    }
};

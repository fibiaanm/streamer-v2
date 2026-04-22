<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('assistant_events')->cascadeOnDelete();
            $table->timestamp('fire_at');
            $table->string('message');
            $table->string('status')->default('pending');  // pending | fired | failed
            $table->timestamp('fired_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['status', 'fire_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_reminders');
    }
};

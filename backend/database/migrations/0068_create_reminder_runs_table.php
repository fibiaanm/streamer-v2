<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('run_at');
            $table->string('kind');   // digest | ahead | inline
            $table->string('job_id')->nullable();
            $table->string('status')->default('pending');  // pending | fired
            $table->timestamps();

            $table->index(['user_id', 'status', 'run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_runs');
    }
};

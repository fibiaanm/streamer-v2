<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency', 3);
            $table->string('description');
            $table->string('type')->nullable();
            $table->timestamp('spent_at');
            $table->timestamps();

            $table->index(['user_id', 'spent_at']);
            $table->index(['user_id', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};

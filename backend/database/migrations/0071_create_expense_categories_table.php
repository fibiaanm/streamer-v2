<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('enterprise_id')->nullable()->constrained('enterprises')->cascadeOnDelete();
            $table->string('name');
            $table->string('emoji', 20);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
            $table->unique(['enterprise_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};

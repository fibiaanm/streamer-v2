<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enterprise_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('enterprise_roles');
            $table->string('status')->default('active'); // active | suspended
            $table->timestamps();
            $table->unique(['user_id', 'enterprise_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enterprise_members');
    }
};

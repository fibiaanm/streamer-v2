<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('invitable_type');
            $table->unsignedBigInteger('invitable_id');
            $table->foreignId('invited_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->foreignId('enterprise_role_id')->nullable()->constrained('enterprise_roles')->nullOnDelete();
            $table->foreignId('workspace_role_id')->nullable(); // FK añadida cuando exista workspace_roles
            $table->string('token')->unique();
            $table->string('status')->default('pending'); // pending | accepted | expired | revoked
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->index(['invitable_type', 'invitable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};

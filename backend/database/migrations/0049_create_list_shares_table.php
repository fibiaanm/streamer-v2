<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('list_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('assistant_lists')->cascadeOnDelete();
            $table->foreignId('shared_with_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('invited_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('permission')->default('read');  // read | write
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['list_id', 'shared_with_user_id']);
            $table->index('shared_with_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('list_shares');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('assistant_lists')->cascadeOnDelete();
            $table->foreignId('added_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('content');
            $table->string('status')->default('pending');  // pending | done
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['list_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('list_items');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('type_catalogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('domain');  // event | list | expense
            $table->string('name');
            $table->timestamp('created_at')->useCurrent();

            $table->index('domain');
            $table->unique(['user_id', 'domain', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('type_catalogs');
    }
};

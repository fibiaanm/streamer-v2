<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistant_lists', function (Blueprint $table) {
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('list_items', function (Blueprint $table) {
            $table->index(['list_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('assistant_lists', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
        });

        Schema::table('list_items', function (Blueprint $table) {
            $table->dropIndex(['list_id', 'status']);
        });
    }
};

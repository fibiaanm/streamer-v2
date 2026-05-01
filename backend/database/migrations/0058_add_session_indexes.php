<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistant_sessions', function (Blueprint $table) {
            $table->index('started_at');
        });

        Schema::table('assistant_messages', function (Blueprint $table) {
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::table('assistant_messages', function (Blueprint $table) {
            $table->dropIndex(['session_id']);
        });

        Schema::table('assistant_sessions', function (Blueprint $table) {
            $table->dropIndex(['started_at']);
        });
    }
};

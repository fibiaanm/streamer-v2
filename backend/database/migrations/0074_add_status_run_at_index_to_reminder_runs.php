<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminder_runs', function (Blueprint $table) {
            $table->index(['status', 'run_at']);
        });
    }

    public function down(): void
    {
        Schema::table('reminder_runs', function (Blueprint $table) {
            $table->dropIndex(['status', 'run_at']);
        });
    }
};

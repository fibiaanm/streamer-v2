<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('failed_jobs', function (Blueprint $table) {
            // Redundant now that the composite (failed_at, queue) covers it
            $table->dropIndex(['queue']);

            // All queries filter by failed_at (range); many also filter by queue.
            // A composite with failed_at as the leading column covers both patterns.
            $table->index(['failed_at', 'queue']);
        });
    }

    public function down(): void
    {
        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->dropIndex(['failed_at', 'queue']);
            $table->index('queue');
        });
    }
};

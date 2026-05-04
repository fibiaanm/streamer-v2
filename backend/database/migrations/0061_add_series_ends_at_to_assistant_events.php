<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistant_events', function (Blueprint $table) {
            $table->timestamp('series_ends_at')->nullable()->after('series_id');
        });

        // Partial index — only masters (series_id IS NULL AND recurrence_rule IS NOT NULL)
        DB::statement('
            CREATE INDEX idx_events_masters
            ON assistant_events (user_id, event_at, series_ends_at)
            WHERE series_id IS NULL AND recurrence_rule IS NOT NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_events_masters');

        Schema::table('assistant_events', function (Blueprint $table) {
            $table->dropColumn('series_ends_at');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_reminders', function (Blueprint $table) {
            // PostgreSQL does not auto-create an index for FK constraints.
            // This index is used by: releaseByEventIds, admin event detail, cascade FK checks.
            $table->index('event_id');
            // For admin queries and future reporting filtered/sorted by fire date.
            $table->index('fire_at');
        });

        Schema::table('assistant_events', function (Blueprint $table) {
            // The existing (user_id, event_at) compound index requires user_id as leading column.
            // Admin queries have no user_id filter — this index covers cross-user date-range scans.
            $table->index('event_at');
        });
    }

    public function down(): void
    {
        Schema::table('event_reminders', function (Blueprint $table) {
            $table->dropIndex(['event_id']);
            $table->dropIndex(['fire_at']);
        });

        Schema::table('assistant_events', function (Blueprint $table) {
            $table->dropIndex(['event_at']);
        });
    }
};

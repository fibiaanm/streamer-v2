<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistant_events', function (Blueprint $table) {
            $table->foreignId('series_id')->nullable()->constrained('assistant_events')->nullOnDelete()->after('user_id');
            $table->timestamp('occurrence_at')->nullable()->after('event_end');
            $table->json('reminders_template_json')->nullable()->after('occurrence_at');

            $table->index('series_id');
        });
    }

    public function down(): void
    {
        Schema::table('assistant_events', function (Blueprint $table) {
            $table->dropForeign(['series_id']);
            $table->dropIndex(['series_id']);
            $table->dropColumn(['series_id', 'occurrence_at', 'reminders_template_json']);
        });
    }
};

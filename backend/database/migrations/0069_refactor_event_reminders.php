<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_reminders', function (Blueprint $table) {
            $table->dropIndex(['status', 'fire_at']);
            $table->dropColumn(['message', 'job_id']);

            $table->string('kind')->default('ahead')->after('fire_at');   // digest | ahead | inline
            $table->foreignId('reminder_run_id')
                ->nullable()
                ->after('kind')
                ->constrained('reminder_runs')
                ->nullOnDelete();

            $table->index('reminder_run_id');
        });
    }

    public function down(): void
    {
        Schema::table('event_reminders', function (Blueprint $table) {
            $table->dropForeign(['reminder_run_id']);
            $table->dropIndex(['reminder_run_id']);
            $table->dropColumn(['kind', 'reminder_run_id']);

            $table->string('message')->default('')->after('fire_at');
            $table->string('job_id')->nullable()->after('fired_at');
            $table->index(['status', 'fire_at']);
        });
    }
};

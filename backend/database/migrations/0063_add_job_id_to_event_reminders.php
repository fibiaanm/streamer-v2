<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_reminders', function (Blueprint $table) {
            $table->unsignedBigInteger('job_id')->nullable()->after('fired_at');
        });
    }

    public function down(): void
    {
        Schema::table('event_reminders', function (Blueprint $table) {
            $table->dropColumn('job_id');
        });
    }
};

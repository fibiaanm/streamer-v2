<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistant_events', function (Blueprint $table) {
            $table->dropColumn('reminders_template_json');
        });
    }

    public function down(): void
    {
        Schema::table('assistant_events', function (Blueprint $table) {
            $table->json('reminders_template_json')->nullable()->after('occurrence_at');
        });
    }
};

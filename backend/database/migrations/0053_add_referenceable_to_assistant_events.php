<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistant_events', function (Blueprint $table) {
            $table->string('referenceable_type')->nullable()->after('reminders_template_json');
            $table->string('referenceable_id')->nullable()->after('referenceable_type');

            $table->index(['referenceable_type', 'referenceable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('assistant_events', function (Blueprint $table) {
            $table->dropIndex(['referenceable_type', 'referenceable_id']);
            $table->dropColumn(['referenceable_type', 'referenceable_id']);
        });
    }
};

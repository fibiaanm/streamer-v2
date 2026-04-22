<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone')->default('UTC')->after('email');
            $table->string('default_currency', 3)->default('USD')->after('timezone');
            $table->string('username')->unique()->nullable()->after('default_currency');
            $table->string('friend_code', 8)->unique()->nullable()->after('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'default_currency', 'username', 'friend_code']);
        });
    }
};

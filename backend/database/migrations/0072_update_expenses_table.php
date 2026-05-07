<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->bigInteger('amount_cents')->change();
            $table->dropColumn('type');
            $table->foreignId('category_id')->nullable()->after('description')->constrained('expense_categories')->nullOnDelete();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropConstrainedForeignId('category_id');
            $table->string('type')->nullable()->after('description');
            $table->unsignedBigInteger('amount_cents')->change();
        });
    }
};

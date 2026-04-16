<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // individual | enterprise
            $table->json('limits_json');
            $table->boolean('is_free')->default(false);
            $table->unsignedInteger('price_monthly_cents')->nullable();
            $table->unsignedInteger('price_yearly_cents')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};

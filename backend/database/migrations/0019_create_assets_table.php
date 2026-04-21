<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_workspace_id')->constrained('workspaces');
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->string('type'); // image | video | audio | document | other
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // pending | active | archived | trashed
            $table->timestamp('upload_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};

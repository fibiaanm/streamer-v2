<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('owner_user_id')->constrained('users');
            $table->foreignId('parent_workspace_id')->nullable()->constrained('workspaces')->nullOnDelete();
            $table->string('status')->default('active'); // active | archived | orphaned
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('ALTER TABLE workspaces ADD COLUMN path ltree NOT NULL DEFAULT \'\'');
        DB::statement('CREATE INDEX workspaces_path_gist ON workspaces USING GIST (path)');
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};

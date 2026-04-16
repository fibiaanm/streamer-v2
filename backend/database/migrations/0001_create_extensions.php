<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        DB::statement('CREATE EXTENSION IF NOT EXISTS "ltree"');
    }

    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS "ltree"');
        DB::statement('DROP EXTENSION IF EXISTS "uuid-ossp"');
    }
};

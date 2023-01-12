<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateLtreeExtension extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('create extension if not exists ltree');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('drop extension if exists ltree');
    }
}

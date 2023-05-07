<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('uuid_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->ltree('path')->spatialIndex();
            $table->timestamps();
        });

        Schema::table('uuid_categories', function (Blueprint $table) {
            $table->foreignUuid('parent_id')
                ->nullable()
                ->index()
                ->constrained('uuid_categories')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uuid_categories');
    }
};

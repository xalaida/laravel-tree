<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('substring_index', function ($string, $delimiter, $count) {
                if ($count > 0) {
                    return implode($delimiter, array_slice(explode($delimiter, $string), 0, $count));
                }
                elseif ($count < 0) {
                    return implode($delimiter, array_slice(explode($delimiter, $string), $count));
                }
                return '';
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

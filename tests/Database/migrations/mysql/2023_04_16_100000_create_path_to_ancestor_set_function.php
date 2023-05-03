<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            create function path_to_ancestor_set(input varchar(255))
            returns varchar(255)
            begin
                declare output varchar(255);
                declare depth int;
                declare i int default 0;
                set depth = length(input) - length(replace(input, '.', '')) + 1;
                set output = '';

                while i < depth do
                    set output = concat_ws(',', output, substring_index(input, '.', depth - i));
                    set i = i + 1;
                end while;

                return substring(output, 2);
            end;
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('drop function if exists path_to_ancestor_set');
    }
};

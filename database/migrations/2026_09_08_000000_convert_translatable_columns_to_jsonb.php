<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $columns = [
                ['translations', 'text'],
                ['menu_items', 'name'],
                ['menu_items', 'description'],
                ['combos', 'name'],
                ['combos', 'description'],
                ['pages', 'title'],
                ['pages', 'seo_title'],
                ['pages', 'seo_description'],
            ];

            foreach ($columns as [$table, $col]) {
                DB::statement("ALTER TABLE \"$table\" ALTER COLUMN \"$col\" TYPE jsonb USING CASE WHEN \"$col\" IS NULL THEN NULL ELSE \"$col\"::jsonb END");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $columns = [
                ['translations', 'text'],
                ['menu_items', 'name'],
                ['menu_items', 'description'],
                ['combos', 'name'],
                ['combos', 'description'],
                ['pages', 'title'],
                ['pages', 'seo_title'],
                ['pages', 'seo_description'],
            ];

            foreach ($columns as [$table, $col]) {
                DB::statement("ALTER TABLE \"$table\" ALTER COLUMN \"$col\" TYPE text USING \"$col\"::text");
            }
        }
    }
};

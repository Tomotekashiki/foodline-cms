<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('menu_items', 'sort_order')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->integer('sort_order')->default(0)->after('badge');
            });
        }

        if (! Schema::hasColumn('combos', 'sort_order')) {
            Schema::table('combos', function (Blueprint $table) {
                $table->integer('sort_order')->default(0)->after('badge');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('menu_items', 'sort_order')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }

        if (Schema::hasColumn('combos', 'sort_order')) {
            Schema::table('combos', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};

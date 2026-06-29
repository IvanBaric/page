<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('pages.tables.section_items', 'section_items');

        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if (Schema::hasColumn($tableName, 'image')) {
                $table->dropColumn('image');
            }
        });
    }

    public function down(): void
    {
        $tableName = config('pages.tables.section_items', 'section_items');

        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if (! Schema::hasColumn($tableName, 'image')) {
                $table->string('image')->nullable()->after('content');
            }
        });
    }
};

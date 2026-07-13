<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use IvanBaric\Pages\Support\PagesConfigResolver;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = PagesConfigResolver::sectionItemsTable();

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
        $tableName = PagesConfigResolver::sectionItemsTable();

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

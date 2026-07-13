<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use IvanBaric\Pages\Support\PagesConfigResolver;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->tableNames() as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'image')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('image');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tableNames() as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'image')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('image')->nullable()->after('content');
            });
        }
    }

    /** @return array<int, string> */
    private function tableNames(): array
    {
        return [
            PagesConfigResolver::sectionsTable(),
            PagesConfigResolver::sectionItemsTable(),
        ];
    }
};

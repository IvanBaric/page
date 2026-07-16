<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use IvanBaric\Pages\Support\PagesConfigResolver;

return new class extends Migration
{
    public function up(): void
    {
        $pages = PagesConfigResolver::pagesTable();

        if (Schema::hasColumn($pages, 'parent_id')) {
            return;
        }

        Schema::table($pages, function (Blueprint $table) use ($pages): void {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('team_id')
                ->constrained($pages)
                ->nullOnDelete();
            $table->index(['team_id', 'parent_id', 'sort_order'], 'pages_tenant_parent_sort_index');
        });
    }

    public function down(): void
    {
        $pages = PagesConfigResolver::pagesTable();

        if (! Schema::hasColumn($pages, 'parent_id')) {
            return;
        }

        Schema::table($pages, function (Blueprint $table): void {
            $table->dropIndex('pages_tenant_parent_sort_index');
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};

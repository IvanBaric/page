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

        if (! Schema::hasColumn($pages, 'navigation_type')) {
            Schema::table($pages, function (Blueprint $table): void {
                $table->string('navigation_type')->default('page')->index()->after('template');
                $table->string('navigation_url', 2048)->nullable()->after('navigation_type');
                $table->string('navigation_target', 16)->default('_self')->after('navigation_url');
            });
        }
    }

    public function down(): void
    {
        $pages = PagesConfigResolver::pagesTable();

        if (Schema::hasColumn($pages, 'navigation_type')) {
            Schema::table($pages, function (Blueprint $table): void {
                $table->dropIndex(['navigation_type']);
                $table->dropColumn(['navigation_type', 'navigation_url', 'navigation_target']);
            });
        }
    }
};

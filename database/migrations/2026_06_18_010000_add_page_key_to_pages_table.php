<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('pages.tables.pages', 'pages');

        if (! Schema::hasTable($table)) {
            return;
        }

        if (! Schema::hasColumn($table, 'page_key')) {
            Schema::table($table, function (Blueprint $table): void {
                $table->string('page_key')->nullable()->after('slug')->index();
            });
        }

        DB::table($table)
            ->orderBy('id')
            ->select(['id', 'slug', 'title', 'is_home'])
            ->get()
            ->each(function (object $page) use ($table): void {
                DB::table($table)
                    ->where('id', $page->id)
                    ->whereNull('page_key')
                    ->update(['page_key' => $this->canonicalPageKey((string) $page->slug, $page->title, (bool) $page->is_home)]);
            });

        $this->createUniqueIndex($table);
    }

    public function down(): void
    {
        $table = config('pages.tables.pages', 'pages');

        if (Schema::hasTable($table) && Schema::hasColumn($table, 'page_key')) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                if ($this->hasIndex($table, $this->defaultIndexName($table, ['team_id', 'page_key'], 'unique'))) {
                    $blueprint->dropUnique(['team_id', 'page_key']);
                }

                if ($this->hasIndex($table, $this->defaultIndexName($table, ['page_key'], 'index'))) {
                    $blueprint->dropIndex(['page_key']);
                }

                $blueprint->dropColumn('page_key');
            });
        }
    }

    private function createUniqueIndex(string $table): void
    {
        try {
            Schema::table($table, function (Blueprint $table): void {
                $table->unique(['team_id', 'page_key']);
            });
        } catch (Throwable) {
            // Existing duplicated custom pages should not block the deploy; runtime filters still use page_key when present.
        }
    }

    private function canonicalPageKey(string $slug, mixed $title, bool $isHome): string
    {
        if ($isHome || $slug === 'home') {
            return 'home';
        }

        $aliases = (array) config('pages.public_slug_aliases', []);

        $title = $this->plainTitle($title);

        return (string) ($aliases[$slug] ?? match ($title) {
            'radovi', 'proizvodi', 'radovi i rukotvorine' => 'products',
            'objave', 'novosti' => 'posts',
            'galerija', 'fotogalerija' => 'gallery',
            'o nama', 'o udruzi', 'o zadruzi' => 'about',
            'kontakt' => 'contact',
            default => match ($slug) {
            'o-udruzi' => 'about',
            default => $slug,
            },
        });
    }

    private function plainTitle(mixed $title): string
    {
        $decoded = is_string($title) ? json_decode($title, true) : $title;

        if (is_array($decoded)) {
            $decoded = $decoded['hr'] ?? reset($decoded);
        }

        return str((string) $decoded)->lower()->ascii()->toString();
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $definition): bool => ($definition['name'] ?? null) === $index);
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function defaultIndexName(string $table, array $columns, string $type): string
    {
        return strtolower($table.'_'.implode('_', $columns).'_'.$type);
    }
};

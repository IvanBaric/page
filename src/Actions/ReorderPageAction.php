<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Models\Page;

final class ReorderPageAction
{
    /**
     * @param array<int, string> $keys
     * @param array<int, string> $slugs
     */
    public function handle(Page $page, int $position, ?int $teamId, array $keys, array $slugs = []): ActionResult
    {
        DB::transaction(function () use ($page, $position, $teamId, $keys, $slugs): void {
            $pages = $page::query()
                ->forTeam($teamId)
                ->when(
                    Schema::hasColumn(config('pages.tables.pages', 'pages'), 'page_key'),
                    fn ($query) => $query->where(function ($query) use ($keys, $slugs): void {
                        $query->whereIn('page_key', $keys)
                            ->orWhere(function ($query) use ($slugs): void {
                                $query->whereNull('page_key')->whereIn('slug', $slugs);
                            });
                    }),
                    fn ($query) => $query->whereIn('slug', $slugs ?: $keys),
                )
                ->ordered()
                ->get();

            $moving = $pages->firstWhere('id', $page->getKey());

            if (! $moving instanceof Page) {
                return;
            }

            $pages = $pages
                ->reject(fn (Page $current): bool => $current->is($moving))
                ->values();

            $pages->splice(max(0, min($position, $pages->count())), 0, [$moving]);

            $pages->values()->each(function (Page $current, int $index): void {
                $current->forceFill(['sort_order' => $index])->save();
            });
        });

        return ActionResult::success(__('Redoslijed stranica je spremljen.'));
    }
}

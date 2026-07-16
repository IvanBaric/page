<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Support\PagesConfigResolver;

final class ReorderPageAction
{
    use AuthorizesPageActions, ResolvesPageModels;

    /**
     * @param  array<int, string>  $keys
     * @param  array<int, string>  $slugs
     */
    public function handle(Page $page, int $position, array $keys, array $slugs = []): ActionResult
    {
        $page = $this->resolvePage($page);

        if (! $page) {
            return ActionResult::error(__('Stranica nije pronađena.'));
        }

        if ($result = $this->authorizePageAction('pages.update', $page)) {
            return $result;
        }

        DB::transaction(function () use ($page, $position, $keys, $slugs): void {
            $pages = $page::query()
                ->where('parent_id', $page->getAttribute('parent_id'))
                ->when(
                    Schema::hasColumn(PagesConfigResolver::pagesTable(), 'page_key'),
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

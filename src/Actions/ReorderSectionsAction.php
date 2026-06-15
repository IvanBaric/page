<?php

namespace IvanBaric\Pages\Actions;

use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Models\Page;

final class ReorderSectionsAction
{
    /**
     * @param  array<int, string>  $sectionUuids
     */
    public function handle(Page|string $page, array $sectionUuids): ActionResult
    {
        $page = $this->findPage($page);

        if (! $page) {
            return ActionResult::failure(__('Page not found.'));
        }

        foreach (array_values($sectionUuids) as $position => $uuid) {
            $page->sections()->where('uuid', $uuid)->update(['sort_order' => $position]);
        }

        return ActionResult::success(__('Sections reordered.'), $page->refresh());
    }

    private function findPage(Page|string $page): ?Page
    {
        if ($page instanceof Page) {
            return $page;
        }

        $model = config('pages.models.page', Page::class);

        return $model::query()->where('uuid', $page)->first();
    }
}

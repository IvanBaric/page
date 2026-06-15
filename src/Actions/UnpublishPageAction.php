<?php

namespace IvanBaric\Pages\Actions;

use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Models\Page;

final class UnpublishPageAction
{
    public function handle(Page|string $page): ActionResult
    {
        $page = $this->findPage($page);

        if (! $page) {
            return ActionResult::failure(__('Page not found.'));
        }

        $page->unpublish();

        return ActionResult::success(__('Page unpublished.'), $page->refresh());
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

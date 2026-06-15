<?php

namespace IvanBaric\Pages\Actions;

use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Models\Page;

final class DeletePageAction
{
    public function handle(Page|string $page): ActionResult
    {
        $page = $this->findPage($page);

        if (! $page) {
            return ActionResult::failure(__('Page not found.'));
        }

        $page->delete();

        return ActionResult::success(__('Page deleted.'));
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

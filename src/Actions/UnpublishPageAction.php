<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Events\PageUnpublished;
use IvanBaric\Pages\Models\Page;

final class UnpublishPageAction
{
    use AuthorizesPageActions;

    public function handle(Page|string $page): ActionResult
    {
        $page = $this->findPage($page);

        if (! $page) {
            return ActionResult::failure(__('Page not found.'));
        }

        if ($result = $this->authorizePageAction('pages.publish', $page)) {
            return $result;
        }

        DB::transaction(static function () use ($page): void {
            $page->unpublish();
        });

        $page->refresh();
        PageUnpublished::dispatch($page);

        return ActionResult::success(__('Page unpublished.'), $page);
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

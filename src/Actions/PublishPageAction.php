<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Events\PagePublished;
use IvanBaric\Pages\Models\Page;

final class PublishPageAction
{
    use AuthorizesPageActions, ResolvesPageModels;

    public function handle(Page|string $page): ActionResult
    {
        $page = $this->resolvePage($page);

        if (! $page) {
            return ActionResult::failure(__('Page not found.'));
        }

        if ($result = $this->authorizePageAction('pages.publish', $page)) {
            return $result;
        }

        DB::transaction(static function () use ($page): void {
            /** @var Page $lockedPage */
            $lockedPage = Page::query()
                ->whereKey($page->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPage->publish();
        });

        $page->refresh();
        PagePublished::dispatch($page);

        return ActionResult::success(__('Page published.'), $page);
    }

}

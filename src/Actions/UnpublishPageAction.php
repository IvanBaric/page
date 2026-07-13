<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Events\PageUnpublished;
use IvanBaric\Pages\Models\Page;

final class UnpublishPageAction
{
    use AuthorizesPageActions, ResolvesPageModels;

    public function handle(Page|string $page): ActionResult
    {
        $page = $this->resolvePage($page);

        if (! $page) {
            return ActionResult::error(__('Stranica nije pronađena.'));
        }

        if ($result = $this->authorizePageAction('pages.publish', $page)) {
            return $result;
        }

        DB::transaction(static function () use ($page): void {
            /** @var Page $lockedPage */
            $lockedPage = $page->newQuery()
                ->whereKey($page->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPage->unpublish();
        });

        $page->refresh();
        PageUnpublished::dispatch($page);

        return ActionResult::success(__('Stranica je spremljena kao skica.'), $page);
    }
}

<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Events\SectionItemUpdated;
use IvanBaric\Pages\Models\SectionItem;

final class ToggleSectionItemVisibilityAction
{
    use AuthorizesPageActions, ResolvesPageModels;

    public function handle(SectionItem|string $item): ActionResult
    {
        $item = $this->resolveSectionItem($item);

        if (! $item) {
            return ActionResult::error(__('Stavka nije pronađena.'));
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $item)) {
            return $result;
        }

        DB::transaction(function () use ($item): void {
            $locked = $item->newQuery()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();
            $locked->isVisible() ? $locked->hide() : $locked->show();
        });

        $item->refresh();
        SectionItemUpdated::dispatch($item);

        return ActionResult::success(
            $item->isVisible() ? __('Stavka je uključena.') : __('Stavka je isključena.'),
            $item,
        );
    }
}

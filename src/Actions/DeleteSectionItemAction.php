<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Events\SectionItemDeleted;
use IvanBaric\Pages\Models\SectionItem;

final class DeleteSectionItemAction
{
    use AuthorizesPageActions, ResolvesPageModels;

    public function handle(SectionItem|string $item): ActionResult
    {
        $item = $this->resolveSectionItem($item);

        if (! $item) {
            return ActionResult::error(__('Zapis nije pronađen.'));
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $item)) {
            return $result;
        }

        $itemKey = $item->getKey();
        $uuid = (string) $item->getAttribute('uuid');

        DB::transaction(static function () use ($item): void {
            /** @var SectionItem $lockedItem */
            $lockedItem = $item->newQuery()
                ->whereKey($item->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedItem->archive();
        });

        SectionItemDeleted::dispatch($itemKey, $uuid);

        return ActionResult::success(__('Zapis je arhiviran.'));
    }
}

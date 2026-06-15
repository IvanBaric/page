<?php

namespace IvanBaric\Pages\Actions;

use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Models\SectionItem;

final class DeleteSectionItemAction
{
    public function handle(SectionItem|string $item): ActionResult
    {
        $item = $this->findItem($item);

        if (! $item) {
            return ActionResult::failure(__('Section item not found.'));
        }

        $item->delete();

        return ActionResult::success(__('Section item deleted.'));
    }

    private function findItem(SectionItem|string $item): ?SectionItem
    {
        if ($item instanceof SectionItem) {
            return $item;
        }

        $model = config('pages.models.section_item', SectionItem::class);

        return $model::query()->where('uuid', $item)->first();
    }
}

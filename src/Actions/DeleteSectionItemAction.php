<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Events\SectionItemDeleted;
use IvanBaric\Pages\Models\SectionItem;

final class DeleteSectionItemAction
{
    use AuthorizesPageActions;

    public function handle(SectionItem|string $item): ActionResult
    {
        $item = $this->findItem($item);

        if (! $item) {
            return ActionResult::failure(__('Section item not found.'));
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $item)) {
            return $result;
        }

        $itemKey = $item->getKey();
        $uuid = (string) $item->uuid;

        DB::transaction(static function () use ($item): void {
            $item->delete();
        });

        SectionItemDeleted::dispatch($itemKey, $uuid);

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

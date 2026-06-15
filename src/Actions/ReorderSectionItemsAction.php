<?php

namespace IvanBaric\Pages\Actions;

use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Models\Section;

final class ReorderSectionItemsAction
{
    /**
     * @param  array<int, string>  $itemUuids
     */
    public function handle(Section|string $section, array $itemUuids): ActionResult
    {
        $section = $this->findSection($section);

        if (! $section) {
            return ActionResult::failure(__('Section not found.'));
        }

        foreach (array_values($itemUuids) as $position => $uuid) {
            $section->items()->where('uuid', $uuid)->update(['sort_order' => $position]);
        }

        return ActionResult::success(__('Section items reordered.'), $section->refresh());
    }

    private function findSection(Section|string $section): ?Section
    {
        if ($section instanceof Section) {
            return $section;
        }

        $model = config('pages.models.section', Section::class);

        return $model::query()->where('uuid', $section)->first();
    }
}

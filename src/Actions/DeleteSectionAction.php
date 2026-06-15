<?php

namespace IvanBaric\Pages\Actions;

use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Models\Section;

final class DeleteSectionAction
{
    public function handle(Section|string $section): ActionResult
    {
        $section = $this->findSection($section);

        if (! $section) {
            return ActionResult::failure(__('Section not found.'));
        }

        $section->delete();

        return ActionResult::success(__('Section deleted.'));
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

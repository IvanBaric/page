<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Events\SectionItemsReordered;
use IvanBaric\Pages\Models\Section;

final class ReorderSectionItemsAction
{
    use AuthorizesPageActions;

    /**
     * @param  array<int, string>  $itemUuids
     */
    public function handle(Section|string $section, array $itemUuids): ActionResult
    {
        $section = $this->findSection($section);

        if (! $section) {
            return ActionResult::failure(__('Section not found.'));
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $section)) {
            return $result;
        }

        DB::transaction(static function () use ($section, $itemUuids): void {
            foreach (array_values($itemUuids) as $position => $uuid) {
                $section->items()->where('uuid', $uuid)->update(['sort_order' => $position]);
            }
        });

        $section->refresh();
        SectionItemsReordered::dispatch($section, array_values($itemUuids));

        return ActionResult::success(__('Section items reordered.'), $section);
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

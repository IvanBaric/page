<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Events\SectionDeleted;
use IvanBaric\Pages\Models\Section;

final class DeleteSectionAction
{
    use AuthorizesPageActions;

    public function handle(Section|string $section): ActionResult
    {
        $section = $this->findSection($section);

        if (! $section) {
            return ActionResult::failure(__('Section not found.'));
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $section)) {
            return $result;
        }

        $sectionKey = $section->getKey();
        $uuid = (string) $section->uuid;

        DB::transaction(static function () use ($section): void {
            $section->delete();
        });

        SectionDeleted::dispatch($sectionKey, $uuid);

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

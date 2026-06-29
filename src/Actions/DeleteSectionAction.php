<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Events\SectionDeleted;
use IvanBaric\Pages\Models\Section;

final class DeleteSectionAction
{
    use AuthorizesPageActions, ResolvesPageModels;

    public function handle(Section|string $section): ActionResult
    {
        $section = $this->resolveSection($section);

        if (! $section) {
            return ActionResult::failure(__('Sekcija nije pronađena.'));
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $section)) {
            return $result;
        }

        $sectionKey = $section->getKey();
        $uuid = (string) $section->uuid;

        DB::transaction(static function () use ($section): void {
            /** @var Section $lockedSection */
            $lockedSection = Section::query()
                ->whereKey($section->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedSection->archive();
        });

        SectionDeleted::dispatch($sectionKey, $uuid);

        return ActionResult::success(__('Sekcija je arhivirana.'));
    }

}

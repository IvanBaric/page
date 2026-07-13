<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Events\SectionUpdated;
use IvanBaric\Pages\Models\Section;

final class ToggleSectionVisibilityAction
{
    use AuthorizesPageActions, ResolvesPageModels;

    public function handle(Section|string $section): ActionResult
    {
        $section = $this->resolveSection($section);

        if (! $section) {
            return ActionResult::error(__('Sekcija nije pronađena.'));
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $section)) {
            return $result;
        }

        DB::transaction(function () use ($section): void {
            $locked = $section->newQuery()->whereKey($section->getKey())->lockForUpdate()->firstOrFail();
            $locked->isVisible() ? $locked->hide() : $locked->show();
        });

        $section->refresh();
        SectionUpdated::dispatch($section);

        return ActionResult::success(
            $section->isVisible() ? __('Sekcija je uključena.') : __('Sekcija je isključena.'),
            $section,
        );
    }
}

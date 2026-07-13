<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Events\SectionUpdated;
use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Models\Section;

final class MoveSectionAction
{
    use AuthorizesPageActions, ResolvesPageModels;

    public function handle(Section|string $source, Page|string $targetPage): ActionResult
    {
        $source = $this->resolveSection($source);
        $targetPage = $this->resolvePage($targetPage);

        if (! $source) {
            return ActionResult::error(__('Sekcija nije pronađena.'));
        }

        if (! $targetPage) {
            return ActionResult::error(__('Odabrana stranica nije pronađena.'));
        }

        if ((int) $source->getAttribute('page_id') === (int) $targetPage->getKey()) {
            return ActionResult::error(__('Sekcija je već na odabranoj stranici.'));
        }

        if ((int) $source->getAttribute('team_id') !== (int) $targetPage->getAttribute('team_id')) {
            return ActionResult::error(__('Sekciju nije moguće premjestiti na odabranu stranicu.'));
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $source)) {
            return $result;
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $targetPage)) {
            return $result;
        }

        $moved = DB::transaction(function () use ($source, $targetPage): Section {
            $sectionClass = $source::class;

            /** @var Section $lockedSection */
            $lockedSection = $sectionClass::query()
                ->whereKey($source->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedSection->forceFill([
                'page_id' => $targetPage->getKey(),
                'sort_order' => ((int) $targetPage->sections()->max('sort_order')) + 1,
            ])->save();

            return $lockedSection->refresh();
        });

        SectionUpdated::dispatch($moved);

        return ActionResult::success(__('Sekcija je premještena.'), $moved);
    }
}

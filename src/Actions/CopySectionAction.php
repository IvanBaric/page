<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Models\Section;

final class CopySectionAction
{
    use AuthorizesPageActions, ResolvesPageModels;

    public function handle(Section|string $source, Page|string $targetPage): ActionResult
    {
        $source = $this->resolveSection($source);
        $targetPage = $this->resolvePage($targetPage);

        if (! $source) {
            return ActionResult::failure(__('Sekcija nije pronađena.'));
        }

        if (! $targetPage) {
            return ActionResult::failure(__('Odabrana stranica nije pronađena.'));
        }

        if ((int) $source->getAttribute('team_id') !== (int) $targetPage->getAttribute('team_id')) {
            return ActionResult::failure(__('Sekciju nije moguće kopirati na odabranu stranicu.'));
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $source)) {
            return $result;
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $targetPage)) {
            return $result;
        }

        $copy = DB::transaction(function () use ($source, $targetPage): Section {
            $source->loadMissing('items');

            $sectionCopy = $source->replicate(['uuid', 'slug', 'lock_version']);
            $sectionCopy->forceFill([
                'page_id' => $targetPage->getKey(),
                'team_id' => $targetPage->getAttribute('team_id'),
                'slug' => null,
                'sort_order' => ((int) $targetPage->sections()->max('sort_order')) + 1,
            ]);
            $sectionCopy->save();

            foreach ($source->items()->orderBy('sort_order')->orderBy('created_at')->get() as $item) {
                $itemCopy = $item->replicate(['uuid', 'slug', 'lock_version']);
                $itemCopy->forceFill([
                    'section_id' => $sectionCopy->getKey(),
                    'team_id' => $targetPage->getAttribute('team_id'),
                    'slug' => null,
                ]);
                $itemCopy->save();
            }

            return $sectionCopy->refresh();
        });

        return ActionResult::success(__('Sekcija je kopirana.'), $copy);
    }
}

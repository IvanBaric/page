<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Events\PageSectionsReordered;
use IvanBaric\Pages\Models\Page;

final class ReorderSectionsAction
{
    use AuthorizesPageActions, ResolvesPageModels;

    /**
     * @param  array<int, string>  $sectionUuids
     */
    public function handle(Page|string $page, array $sectionUuids): ActionResult
    {
        $page = $this->resolvePage($page);

        if (! $page) {
            return ActionResult::error(__('Stranica nije pronađena.'));
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $page)) {
            return $result;
        }

        $validator = Validator::make(['uuids' => $sectionUuids], [
            'uuids' => ['array'],
            'uuids.*' => ['required', 'uuid', 'distinct'],
        ]);

        if ($validator->fails()) {
            return ActionResult::error(__('Redoslijed sekcija nije valjan.'), errors: $validator->errors()->toArray());
        }

        if ($page->sections()->whereIn('uuid', $sectionUuids)->count() !== count($sectionUuids)) {
            return ActionResult::error(__('Redoslijed sadrži sekciju koja ne pripada ovoj stranici.'));
        }

        DB::transaction(static function () use ($page, $sectionUuids): void {
            $page->newQuery()
                ->whereKey($page->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $page->sections()
                ->whereIn('uuid', $sectionUuids)
                ->lockForUpdate()
                ->get();

            foreach ($sectionUuids as $position => $uuid) {
                $page->sections()->where('uuid', $uuid)->update(['sort_order' => $position]);
            }
        });

        $page->refresh();
        PageSectionsReordered::dispatch($page, $sectionUuids);

        return ActionResult::success(__('Redoslijed sekcija je spremljen.'), $page);
    }
}

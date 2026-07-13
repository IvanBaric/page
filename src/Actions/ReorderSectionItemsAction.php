<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Events\SectionItemsReordered;
use IvanBaric\Pages\Models\Section;

final class ReorderSectionItemsAction
{
    use AuthorizesPageActions, ResolvesPageModels;

    /**
     * @param  array<int, string>  $itemUuids
     */
    public function handle(Section|string $section, array $itemUuids): ActionResult
    {
        $section = $this->resolveSection($section);

        if (! $section) {
            return ActionResult::error(__('Sekcija nije pronađena.'));
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $section)) {
            return $result;
        }

        $validator = Validator::make(['uuids' => $itemUuids], [
            'uuids' => ['array'],
            'uuids.*' => ['required', 'uuid', 'distinct'],
        ]);

        if ($validator->fails()) {
            return ActionResult::error(__('Redoslijed stavki nije valjan.'), errors: $validator->errors()->toArray());
        }

        if ($section->items()->whereIn('uuid', $itemUuids)->count() !== count($itemUuids)) {
            return ActionResult::error(__('Redoslijed sadrži stavku koja ne pripada ovoj sekciji.'));
        }

        DB::transaction(static function () use ($section, $itemUuids): void {
            $section->newQuery()
                ->whereKey($section->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $section->items()
                ->whereIn('uuid', $itemUuids)
                ->lockForUpdate()
                ->get();

            foreach ($itemUuids as $position => $uuid) {
                $section->items()->where('uuid', $uuid)->update(['sort_order' => $position]);
            }
        });

        $section->refresh();
        SectionItemsReordered::dispatch($section, $itemUuids);

        return ActionResult::success(__('Redoslijed stavki je spremljen.'), $section);
    }
}

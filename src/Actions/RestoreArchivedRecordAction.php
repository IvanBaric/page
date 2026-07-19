<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesArchivedPageRecords;
use IvanBaric\Pages\Models\Section;
use IvanBaric\Pages\Models\SectionItem;

final class RestoreArchivedRecordAction
{
    use AuthorizesPageActions, ResolvesArchivedPageRecords;

    public function handle(string $type, string $uuid): ActionResult
    {
        $record = $this->resolveArchivedRecord($type, $uuid);
        $ability = $this->archivedRecordAbility($type);

        if (! $record || ! $ability) {
            return ActionResult::error(__('Arhivirani zapis nije pronađen.'));
        }

        if ($result = $this->authorizePageAction($ability, $record)) {
            return $result;
        }

        DB::transaction(function () use ($type, $uuid): void {
            $record = $this->resolveArchivedRecord($type, $uuid, lock: true);

            if (! $record) {
                return;
            }

            $record->restore();

            if ($record instanceof Section || $record instanceof SectionItem) {
                $record->forceFill(['is_visible' => true])->save();
            }
        });

        return ActionResult::success(__('Zapis je vraćen iz arhive.'));
    }
}

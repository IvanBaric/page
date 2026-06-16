<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Events\PageSectionsReordered;
use IvanBaric\Pages\Models\Page;

final class ReorderSectionsAction
{
    use AuthorizesPageActions;

    /**
     * @param  array<int, string>  $sectionUuids
     */
    public function handle(Page|string $page, array $sectionUuids): ActionResult
    {
        $page = $this->findPage($page);

        if (! $page) {
            return ActionResult::failure(__('Page not found.'));
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $page)) {
            return $result;
        }

        DB::transaction(static function () use ($page, $sectionUuids): void {
            foreach (array_values($sectionUuids) as $position => $uuid) {
                $page->sections()->where('uuid', $uuid)->update(['sort_order' => $position]);
            }
        });

        $page->refresh();
        PageSectionsReordered::dispatch($page, array_values($sectionUuids));

        return ActionResult::success(__('Sections reordered.'), $page);
    }

    private function findPage(Page|string $page): ?Page
    {
        if ($page instanceof Page) {
            return $page;
        }

        $model = config('pages.models.page', Page::class);

        return $model::query()->where('uuid', $page)->first();
    }
}

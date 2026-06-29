<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Models\Page;

final class DeleteAdminPageAction
{
    public function handle(Page $page): ActionResult
    {
        if ((bool) $page->getAttribute('is_home')) {
            return ActionResult::failure(__('Naslovnicu nije moguće arhivirati.'));
        }

        $page->archive();

        return ActionResult::success(__('Stranica je arhivirana.'));
    }
}

<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Models\Page;

final class DeleteAdminPageAction
{
    public function handle(Page $page): ActionResult
    {
        if ((bool) $page->getAttribute('is_home')) {
            return ActionResult::error(__('Naslovnicu nije moguće arhivirati.'));
        }

        return app(DeletePageAction::class)->handle($page);
    }
}

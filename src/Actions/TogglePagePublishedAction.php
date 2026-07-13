<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Models\Page;

final class TogglePagePublishedAction
{
    public function handle(Page $page): ActionResult
    {
        if ($page->getAttribute('slug') === 'home' || (bool) $page->getAttribute('is_home')) {
            return app(PublishPageAction::class)->handle($page);
        }

        return (bool) $page->getAttribute('is_published')
            ? app(UnpublishPageAction::class)->handle($page)
            : app(PublishPageAction::class)->handle($page);
    }
}

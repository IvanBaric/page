<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Contracts\PagePublicationGuard;
use IvanBaric\Pages\Models\Page;

final class AllowPagePublication implements PagePublicationGuard
{
    public function inspect(Page $page): ?ActionResult
    {
        return null;
    }
}

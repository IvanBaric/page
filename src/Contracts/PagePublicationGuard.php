<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Contracts;

use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Models\Page;

interface PagePublicationGuard
{
    public function inspect(Page $page): ?ActionResult;
}

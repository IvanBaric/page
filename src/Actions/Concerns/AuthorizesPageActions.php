<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions\Concerns;

use IvanBaric\Pages\Data\ActionResult;

trait AuthorizesPageActions
{
    protected function authorizePageAction(string $ability, mixed $arguments = []): ?ActionResult
    {
        $result = corexis_authorization_result($ability, $arguments);

        return $result ? ActionResult::fromCorexis($result) : null;
    }
}

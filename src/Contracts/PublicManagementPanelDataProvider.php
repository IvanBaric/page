<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Contracts;

use IvanBaric\Pages\Data\PublicManagementPanel;

interface PublicManagementPanelDataProvider
{
    /** @return array<string, mixed> */
    public function data(PublicManagementPanel $panel): array;
}

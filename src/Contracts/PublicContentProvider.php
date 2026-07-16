<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Contracts;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use IvanBaric\Pages\Data\PublicContentContext;

interface PublicContentProvider
{
    public function render(Request $request, PublicContentContext $context): View;
}

<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Contracts;

use Illuminate\Http\Request;
use IvanBaric\Pages\Data\PublicSiteSubject;

interface PublicSiteSubjectResolver
{
    public function resolve(Request $request, string $slug): ?PublicSiteSubject;
}

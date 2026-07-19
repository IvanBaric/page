<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use IvanBaric\Pages\Models\Page;

interface PublicPageSeoDataProvider
{
    /**
     * @param  Collection<int, Page>  $publicPages
     * @return array<string, mixed>|null
     */
    public function page(Model $subject, Page $page, Collection $publicPages): ?array;
}

<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use IvanBaric\Pages\Models\Page;

interface PublicPageViewTracker
{
    public function track(Request $request, Model $subject, Page $page): void;
}

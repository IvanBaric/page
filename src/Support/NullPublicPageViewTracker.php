<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use IvanBaric\Pages\Contracts\PublicPageViewTracker;
use IvanBaric\Pages\Models\Page;

final class NullPublicPageViewTracker implements PublicPageViewTracker
{
    public function track(Request $request, Model $subject, Page $page): void {}
}

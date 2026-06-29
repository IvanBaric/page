<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Models\Page;

final class TogglePagePublishedAction
{
    public function handle(Page $page): ActionResult
    {
        if ($page->getAttribute('slug') === 'home' || (bool) $page->getAttribute('is_home')) {
            $page->forceFill([
                'is_published' => true,
                'status' => 'published',
                'published_at' => $page->getAttribute('published_at') ?? now(),
            ])->save();

            return ActionResult::failure(__('Naslovnica mora ostati vidljiva.'), code: 'home_page_required');
        }

        $isPublished = ! (bool) $page->getAttribute('is_published');

        $page->forceFill([
            'is_published' => $isPublished,
            'status' => $isPublished ? 'published' : 'draft',
            'published_at' => $isPublished ? ($page->getAttribute('published_at') ?? now()) : null,
        ])->save();

        return ActionResult::success(
            $isPublished
                ? __('Stranica je uključena.')
                : __('Stranica je isključena.'),
        );
    }
}

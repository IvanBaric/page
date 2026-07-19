<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Contracts\PagePublicationGuard;
use IvanBaric\Pages\Events\PagePublished;
use IvanBaric\Pages\Models\Page;

final class PublishPageAction
{
    use AuthorizesPageActions, ResolvesPageModels;

    public function __construct(private readonly PagePublicationGuard $publicationGuard) {}

    public function handle(Page|string $page): ActionResult
    {
        $page = $this->resolvePage($page);

        if (! $page) {
            return ActionResult::error(__('Stranica nije pronađena.'));
        }

        if ($result = $this->authorizePageAction('pages.publish', $page)) {
            return $result;
        }

        if ($result = $this->publicationGuard->inspect($page)) {
            return $result;
        }

        $ancestor = $page->parent;
        $visited = [];

        while ($ancestor instanceof Page && ! isset($visited[(string) $ancestor->getKey()])) {
            $visited[(string) $ancestor->getKey()] = true;

            if (! $ancestor->isPublished()) {
                return ActionResult::error(__('Prije ove stranice objavite sve njezine nadređene stranice.'));
            }

            $ancestor = $ancestor->parent;
        }

        DB::transaction(static function () use ($page): void {
            /** @var Page $lockedPage */
            $lockedPage = $page->newQuery()
                ->whereKey($page->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPage->publish();
        });

        $page->refresh();
        PagePublished::dispatch($page);

        return ActionResult::success(__('Stranica je objavljena.'), $page);
    }
}

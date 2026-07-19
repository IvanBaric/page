<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Events\PageUpdated;
use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Support\PageHierarchy;

final class MovePageAction
{
    use AuthorizesPageActions, ResolvesPageModels;

    public function __construct(private readonly PageHierarchy $hierarchy) {}

    public function handle(Page|string $page, ?string $parentUuid, int $position): ActionResult
    {
        $page = $this->resolvePage($page);

        if (! $page) {
            return ActionResult::error(__('Stranica nije pronađena.'));
        }

        $tenantId = corexis_tenant_id();

        if (is_numeric($tenantId) && (int) $page->team_id !== (int) $tenantId) {
            return ActionResult::error(__('Stranica nije pronađena.'));
        }

        if ($result = $this->authorizePageAction('pages.update', $page)) {
            return $result;
        }

        if ($page->is_home && $parentUuid !== null) {
            return ActionResult::error(__('Naslovnica mora ostati u glavnom izborniku.'));
        }

        $parent = $this->resolveParent($page, $parentUuid);

        if ($parentUuid !== null && ! $parent) {
            return ActionResult::error(__('Odabrana nadređena stranica nije dostupna.'));
        }

        if (! $this->hierarchy->canMoveUnder($page, $parent)) {
            return ActionResult::error(__('Odabrano mjesto prelazi dopuštene :count razine ili pripada podstablu ove stranice.', ['count' => $this->hierarchy->maxDepth()]));
        }

        $moved = DB::transaction(function () use ($page, $parent, $position): ?Page {
            /** @var Page $lockedPage */
            $lockedPage = $page->newQuery()
                ->whereKey($page->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $oldParentId = $lockedPage->parent_id;
            $newParentId = $parent?->getKey();

            if ($parent) {
                $validParent = $page->newQuery()
                    ->whereKey($parent->getKey())
                    ->where('team_id', $lockedPage->team_id)
                    ->where('is_home', false)
                    ->lockForUpdate()
                    ->first();

                if (! $validParent instanceof Page || ! $this->hierarchy->canMoveUnder($lockedPage, $validParent)) {
                    return null;
                }
            }

            if ($oldParentId !== $newParentId) {
                $lockedPage->forceFill(['parent_id' => $newParentId])->save();
                $this->normalizeGroup($lockedPage, $oldParentId);
            }

            $this->normalizeGroup($lockedPage, $newParentId, $lockedPage, $position);

            return $lockedPage->refresh();
        });

        if (! $moved) {
            return ActionResult::error(__('Odabrana nadređena stranica više nije dostupna.'));
        }

        PageUpdated::dispatch($moved);

        return ActionResult::success(__('Položaj stranice je spremljen.'), $moved);
    }

    private function resolveParent(Page $page, ?string $parentUuid): ?Page
    {
        if ($parentUuid === null) {
            return null;
        }

        return $page->newQuery()
            ->where('team_id', $page->team_id)
            ->where('uuid', $parentUuid)
            ->whereKeyNot($page->getKey())
            ->where('is_home', false)
            ->first();
    }

    private function normalizeGroup(Page $page, ?int $parentId, ?Page $moving = null, ?int $position = null): void
    {
        /** @var Collection<int, Page> $siblings */
        $siblings = $page->newQuery()
            ->where('team_id', $page->team_id)
            ->when(
                $parentId === null,
                fn (Builder $query): Builder => $query->whereNull('parent_id'),
                fn (Builder $query): Builder => $query->where('parent_id', $parentId),
            )
            ->lockForUpdate()
            ->ordered()
            ->get();

        if ($moving) {
            $siblings = $siblings
                ->reject(fn (Page $sibling): bool => $sibling->is($moving))
                ->values();
            $siblings->splice(max(0, min((int) $position, $siblings->count())), 0, [$moving]);
        }

        $siblings->values()->each(function (Page $sibling, int $index): void {
            if ($sibling->sort_order !== $index) {
                $sibling->forceFill(['sort_order' => $index])->save();
            }
        });
    }
}

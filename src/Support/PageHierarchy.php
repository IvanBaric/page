<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use Illuminate\Support\Collection;
use IvanBaric\Pages\Models\Page;

final class PageHierarchy
{
    public function maxDepth(): int
    {
        return max(1, (int) config('pages.hierarchy.max_depth', 3));
    }

    /** @param Collection<int, Page>|null $pages */
    public function depth(Page $page, ?Collection $pages = null): int
    {
        $depth = 1;
        $visited = [(string) $page->getKey() => true];
        $parentId = $page->getAttribute('parent_id');

        while ($parentId !== null) {
            if (isset($visited[(string) $parentId])) {
                return $this->maxDepth() + 1;
            }

            $parent = $this->findByKey($page, $parentId, $pages);

            if (! $parent) {
                break;
            }

            $visited[(string) $parentId] = true;
            $depth++;
            $parentId = $parent->getAttribute('parent_id');
        }

        return $depth;
    }

    /** @param Collection<int, Page>|null $pages */
    public function subtreeHeight(Page $page, ?Collection $pages = null): int
    {
        $pages ??= $this->tenantPages($page);
        $height = 1;
        $levelIds = collect([$page->getKey()]);
        $visited = [(string) $page->getKey() => true];

        while ($levelIds->isNotEmpty()) {
            $children = $pages
                ->filter(fn (Page $candidate): bool => $levelIds->contains($candidate->getAttribute('parent_id')))
                ->reject(fn (Page $candidate): bool => isset($visited[(string) $candidate->getKey()]))
                ->values();

            if ($children->isEmpty()) {
                break;
            }

            $height++;
            $children->each(function (Page $child) use (&$visited): void {
                $visited[(string) $child->getKey()] = true;
            });
            $levelIds = $children->pluck($page->getKeyName());
        }

        return $height;
    }

    /** @param Collection<int, Page>|null $pages */
    public function descendantIds(Page $page, ?Collection $pages = null): array
    {
        $pages ??= $this->tenantPages($page);
        $descendantIds = [];
        $levelIds = collect([$page->getKey()]);
        $visited = [(string) $page->getKey() => true];

        while ($levelIds->isNotEmpty()) {
            $children = $pages
                ->filter(fn (Page $candidate): bool => $levelIds->contains($candidate->getAttribute('parent_id')))
                ->reject(fn (Page $candidate): bool => isset($visited[(string) $candidate->getKey()]))
                ->values();

            if ($children->isEmpty()) {
                break;
            }

            $children->each(function (Page $child) use (&$visited): void {
                $visited[(string) $child->getKey()] = true;
            });
            $levelIds = $children->pluck($page->getKeyName());
            $descendantIds = [...$descendantIds, ...$levelIds->all()];
        }

        return $descendantIds;
    }

    /** @param Collection<int, Page>|null $pages */
    public function canMoveUnder(Page $page, ?Page $parent, ?Collection $pages = null): bool
    {
        if ($parent === null) {
            return $this->subtreeHeight($page, $pages) <= $this->maxDepth();
        }

        if (
            $parent->is($page)
            || (bool) $parent->getAttribute('is_home')
            || (int) $parent->getAttribute('team_id') !== (int) $page->getAttribute('team_id')
            || in_array($parent->getKey(), $this->descendantIds($page, $pages), true)
        ) {
            return false;
        }

        return $this->depth($parent, $pages) + $this->subtreeHeight($page, $pages) <= $this->maxDepth();
    }

    /**
     * @param  Collection<int, Page>  $pages
     * @return array<int, array{uuid: string, label: string, path: string, depth: int, resulting_depth: int}>
     */
    public function parentOptions(Collection $pages, ?Page $movingPage = null): array
    {
        $excludedIds = $movingPage
            ? [$movingPage->getKey(), ...$this->descendantIds($movingPage, $pages)]
            : [];
        $subtreeHeight = $movingPage ? $this->subtreeHeight($movingPage, $pages) : 1;

        return $this->flatten($pages)
            ->filter(function (Page $candidate) use ($pages, $excludedIds, $subtreeHeight): bool {
                return ! (bool) $candidate->getAttribute('is_home')
                    && ! in_array($candidate->getKey(), $excludedIds, true)
                    && $this->depth($candidate, $pages) + $subtreeHeight <= $this->maxDepth();
            })
            ->map(function (Page $candidate) use ($pages): array {
                $depth = $this->depth($candidate, $pages);

                return [
                    'uuid' => (string) $candidate->getAttribute('uuid'),
                    'label' => $candidate->localized('title') ?: (string) $candidate->getAttribute('slug'),
                    'path' => $this->path($candidate, $pages),
                    'depth' => $depth,
                    'resulting_depth' => $depth + 1,
                ];
            })
            ->values()
            ->all();
    }

    /** @param Collection<int, Page> $pages */
    public function path(Page $page, Collection $pages): string
    {
        $labels = [];
        $current = $page;
        $visited = [];

        while ($current && ! isset($visited[(string) $current->getKey()])) {
            $visited[(string) $current->getKey()] = true;
            array_unshift($labels, $current->localized('title') ?: (string) $current->getAttribute('slug'));
            $parentId = $current->getAttribute('parent_id');
            $current = $parentId === null ? null : $pages->firstWhere($current->getKeyName(), $parentId);
        }

        return implode(' / ', array_filter($labels));
    }

    /** @param Collection<int, Page>|null $pages */
    public function slugPath(Page $page, ?Collection $pages = null): string
    {
        if ((bool) $page->getAttribute('is_home')) {
            return '';
        }

        $pages ??= $this->tenantPages($page);
        $slugs = [];
        $current = $page;
        $visited = [];

        while ($current && ! isset($visited[(string) $current->getKey()])) {
            $visited[(string) $current->getKey()] = true;
            $slug = trim((string) $current->getAttribute('slug'), '/');

            if ($slug !== '' && ! (bool) $current->getAttribute('is_home')) {
                array_unshift($slugs, $slug);
            }

            $parentId = $current->getAttribute('parent_id');
            $current = $parentId === null
                ? null
                : $pages->firstWhere($current->getKeyName(), $parentId);
        }

        return implode('/', $slugs);
    }

    /**
     * @param  Collection<int, Page>  $pages
     * @return Collection<int, Page>
     */
    public function flatten(Collection $pages): Collection
    {
        $ordered = collect();
        $visited = [];

        $append = function (Page $page) use (&$append, $pages, $ordered, &$visited): void {
            if (isset($visited[(string) $page->getKey()])) {
                return;
            }

            $visited[(string) $page->getKey()] = true;
            $ordered->push($page);

            $pages
                ->filter(fn (Page $candidate): bool => (string) $candidate->getAttribute('parent_id') === (string) $page->getKey())
                ->each($append);
        };

        $pages->whereNull('parent_id')->each($append);
        $pages->each($append);

        return $ordered->values();
    }

    /** @return Collection<int, Page> */
    private function tenantPages(Page $page): Collection
    {
        return $page->newQuery()
            ->where('team_id', $page->getAttribute('team_id'))
            ->ordered()
            ->get();
    }

    /** @param Collection<int, Page>|null $pages */
    private function findByKey(Page $page, mixed $key, ?Collection $pages): ?Page
    {
        if ($pages) {
            $candidate = $pages->firstWhere($page->getKeyName(), $key);

            return $candidate instanceof Page ? $candidate : null;
        }

        return $page->newQuery()
            ->where('team_id', $page->getAttribute('team_id'))
            ->whereKey($key)
            ->first();
    }
}

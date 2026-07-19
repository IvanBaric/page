<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use IvanBaric\Pages\Data\PublicSiteSubject;
use IvanBaric\Pages\Models\Page;

final class PublicSitePageResolver
{
    private ?bool $pageKeyColumnExists = null;

    /** @return array{page: Page, publicPages: Collection<int, Page>} */
    public function resolve(PublicSiteSubject $subject, ?string $pagePath): array
    {
        $resolved = $this->resolveInternal($subject, $pagePath, allowFlatFallback: true);

        if ($resolved !== null) {
            return $resolved;
        }

        $pageModel = PagesModels::page();

        throw (new ModelNotFoundException)->setModel($pageModel);
    }

    /** @return array{page: Page, publicPages: Collection<int, Page>}|null */
    public function resolveExact(PublicSiteSubject $subject, ?string $pagePath): ?array
    {
        return $this->resolveInternal($subject, $pagePath, allowFlatFallback: false);
    }

    /** @return array{page: Page, publicPages: Collection<int, Page>}|null */
    private function resolveInternal(PublicSiteSubject $subject, ?string $pagePath, bool $allowFlatFallback): ?array
    {
        $pageModel = PagesModels::page();

        /** @var Collection<int, Page> $publicPages */
        $publicPages = $pageModel::query()
            ->forTenant($subject->tenantId)
            ->published()
            ->navigationVisible()
            ->ordered()
            ->get($this->publicPageColumns());

        $segments = $this->pathSegments($pagePath);

        if ($segments === null) {
            return null;
        }

        $page = $segments === []
            ? $publicPages->first(fn (Page $candidate): bool => (bool) $candidate->getAttribute('is_home'))
            : $this->pageForSegments($publicPages, $segments);

        if (! $page && $allowFlatFallback && count($segments) === 1) {
            $page = $this->flatPage($publicPages, $segments[0]);
        }

        if (! $page) {
            return null;
        }

        /** @var Page|null $resolvedPage */
        $resolvedPage = $pageModel::query()
            ->forTenant($subject->tenantId)
            ->published()
            ->navigationVisible()
            ->with('visibleSections')
            ->find($page->getKey());

        if (! $resolvedPage) {
            return null;
        }

        if ($publicPages->count() === 1) {
            $resolvedPage->load('visibleSections.visibleItems');
        }

        return ['page' => $resolvedPage, 'publicPages' => $publicPages];
    }

    /** @param Collection<int, Page> $pages */
    private function pageForSegments(Collection $pages, array $segments): ?Page
    {
        $parentId = null;
        $page = null;

        foreach ($segments as $segment) {
            $page = $pages->first(function (Page $candidate) use ($parentId, $segment): bool {
                $candidateParentId = $candidate->getAttribute('parent_id');
                $sameParent = $parentId === null
                    ? $candidateParentId === null
                    : (string) $candidateParentId === (string) $parentId;

                return $sameParent && $this->matchesSegment($candidate, $segment);
            });

            if (! $page) {
                return null;
            }

            $parentId = $page->getKey();
        }

        return $page;
    }

    /** @param Collection<int, Page> $pages */
    private function flatPage(Collection $pages, string $segment): ?Page
    {
        return $pages->first(fn (Page $candidate): bool => $this->matchesSegment($candidate, $segment));
    }

    private function matchesSegment(Page $page, string $segment): bool
    {
        $slugCandidates = $this->pageSlugCandidates($segment);

        if (in_array((string) $page->getAttribute('slug'), $slugCandidates, true)) {
            return true;
        }

        return $this->hasPageKeyColumn()
            && in_array((string) $page->getAttribute('page_key'), $this->pageKeyCandidates($segment), true);
    }

    /** @return array<int, string>|null */
    private function pathSegments(?string $pagePath): ?array
    {
        if ($pagePath === null || trim($pagePath, '/') === '') {
            return [];
        }

        $segments = array_values(array_filter(
            explode('/', trim($pagePath, '/')),
            fn (string $segment): bool => $segment !== '',
        ));

        if (count($segments) > max(1, (int) config('pages.hierarchy.max_depth', 3))) {
            return null;
        }

        $normalized = array_map(fn (string $segment): string => Str::slug($segment), $segments);

        return in_array('', $normalized, true) ? null : $normalized;
    }

    /** @return array<int, string> */
    private function pageSlugCandidates(?string $pageSlug): array
    {
        if ($pageSlug === null || $pageSlug === '') {
            return [];
        }

        $normalized = Str::slug($pageSlug);
        $aliases = (array) config('pages.public_slug_aliases', []);
        $canonical = (string) ($aliases[$normalized] ?? $normalized);

        return $canonical === 'home'
            ? []
            : array_values(array_unique([$normalized, $canonical]));
    }

    /** @return array<int, string> */
    private function pageKeyCandidates(?string $pageSlug): array
    {
        if ($pageSlug === null || $pageSlug === '') {
            return [];
        }

        $normalized = Str::slug($pageSlug);
        $aliases = (array) config('pages.public_slug_aliases', []);
        $canonical = (string) ($aliases[$normalized] ?? $normalized);

        return array_values(array_unique([$normalized, $canonical]));
    }

    private function hasPageKeyColumn(): bool
    {
        return $this->pageKeyColumnExists ??= Schema::hasColumn(PagesConfigResolver::pagesTable(), 'page_key');
    }

    /** @return array<int, string> */
    private function publicPageColumns(): array
    {
        $columns = ['id', 'team_id', 'parent_id', 'uuid', 'slug', 'title', 'navigation_type', 'navigation_url', 'navigation_target', 'is_home', 'sort_order', 'created_at'];

        if ($this->hasPageKeyColumn()) {
            $columns[] = 'page_key';
        }

        return $columns;
    }
}

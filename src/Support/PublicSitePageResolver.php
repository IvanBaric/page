<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use IvanBaric\Pages\Data\PublicSiteSubject;
use IvanBaric\Pages\Models\Page;

final class PublicSitePageResolver
{
    private ?bool $pageKeyColumnExists = null;

    /** @return array{page: Page, publicPages: Collection<int, Page>} */
    public function resolve(PublicSiteSubject $subject, ?string $pageSlug): array
    {
        $pageModel = PagesModels::page();
        $pageSlugCandidates = $this->pageSlugCandidates($pageSlug);
        $pageKeyCandidates = $this->pageKeyCandidates($pageSlug);

        /** @var Page $page */
        $page = $pageModel::query()
            ->forTenant($subject->tenantId)
            ->published()
            ->when($pageSlugCandidates === [], fn (Builder $query): Builder => $query->home())
            ->when($pageSlugCandidates !== [], function (Builder $query) use ($pageSlugCandidates, $pageKeyCandidates): void {
                $query->where(function (Builder $query) use ($pageSlugCandidates, $pageKeyCandidates): void {
                    $query->whereIn('slug', $pageSlugCandidates);

                    if ($this->hasPageKeyColumn()) {
                        $query->orWhereIn('page_key', $pageKeyCandidates);
                    }
                });
            })
            ->with('visibleSections')
            ->firstOrFail();

        /** @var Collection<int, Page> $publicPages */
        $publicPages = $pageModel::query()
            ->forTenant($subject->tenantId)
            ->published()
            ->navigationVisible()
            ->ordered()
            ->get($this->publicPageColumns());

        if ($publicPages->count() === 1) {
            $publicPages->first()?->load([
                'visibleSections' => fn ($query) => $query->select([
                    'id', 'page_id', 'team_id', 'uuid', 'type', 'title', 'is_visible', 'settings', 'sort_order', 'created_at',
                ]),
                'visibleSections.visibleItems' => fn ($query) => $query->select([
                    'id', 'section_id', 'team_id', 'url', 'is_visible', 'sort_order', 'created_at',
                ]),
            ]);
        }

        return ['page' => $page, 'publicPages' => $publicPages];
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
        $columns = ['id', 'team_id', 'parent_id', 'uuid', 'slug', 'title', 'is_home', 'sort_order', 'created_at'];

        if ($this->hasPageKeyColumn()) {
            $columns[] = 'page_key';
        }

        return $columns;
    }
}

<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions\Concerns;

use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Models\Section;
use IvanBaric\Pages\Models\SectionItem;
use IvanBaric\Pages\Support\TeamResolver;

trait ResolvesPageModels
{
    protected function resolvePage(Page|string $page): ?Page
    {
        if ($page instanceof Page) {
            return $page;
        }

        $model = config('pages.models.page', Page::class);

        return $model::query()
            ->forTeam($this->currentPageTeamId())
            ->where('uuid', $page)
            ->first();
    }

    protected function resolveSection(Section|string $section): ?Section
    {
        if ($section instanceof Section) {
            return $section;
        }

        $model = config('pages.models.section', Section::class);

        return $model::query()
            ->forTeam($this->currentPageTeamId())
            ->where('uuid', $section)
            ->first();
    }

    protected function resolveSectionItem(SectionItem|string $item): ?SectionItem
    {
        if ($item instanceof SectionItem) {
            return $item;
        }

        $model = config('pages.models.section_item', SectionItem::class);

        return $model::query()
            ->forTeam($this->currentPageTeamId())
            ->where('uuid', $item)
            ->first();
    }

    protected function currentPageTeamId(): ?int
    {
        return app(TeamResolver::class)->resolve();
    }
}

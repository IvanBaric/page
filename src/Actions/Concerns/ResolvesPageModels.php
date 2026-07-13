<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions\Concerns;

use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Models\Section;
use IvanBaric\Pages\Models\SectionItem;
use IvanBaric\Pages\Support\PagesModels;

trait ResolvesPageModels
{
    protected function resolvePage(Page|string $page): ?Page
    {
        if ($page instanceof Page) {
            return $page->newQuery()->whereKey($page->getKey())->first();
        }

        $model = PagesModels::page();

        return $model::query()->where('uuid', $page)->first();
    }

    protected function resolveSection(Section|string $section): ?Section
    {
        if ($section instanceof Section) {
            return $section->newQuery()->whereKey($section->getKey())->first();
        }

        $model = PagesModels::section();

        return $model::query()->where('uuid', $section)->first();
    }

    protected function resolveSectionItem(SectionItem|string $item): ?SectionItem
    {
        if ($item instanceof SectionItem) {
            return $item->newQuery()->whereKey($item->getKey())->first();
        }

        $model = PagesModels::sectionItem();

        return $model::query()->where('uuid', $item)->first();
    }
}

<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions\Concerns;

use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Models\Section;
use IvanBaric\Pages\Models\SectionItem;
use IvanBaric\Pages\Support\PagesModels;

trait ResolvesArchivedPageRecords
{
    protected function resolveArchivedRecord(string $type, string $uuid, bool $lock = false): Page|Section|SectionItem|null
    {
        $query = match ($type) {
            'page' => PagesModels::page()::onlyTrashed()->where('uuid', $uuid),
            'section' => PagesModels::section()::onlyTrashed()->where('uuid', $uuid),
            'item' => PagesModels::sectionItem()::onlyTrashed()->where('uuid', $uuid),
            default => null,
        };

        if ($query === null) {
            return null;
        }

        return ($lock ? $query->lockForUpdate() : $query)->first();
    }

    protected function archivedRecordAbility(string $type): ?string
    {
        return match ($type) {
            'page' => 'pages.delete',
            'section', 'item' => 'pages.sections.manage',
            default => null,
        };
    }
}

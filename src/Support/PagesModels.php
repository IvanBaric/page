<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Models\Section;
use IvanBaric\Pages\Models\SectionItem;

final class PagesModels
{
    /** @return class-string<Page> */
    public static function page(): string
    {
        return PagesConfigResolver::pageModel();
    }

    /** @return class-string<Section> */
    public static function section(): string
    {
        return PagesConfigResolver::sectionModel();
    }

    /** @return class-string<SectionItem> */
    public static function sectionItem(): string
    {
        return PagesConfigResolver::sectionItemModel();
    }
}

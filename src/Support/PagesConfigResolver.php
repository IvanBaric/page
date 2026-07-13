<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Corexis\Support\ConfigResolver;
use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Models\Section;
use IvanBaric\Pages\Models\SectionItem;

final class PagesConfigResolver
{
    /** @return class-string<Page> */
    public static function pageModel(): string
    {
        return app(ConfigResolver::class)->model(
            key: 'pages.models.page',
            default: Page::class,
            expectedType: Page::class,
        );
    }

    /** @return class-string<Section> */
    public static function sectionModel(): string
    {
        return app(ConfigResolver::class)->model(
            key: 'pages.models.section',
            default: Section::class,
            expectedType: Section::class,
        );
    }

    /** @return class-string<SectionItem> */
    public static function sectionItemModel(): string
    {
        return app(ConfigResolver::class)->model(
            key: 'pages.models.section_item',
            default: SectionItem::class,
            expectedType: SectionItem::class,
        );
    }

    public static function pagesTable(): string
    {
        return app(ConfigResolver::class)->table(
            key: 'pages.tables.pages',
            default: 'pages',
        );
    }

    public static function sectionsTable(): string
    {
        return app(ConfigResolver::class)->table(
            key: 'pages.tables.sections',
            default: 'sections',
        );
    }

    public static function sectionItemsTable(): string
    {
        return app(ConfigResolver::class)->table(
            key: 'pages.tables.section_items',
            default: 'section_items',
        );
    }

    /** @return class-string<Model>|null */
    public static function singletonModel(): ?string
    {
        $configured = config('pages.admin_index.singleton_model');

        if ($configured === null || $configured === '') {
            return null;
        }

        return app(ConfigResolver::class)->model(
            key: 'pages.admin_index.singleton_model',
            default: Page::class,
            expectedType: Model::class,
        );
    }
}

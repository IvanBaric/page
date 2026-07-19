<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use IvanBaric\Pages\Models\Page;

final readonly class PublicSiteUrl
{
    public function __construct(private PageHierarchy $hierarchy) {}

    /** @param Collection<int, Page>|null $pages */
    public function page(mixed $subject, mixed $page = null, ?Collection $pages = null): ?string
    {
        $slugColumn = (string) config('pages.public_site.subject.slug_column', 'slug');
        $subjectSlug = data_get($subject, $slugColumn);

        $pageSlug = $page instanceof Page
            ? $this->hierarchy->slugPath($page, $pages)
            : (is_string($page) ? $page : null);

        return is_string($subjectSlug) && $subjectSlug !== ''
            ? $this->pageForSlug($subjectSlug, $pageSlug)
            : null;
    }

    /** @param Collection<int, Page>|null $pages */
    public function content(mixed $subject, Page $page, string $contentSlug, ?Collection $pages = null): ?string
    {
        $slugColumn = (string) config('pages.public_site.subject.slug_column', 'slug');
        $subjectSlug = data_get($subject, $slugColumn);

        if (! is_string($subjectSlug) || $subjectSlug === '') {
            return null;
        }

        return $this->contentForSlug(
            $subjectSlug,
            $this->hierarchy->slugPath($page, $pages),
            $contentSlug,
        );
    }

    public function pageForSlug(string $subjectSlug, ?string $pageSlug = null): ?string
    {
        $routeName = (string) config('pages.public_site.route.name', '');

        if ($routeName === '' || ! Route::has($routeName)) {
            return null;
        }

        $parameters = [
            (string) config('pages.public_site.route.subject_parameter', 'subjectSlug') => $subjectSlug,
        ];

        if (filled($pageSlug)) {
            $parameters[(string) config('pages.public_site.route.page_parameter', 'pageSlug')] = $pageSlug;
        }

        return route($routeName, $parameters);
    }

    public function contentForSlug(string $subjectSlug, string $pageSlug, string $contentSlug): ?string
    {
        $routeName = (string) config('pages.public_site.content_route.name', '');

        if ($routeName === '' || ! Route::has($routeName)) {
            return null;
        }

        return route($routeName, [
            (string) config('pages.public_site.route.subject_parameter', 'subjectSlug') => $subjectSlug,
            (string) config('pages.public_site.route.page_parameter', 'pageSlug') => $pageSlug,
            (string) config('pages.public_site.content_route.content_parameter', 'contentSlug') => $contentSlug,
        ]);
    }
}

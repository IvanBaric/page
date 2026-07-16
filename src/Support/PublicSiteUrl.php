<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use Illuminate\Support\Facades\Route;

final readonly class PublicSiteUrl
{
    public function page(mixed $subject, ?string $pageSlug = null): ?string
    {
        $slugColumn = (string) config('pages.public_site.subject.slug_column', 'slug');
        $subjectSlug = data_get($subject, $slugColumn);

        return is_string($subjectSlug) && $subjectSlug !== ''
            ? $this->pageForSlug($subjectSlug, $pageSlug)
            : null;
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

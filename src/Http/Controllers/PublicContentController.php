<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use IvanBaric\Pages\Contracts\PublicSiteSubjectResolver;
use IvanBaric\Pages\Data\PublicContentContext;
use IvanBaric\Pages\Support\PublicContentProviderRegistry;
use IvanBaric\Pages\Support\PublicSitePageResolver;

final readonly class PublicContentController
{
    public function __construct(
        private PublicSiteSubjectResolver $subjects,
        private PublicSitePageResolver $pages,
        private PublicContentProviderRegistry $providers,
    ) {}

    public function __invoke(Request $request): View
    {
        $subjectParameter = (string) config('pages.public_site.route.subject_parameter', 'subjectSlug');
        $pageParameter = (string) config('pages.public_site.route.page_parameter', 'pageSlug');
        $contentParameter = (string) config('pages.public_site.content_route.content_parameter', 'contentSlug');
        $subjectSlug = $request->route($subjectParameter);
        $pageSlug = $request->route($pageParameter);
        $contentSlug = $request->route($contentParameter);

        abort_unless(is_string($subjectSlug) && $subjectSlug !== '', 404);
        abort_unless(is_string($pageSlug) && $pageSlug !== '', 404);
        abort_unless(is_string($contentSlug) && $contentSlug !== '', 404);

        $subject = $this->subjects->resolve($request, $subjectSlug);
        abort_unless($subject !== null, 404);

        $resolved = $this->pages->resolve($subject, $pageSlug);
        $provider = $this->providers->forPage($resolved['page']);
        abort_unless($provider !== null, 404);

        return $provider->render($request, new PublicContentContext(
            subject: $subject,
            page: $resolved['page'],
            publicPages: $resolved['publicPages'],
            contentSlug: $contentSlug,
        ));
    }
}

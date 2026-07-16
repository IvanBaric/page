<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use IvanBaric\Corexis\Support\PublicEmptyStatePreview;
use IvanBaric\Pages\Contracts\PublicPageViewTracker;
use IvanBaric\Pages\Contracts\PublicSiteSubjectResolver;
use IvanBaric\Pages\Support\PublicSitePageResolver;

final readonly class PublicPageController
{
    public function __construct(
        private PublicSiteSubjectResolver $subjects,
        private PublicSitePageResolver $pages,
        private PublicPageViewTracker $tracker,
        private PublicEmptyStatePreview $emptyStatePreview,
    ) {}

    public function __invoke(Request $request): View
    {
        $subjectParameter = (string) config('pages.public_site.route.subject_parameter', 'subjectSlug');
        $pageParameter = (string) config('pages.public_site.route.page_parameter', 'pageSlug');
        $subjectSlug = $request->route($subjectParameter);
        $pageSlug = $request->route($pageParameter);

        abort_unless(is_string($subjectSlug) && $subjectSlug !== '', 404);
        abort_unless($pageSlug === null || is_string($pageSlug), 404);

        $subject = $this->subjects->resolve($request, $subjectSlug);
        abort_unless($subject !== null, 404);

        $resolved = $this->pages->resolve($subject, $pageSlug);
        $page = $resolved['page'];

        if (! $this->emptyStatePreview->enabledForTeam($subject->tenantId)) {
            $this->tracker->track($request, $subject->model, $page);
        }

        $subjectVariable = (string) config('pages.public_site.view_subject_variable', 'subject');
        $subjectVariable = preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $subjectVariable) === 1
            ? $subjectVariable
            : 'subject';
        $view = (string) config('pages.public_site.view', 'pages::public-site.page');

        return view($view, [
            'subject' => $subject->model,
            $subjectVariable => $subject->model,
            'page' => $page,
            'publicPages' => $resolved['publicPages'],
        ]);
    }
}

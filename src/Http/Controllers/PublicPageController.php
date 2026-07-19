<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use IvanBaric\Corexis\Support\PublicEmptyStatePreview;
use IvanBaric\Pages\Contracts\PublicPageSeoDataProvider;
use IvanBaric\Pages\Contracts\PublicPageViewTracker;
use IvanBaric\Pages\Contracts\PublicSiteSubjectResolver;
use IvanBaric\Pages\Data\PublicSiteSubject;
use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Support\PageHierarchy;
use IvanBaric\Pages\Support\PublicSitePageResolver;
use IvanBaric\Pages\Support\PublicSiteUrl;

final readonly class PublicPageController
{
    public function __construct(
        private PublicSiteSubjectResolver $subjects,
        private PublicSitePageResolver $pages,
        private PublicPageViewTracker $tracker,
        private PublicEmptyStatePreview $emptyStatePreview,
        private PageHierarchy $hierarchy,
        private PublicSiteUrl $urls,
    ) {}

    public function __invoke(Request $request): View|RedirectResponse
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

        $canonicalPath = $this->hierarchy->slugPath($resolved['page'], $resolved['publicPages']);

        if (trim((string) $pageSlug, '/') !== $canonicalPath) {
            $canonicalUrl = $this->urls->page($subject->model, $resolved['page'], $resolved['publicPages']);

            if ($canonicalUrl !== null) {
                return redirect()->to($canonicalUrl, 301);
            }
        }

        return $this->renderResolved($request, $subject, $resolved);
    }

    /** @param array{page: Page, publicPages: Collection<int, Page>} $resolved */
    public function renderResolved(Request $request, PublicSiteSubject $subject, array $resolved): View
    {
        $page = $resolved['page'];

        if (! $this->emptyStatePreview->enabledForTeam($subject->tenantId)) {
            $this->tracker->track($request, $subject->model, $page);
        }

        $subjectVariable = (string) config('pages.public_site.view_subject_variable', 'subject');
        $subjectVariable = preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $subjectVariable) === 1
            ? $subjectVariable
            : 'subject';
        $view = $this->publicView();

        return view($view, [
            'subject' => $subject->model,
            $subjectVariable => $subject->model,
            'page' => $page,
            'publicPages' => $resolved['publicPages'],
            'seoData' => $this->seoData($subject->model, $page, $resolved['publicPages']),
        ]);
    }

    /**
     * @param  Collection<int, Page>  $publicPages
     * @return array<string, mixed>|null
     */
    private function seoData(mixed $subject, Page $page, Collection $publicPages): ?array
    {
        $provider = config('pages.public_site.seo_data_provider');

        if (! is_string($provider) || $provider === '') {
            return null;
        }

        $resolved = app($provider);

        return $resolved instanceof PublicPageSeoDataProvider
            ? $resolved->page($subject, $page, $publicPages)
            : null;
    }

    /** @return view-string */
    private function publicView(): string
    {
        $view = config('pages.public_site.view', 'pages::public-site.page');
        abort_unless(is_string($view) && view()->exists($view), 500);

        return $view;
    }
}

<?php

namespace IvanBaric\Pages\Livewire\Admin;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\CreatePageAction;
use IvanBaric\Pages\Actions\DeleteAdminPageAction;
use IvanBaric\Pages\Actions\ReorderPageAction;
use IvanBaric\Pages\Actions\TogglePagePublishedAction;
use IvanBaric\Pages\Actions\UpdatePageAction;
use IvanBaric\Pages\Admin\AdminSectionRegistry;
use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Rules\NavigationUrl;
use IvanBaric\Pages\Support\PageHierarchy;
use IvanBaric\Pages\Support\PagesConfigResolver;
use IvanBaric\Pages\Support\PagesModels;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class PageIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    #[Locked]
    public string $part = 'pages';

    #[Locked]
    public ?string $editingPageUuid = null;

    public string $pageTitle = '';

    public string $pageExcerpt = '';

    public ?string $pageParentUuid = null;

    public string $pageNavigationType = 'page';

    public string $pageNavigationUrl = '';

    public bool $pageNavigationNewTab = false;

    public bool $pageIsHome = false;

    public string $newPageTitle = '';

    public ?string $newPageParentUuid = null;

    public string $newPageNavigationType = 'page';

    public string $newPageNavigationUrl = '';

    public bool $newPageNavigationNewTab = false;

    #[Locked]
    public ?string $deletingPageUuid = null;

    public function mount(): void
    {
        $part = (string) request()->query('part', 'pages');
        $this->part = in_array($part, $this->allowedParts(), true) ? $part : 'pages';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setStatus(string $status): void
    {
        if (! in_array($status, ['all', 'active', 'inactive'], true)) {
            return;
        }

        $this->status = $status;
        $this->resetPage();
    }

    public function reorderPage(string $uuid, int $position, ReorderPageAction $action): void
    {
        if (! $this->canReorder()) {
            return;
        }

        $page = $this->editablePage($uuid);
        $result = $action->handle($page, $position, $this->adminPageKeys(), $this->adminPageSlugs());

        unset($this->pages);

        $this->toastFromResult($result);
    }

    public function togglePublished(string $uuid, TogglePagePublishedAction $action): void
    {
        $result = $action->handle($this->editablePage($uuid));

        unset($this->pages, $this->stats);

        $this->toastFromResult($result);
    }

    public function openCreatePage(): void
    {
        $this->reset('newPageTitle', 'newPageParentUuid', 'newPageNavigationType', 'newPageNavigationUrl', 'newPageNavigationNewTab');
        Flux::modal('page-create-form')->show();
    }

    public function cancelCreatePage(): void
    {
        $this->reset('newPageTitle', 'newPageParentUuid', 'newPageNavigationType', 'newPageNavigationUrl', 'newPageNavigationNewTab');
    }

    public function editPage(string $uuid): void
    {
        $page = $this->editablePage($uuid);

        $this->reset('editingPageUuid', 'pageTitle', 'pageExcerpt', 'pageParentUuid', 'pageNavigationType', 'pageNavigationUrl', 'pageNavigationNewTab', 'pageIsHome');
        $this->editingPageUuid = $page->uuid;
        $this->pageTitle = $page->localized('title');
        $this->pageExcerpt = $page->localized('excerpt') ?: $page->localized('content');
        $this->pageParentUuid = $page->parent?->uuid;
        $this->pageNavigationType = $page->navigationType();
        $this->pageNavigationUrl = (string) ($page->navigationUrl() ?? '');
        $this->pageNavigationNewTab = $page->navigationTarget() === '_blank';
        $this->pageIsHome = (bool) $page->is_home;

        Flux::modal('page-title-form')->show();
    }

    public function cancelPageTitleForm(): void
    {
        $this->reset('editingPageUuid', 'pageTitle', 'pageExcerpt', 'pageParentUuid', 'pageNavigationType', 'pageNavigationUrl', 'pageNavigationNewTab', 'pageIsHome');
    }

    public function savePage(UpdatePageAction $action): void
    {
        $validated = $this->validate([
            'pageTitle' => ['required', 'string', 'max:120'],
            'pageExcerpt' => ['nullable', 'string', 'max:500'],
            'pageParentUuid' => ['nullable', 'uuid'],
            'pageNavigationType' => ['required', 'string', 'in:page,url'],
            'pageNavigationUrl' => ['nullable', 'required_if:pageNavigationType,url', 'string', 'max:2048', new NavigationUrl],
            'pageNavigationNewTab' => ['boolean'],
        ], [], [
            'pageTitle' => __('naziv stranice'),
            'pageExcerpt' => __('kratki opis'),
            'pageParentUuid' => __('nadređena stranica'),
        ]);

        if (! $this->editingPageUuid) {
            return;
        }

        $page = $this->editablePage($this->editingPageUuid);
        $excerpt = trim((string) $validated['pageExcerpt']);
        $locale = $this->locale();

        $result = $action->handle($page, [
            'title' => [$locale => trim((string) $validated['pageTitle'])],
            'excerpt' => $excerpt !== '' ? [$locale => $excerpt] : null,
            'content' => $excerpt !== '' ? [$locale => $excerpt] : $page->getAttribute('content'),
            'status' => $page->getAttribute('status'),
            'template' => $page->getAttribute('template'),
            'is_home' => (bool) $page->getAttribute('is_home'),
            'is_published' => (bool) $page->getAttribute('is_published'),
            'published_at' => $page->getAttribute('published_at'),
            'sort_order' => (int) $page->getAttribute('sort_order'),
            'settings' => $page->getAttribute('settings'),
            'navigation_type' => $page->is_home ? 'page' : (string) $validated['pageNavigationType'],
            'navigation_url' => $page->is_home || $validated['pageNavigationType'] !== 'url' ? null : trim((string) $validated['pageNavigationUrl']),
            'navigation_target' => ! $page->is_home && $validated['pageNavigationType'] === 'url' && $validated['pageNavigationNewTab'] ? '_blank' : '_self',
            'lock_version' => (int) $page->getAttribute('lock_version'),
            'parent_uuid' => filled($validated['pageParentUuid'] ?? null) ? (string) $validated['pageParentUuid'] : null,
        ]);

        if ($result->failed()) {
            $this->toastFromResult($result);

            return;
        }

        $this->reset('editingPageUuid', 'pageTitle', 'pageExcerpt', 'pageParentUuid', 'pageNavigationType', 'pageNavigationUrl', 'pageNavigationNewTab', 'pageIsHome');
        unset($this->pages);

        Flux::modal('page-title-form')->close();
        Flux::toast(variant: 'success', text: __('Naziv stranice je spremljen.'));
    }

    public function createPage(CreatePageAction $action): void
    {
        $validated = $this->validate([
            'newPageTitle' => ['required', 'string', 'max:120'],
            'newPageParentUuid' => ['nullable', 'uuid'],
            'newPageNavigationType' => ['required', 'string', 'in:page,url'],
            'newPageNavigationUrl' => ['nullable', 'required_if:newPageNavigationType,url', 'string', 'max:2048', new NavigationUrl],
            'newPageNavigationNewTab' => ['boolean'],
        ], [], [
            'newPageTitle' => __('naziv stranice'),
            'newPageParentUuid' => __('nadređena stranica'),
        ]);

        $model = PagesModels::page();
        $title = trim((string) $validated['newPageTitle']);
        $locale = $this->locale();

        $result = $action->handle([
            'title' => [$locale => $title],
            'excerpt' => null,
            'content' => null,
            'status' => 'published',
            'template' => $this->currentTemplateKey(),
            'is_home' => false,
            'is_published' => true,
            'published_at' => now(),
            'navigation_type' => (string) $validated['newPageNavigationType'],
            'navigation_url' => $validated['newPageNavigationType'] === 'url' ? trim((string) $validated['newPageNavigationUrl']) : null,
            'navigation_target' => $validated['newPageNavigationType'] === 'url' && $validated['newPageNavigationNewTab'] ? '_blank' : '_self',
            'sort_order' => ((int) $model::query()->max('sort_order')) + 1,
            'parent_uuid' => filled($validated['newPageParentUuid'] ?? null) ? (string) $validated['newPageParentUuid'] : null,
        ]);

        if ($result->failed() || ! $result->data instanceof Page) {
            $this->toastFromResult($result);

            return;
        }

        $page = $result->data;

        $this->reset('newPageTitle', 'newPageParentUuid', 'newPageNavigationType', 'newPageNavigationUrl', 'newPageNavigationNewTab');
        unset($this->pages, $this->stats);

        Flux::modal('page-create-form')->close();

        session()->flash('velora.toast', [
            'text' => __('Stranica je izrađena.'),
            'variant' => 'success',
        ]);

        $this->redirectRoute($this->pageShowRouteName(), ['page' => $page->uuid], navigate: true);
    }

    public function confirmDeletePage(string $uuid): void
    {
        $page = $this->editablePage($uuid);

        if (! $this->canDeletePage($page)) {
            Flux::toast(variant: 'danger', text: __('Ovu stranicu nije moguće arhivirati.'));

            return;
        }

        $this->deletingPageUuid = (string) $page->uuid;
        Flux::modal('page-delete')->show();
    }

    public function cancelDeletePage(): void
    {
        $this->deletingPageUuid = null;
    }

    public function deletePage(DeleteAdminPageAction $action): void
    {
        if (! $this->deletingPageUuid) {
            return;
        }

        $result = $action->handle($this->editablePage($this->deletingPageUuid));

        $this->deletingPageUuid = null;
        unset($this->pages, $this->stats);

        Flux::modal('page-delete')->close();
        $this->toastFromResult($result);
    }

    public function canDeletePage(Page $page): bool
    {
        return ! (bool) $page->getAttribute('is_home');
    }

    public function canReorder(): bool
    {
        return $this->part === 'pages' && $this->search === '' && $this->status === 'all' && $this->getPage() === 1;
    }

    /** @return Paginator<int, Page> */
    #[Computed]
    public function pages(): Paginator
    {
        $model = PagesModels::page();

        return $model::query()
            ->tap(fn (Builder $query): Builder => $this->adminPagesQuery($query))
            ->withCount('sections')
            ->with('parent:id,uuid,title')
            ->when($this->search !== '', function (Builder $query): void {
                $search = trim($this->search);

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($this->status === 'active', fn (Builder $query): Builder => $query->where('is_published', true))
            ->when($this->status === 'inactive', fn (Builder $query): Builder => $query->where('is_published', false))
            ->ordered()
            ->simplePaginate((int) config('pages.admin_index.per_page', 12));
    }

    /** @return array<string, array<string, mixed>> */
    #[Computed]
    public function filterOptions(): array
    {
        $stats = $this->stats();

        return [
            'all' => ['label' => __('Sve'), 'icon' => 'document-text', 'count' => $stats['total']],
            'active' => ['label' => __('Aktivne'), 'icon' => 'check-circle', 'count' => $stats['active']],
            'inactive' => ['label' => __('Neaktivne'), 'icon' => 'eye-slash', 'count' => $stats['inactive']],
        ];
    }

    /** @return array{total: int, active: int, inactive: int, sections: int} */
    #[Computed]
    public function stats(): array
    {
        $model = PagesModels::page();

        return [
            'total' => $model::query()->tap(fn (Builder $query): Builder => $this->adminPagesQuery($query))->count(),
            'active' => $model::query()->tap(fn (Builder $query): Builder => $this->adminPagesQuery($query))->where('is_published', true)->count(),
            'inactive' => $model::query()->tap(fn (Builder $query): Builder => $this->adminPagesQuery($query))->where('is_published', false)->count(),
            'sections' => $this->sectionsCountForAdminPages(),
        ];
    }

    public function render(): View
    {
        return view('pages::livewire.admin.page-index')
            ->layout(config('pages.admin_ui.layout', 'layouts.app'), ['title' => __('Stranice')]);
    }

    public function publicPageUrl(Page $page): string
    {
        if ($page->navigationUrl() !== null) {
            return $page->navigationUrl();
        }

        $subject = $this->organization();
        $route = (string) config('pages.admin_index.public_route.name', '');

        if ($subject instanceof Model && $route !== '' && Route::has($route)) {
            $parameters = [
                (string) config('pages.admin_index.public_route.subject_parameter', 'organizationSlug') => $subject->getAttribute('slug'),
            ];
            $slug = (string) $page->getAttribute('slug');

            if (! (bool) $page->getAttribute('is_home') && filled($slug)) {
                $parameters[(string) config('pages.admin_index.public_route.page_parameter', 'pageSlug')] = $slug;
            }

            return route($route, $parameters);
        }

        return url($page->getAttribute('slug') === 'home' || (bool) $page->getAttribute('is_home') ? '/' : '/'.$page->getAttribute('slug'));
    }

    #[Computed]
    public function organization(): ?Model
    {
        $model = PagesConfigResolver::singletonModel();

        if ($model === null) {
            return null;
        }

        $query = $model::query();
        $teamId = $this->currentTeamId();

        if ($teamId !== null && Schema::hasColumn((new $model)->getTable(), 'team_id')) {
            $query->where('team_id', $teamId);
        }

        $activeScope = (string) config('pages.admin_index.singleton_active_scope', 'active');

        if ($activeScope !== '' && method_exists($model, 'scope'.ucfirst($activeScope))) {
            $query->{$activeScope}();
        }

        return $query->first();
    }

    /** @return EloquentCollection<int, Page> */
    #[Computed]
    public function publicPages(): EloquentCollection
    {
        $model = PagesModels::page();

        return $model::query()
            ->published()
            ->navigationVisible()
            ->ordered()
            ->get();
    }

    /** @return array<int, array{uuid: string, label: string, path: string, depth: int, resulting_depth: int}> */
    #[Computed]
    public function parentPageOptions(): array
    {
        $model = PagesModels::page();

        $pages = $model::query()
            ->tap(fn (Builder $query): Builder => $this->adminPagesQuery($query))
            ->ordered()
            ->get();
        $editingPage = $this->editingPageUuid
            ? $pages->firstWhere('uuid', $this->editingPageUuid)
            : null;

        return app(PageHierarchy::class)->parentOptions($pages, $editingPage instanceof Page ? $editingPage : null);
    }

    #[Computed]
    public function currentTemplateKey(): string
    {
        $page = $this->publicPages()->first();

        if ($page && function_exists('template_engine')) {
            return template_engine()->resolveTemplateKey($page);
        }

        return $page ? (string) $page->getAttribute('template') : (string) config('template_engine.default_template', config('pages.default_template', 'classic'));
    }

    #[Computed]
    public function currentTemplateLabel(): string
    {
        if (function_exists('template_engine')) {
            return template_engine()->template($this->currentTemplateKey())->translatedLabel();
        }

        return (string) data_get(config('pages.templates'), $this->currentTemplateKey().'.label', $this->currentTemplateKey());
    }

    public function pageShowRouteName(): string
    {
        return (string) config('pages.admin_routes.page_show', 'admin.pages.show');
    }

    public function isTemplatePart(): bool
    {
        return $this->part !== 'pages'
            && array_key_exists($this->part, (array) config('pages.admin_index.template_parts', []));
    }

    public function partLabel(string $part): string
    {
        $configured = config('pages.admin_index.template_parts.'.$part.'.label');

        if (filled($configured)) {
            return (string) $configured;
        }

        return match ($part) {
            'header' => __('Zaglavlje'),
            'footer' => __('Podnožje'),
            'sections' => __('Sekcije'),
            default => Str::of($part)->replace(['-', '_'], ' ')->headline()->toString(),
        };
    }

    public function partDescription(string $part): string
    {
        $configured = config('pages.admin_index.template_parts.'.$part.'.description');

        if (filled($configured)) {
            return (string) $configured;
        }

        return match ($part) {
            'header' => __('Pregled javnog zaglavlja. Linkovi dolaze iz objavljenih stranica i njihovog redoslijeda.'),
            'footer' => __('Uredite tekst javnog podnožja. Linkovi dolaze iz objavljenih stranica i njihovog redoslijeda.'),
            'sections' => __('Uredite zajednički izgled naziva i opisa sekcija na javnoj stranici.'),
            default => __('Uredite ovaj dio javnog templatea.'),
        };
    }

    /** @return array{title: string, description: string} */
    public function editorHeader(string $part): array
    {
        $definition = app(AdminSectionRegistry::class)->get($this->templatePartDefinitionKey($part));
        $tabs = $definition?->tabsValue() ?? [];
        $requestedTab = (string) request()->query('editorTab', '');
        $activeTab = collect($tabs)->first(
            static fn ($tab): bool => $tab->key() === $requestedTab,
        ) ?? ($tabs[0] ?? null);

        if ($activeTab === null) {
            return [
                'title' => $this->partLabel($part),
                'description' => $this->partDescription($part),
            ];
        }

        return [
            'title' => $this->partLabel($part),
            'description' => (string) ($activeTab->optionValue('description', $this->partDescription($part)) ?? $this->partDescription($part)),
        ];
    }

    public function templatePartDefinitionKey(string $part): string
    {
        return (string) config('pages.admin_index.template_parts.'.$part.'.definition_key', 'template_'.$part);
    }

    public function canRenderTemplatePart(string $part): bool
    {
        $template = (string) config('pages.admin_index.template_parts.'.$part.'.template', '');

        return $template === '' || $template === $this->currentTemplateKey();
    }

    public function missingSingletonSubjectText(): string
    {
        return (string) config('pages.admin_index.missing_singleton_text', __('Javna organizacija još nije dostupna za trenutni tim.'));
    }

    public function unsupportedTemplatePartText(string $part): string
    {
        return (string) config('pages.admin_index.template_parts.'.$part.'.unsupported_text', __('Za ovaj template još nije definirana komponenta.'));
    }

    private function editablePage(string $uuid): Page
    {
        $model = PagesModels::page();

        return $model::query()
            ->tap(fn (Builder $query): Builder => $this->adminPagesQuery($query))
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function currentTeamId(): ?int
    {
        $tenantId = corexis_tenant_id();

        return is_numeric($tenantId) ? (int) $tenantId : null;
    }

    private function sectionsCountForAdminPages(): int
    {
        $pageIds = PagesModels::page()::query()
            ->tap(fn (Builder $query): Builder => $this->adminPagesQuery($query))
            ->pluck('id');

        return PagesModels::section()::query()
            ->whereIn('page_id', $pageIds)
            ->count();
    }

    /**
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    private function adminPagesQuery(Builder $query): Builder
    {
        if ($this->hasPageKeyColumn()) {
            return $query->where(function (Builder $query): void {
                $query->whereIn('page_key', $this->adminPageKeys())
                    ->orWhere(function (Builder $query): void {
                        $query->whereNull('page_key')->whereIn('slug', $this->adminPageSlugs());
                    })
                    ->orWhere(function (Builder $query): void {
                        $query->whereNull('page_key')
                            ->whereNotIn('slug', $this->systemPageSlugs());
                    });
            });
        }

        return $query->whereIn('slug', $this->adminPageSlugs());
    }

    /** @return array<int, string> */
    private function adminPageKeys(): array
    {
        return array_values(array_unique((array) config('pages.admin_pages', ['home', 'about', 'products', 'posts', 'gallery', 'contact'])));
    }

    /** @return array<int, string> */
    private function adminPageSlugs(): array
    {
        $adminPages = $this->adminPageKeys();
        $publicSlugs = array_filter((array) config('pages.public_slugs', []));
        $legacySlugs = (array) config('pages.admin_index.legacy_slugs', ['o-udruzi']);

        return array_values(array_unique([
            ...$adminPages,
            ...array_values($publicSlugs),
            ...array_values($legacySlugs),
        ]));
    }

    /** @return array<int, string> */
    private function systemPageSlugs(): array
    {
        return array_values((array) config('pages.admin_index.system_slugs', ['header', 'footer']));
    }

    /** @return array<int, string> */
    private function allowedParts(): array
    {
        return array_values((array) config('pages.admin_index.parts', ['pages', 'header', 'footer']));
    }

    private function hasPageKeyColumn(): bool
    {
        return Schema::hasColumn(PagesConfigResolver::pagesTable(), 'page_key');
    }

    private function locale(): string
    {
        return corexis_locale_code()
            ?: (string) config('pages.translatable.default_locale')
            ?: (string) config('app.locale', 'en');
    }

    private function toastFromResult(ActionResult $result): void
    {
        Flux::toast(variant: $result->success ? 'success' : 'danger', text: $result->message);
    }
}

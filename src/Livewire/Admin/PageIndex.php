<?php

namespace IvanBaric\Pages\Livewire\Admin;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use IvanBaric\Pages\Actions\DeleteAdminPageAction;
use IvanBaric\Pages\Actions\ReorderPageAction;
use IvanBaric\Pages\Actions\TogglePagePublishedAction;
use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Models\Section;
use IvanBaric\Pages\Support\TeamResolver;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class PageIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public string $part = 'pages';

    public ?string $editingPageUuid = null;

    public string $pageTitle = '';

    public string $pageExcerpt = '';

    public string $newPageTitle = '';

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
        $result = $action->handle($page, $position, $this->currentTeamId(), $this->adminPageKeys(), $this->adminPageSlugs());

        unset($this->pages);

        $this->toastFromResult($result);
    }

    public function togglePublished(string $uuid, TogglePagePublishedAction $action): void
    {
        $result = $action->handle($this->editablePage($uuid));

        unset($this->pages, $this->stats);

        $this->toastFromResult($result);
    }

    public function editPage(string $uuid): void
    {
        $page = $this->editablePage($uuid);

        $this->editingPageUuid = $page->uuid;
        $this->pageTitle = $page->localized('title');
        $this->pageExcerpt = $page->localized('excerpt') ?: $page->localized('content');

        Flux::modal('page-title-form')->show();
    }

    public function savePage(): void
    {
        $validated = $this->validate([
            'pageTitle' => ['required', 'string', 'max:120'],
            'pageExcerpt' => ['nullable', 'string', 'max:500'],
        ], [], [
            'pageTitle' => __('naziv stranice'),
            'pageExcerpt' => __('kratki opis'),
        ]);

        if (! $this->editingPageUuid) {
            return;
        }

        $page = $this->editablePage($this->editingPageUuid);
        $excerpt = trim((string) $validated['pageExcerpt']);
        $locale = $this->locale();

        $page->forceFill([
            'title' => [$locale => trim((string) $validated['pageTitle'])],
            'excerpt' => $excerpt !== '' ? [$locale => $excerpt] : null,
            'content' => $excerpt !== '' ? [$locale => $excerpt] : $page->getAttribute('content'),
        ])->save();

        $this->reset('editingPageUuid', 'pageTitle', 'pageExcerpt');
        unset($this->pages);

        Flux::modal('page-title-form')->close();
        Flux::toast(variant: 'success', text: __('Naziv stranice je spremljen.'));
    }

    public function createPage(): void
    {
        $validated = $this->validate([
            'newPageTitle' => ['required', 'string', 'max:120'],
        ], [], [
            'newPageTitle' => __('naziv stranice'),
        ]);

        $model = config('pages.models.page', Page::class);
        $title = trim((string) $validated['newPageTitle']);
        $teamId = $this->currentTeamId();
        $locale = $this->locale();

        $page = $model::query()->create([
            'team_id' => $teamId,
            'title' => [$locale => $title],
            'excerpt' => null,
            'content' => null,
            'status' => 'published',
            'template' => $this->currentTemplateKey,
            'is_home' => false,
            'is_published' => true,
            'published_at' => now(),
            'sort_order' => ((int) $model::query()->forTeam($teamId)->max('sort_order')) + 1,
        ]);

        $this->reset('newPageTitle');
        unset($this->pages, $this->stats);

        Flux::modal('page-create-form')->close();

        $this->redirectRoute($this->pageShowRouteName(), ['page' => $page->uuid], navigate: true);
    }

    public function confirmDeletePage(string $uuid): void
    {
        $page = $this->editablePage($uuid);

        if (! $this->canDeletePage($page)) {
            Flux::toast(variant: 'danger', text: __('Ovu stranicu nije moguće arhivirati.'));

            return;
        }

        $this->deletingPageUuid = $uuid;
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
        $model = config('pages.models.page', Page::class);

        return $model::query()
            ->forTeam($this->currentTeamId())
            ->tap(fn (Builder $query): Builder => $this->adminPagesQuery($query))
            ->withCount('sections')
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
        $model = config('pages.models.page', Page::class);

        return [
            'total' => $model::query()->forTeam($this->currentTeamId())->tap(fn (Builder $query): Builder => $this->adminPagesQuery($query))->count(),
            'active' => $model::query()->forTeam($this->currentTeamId())->tap(fn (Builder $query): Builder => $this->adminPagesQuery($query))->where('is_published', true)->count(),
            'inactive' => $model::query()->forTeam($this->currentTeamId())->tap(fn (Builder $query): Builder => $this->adminPagesQuery($query))->where('is_published', false)->count(),
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
        $model = config('pages.admin_index.singleton_model');

        if (! is_string($model) || $model === '' || ! class_exists($model)) {
            return null;
        }

        $query = $model::query();
        $teamId = $this->currentTeamId();
        $teamScope = (string) config('pages.admin_index.singleton_team_scope', 'forTeam');

        if ($teamId !== null && method_exists($model, 'scope'.ucfirst($teamScope))) {
            $query->{$teamScope}($teamId);
        } elseif ($teamId !== null && Schema::hasColumn((new $model)->getTable(), 'team_id')) {
            $query->where('team_id', $teamId);
        }

        $activeScope = (string) config('pages.admin_index.singleton_active_scope', 'active');

        if ($activeScope !== '' && method_exists($model, 'scope'.ucfirst($activeScope))) {
            $query->{$activeScope}();
        }

        return $query->first();
    }

    #[Computed]
    public function publicPages()
    {
        $model = config('pages.models.page', Page::class);

        return $model::query()
            ->forTeam($this->currentTeamId())
            ->published()
            ->ordered()
            ->get();
    }

    #[Computed]
    public function currentTemplateKey(): string
    {
        $page = $this->publicPages->first();

        if ($page && function_exists('template_engine')) {
            return template_engine()->resolveTemplateKey($page);
        }

        return $page ? (string) $page->getAttribute('template') : (string) config('template_engine.default_template', config('pages.default_template', 'classic'));
    }

    #[Computed]
    public function currentTemplateLabel(): string
    {
        if (function_exists('template_engine')) {
            return template_engine()->template($this->currentTemplateKey)->translatedLabel();
        }

        return (string) data_get(config('pages.templates'), $this->currentTemplateKey.'.label', $this->currentTemplateKey);
    }

    public function pageShowRouteName(): string
    {
        return (string) config('pages.admin_routes.page_show', 'admin.pages.show');
    }

    public function templatePartDefinitionKey(string $part): string
    {
        return (string) config('pages.admin_index.template_parts.'.$part.'.definition_key', 'template_'.$part);
    }

    public function canRenderTemplatePart(string $part): bool
    {
        $template = (string) config('pages.admin_index.template_parts.'.$part.'.template', '');

        return $template === '' || $template === $this->currentTemplateKey;
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
        $model = config('pages.models.page', Page::class);

        return $model::query()
            ->forTeam($this->currentTeamId())
            ->tap(fn (Builder $query): Builder => $this->adminPagesQuery($query))
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function currentTeamId(): ?int
    {
        return app(TeamResolver::class)->resolve();
    }

    private function sectionsCountForAdminPages(): int
    {
        $model = config('pages.models.section', Section::class);

        return $model::query()
            ->forTeam($this->currentTeamId())
            ->whereHas('page', fn (Builder $query): Builder => $this->adminPagesQuery($query))
            ->count();
    }

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
        return Schema::hasColumn(config('pages.tables.pages', 'pages'), 'page_key');
    }

    private function locale(): string
    {
        return corexis_locale_code()
            ?: (string) config('pages.translatable.default_locale')
            ?: (string) config('app.locale', 'en');
    }

    private function toastFromResult(ActionResult $result): void
    {
        Flux::toast(variant: $result->successful ? 'success' : 'danger', text: $result->message);
    }
}

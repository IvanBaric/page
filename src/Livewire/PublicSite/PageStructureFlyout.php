<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Livewire\PublicSite;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\CreatePageAction;
use IvanBaric\Pages\Actions\DeleteAdminPageAction;
use IvanBaric\Pages\Actions\MovePageAction;
use IvanBaric\Pages\Actions\ReorderPageAction;
use IvanBaric\Pages\Actions\TogglePagePublishedAction;
use IvanBaric\Pages\Actions\UpdatePageAction;
use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Support\PagesConfigResolver;
use IvanBaric\Pages\Support\PagesModels;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

final class PageStructureFlyout extends Component
{
    #[Locked]
    public Page $page;

    #[Locked]
    public int $tenantId;

    #[Locked]
    public bool $loaded = false;

    #[Locked]
    public ?string $selectedPageUuid = null;

    #[Locked]
    public ?string $editingPageUuid = null;

    #[Locked]
    public ?string $deletingPageUuid = null;

    #[Locked]
    public ?string $movingPageUuid = null;

    #[Locked]
    public ?string $publicSubjectSlug = null;

    public string $newPageTitle = '';

    public ?string $newPageParentUuid = null;

    public string $pageTitle = '';

    public string $pageExcerpt = '';

    public ?string $pageParentUuid = null;

    public ?string $movePageParentUuid = null;

    public function mount(Page $page): void
    {
        $tenantId = corexis_tenant_id();

        abort_unless(corexis_actor_id() !== null && is_numeric($tenantId), 403);

        $model = PagesModels::page();
        $resolved = $model::query()
            ->forTenant((int) $tenantId)
            ->where('uuid', $page->uuid)
            ->first();

        abort_unless($resolved instanceof Page, 404);
        corexis_authorize('pages.update', $resolved);

        $this->tenantId = (int) $tenantId;
        $this->page = $resolved;

        $subjectParameter = (string) config('pages.admin_index.public_route.subject_parameter', 'organizationSlug');
        $subject = request()->route($subjectParameter);
        $this->publicSubjectSlug = $subject instanceof Model
            ? (string) $subject->getAttribute('slug')
            : (is_scalar($subject) ? (string) $subject : null);
    }

    #[On('pages-open-public-page-structure')]
    public function openStructure(): void
    {
        corexis_authorize('pages.update', $this->resolveRootPage());

        $this->loaded = true;
        $this->selectedPageUuid = null;
        unset($this->pages, $this->selectedPage);

        Flux::modal('public-page-structure')->show();
    }

    public function closeStructure(): void
    {
        $this->loaded = false;
        $this->selectedPageUuid = null;
        unset($this->pages, $this->selectedPage);
    }

    #[On('pages-public-structure-updated')]
    public function refreshStructure(): void
    {
        unset($this->pages, $this->selectedPage);
    }

    public function selectPage(string $pageUuid): void
    {
        $selectedPage = $this->findPage($pageUuid);
        corexis_authorize('pages.update', $selectedPage);

        $this->selectedPageUuid = (string) $selectedPage->uuid;
    }

    public function showPages(): void
    {
        $this->selectedPageUuid = null;
    }

    public function reorderPage(string $pageUuid, int $position, ReorderPageAction $action): void
    {
        $page = $this->findPage($pageUuid);
        corexis_authorize('pages.update', $page);

        $pages = $this->pages;
        $result = $action->handle(
            $page,
            $position,
            $pages->pluck('page_key')->filter()->map(static fn (mixed $key): string => (string) $key)->values()->all(),
            $pages->pluck('slug')->filter()->map(static fn (mixed $slug): string => (string) $slug)->values()->all(),
        );

        unset($this->pages);
        $this->toastFromResult($result);
    }

    public function movePageInStructure(string $pageUuid, int $position, string $groupId, MovePageAction $action): void
    {
        $page = $this->findPage($pageUuid);
        corexis_authorize('pages.update', $page);

        $parentUuid = $this->parentUuidFromGroup($groupId);
        $result = $action->handle($page, $parentUuid, $position);

        unset($this->pages, $this->parentPageOptions, $this->movePageOptions);
        $this->toastFromResult($result);
    }

    public function togglePublished(string $pageUuid, TogglePagePublishedAction $action): void
    {
        $page = $this->findPage($pageUuid);
        corexis_authorize('pages.update', $page);

        $result = $action->handle($page);

        unset($this->pages);
        $this->toastFromResult($result);
    }

    public function editPage(string $pageUuid): void
    {
        $page = $this->findPage($pageUuid);
        corexis_authorize('pages.update', $page);

        $this->reset('editingPageUuid', 'pageTitle', 'pageExcerpt', 'pageParentUuid');
        $this->editingPageUuid = (string) $page->uuid;
        $this->pageTitle = $page->localized('title');
        $this->pageExcerpt = $page->localized('excerpt') ?: $page->localized('content');
        $this->pageParentUuid = $page->parent?->uuid;

        Flux::modal('public-page-title-form')->show();
    }

    public function cancelPageEditor(): void
    {
        $this->reset('editingPageUuid', 'pageTitle', 'pageExcerpt', 'pageParentUuid');
    }

    public function openPageMover(string $pageUuid): void
    {
        $page = $this->findPage($pageUuid);
        corexis_authorize('pages.update', $page);

        if ($page->is_home) {
            Flux::toast(variant: 'danger', text: __('Naslovnicu nije moguće premjestiti u drugu stranicu.'));

            return;
        }

        $this->reset('movingPageUuid', 'movePageParentUuid');
        $this->movingPageUuid = (string) $page->uuid;
        $this->movePageParentUuid = $page->parent?->uuid;
        unset($this->movePageOptions);

        Flux::modal('public-page-move')->show();
    }

    public function cancelPageMover(): void
    {
        $this->reset('movingPageUuid', 'movePageParentUuid');
        unset($this->movePageOptions);
    }

    public function savePageMove(MovePageAction $action): void
    {
        $validated = $this->validate([
            'movePageParentUuid' => ['nullable', 'uuid'],
        ], [], [
            'movePageParentUuid' => __('nadređena stranica'),
        ]);

        if ($this->movingPageUuid === null) {
            return;
        }

        $page = $this->findPage($this->movingPageUuid);
        corexis_authorize('pages.update', $page);

        $parentUuid = filled($validated['movePageParentUuid'] ?? null)
            ? (string) $validated['movePageParentUuid']
            : null;
        $position = $this->siblingCount($parentUuid);
        $result = $action->handle($page, $parentUuid, $position);

        if ($result->failed()) {
            $this->toastFromResult($result);

            return;
        }

        $this->reset('movingPageUuid', 'movePageParentUuid');
        unset($this->pages, $this->parentPageOptions, $this->movePageOptions);

        Flux::modal('public-page-move')->close();
        $this->toastFromResult($result);
    }

    public function savePage(UpdatePageAction $action): void
    {
        $validated = $this->validate([
            'pageTitle' => ['required', 'string', 'max:120'],
            'pageExcerpt' => ['nullable', 'string', 'max:500'],
            'pageParentUuid' => ['nullable', 'uuid'],
        ], [], [
            'pageTitle' => __('naziv stranice'),
            'pageExcerpt' => __('kratki opis'),
            'pageParentUuid' => __('nadređena stranica'),
        ]);

        if ($this->editingPageUuid === null) {
            return;
        }

        $page = $this->findPage($this->editingPageUuid);
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
            'lock_version' => (int) $page->getAttribute('lock_version'),
            'parent_uuid' => filled($validated['pageParentUuid'] ?? null) ? (string) $validated['pageParentUuid'] : null,
        ]);

        if ($result->failed()) {
            $this->toastFromResult($result);

            return;
        }

        $this->reset('editingPageUuid', 'pageTitle', 'pageExcerpt', 'pageParentUuid');
        unset($this->pages);

        Flux::modal('public-page-title-form')->close();
        Flux::toast(variant: 'success', text: __('Naziv stranice je spremljen.'));
    }

    public function confirmDeletePage(string $pageUuid): void
    {
        $page = $this->findPage($pageUuid);
        corexis_authorize('pages.delete', $page);

        if (! $this->canDeletePage($page)) {
            Flux::toast(variant: 'danger', text: __('Ovu stranicu nije moguće arhivirati.'));

            return;
        }

        $this->deletingPageUuid = (string) $page->uuid;
        Flux::modal('public-page-delete')->show();
    }

    public function cancelDeletePage(): void
    {
        $this->deletingPageUuid = null;
    }

    public function deletePage(DeleteAdminPageAction $action): void
    {
        if ($this->deletingPageUuid === null) {
            return;
        }

        $page = $this->findPage($this->deletingPageUuid);
        $result = $action->handle($page);

        $this->deletingPageUuid = null;
        unset($this->pages);

        Flux::modal('public-page-delete')->close();
        $this->toastFromResult($result);
    }

    public function canDeletePage(Page $page): bool
    {
        return ! (bool) $page->getAttribute('is_home')
            && corexis_can('pages.delete', $page);
    }

    public function canCreatePage(): bool
    {
        return corexis_can('pages.create');
    }

    public function canTogglePublished(Page $page): bool
    {
        return ! (bool) $page->getAttribute('is_home')
            && corexis_can('pages.publish', $page);
    }

    public function openPageCreator(): void
    {
        $this->reset('newPageTitle', 'newPageParentUuid');
        Flux::modal('public-page-create')->show();
    }

    public function cancelPageCreator(): void
    {
        $this->reset('newPageTitle', 'newPageParentUuid');
    }

    public function createPage(CreatePageAction $action): void
    {
        $validated = $this->validate([
            'newPageTitle' => ['required', 'string', 'max:120'],
            'newPageParentUuid' => ['nullable', 'uuid'],
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
            'template' => (string) $this->page->getAttribute('template'),
            'is_home' => false,
            'is_published' => true,
            'published_at' => now(),
            'sort_order' => ((int) $model::query()->forTenant($this->tenantId)->max('sort_order')) + 1,
            'parent_uuid' => filled($validated['newPageParentUuid'] ?? null) ? (string) $validated['newPageParentUuid'] : null,
        ]);

        if ($result->failed() || ! $result->data instanceof Page) {
            Flux::toast(variant: 'danger', text: $result->message);

            return;
        }

        $this->reset('newPageTitle', 'newPageParentUuid');
        unset($this->pages);
        Flux::modal('public-page-create')->close();
        Flux::toast(variant: 'success', text: __('Stranica je izrađena.'));
        $this->dispatch('pages-public-structure-updated');
    }

    public function render(): View
    {
        return view('pages::livewire.public-site.page-structure-flyout');
    }

    /** @return Collection<int, Page> */
    #[Computed]
    public function pages(): Collection
    {
        if (! $this->loaded) {
            return new Collection;
        }

        $model = PagesModels::page();

        $columns = ['id', 'team_id', 'parent_id', 'uuid', 'title', 'excerpt', 'content', 'slug', 'status', 'is_home', 'is_published', 'published_at', 'sort_order', 'template', 'settings', 'lock_version'];

        if (Schema::hasColumn(PagesConfigResolver::pagesTable(), 'page_key')) {
            $columns[] = 'page_key';
        }

        $pages = $model::query()
            ->select($columns)
            ->forTenant($this->tenantId)
            ->whereNotIn('slug', $this->systemPageSlugs())
            ->with('parent:id,uuid,title')
            ->withCount('sections')
            ->withExists('children')
            ->ordered()
            ->get();

        $roots = $pages->whereNull('parent_id')->values();
        $nested = $roots->flatMap(fn (Page $root): array => [
            $root,
            ...$pages->where('parent_id', $root->getKey())->values()->all(),
        ]);

        return new Collection($nested
            ->concat($pages->whereNotNull('parent_id')->whereNotIn('id', $nested->pluck('id')))
            ->values()
            ->all());
    }

    #[Computed]
    public function selectedPage(): ?Page
    {
        return ! $this->loaded || $this->selectedPageUuid === null
            ? null
            : $this->findPage($this->selectedPageUuid);
    }

    /** @return array<int, array{uuid: string, label: string}> */
    #[Computed]
    public function parentPageOptions(): array
    {
        return $this->pages
            ->filter(fn (Page $page): bool => ! $page->is_home
                && $page->parent_id === null
                && (string) $page->uuid !== (string) $this->editingPageUuid)
            ->map(fn (Page $page): array => [
                'uuid' => (string) $page->uuid,
                'label' => $page->localized('title') ?: (string) $page->slug,
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array{uuid: string, label: string}> */
    #[Computed]
    public function movePageOptions(): array
    {
        $movingPage = $this->movingPageUuid === null
            ? null
            : $this->pages->firstWhere('uuid', $this->movingPageUuid);

        if ($movingPage?->getAttribute('children_exists')) {
            return [];
        }

        return $this->pages
            ->filter(fn (Page $page): bool => ! $page->is_home
                && $page->parent_id === null
                && (string) $page->uuid !== (string) $this->movingPageUuid)
            ->map(fn (Page $page): array => [
                'uuid' => (string) $page->uuid,
                'label' => $page->localized('title') ?: (string) $page->slug,
            ])
            ->values()
            ->all();
    }

    private function findPage(string $pageUuid): Page
    {
        $page = $this->pages->firstWhere('uuid', $pageUuid);

        abort_unless($page instanceof Page, 404);

        return $page;
    }

    private function parentUuidFromGroup(string $groupId): ?string
    {
        if ($groupId === 'root') {
            return null;
        }

        $parent = $this->findPage($groupId);
        abort_unless($parent->parent_id === null && ! $parent->is_home, 422);

        return (string) $parent->uuid;
    }

    private function siblingCount(?string $parentUuid): int
    {
        if ($parentUuid === null) {
            return $this->pages->whereNull('parent_id')->count();
        }

        $parent = $this->findPage($parentUuid);
        abort_unless($parent->parent_id === null && ! $parent->is_home, 422);

        return $this->pages->where('parent_id', $parent->getKey())->count();
    }

    private function resolveRootPage(): Page
    {
        $model = PagesModels::page();
        $page = $model::query()
            ->forTenant($this->tenantId)
            ->where('uuid', $this->page->uuid)
            ->first();

        abort_unless($page instanceof Page, 404);

        return $page;
    }

    public function publicPageUrl(Page $page): string
    {
        $route = (string) config('pages.admin_index.public_route.name', '');

        if ($route !== '' && Route::has($route) && filled($this->publicSubjectSlug)) {
            $parameters = [
                (string) config('pages.admin_index.public_route.subject_parameter', 'organizationSlug') => $this->publicSubjectSlug,
            ];
            $slug = (string) $page->getAttribute('slug');

            if (! (bool) $page->getAttribute('is_home') && filled($slug)) {
                $parameters[(string) config('pages.admin_index.public_route.page_parameter', 'pageSlug')] = $slug;
            }

            return route($route, $parameters);
        }

        return url($page->getAttribute('slug') === 'home' || (bool) $page->getAttribute('is_home') ? '/' : '/'.$page->getAttribute('slug'));
    }

    /** @return array<int, string> */
    private function systemPageSlugs(): array
    {
        return array_values((array) config('pages.admin_index.system_slugs', ['header', 'footer']));
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

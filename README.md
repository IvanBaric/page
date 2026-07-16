# IvanBaric Pages

Reusable CMS page-builder foundation for Laravel 13 and PHP 8.3+.

The package provides three generic models:

- `IvanBaric\Pages\Models\Page`
- `IvanBaric\Pages\Models\Section`
- `IvanBaric\Pages\Models\SectionItem`

Templates only change rendering, order and visual emphasis. They do not require different database fields.

## Installation

```bash
composer require ivanbaric/pages
php artisan vendor:publish --tag=pages-config
php artisan vendor:publish --tag=pages-migrations
php artisan migrate
```

Optional publish tags:

```bash
php artisan vendor:publish --tag=pages-views
php artisan vendor:publish --tag=pages-translations
```

## Configuration

`config/pages.php` controls table and model overrides, statuses, templates, section definitions, editor integration, routes, middleware, permissions and the admin layout.

The default admin routes are:

```php
route('admin.pages.index');
route('admin.pages.create');
route('admin.pages.edit', ['page' => $page->uuid]);
route('admin.pages.sections', ['page' => $page->uuid]);
route('admin.pages.sections.items', ['page' => $page->uuid, 'section' => $section->uuid]);
```

## Tenancy

`Page`, `Section` and `SectionItem` use Corexis `BelongsToTenant`. The central `IvanBaric\Corexis\Contracts\TenantResolver` assigns `team_id`, applies the global tenant scope and prevents tenant mutation.

Configure the resolver once through Corexis. Pages does not ship or configure a package-specific resolver, and write Actions never accept `team_id` as user input.

## UUID Rule

All public/admin lookups use `uuid`. The route key is `uuid` for pages, sections and section items. UI actions should pass UUIDs, not database IDs.

## Model Resolution

`IvanBaric\Pages\Support\PagesModels` is the single model resolver for package internals. Every configured class in `pages.models` must extend its matching package model. Relations, Actions and archive operations use this resolver so host applications can replace models without patching the package.

## Slugs

All three models use Corexis `HasUniqueSlug`. Slugs are unique inside the current tenant and use predictable `-1`, `-2`, `-3` suffixes. Explicit slug changes can be made with `regenerateSlug()`.

## Project Integration

For the standard admin integration used in every project, see:

- [`docs/project-integration.md`](docs/project-integration.md)

That document covers the required `Sve stranice`, `Zaglavlje` and `Podnožje` sidebar links, the shared `/app/pages` route, the `part=header` and `part=footer` screens, and the required `template_header` / `template_footer` definitions.

## Integrations

SEO is not implemented here. Add the shared SEO concern to a configured model override when a project needs it.

Images are stored only through Gallery and Media Library. `SectionItem` includes `HasGalleries`; legacy string image columns are not part of the current schema.

Taxonomy logic is not duplicated. Add the taxonomy concern to a configured model override when needed.

Admin layout, sidebar, header and shell are not implemented here. The Livewire views use Flux components and render inside `pages.admin_ui.layout`.

Status, settings, language and audit remain integration points. Pages stores only the generic fields required for page-builder structure.

## Usage

```php
use IvanBaric\Pages\Models\Page;

$page = Page::createHome(['title' => 'Naslovnica']);

$hero = $page->addSection(type: 'hero', data: [
    'title' => 'Niva',
    'subtitle' => 'Predstavite rad svoje organizacije.',
    'button_text' => 'Pogledaj proizvode',
    'button_url' => '/proizvodi',
]);

$features = $page->addSection(type: 'features', data: [
    'title' => 'Što možete predstaviti?',
]);

$features->addItem([
    'title' => 'Proizvodi',
    'description' => 'Prikažite proizvode, radove i katalog.',
    'icon' => 'shopping-bag',
]);

$page->publish();
```

## Hierarchical Pages

Pages supports one level of tenant-scoped subpages. Run the package migrations to add the nullable self-referencing `parent_id` column and its tenant/navigation index.

```php
$parent = Page::forSlug('about');
$child = Page::forSlug('team');

$child->parent()->associate($parent)->save();
$parent->children()->get();
```

User-driven hierarchy changes must use `MovePageAction`, which authorizes the page, locks both navigation groups, prevents cross-tenant or second-level nesting and normalizes sort order:

```php
$result = app(\IvanBaric\Pages\Actions\MovePageAction::class)->handle(
    page: $child->uuid,
    parentUuid: $parent->uuid,
    position: 0,
);
```

The home page always remains in the root navigation. A page that already has children cannot become a child. Archiving a parent promotes its children to the root so published content does not become unreachable. `PageStructureFlyout` supports drag/reorder between root and parent groups and dispatches `pages-public-structure-updated` after a successful write, allowing headers to refresh without a hard page reload.

Public navigation is returned as root pages with ordered `children`; concrete templates decide how desktop dropdowns and mobile disclosure menus are rendered.

## Configurable Public Site

Pages provides a reusable public page pipeline in addition to the admin and public-first editors:

- `PublicSiteSubjectResolver` resolves a public owner and tenant from the URL.
- `PublicSitePageResolver` resolves the published page, visible sections and hierarchical navigation.
- `PublicPageController` renders the configured view without depending on an application Organization model.
- `PublicContentController` resolves the same subject, page and navigation for nested single-content URLs.
- `PublicContentProvider` lets Blog, Gallery, a catalog package or the host render records owned by that domain.
- `PublicPageViewTracker` is an optional adapter contract; the default implementation does nothing.
- `pages::public-site.page` renders the page through Template Engine and mounts editors only for an authorized actor from the same tenant.

### Public section creator

The default public page view includes the `Dodaj sekciju` control and its Livewire workflow. Pages owns authorization, tenant-safe writes, the selection modal and `CreateSectionAction`; Template Engine owns the active template's section allowlist.

A concrete template package only needs to register its sections:

```php
'templates' => [
    'business' => [
        'sections' => [
            'services' => [
                'label' => 'Services',
                'component' => ServicesSection::class,
                'metadata' => [
                    'icon' => 'briefcase',
                ],
            ],
        ],
    ],
],
```

Disabled sections, sections with `metadata.creatable = false`, and internal `template_*` definitions are rejected by `CreateSectionAction`, so a crafted Livewire request cannot create a section unsupported by the page's active template. Structural types listed in `pages.section_creator.exclude_types`, including `hero` by default, remain available to installers and onboarding Actions but are not offered in the public creator.

`pages.section_types` remains an optional host override for labels, icons and backwards compatibility. Use `pages.section_creator.groups` to group related section types in the selector and `pages.section_creator.exclude_types` for additional non-creatable structural sections. A new template package does not need to duplicate its complete section list in Pages configuration.

Public routes are disabled by default because the page route is commonly a final catch-all route. Enable package route registration or connect the controller manually after content-specific routes:

```php
'public_site' => [
    'enabled' => true,
    'layout' => 'layouts.public',
    'subject' => [
        'model' => App\Models\Organization::class,
        'slug_column' => 'slug',
        'tenant_column' => 'team_id',
        'active_column' => 'is_active',
    ],
    'route' => [
        'enabled' => true,
        'uri' => '/{organizationSlug}/{pageSlug?}',
        'name' => 'public.organization.page',
        'middleware' => ['web'],
        'subject_parameter' => 'organizationSlug',
        'page_parameter' => 'pageSlug',
    ],
    'content_route' => [
        'enabled' => true,
        'uri' => '/{organizationSlug}/{pageSlug}/{contentSlug}',
        'name' => 'public.organization.content',
        'middleware' => ['web'],
        'content_parameter' => 'contentSlug',
    ],
    'content_providers' => [
        'posts' => App\Support\PublicPostContentProvider::class,
        'gallery' => App\Support\PublicGalleryContentProvider::class,
    ],
],
```

Content providers are selected by stable `page_key`, with the page slug as a fallback. A provider receives a tenant-safe `PublicContentContext` containing the resolved subject, published page, public navigation and content slug:

```php
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use IvanBaric\Pages\Contracts\PublicContentProvider;
use IvanBaric\Pages\Data\PublicContentContext;

final class PublicPostContentProvider implements PublicContentProvider
{
    public function render(Request $request, PublicContentContext $context): View
    {
        $post = Post::query()
            ->forTenant($context->subject->tenantId)
            ->published()
            ->where('slug', $context->contentSlug)
            ->firstOrFail();

        return view('public.posts.show', [
            'subject' => $context->subject->model,
            'page' => $context->page,
            'publicPages' => $context->publicPages,
            'post' => $post,
        ]);
    }
}
```

The package registers the content route before its final page catch-all route. When a host has taxonomy or other more specific public routes, keep both package routes disabled and manually connect `PublicContentController` and `PublicPageController` in the required order. Concrete templates, headers, footers and domain record queries remain configurable integrations and are not hardcoded in Pages.

## Public Management Registry

Pages can provide the authenticated public-site management flyout without hardcoding project components. Enable it and register panels in configuration:

```php
'public_management' => [
    'enabled' => true,
    'event' => 'pages-open-public-management',
    'panels' => [
        'organization' => [
            'title' => 'Organization',
            'icon' => 'building-office-2',
            'permission' => 'pages.update',
            'component' => App\Livewire\OrganizationEditor::class,
        ],
        'pages' => [
            'title' => 'Pages',
            'icon' => 'document-text',
            'permission' => 'pages.view',
            'event' => 'pages-open-public-page-structure',
        ],
        'usage' => [
            'title' => 'Usage',
            'icon' => 'circle-stack',
            'permission' => 'pages.view',
            'view' => 'public-management.usage',
            'data_provider' => App\Support\UsagePanelData::class,
        ],
    ],
],
```

Each panel defines exactly one presentation path: a Livewire `component`, a server-side `view`, or an `event`. View panels may use a `PublicManagementPanelDataProvider`. Browser panel keys are re-resolved through `PublicManagementRegistry`, and configured permissions are authorized before rendering.

Mount the packaged component only for an authenticated actor viewing its own tenant:

```blade
<livewire:pages.public.management-flyout />
```

## Configured Editor Safety

Configured section editors support dynamic checkbox options, conditional fields/tabs/layout variants and reusable Livewire or form tabs. Required settings receive their schema default when an older section has no stored value.

Gallery sections receive an additional data-loss guard. When a section changes from directly attached photographs to existing gallery albums, the editor lists hidden direct media and requires the user to keep or delete those files explicitly. If files are kept, a persistent warning remains visible while the album source is active. Deletion is delegated to Gallery's authorized `DeleteGalleryMediaAction`.

Fetch the home page with visible sections:

```php
$home = Page::query()
    ->home()
    ->published()
    ->with(['visibleSections.visibleItems'])
    ->first();
```

Find pages by public keys:

```php
$page = Page::findByUuid($uuid);
$page = Page::forSlug('naslovnica');
```

Render sections in Blade:

```blade
@foreach ($page->visibleSections as $section)
    @includeIf("pages.sections.{$section->type}", ['section' => $section])
@endforeach
```

## API Examples

```php
$page->isHome();
$page->isPublished();
$page->unpublish();

$page->section('hero');
$page->sectionsOfType('features')->get();
$page->moveSection($section->uuid, 1);
$page->hideSection($section->uuid);
$page->showSection($section->uuid);

$section->isVisible();
$section->hasItems();
$section->moveItem($item->uuid, 2);
$section->setting('variant', 'default');

$item->isVisible();
$item->hasImage();
$item->buttonUrl();
$item->setting('size', 'md');
```

## Actions

Actions return `IvanBaric\Corexis\Data\ActionResult` directly and avoid UI exceptions for normal validation or lookup failures.

The write API covers create, update, publish/unpublish, archive/delete, copy, move, reorder and visibility changes for pages, sections and section items. `ToggleSectionVisibilityAction` and `ToggleSectionItemVisibilityAction` own visibility transitions; Livewire components do not save these models directly.

Archived records use `RestoreArchivedRecordAction` and `ForceDeleteArchivedRecordAction`. Both accept only the supported record type and UUID, resolve trashed records through `PagesModels`, authorize the resolved model and perform the write in a transaction. Browser-provided model ids or classes are never trusted.

`Page::publish()`, `Page::unpublish()` and each model's `archive()` method contain the state mutation itself. Actions remain the required boundary for user-driven writes because they add authorization, tenant-safe resolution, locking and domain events.

`pages.publication.guard` may point to a host implementation of `PagePublicationGuard`. The guard runs after authorization and before the transaction, and may return an error `ActionResult` to block publication for billing, approval or product-lifecycle reasons. Pages does not depend on Billing or Plans.

Successful Actions dispatch small domain events after the write path succeeds. Failed validation and lookup results do not dispatch success events.

Current events:

- `PageCreated`
- `PageUpdated`
- `PageDeleted`
- `PagePublished`
- `PageUnpublished`
- `PageSectionsReordered`
- `SectionCreated`
- `SectionUpdated`
- `SectionDeleted`
- `SectionItemsReordered`
- `SectionItemCreated`
- `SectionItemUpdated`
- `SectionItemDeleted`

Standard write flow:

```text
Livewire Component -> Action -> Corexis ActionResult -> Domain Event -> Listener
```

Form Object extraction for the Livewire admin forms is still a separate compatibility pass because this package currently targets Livewire 4.

```php
$result = app(CreatePageAction::class)->handle([
    'title' => ['en' => 'About'],
    'template' => 'classic',
]);

if ($result->success) {
    $page = $result->data;
}
```

## No-Duplication Philosophy

This package owns only pages, sections and section items. It intentionally does not create a custom admin layout, sidebar, SEO tables, media tables, taxonomy tables, tenant resolver or billing logic.

Existing packages should continue to own their responsibilities:

- `corexis`: shared contracts, UUIDs, unique slugs, Action results and tenant scope
- `admin-ui`: layout, sidebar, shell and Flux/Tailwind structure
- `taxonomy`: taxonomies and taxonomy items
- `gallery`: media, galleries and images
- `seo`: SEO metadata
- `status`: status handling
- `settings`: settings
- `language`: languages and translations
- `audit`: audit logging
- `template-engine`: template registration, schema and concrete page rendering boundary

## Testing

```bash
cd packages/ivanbaric/pages
composer install
vendor/bin/pest
```

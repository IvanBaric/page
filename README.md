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

## Testing

```bash
cd packages/ivanbaric/pages
composer install
vendor/bin/pest
```

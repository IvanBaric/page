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

`config/pages.php` controls table names, model overrides, team resolver, statuses, templates, section types, route settings, middleware, pagination, translatable fields, Sanigen, SEO, gallery/media, admin-ui and feature flags.

The default admin routes are:

```php
route('admin.pages.index');
route('admin.pages.create');
route('admin.pages.edit', ['page' => $page->uuid]);
route('admin.pages.sections', ['page' => $page->uuid]);
route('admin.pages.sections.items', ['page' => $page->uuid, 'section' => $section->uuid]);
```

## Team Resolver

Team ownership uses `App\Resolvers\TeamResolver` automatically when that class exists. The resolver may expose `resolve()`, `currentTeamId()`, `teamId()` or `id()`.

If your shared resolver lives elsewhere, set:

```php
'team_resolver' => App\Resolvers\TeamResolver::class,
```

The package never hardcodes `auth()->user()->current_team_id`.

## UUID Rule

All public/admin lookups use `uuid`. The route key is `uuid` for pages, sections and section items. UI actions should pass UUIDs, not database IDs.

## Slugs

Slug generation is delegated to Sanigen when a configured generator or known Sanigen class is available:

```php
'slug' => [
    'sanigen' => [
        'generator' => App\Support\SanigenSlugGenerator::class,
        'method' => 'generate',
    ],
],
```

The package does not ship a reusable `HasSlug` trait and does not duplicate application slug infrastructure.

## Integrations

SEO is not implemented here. Configure `pages.seo.trait` for your SEO package, for example `IvanBaric\Seo\Concerns\HasSeo`, and store SEO data through that package.

Gallery/media logic is not duplicated. The package includes optional `image` string columns for simple references and exposes gallery/media config hooks for the existing gallery package.

Taxonomy logic is not duplicated. Configure `pages.taxonomy.trait` for your taxonomy package and attach taxonomies through that package.

Admin layout, sidebar, header and shell are not implemented here. The Livewire views use Flux components and render inside `pages.admin_ui.layout`.

Status, settings, language and audit packages remain integration points. Pages stores only the generic fields required for page-builder structure.

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

Actions return `IvanBaric\Pages\Data\ActionResult` and avoid UI exceptions for normal validation or lookup failures.

```php
$result = app(CreatePageAction::class)->handle([
    'title' => ['en' => 'About'],
    'template' => 'classic',
]);

if ($result->successful) {
    $page = $result->data;
}
```

## No-Duplication Philosophy

This package owns only pages, sections and section items. It intentionally does not create a custom admin layout, sidebar, SEO tables, media tables, taxonomy tables, slug trait, team resolver or billing logic.

Existing packages should continue to own their responsibilities:

- `corexis`: shared contracts, action results, resolvers and tenant/team abstractions
- `admin-ui`: layout, sidebar, shell and Flux/Tailwind structure
- `sanigen`: UUID, slug, generation and sanitization when available
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

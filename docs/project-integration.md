# Pages Project Integration

This document describes the minimum integration every project should add when it uses `ivanbaric/pages`.

The package provides the page-builder foundation, configured editors and Livewire admin components. The host project still owns the admin shell, sidebar, project-specific routes and template-specific header/footer definitions.

## Required Admin Links

Every project should expose these three admin links:

- `Sve stranice` -> `route('admin.pages.index')`
- `Zaglavlje` -> `route('admin.pages.index', ['part' => 'header'])`
- `Podnožje` -> `route('admin.pages.index', ['part' => 'footer'])`

Example sidebar group:

```blade
<flux:sidebar.group expandable icon="document-text" heading="Stranice" class="grid">
    <flux:sidebar.item
        :href="route('admin.pages.index')"
        :current="request()->routeIs('admin.pages.index') && ! request()->has('part')"
        wire:navigate
    >
        {{ __('Sve stranice') }}
    </flux:sidebar.item>

    <flux:sidebar.item
        :href="route('admin.pages.index', ['part' => 'header'])"
        :current="request()->query('part') === 'header'"
        wire:navigate
    >
        {{ __('Zaglavlje') }}
    </flux:sidebar.item>

    <flux:sidebar.item
        :href="route('admin.pages.index', ['part' => 'footer'])"
        :current="request()->query('part') === 'footer'"
        wire:navigate
    >
        {{ __('Podnožje') }}
    </flux:sidebar.item>
</flux:sidebar.group>
```

## Required Routes

The project should route all three links through the same page index component. `Zaglavlje` and `Podnožje` are not separate page records; they are `part` views inside the same admin screen.

```php
use App\Livewire\Admin\Pages\Index as PagesIndex;
use Illuminate\Support\Facades\Route;
use IvanBaric\Pages\Livewire\Admin\PageShow;
use IvanBaric\Pages\Livewire\Admin\SectionShow;

Route::middleware(['auth', 'verified'])
    ->prefix('app')
    ->group(function (): void {
        Route::get('/pages', PagesIndex::class)->name('admin.pages.index');
        Route::get('/pages/{page:uuid}', PageShow::class)->name('admin.pages.show');
        Route::get('/sections/{section:uuid}', SectionShow::class)->name('admin.sections.show');
    });
```

`PagesIndex` is usually project-specific because it can include project stats,
template links and custom filters. `PageShow` and `SectionShow` should come from
the package so the page-builder shell stays reusable across projects.

`PagesIndex` should accept only these parts:

```php
public string $part = 'pages';

public function mount(): void
{
    $part = (string) request()->query('part', 'pages');

    $this->part = in_array($part, ['pages', 'header', 'footer'], true) ? $part : 'pages';
}
```

## Required Page Index View

The project index view should render:

- the regular page list when `$part === 'pages'`
- a configured singleton editor for `template_header` when `$part === 'header'`
- a configured singleton editor for `template_footer` when `$part === 'footer'`

Example:

```blade
@if ($part === 'pages')
    {{-- Project page list. --}}
@elseif ($part === 'header')
    @livewire(\IvanBaric\Pages\Livewire\Admin\ConfiguredSingletonEditor::class, [
        'model' => $this->organization,
        'definitionKey' => 'template_header',
    ], key('admin-template-header-editor-'.$this->organization->getKey()))
@elseif ($part === 'footer')
    @livewire(\IvanBaric\Pages\Livewire\Admin\ConfiguredSingletonEditor::class, [
        'model' => $this->organization,
        'definitionKey' => 'template_footer',
    ], key('admin-template-footer-editor-'.$this->organization->getKey()))
@endif
```

The model passed to `ConfiguredSingletonEditor` should be the project model that stores template settings. In Niva this is `Organization`, and values are stored under:

- `settings.templates.niva-classic.header`
- `settings.templates.niva-classic.footer`

## Required Config

Register the project section definition provider:

```php
'admin_section_definitions' => [
    App\Admin\PageSections::class,
],

'admin_routes' => [
    'page_index' => 'admin.pages.index',
    'page_show' => 'admin.pages.show',
    'section_show' => 'admin.sections.show',
],
```

The provider should define at least these singleton definitions:

- `template_header`
- `template_footer`

Use `ConfiguredSingletonEditor` for these definitions, and store values in the model `settings` JSON column or another project-owned settings store.

## Recommended Structure

For consistency between projects, keep project-specific definitions in:

```text
app/Admin/PageSections.php
```

Use this file for:

- reusable section item editors, such as FAQ, testimonials, partners and contact
- template singleton editors, such as header and footer
- layout variants and their preview keys
- field labels, validation messages and success messages

Keep the generic editor implementation inside the package. Do not create one-off Livewire editors such as `FaqEditor` or `FooterEditor` unless the section truly needs custom behavior that cannot be described through the configured API.

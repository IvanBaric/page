# Admin Section API

Reusable admin section definitions live in `IvanBaric\Pages\Admin`.

This API is used to describe page-section admin editors in project code, while the
package provides the Livewire editor implementation.

## Core Objects

- `AdminSection` - top-level editor definition.
- `Tab` - editor tab definition.
- `Field` - form field definition.
- `Action` - action button or item menu action definition.
- `LayoutVariant` - public layout variant definition and admin preview metadata.
- `AdminSectionRegistry` - resolves definitions from configured providers.

Register provider classes in `config/pages.php`:

```php
'admin_section_definitions' => [
    App\Admin\PageSections::class,
],
```

Map section types to the configured package editor:

```php
'section_editors' => [
    'testimonials' => 'admin.pages.sections.configured-items-editor',
    'faq' => 'admin.pages.sections.configured-items-editor',
],
```

## Item-Based Sections

Use `ConfiguredItemsEditor` for sections backed by `section_items`.

```php
use IvanBaric\Pages\Admin\Action;
use IvanBaric\Pages\Admin\AdminSection;
use IvanBaric\Pages\Admin\Field;
use IvanBaric\Pages\Admin\LayoutVariant;
use IvanBaric\Pages\Admin\Tab;

AdminSection::add('testimonials')
    ->label(__('Dječji dojmovi'))
    ->messages([
        'required' => __('Polje je obavezno.'),
        'saved' => __('Stavka je spremljena.'),
        'layout_saved' => __('Izgled sekcije je spremljen.'),
    ])
    ->tabs([
        Tab::items(__('Sadržaj'))
            ->heading(__('Dječji dojmovi'))
            ->description(__('Uredite izjave koje se prikazuju na javnoj stranici.'))
            ->formTitle(__('Uredi izjavu'), __('Dodaj izjavu'))
            ->modalFlyout()
            ->singleColumn()
            ->hideSortOrder()
            ->fields([
                Field::text('title')->label(__('Ime ili potpis'))->required()->max(120),
                Field::text('subtitle')->label(__('Opis osobe'))->nullable()->max(160),
                Field::textarea('content')->label(__('Izjava'))->rows(4)->required()->max(600),
                Field::image('image')
                    ->label(__('Slika ili logo'))
                    ->size('small')
                    ->mediaCollection('image')
                    ->storeAsGalleryMedia(),
            ])
            ->actions([
                Action::edit()->label(__('Uredi')),
                Action::delete()
                    ->label(__('Obriši'))
                    ->confirmTitle(__('Obrisati izjavu?'))
                    ->confirmText(__('Ova radnja briše izjavu iz sekcije.')),
            ]),

        Tab::layout(__('Izgled'))
            ->heading(__('Izgled sekcije'))
            ->description(__('Odaberite kako će se stavke prikazati na javnoj stranici.'))
            ->storage('settings.layout_variant')
            ->default('cards')
            ->variants([
                LayoutVariant::add('cards')
                    ->label(__('Jednake kartice'))
                    ->description(__('Jednostavan mrežni prikaz.'))
                    ->preview('cards'),
            ]),
    ]);
```

## Singleton Item Sections

Use `Tab::items()->maxItems(1)->inlineForm()` when a section has one
`section_items` record and should be edited directly in the content tab, without a
list and modal.

```php
AdminSection::add('about')
    ->label(__('O zadruzi'))
    ->tabs([
        Tab::items(__('Sadržaj'))
            ->heading(__('O zadruzi'))
            ->description(__('Uredite tekst i sliku koji se prikazuju u ovoj sekciji.'))
            ->showVisibility(false)
            ->maxItems(1)
            ->inlineForm(submitLabel: __('Spremi sadržaj'))
            ->fields([
                Field::textarea('content')->label(__('O zadruzi'))->rows(10)->nullable(),
                Field::image('image')
                    ->label(__('Slika'))
                    ->size('max-w-sm aspect-[4/3]')
                    ->mediaCollection('image')
                    ->storeAsGalleryMedia(),
            ]),
    ]);
```

Inline singleton sections still use the same save action as normal item sections.

## Icon Fields

Use `Field::icon('icon')` for icon-name inputs. The configured item form renders
the standard icon help link automatically, so every icon field has the same
`Pregled ikonica` link to Heroicons.

```php
Field::icon('icon')
    ->label(__('Ikona'))
    ->nullable()
    ->max(255);
```

If a specific project really needs to hide that help link, set
`->option('show_help', false)`.

## Source Sections

Use `Tab::source()` when the section displays records managed by another module,
for example products, posts or galleries. No `section_items` form is rendered.

```php
AdminSection::add('featured_products')
    ->label(__('Istaknuti radovi'))
    ->tabs([
        Tab::source(__('Sadržaj'))
            ->heading(__('Istaknuti radovi'))
            ->description(__('Ova sekcija prikazuje objavljene radove iz modula Radovi.'))
            ->actions([
                Action::add('open_products')
                    ->label(__('Otvori radove'))
                    ->icon('rectangle-stack')
                    ->variant('primary')
                    ->route('niva.products.index'),

                Action::add('create_product')
                    ->label(__('Dodaj rad'))
                    ->icon('plus')
                    ->variant('ghost')
                    ->route('niva.products.create'),
            ]),

        Tab::layout(__('Izgled'))->variants([
            LayoutVariant::add('cards')->label(__('Jednake kartice'))->preview('products_cards'),
        ]),

        Tab::settings(__('Postavke'))->fields([
            Field::select('limit')
                ->label(__('Broj radova'))
                ->default(6)
                ->options([
                    ['value' => 3, 'label' => __('3 rada')],
                    ['value' => 6, 'label' => __('6 radova')],
                ])
                ->rules(['required', 'integer', 'in:3,6'])
                ->storage('settings.limit'),
        ]),
    ]);
```

## Settings Tab

Use `Tab::settings()` for section-level settings stored on `section.settings`.

```php
Tab::settings(__('Postavke'))
    ->heading(__('Postavke prikaza'))
    ->description(__('Uredite koliko se zapisa prikazuje u ovoj sekciji.'))
    ->fields([
        Field::select('limit')
            ->label(__('Broj objava'))
            ->default(3)
            ->options([
                ['value' => 3, 'label' => __('3 objave')],
                ['value' => 6, 'label' => __('6 objava')],
                ['value' => 12, 'label' => __('12 objava')],
            ])
            ->rules(['required', 'integer', 'in:3,6,12'])
            ->storage('settings.limit'),

        Field::boolean('show_date')
            ->label(__('Prikaži datum objave'))
            ->default(false)
            ->storage('settings.show_date'),
    ]);
```

## Dynamic Checkbox Lists

Use `Field::checkboxList()` when a section setting stores multiple values whose
options come from another module or from tenant-owned data. Keep the field
definition declarative and resolve the options through a provider class.

```php
use Illuminate\Database\Eloquent\Model;
use IvanBaric\Pages\Admin\Contracts\FieldOptionsProvider;
use IvanBaric\Pages\Admin\Field;

final class TopicOptions implements FieldOptionsProvider
{
    public function options(Model $context, Field $field): array
    {
        $teamId = $context->getAttribute('team_id');

        // Providers must explicitly scope every query to the context tenant.
        return Topic::query()
            ->where('team_id', $teamId)
            ->orderBy('name')
            ->get()
            ->map(fn (Topic $topic): array => [
                'value' => $topic->uuid,
                'label' => $topic->name,
                'description' => $topic->description,
                'group_key' => 'topics',
                'group_label' => __('Teme'),
                'group_description' => __('Dostupne teme sadržaja.'),
                'group_type' => 'tags',
            ])
            ->all();
    }
}

Field::checkboxList('topic_uuids')
    ->label(__('Teme'))
    ->default([])
    ->optionsProvider(TopicOptions::class)
    ->storage('settings.topic_uuids');
```

Fields and tabs may depend on another settings field. The controlling field is
automatically rendered with a live binding so dependent UI updates immediately.

```php
Field::select('content_source')
    ->options([
        ['value' => 'all', 'label' => __('Sve')],
        ['value' => 'taxonomy', 'label' => __('Prema kategoriji ili oznaci')],
    ])
    ->default('all')
    ->storage('settings.content_source');

Field::checkboxList('taxonomy_item_uuids')
    ->visibleWhen('content_source', 'taxonomy')
    ->storage('settings.taxonomy_item_uuids');

Tab::view(__('Fotografije'), 'project::section-photos', 'photos')
    ->visibleWhen('content_source', 'direct');
```

Conditions compare strict stored values. They control editor visibility only;
submitted values are still server-side validated and public renderers must
allowlist the selected mode independently.

The editor validates every submitted value against the provider result. Values
that no longer exist are removed when the form is initialized. Providers must
not rely only on a client-supplied model identifier: compare the model tenant to
the current tenant and return no options when they do not match.

## Singleton Settings Editor

Use `ConfiguredSingletonEditor` for a single settings object on an arbitrary model.
This is used by the admin header editor, where values are stored on
`organizations.settings`, not in `section_items`.

Definition:

```php
AdminSection::add('template_header')
    ->label(__('Zaglavlje templatea'))
    ->option('storage', 'settings.templates.niva-classic.header')
    ->messages([
        'required' => __('Polje je obavezno.'),
        'saved' => __('Zaglavlje je spremljeno.'),
        'layout_saved' => __('Izgled zaglavlja je spremljen.'),
    ])
    ->tabs([
        Tab::form(__('Sadržaj'))
            ->heading(__('Zaglavlje templatea'))
            ->description(__('Uredite sadržaj javnog zaglavlja.'))
            ->submitLabel(__('Spremi zaglavlje'))
            ->fields([
                Field::text('eyebrow')->label(__('Nadnaslov'))->nullable()->max(120),
                Field::text('title')->label(__('Naslov'))->required()->max(160)->defaultFrom('name'),
                Field::textarea('subtitle')->label(__('Podnaslov'))->rows(4)->nullable()->max(500)->defaultFrom('description'),
                Field::image('image')
                    ->label(__('Slika zaglavlja'))
                    ->size('w-full aspect-[16/9]')
                    ->mediaCollection('website_header_image')
                    ->storeAsGalleryMedia()
                    ->option('media_conversion', 'xlarge')
                    ->option('store_value', false),
            ]),

        Tab::layout(__('Izgled'))
            ->storage('header_variant')
            ->default('header-1')
            ->submitLabel(__('Spremi izgled'))
            ->variants([
                LayoutVariant::add('header-1')->label(__('Zaglavlje 1'))->preview('header_hero'),
                LayoutVariant::add('header-2')->label(__('Zaglavlje 2'))->preview('header_editorial'),
                LayoutVariant::add('header-3')->label(__('Zaglavlje 3'))->preview('header_sticky'),
            ]),
    ]);
```

Render:

```blade
@livewire(\IvanBaric\Pages\Livewire\Admin\ConfiguredSingletonEditor::class, [
    'model' => $organization,
    'definitionKey' => 'template_header',
])
```

The same pattern is used for the footer editor:

```php
AdminSection::add('template_footer')
    ->label(__('Podnožje templatea'))
    ->option('storage', 'settings.templates.niva-classic.footer')
    ->tabs([
        Tab::form(__('Sadržaj'))
            ->heading(__('Podnožje templatea'))
            ->submitLabel(__('Spremi podnožje'))
            ->fields([
                Field::text('copyright')
                    ->label(__('Tekst podnožja'))
                    ->required()
                    ->max(180)
                    ->default(__('© :year Školska zadruga. Sva prava pridržana.', ['year' => now()->year])),
            ]),

        Tab::layout(__('Izgled'))
            ->storage('layout_variant')
            ->default('classic')
            ->variants([
                LayoutVariant::add('classic')
                    ->label(__('Klasično podnožje'))
                    ->preview('footer_classic'),
            ]),
    ]);
```

## Fields

Available field factories:

- `Field::text('key')`
- `Field::textarea('key')`
- `Field::image('key')`
- `Field::select('key')`
- `Field::boolean('key')`
- `Field::number('key')`
- `Field::url('key')`
- `Field::icon('key')`

Common field methods:

- `label(string $label)`
- `required()`
- `nullable()`
- `max(int $max)`
- `rows(int $rows)`
- `size(string $size)`
- `help(string $help)`
- `options(array $options)`
- `default(mixed $value)`
- `defaultFrom(string $attribute)` for singleton model defaults
- `storage(string $path)`
- `rules(array $rules)`
- `rule(string $rule)`
- `mediaCollection(string $collection)`
- `storeAsGalleryMedia(bool $store = true)`
- `option(string $key, mixed $value)`

Known item field keys mapped to `ConfiguredSectionItemForm`:

- `title`
- `subtitle`
- `content`
- `image`
- `icon`
- `url`
- `button_text`
- `button_url`
- `meta_value`
- `meta_suffix`
- `meta_rating`
- `sort_order`

## Tabs

### Livewire source editors

Za sadržaj kojim upravlja drugi modul koristite deklarativni Livewire tab. Pages paket renderira registrirani Livewire alias i ne ovisi o konkretnoj klasi modula:

```php
Tab::livewire(__('Sadržaj'), 'blog.source-manager')
    ->parameters(['mode' => 'compact']);
```

Komponenta izvornog modula odgovorna je za tenant scope, autorizaciju, validaciju i spremanje. Nakon uspješne promjene treba emitirati zajednički događaj koji javnoj stranici omogućuje ciljano osvježavanje:

```php
$this->dispatch('pages-public-content-source-updated', source: 'posts');
```

Statički parametri iz `parameters()` prosljeđuju se komponenti pri mountu. Server-owned parametri u ciljnoj komponenti moraju biti `#[Locked]` prema Corexis sigurnosnom standardu.

Available tab factories:

- `Tab::items('Sadržaj')` for `section_items`.
- `Tab::form('Sadržaj')` for singleton model settings.
- `Tab::source('Sadržaj')` for external module source panels.
- `Tab::layout('Izgled')` for layout variants.
- `Tab::settings('Postavke')` for section settings.
- `Tab::view($label, $view)` as an escape hatch.
- `Tab::livewire($label, $component)` as an escape hatch.

Common tab methods:

- `heading(string $heading)`
- `description(string $description)`
- `formTitle(string $editTitle, ?string $createTitle = null)`
- `addLabel(string $label)`
- `emptyText(string $text)`
- `submitLabel(string $label)`
- `modalDescription(string $description)`
- `modalFlyout(bool $enabled = true)`
- `singleColumn(bool $enabled = true)`
- `hideSortOrder()`
- `showSortOrder(bool $enabled = true)`
- `showVisibility(bool $enabled = true)`
- `maxItems(int $max)`
- `inlineForm(bool $enabled = true, ?string $submitLabel = null)`
- `storage(string $path)`
- `default(mixed $value)`
- `fields(array $fields)`
- `actions(array $actions)`
- `variants(array $variants)`
- `option(string $key, mixed $value)`

## Actions

Actions are used by item menus and source panels.

```php
Action::edit()->label(__('Uredi'));

Action::delete()
    ->label(__('Obriši'))
    ->confirmTitle(__('Obrisati stavku?'))
    ->confirmText(__('Ova radnja briše stavku iz sekcije.'));

Action::add('open_posts')
    ->label(__('Otvori objave'))
    ->icon('document-text')
    ->variant('primary')
    ->route('niva.posts.index');
```

Common action methods:

- `label(string $label)`
- `icon(?string $icon)`
- `variant(string $variant)`
- `confirmTitle(string $title)`
- `confirmText(string $text)`
- `route(string $name, array $parameters = [])`
- `url(string $url)`
- `option(string $key, mixed $value)`

## Layout Variants

```php
LayoutVariant::add('cards')
    ->label(__('Jednake kartice'))
    ->description(__('Jednostavan mrežni prikaz.'))
    ->preview('faq_cards');
```

Preview keys map to `pages::livewire.admin.partials.layout-variant-preview`.
Project sections should provide preview keys that visually match the public layout.

## Configured Actions

Projects can point the generic renderer to project-specific action classes when
saving needs extra behavior such as gallery media handling:

```php
'configured_items_editor' => [
    'actions' => [
        'save_item' => App\Actions\Pages\SaveSectionItemAction::class,
        'toggle_item' => App\Actions\Pages\ToggleSectionItemAction::class,
        'reorder_item' => App\Actions\Pages\ReorderSectionItemAction::class,
        'delete_item' => App\Actions\Pages\DeleteSectionItemAction::class,
        'save_section' => App\Actions\Pages\SaveSectionAction::class,
    ],
],
```

## Current Project Usage

`App\Admin\PageSections` currently uses this API for:

- `template_header`
- `template_footer`
- `about`
- `featured_products`
- `all_products`
- `gallery`
- `featured_news`
- `latest_news`
- `taxonomy_news`
- `testimonials`
- `faq`
- `features`
- `partners`
- `stats`
- `mission`
- `vision`
- `values`
- `team`
- `how_to_order`
- `social_links`
- `contact`

<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use IvanBaric\Corexis\Contracts\TenantResolver;
use IvanBaric\Corexis\Data\ActionResult as CorexisActionResult;
use IvanBaric\Pages\Actions\CreatePageAction;
use IvanBaric\Pages\Actions\CreateSectionAction;
use IvanBaric\Pages\Actions\CreateSectionItemAction;
use IvanBaric\Pages\Actions\PublishPageAction;
use IvanBaric\Pages\Actions\ReorderSectionItemsAction;
use IvanBaric\Pages\Actions\ReorderSectionsAction;
use IvanBaric\Pages\Actions\UnpublishPageAction;
use IvanBaric\Pages\Actions\UpdatePageAction;
use IvanBaric\Pages\Admin\Action;
use IvanBaric\Pages\Admin\AdminSection;
use IvanBaric\Pages\Admin\AdminSectionRegistry;
use IvanBaric\Pages\Admin\Field;
use IvanBaric\Pages\Admin\LayoutVariant;
use IvanBaric\Pages\Admin\Tab;
use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Events\PageCreated;
use IvanBaric\Pages\Events\PagePublished;
use IvanBaric\Pages\Events\PageSectionsReordered;
use IvanBaric\Pages\Events\PageUnpublished;
use IvanBaric\Pages\Events\PageUpdated;
use IvanBaric\Pages\Events\SectionCreated;
use IvanBaric\Pages\Events\SectionItemCreated;
use IvanBaric\Pages\Events\SectionItemsReordered;
use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Models\Section;
use IvanBaric\Pages\Models\SectionItem;

class PagesTestTeamResolver
{
    public function resolve(): int
    {
        return 123;
    }
}

class PagesTestSlugger
{
    public function generate(string $source): string
    {
        return str($source)->slug()->toString();
    }
}

class PagesCorexisTenantResolverFake implements TenantResolver
{
    public function enabled(): bool
    {
        return true;
    }

    public function current(): mixed
    {
        return null;
    }

    public function id(): int|string|null
    {
        return 987;
    }

    public function uuid(): ?string
    {
        return null;
    }

    public function type(): ?string
    {
        return 'team';
    }
}

class PagesTestAdminSectionProvider
{
    /** @return array<int, AdminSection> */
    public function definitions(): array
    {
        return [
            AdminSection::add('testimonials')
                ->label('Testimonials')
                ->tabs([
                    Tab::items('Content')
                        ->formTitle('Edit testimonial', 'Add testimonial')
                        ->fields([
                            Field::text('title')->label('Name')->required(),
                            Field::textarea('content')->label('Quote')->rows(4)->required(),
                            Field::image('image')->label('Photo')->size('small'),
                        ])
                        ->actions([
                            Action::edit()->label('Edit'),
                            Action::delete()->label('Delete'),
                        ]),

                    Tab::layout('Layout')
                        ->default('cards')
                        ->variants([
                            LayoutVariant::add('cards')->label('Cards'),
                        ]),
                ]),
        ];
    }
}

it('boots the package', function (): void {
    expect(config('pages.tables.pages'))->toBe('pages');
});

it('loads config', function (): void {
    expect(config('pages.templates.classic.label'))->toBe('Classic')
        ->and(config('pages.section_types.hero.label'))->toBe('Hero');
});

it('defines admin sections through the reusable fluent API', function (): void {
    $section = AdminSection::add('testimonials')
        ->label('Testimonials')
        ->tabs([
            Tab::items('Content')
                ->formTitle('Edit testimonial')
                ->inlineForm(submitLabel: 'Save content')
                ->fields([
                    Field::text('title')->label('Name')->required(),
                    Field::textarea('content')->label('Quote')->rows(4)->required(),
                ]),

            Tab::layout('Layout')
                ->default('cards')
                ->variants([
                    LayoutVariant::add('cards')->label('Cards'),
                ]),

            Tab::form('Header', 'header')
                ->submitLabel('Save header')
                ->fields([
                    Field::text('title')->label('Title')->defaultFrom('name'),
                ]),
        ]);

    expect($section->key())->toBe('testimonials')
        ->and($section->itemsTab()?->field('content')?->optionValue('rows'))->toBe(4)
        ->and($section->itemsTab()?->optionValue('show_sort_order'))->toBeFalse()
        ->and($section->itemsTab()?->optionValue('modal_flyout'))->toBeTrue()
        ->and($section->itemsTab()?->optionValue('inline_form'))->toBeTrue()
        ->and($section->itemsTab()?->optionValue('inline_submit_label'))->toBe('Save content')
        ->and($section->itemsTab()?->optionValue('single_column'))->toBeTrue()
        ->and($section->layoutTab()?->variantKeys())->toBe(['cards'])
        ->and($section->tab('header')?->type())->toBe('form')
        ->and($section->tab('header')?->optionValue('submit_label'))->toBe('Save header')
        ->and($section->tab('header')?->field('title')?->optionValue('default_from'))->toBe('name')
        ->and($section->toArray()['tabs'])->toHaveCount(3);
});

it('resolves admin section definitions from configured providers', function (): void {
    config()->set('pages.admin_section_definitions', [
        PagesTestAdminSectionProvider::class,
    ]);

    app(AdminSectionRegistry::class)->flush();

    $definition = app(AdminSectionRegistry::class)->get('testimonials');

    expect($definition)->toBeInstanceOf(AdminSection::class)
        ->and($definition?->labelValue())->toBe('Testimonials')
        ->and($definition?->itemsTab()?->field('image')?->optionValue('size'))->toBe('small');
});

it('creates the package tables', function (): void {
    expect(Schema::hasTable('pages'))->toBeTrue()
        ->and(Schema::hasTable('sections'))->toBeTrue()
        ->and(Schema::hasTable('section_items'))->toBeTrue();
});

it('creates a page', function (): void {
    $page = Page::query()->create([
        'title' => ['en' => 'First page'],
    ]);

    expect($page)->toBeInstanceOf(Page::class)
        ->and($page->title)->toBe(['en' => 'First page']);
});

it('generates uuid', function (): void {
    $page = Page::query()->create([
        'title' => ['en' => 'Uuid page'],
    ]);

    expect($page->uuid)->toBeString()->not->toBeEmpty();
});

it('generates slug through the configured sanigen hook when available', function (): void {
    config()->set('pages.slug.sanigen.generator', PagesTestSlugger::class);

    $page = Page::query()->create([
        'title' => ['en' => 'Sanigen Page'],
    ]);

    expect($page->slug)->toBe('sanigen-page');
});

it('resolves team id', function (): void {
    config()->set('pages.team_resolver', PagesTestTeamResolver::class);

    $page = Page::query()->create([
        'title' => ['en' => 'Team page'],
    ]);

    expect($page->team_id)->toBe(123);
});

it('resolves team id through corexis tenant resolver first', function (): void {
    app()->bind(TenantResolver::class, PagesCorexisTenantResolverFake::class);

    $page = Page::query()->create([
        'title' => ['en' => 'Corexis team page'],
    ]);

    expect($page->team_id)->toBe(987);
});

it('finds page by uuid', function (): void {
    $page = Page::query()->create([
        'title' => ['en' => 'Lookup page'],
    ]);

    expect(Page::findByUuid($page->uuid)?->is($page))->toBeTrue()
        ->and(Page::forUuid($page->uuid)?->is($page))->toBeTrue();
});

it('finds page by slug', function (): void {
    $page = Page::query()->create([
        'title' => ['en' => 'Slug page'],
    ]);

    expect(Page::forSlug($page->slug)?->is($page))->toBeTrue();
});

it('keeps only one home page per team', function (): void {
    $first = Page::query()->create([
        'team_id' => 10,
        'title' => ['en' => 'First home'],
        'is_home' => true,
    ]);

    $second = Page::query()->create([
        'team_id' => 10,
        'title' => ['en' => 'Second home'],
        'is_home' => true,
    ]);

    expect($first->refresh()->is_home)->toBeFalse()
        ->and($second->refresh()->is_home)->toBeTrue();
});

it('page can have sections', function (): void {
    $page = Page::query()->create([
        'title' => ['en' => 'Section page'],
    ]);

    $section = $page->addSection('hero', [
        'title' => ['en' => 'Hero'],
    ]);

    expect($page->sections()->first()?->is($section))->toBeTrue();
});

it('section can have items', function (): void {
    $section = Page::query()->create([
        'title' => ['en' => 'Items page'],
    ])->addSection('features');

    $item = $section->addItem([
        'title' => ['en' => 'Feature'],
    ]);

    expect($section->items()->first()?->is($item))->toBeTrue();
});

it('section item slugs stay unique when a previous item was soft deleted', function (): void {
    $section = Page::query()->create([
        'title' => ['en' => 'Soft deleted items page'],
    ])->addSection('features');

    $deleted = $section->addItem([
        'title' => ['en' => 'A'],
    ]);

    $deleted->delete();

    $item = $section->addItem([
        'title' => ['en' => 'A'],
    ]);

    expect($item->slug)->toBe('a-2');
});

it('sections are ordered', function (): void {
    $page = Page::query()->create([
        'title' => ['en' => 'Ordered sections'],
    ]);

    $last = $page->addSection('hero', ['sort_order' => 20]);
    $first = $page->addSection('cta', ['sort_order' => 10]);

    expect($page->orderedSections()->pluck('uuid')->all())->toBe([$first->uuid, $last->uuid]);
});

it('section items are ordered', function (): void {
    $section = Page::query()->create([
        'title' => ['en' => 'Ordered items'],
    ])->addSection('features');

    $last = $section->addItem(['title' => ['en' => 'Last'], 'sort_order' => 20]);
    $first = $section->addItem(['title' => ['en' => 'First'], 'sort_order' => 10]);

    expect($section->orderedItems()->pluck('uuid')->all())->toBe([$first->uuid, $last->uuid]);
});

it('visible scopes work', function (): void {
    $section = Page::query()->create([
        'title' => ['en' => 'Visible page'],
    ])->addSection('features');

    $visible = $section->addItem(['title' => ['en' => 'Visible'], 'is_visible' => true]);
    $section->addItem(['title' => ['en' => 'Hidden'], 'is_visible' => false]);

    expect(Section::query()->visible()->count())->toBe(1)
        ->and(SectionItem::query()->visible()->pluck('uuid')->all())->toBe([$visible->uuid]);
});

it('publish and unpublish works', function (): void {
    $page = Page::query()->create([
        'title' => ['en' => 'Publish page'],
    ]);

    $page->publish();

    expect($page->refresh()->isPublished())->toBeTrue();

    $page->unpublish();

    expect($page->refresh()->isPublished())->toBeFalse();
});

it('section type validation works', function (): void {
    $page = Page::query()->create([
        'title' => ['en' => 'Invalid section page'],
    ]);

    $result = app(CreateSectionAction::class)->handle($page->uuid, [
        'type' => 'invalid',
    ]);

    expect($result->successful)->toBeFalse();
});

it('team scoping works', function (): void {
    Page::query()->create([
        'team_id' => 1,
        'title' => ['en' => 'Team one'],
    ]);

    Page::query()->create([
        'team_id' => 2,
        'title' => ['en' => 'Team two'],
    ]);

    expect(Page::query()->forTeam(1)->count())->toBe(1);
});

it('action classes return action result', function (): void {
    $createPage = app(CreatePageAction::class)->handle([
        'title' => ['en' => 'Action page'],
        'status' => 'draft',
        'template' => 'classic',
    ]);

    expect($createPage)->toBeInstanceOf(ActionResult::class)
        ->and($createPage->successful)->toBeTrue();

    $publish = app(PublishPageAction::class)->handle($createPage->data->uuid);
    $unpublish = app(UnpublishPageAction::class)->handle($createPage->data->uuid);
    $section = app(CreateSectionAction::class)->handle($createPage->data->uuid, ['type' => 'hero']);
    $item = app(CreateSectionItemAction::class)->handle($section->data->uuid, ['title' => ['en' => 'Action item']]);
    $reorderSections = app(ReorderSectionsAction::class)->handle($createPage->data->uuid, [$section->data->uuid]);
    $reorderItems = app(ReorderSectionItemsAction::class)->handle($section->data->uuid, [$item->data->uuid]);

    expect($publish)->toBeInstanceOf(ActionResult::class)
        ->and($unpublish)->toBeInstanceOf(ActionResult::class)
        ->and($section)->toBeInstanceOf(ActionResult::class)
        ->and($item)->toBeInstanceOf(ActionResult::class)
        ->and($reorderSections)->toBeInstanceOf(ActionResult::class)
        ->and($reorderItems)->toBeInstanceOf(ActionResult::class);
});

it('pages action result can be converted to corexis action result', function (): void {
    $result = ActionResult::success(__('Saved.'), ['id' => 10], 'saved');
    $corexis = $result->toCorexis();

    expect($corexis)->toBeInstanceOf(CorexisActionResult::class)
        ->and($corexis->success)->toBeTrue()
        ->and($corexis->message)->toBe('Saved.')
        ->and($corexis->data)->toBe(['id' => 10])
        ->and($corexis->code)->toBe('saved');
});

it('dispatches domain events for successful page actions only', function (): void {
    Event::fake([PageCreated::class, PagePublished::class]);

    $failed = app(CreatePageAction::class)->handle([
        'title' => null,
    ]);

    expect($failed->successful)->toBeFalse();
    Event::assertNotDispatched(PageCreated::class);

    $created = app(CreatePageAction::class)->handle([
        'title' => ['en' => 'Event page'],
        'status' => 'draft',
        'template' => 'classic',
    ]);

    expect($created->successful)->toBeTrue();
    Event::assertDispatched(PageCreated::class);

    $published = app(PublishPageAction::class)->handle($created->data->uuid);

    expect($published->successful)->toBeTrue();
    Event::assertDispatched(PagePublished::class);
});

it('dispatches domain events for successful section and item actions', function (): void {
    Event::fake([
        PageCreated::class,
        PageUnpublished::class,
        PageSectionsReordered::class,
        SectionCreated::class,
        SectionItemCreated::class,
        SectionItemsReordered::class,
    ]);

    $page = app(CreatePageAction::class)->handle([
        'title' => ['en' => 'Nested events'],
        'status' => 'draft',
        'template' => 'classic',
    ])->data;

    $section = app(CreateSectionAction::class)->handle($page->uuid, ['type' => 'hero'])->data;
    $item = app(CreateSectionItemAction::class)->handle($section->uuid, ['title' => ['en' => 'Item']])->data;
    app(ReorderSectionsAction::class)->handle($page->uuid, [$section->uuid]);
    app(ReorderSectionItemsAction::class)->handle($section->uuid, [$item->uuid]);

    Event::assertDispatched(SectionCreated::class);
    Event::assertDispatched(SectionItemCreated::class);
    Event::assertDispatched(PageSectionsReordered::class);
    Event::assertDispatched(SectionItemsReordered::class);
});

it('prevents stale page updates through lock version', function (): void {
    Event::fake([PageUpdated::class]);

    $page = Page::query()->create([
        'title' => ['en' => 'Original'],
        'status' => 'draft',
    ]);

    $result = app(UpdatePageAction::class)->handle($page, [
        'title' => ['en' => 'Updated'],
        'status' => 'draft',
        'lock_version' => 0,
    ]);

    expect($result->successful)->toBeTrue()
        ->and($page->refresh()->lock_version)->toBe(1);
    Event::assertDispatched(PageUpdated::class);

    Event::fake([PageUpdated::class]);

    $stale = app(UpdatePageAction::class)->handle($page->refresh(), [
        'title' => ['en' => 'Stale'],
        'status' => 'draft',
        'lock_version' => 0,
    ]);

    expect($stale->successful)->toBeFalse()
        ->and($stale->code)->toBe('conflict.stale_model')
        ->and($page->refresh()->title)->toBe(['en' => 'Updated']);
    Event::assertNotDispatched(PageUpdated::class);
});

<?php

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View as ViewFacade;
use IvanBaric\Corexis\Contracts\TenantResolver;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\CreatePageAction;
use IvanBaric\Pages\Actions\CreateSectionAction;
use IvanBaric\Pages\Actions\CreateSectionItemAction;
use IvanBaric\Pages\Actions\DeletePageAction;
use IvanBaric\Pages\Actions\MovePageAction;
use IvanBaric\Pages\Actions\PublishPageAction;
use IvanBaric\Pages\Actions\ReorderSectionItemsAction;
use IvanBaric\Pages\Actions\ReorderSectionsAction;
use IvanBaric\Pages\Actions\UnpublishPageAction;
use IvanBaric\Pages\Actions\UpdatePageAction;
use IvanBaric\Pages\Admin\Action;
use IvanBaric\Pages\Admin\AdminSection;
use IvanBaric\Pages\Admin\AdminSectionRegistry;
use IvanBaric\Pages\Admin\Contracts\FieldOptionsProvider;
use IvanBaric\Pages\Admin\Field;
use IvanBaric\Pages\Admin\LayoutVariant;
use IvanBaric\Pages\Admin\Tab;
use IvanBaric\Pages\Contracts\PublicContentProvider;
use IvanBaric\Pages\Contracts\PublicManagementPanelDataProvider;
use IvanBaric\Pages\Data\PublicContentContext;
use IvanBaric\Pages\Data\PublicManagementPanel;
use IvanBaric\Pages\Events\PageCreated;
use IvanBaric\Pages\Events\PagePublished;
use IvanBaric\Pages\Events\PageSectionsReordered;
use IvanBaric\Pages\Events\PageUnpublished;
use IvanBaric\Pages\Events\PageUpdated;
use IvanBaric\Pages\Events\SectionCreated;
use IvanBaric\Pages\Events\SectionItemCreated;
use IvanBaric\Pages\Events\SectionItemsReordered;
use IvanBaric\Pages\Http\Controllers\PublicContentController;
use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Models\Section;
use IvanBaric\Pages\Models\SectionItem;
use IvanBaric\Pages\Support\AvailableSectionTypes;
use IvanBaric\Pages\Support\CurrentPublicSite;
use IvanBaric\Pages\Support\EloquentPublicSiteSubjectResolver;
use IvanBaric\Pages\Support\PublicManagementRegistry;
use IvanBaric\Pages\Support\PublicSitePageResolver;
use IvanBaric\Pages\Support\PublicSiteUrl;
use IvanBaric\Pages\Support\YouTubeVideo;
use IvanBaric\TemplateEngine\Contracts\TemplateEngineContract;
use IvanBaric\TemplateEngine\Contracts\TemplateRegistryContract;

class PagesCorexisTenantResolverFake implements TenantResolver
{
    public static int $tenantId = 987;

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
        return self::$tenantId;
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

class PagesTestFieldOptionsProvider implements FieldOptionsProvider
{
    public function options(Model $context, Field $field): array
    {
        return [];
    }
}

class PagesTestPublicSubject extends Model
{
    protected $table = 'public_subjects';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}

class PagesTestPublicContentProvider implements PublicContentProvider
{
    public static ?PublicContentContext $context = null;

    public function render(Request $request, PublicContentContext $context): View
    {
        self::$context = $context;

        return view('pages-test::public-content', ['context' => $context]);
    }
}

class PagesTestPublicManagementDataProvider implements PublicManagementPanelDataProvider
{
    public function data(PublicManagementPanel $panel): array
    {
        return ['panelKey' => $panel->key];
    }
}

it('boots the package', function (): void {
    expect(config('pages.tables.pages'))->toBe('pages');
});

it('loads config', function (): void {
    expect(config('pages.templates.classic.label'))->toBe('Klasični')
        ->and(config('pages.section_types.hero.label'))->toBe('Uvodni blok');
});

it('resolves a configurable public subject page sections and navigation', function (): void {
    Schema::create('public_subjects', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('team_id');
        $table->string('slug')->unique();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    config()->set('pages.public_site.subject.model', PagesTestPublicSubject::class);

    $subjectModel = PagesTestPublicSubject::query()->create([
        'team_id' => 55,
        'slug' => 'reusable-site',
        'is_active' => true,
    ]);
    $home = Page::query()->create([
        'team_id' => 55,
        'title' => ['en' => 'Home'],
        'status' => 'published',
        'is_home' => true,
        'is_published' => true,
        'published_at' => now(),
        'sort_order' => 0,
    ]);
    $about = Page::query()->create([
        'team_id' => 55,
        'title' => ['en' => 'About'],
        'slug' => 'about',
        'status' => 'published',
        'is_published' => true,
        'published_at' => now(),
        'sort_order' => 1,
    ]);
    $about->sections()->create([
        'team_id' => 55,
        'type' => 'about',
        'title' => ['en' => 'Our story'],
        'is_visible' => true,
        'sort_order' => 0,
    ]);

    $subject = app(EloquentPublicSiteSubjectResolver::class)->resolve(
        Request::create('/reusable-site/about'),
        'reusable-site',
    );

    expect($subject)->not->toBeNull()
        ->and($subject?->model->is($subjectModel))->toBeTrue()
        ->and($subject?->tenantId)->toBe(55);

    $resolved = app(PublicSitePageResolver::class)->resolve($subject, 'about');

    expect($resolved['page']->is($about))->toBeTrue()
        ->and($resolved['page']->relationLoaded('visibleSections'))->toBeTrue()
        ->and($resolved['page']->visibleSections)->toHaveCount(1)
        ->and($resolved['publicPages']->pluck('uuid')->all())->toBe([$home->uuid, $about->uuid]);

    $subjectModel->forceFill(['is_active' => false])->save();

    expect(app(EloquentPublicSiteSubjectResolver::class)->resolve(
        Request::create('/reusable-site'),
        'reusable-site',
    ))->toBeNull();

    $subjectModel->forceFill(['is_active' => true])->save();
    app()->bind(TenantResolver::class, PagesCorexisTenantResolverFake::class);
    PagesCorexisTenantResolverFake::$tenantId = 55;
    config()->set('pages.public_site.route.name', 'test.public-site');
    config()->set('pages.public_site.route.subject_parameter', 'subjectSlug');
    config()->set('pages.public_site.route.page_parameter', 'pageSlug');
    config()->set('pages.public_site.content_route.name', 'test.public-site.content');
    config()->set('pages.public_site.content_route.content_parameter', 'contentSlug');
    Route::get('/test-sites/{subjectSlug}/{pageSlug?}', fn (): string => 'ok')->name('test.public-site');
    Route::get('/test-sites/{subjectSlug}/{pageSlug}/{contentSlug}', fn (): string => 'ok')->name('test.public-site.content');
    Route::getRoutes()->refreshNameLookups();

    $url = app(PublicSiteUrl::class);

    expect(app(CurrentPublicSite::class)->subject()?->is($subjectModel))->toBeTrue()
        ->and(app(CurrentPublicSite::class)->url())->toBeString()->toEndWith('/test-sites/reusable-site')
        ->and($url->page($subjectModel, 'about'))->toEndWith('/test-sites/reusable-site/about')
        ->and($url->contentForSlug('reusable-site', 'about', 'first-item'))
        ->toEndWith('/test-sites/reusable-site/about/first-item');
});

it('dispatches public single content through the configured page provider', function (): void {
    Schema::create('public_subjects', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('team_id');
        $table->string('slug')->unique();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    PagesTestPublicSubject::query()->create([
        'team_id' => 66,
        'slug' => 'content-site',
        'is_active' => true,
    ]);
    Page::query()->create([
        'team_id' => 66,
        'page_key' => 'articles',
        'title' => ['en' => 'Articles'],
        'slug' => 'news',
        'status' => 'published',
        'is_published' => true,
        'published_at' => now(),
        'sort_order' => 0,
    ]);

    ViewFacade::addNamespace('pages-test', __DIR__.'/../fixtures');
    config()->set('pages.public_site.subject.model', PagesTestPublicSubject::class);
    config()->set('pages.public_site.content_providers', [
        'articles' => PagesTestPublicContentProvider::class,
    ]);
    config()->set('pages.public_site.route.subject_parameter', 'subjectSlug');
    config()->set('pages.public_site.route.page_parameter', 'pageSlug');
    config()->set('pages.public_site.content_route.content_parameter', 'contentSlug');
    PagesTestPublicContentProvider::$context = null;

    Route::get('/content-sites/{subjectSlug}/{pageSlug}/{contentSlug}', PublicContentController::class);

    $this->get('/content-sites/content-site/news/first-article')
        ->assertOk()
        ->assertSeeText('content-site|articles|first-article|1');

    expect(PagesTestPublicContentProvider::$context)
        ->not->toBeNull()
        ->and(PagesTestPublicContentProvider::$context?->subject->tenantId)->toBe(66)
        ->and(PagesTestPublicContentProvider::$context?->page->relationLoaded('visibleSections'))->toBeTrue();
});

it('resolves configurable public management panels and their data providers', function (): void {
    config()->set('pages.public_management.panels', [
        'website' => [
            'title' => 'Website',
            'icon' => 'globe-alt',
            'permission' => 'pages.update',
            'view' => 'test.website',
            'data_provider' => PagesTestPublicManagementDataProvider::class,
            'parameters' => ['embedded' => true],
        ],
        'pages' => [
            'title' => 'Pages',
            'icon' => 'document-text',
            'event' => 'pages-open-public-page-structure',
        ],
    ]);

    $registry = app(PublicManagementRegistry::class);
    $website = $registry->get('website');
    $pages = $registry->get('pages');

    expect($website)->toBeInstanceOf(PublicManagementPanel::class)
        ->and($website?->permission)->toBe('pages.update')
        ->and($website?->view)->toBe('test.website')
        ->and($website?->parameters)->toBe(['embedded' => true])
        ->and($registry->dataFor($website))->toBe(['panelKey' => 'website'])
        ->and($pages?->event)->toBe('pages-open-public-page-structure')
        ->and($registry->get('missing'))->toBeNull()
        ->and($registry->get('website.nested'))->toBeNull();

    config()->set('pages.public_management.panels.invalid', [
        'title' => 'Invalid',
        'icon' => 'x-mark',
        'view' => 'test.invalid',
        'event' => 'test-invalid',
    ]);

    expect(fn (): ?PublicManagementPanel => $registry->get('invalid'))
        ->toThrow(InvalidArgumentException::class, 'exactly one');
});

it('describes reusable checkbox list fields with dynamic option providers', function (): void {
    $field = Field::checkboxList('taxonomy_item_uuids')
        ->label('Taxonomy')
        ->optionsProvider(PagesTestFieldOptionsProvider::class)
        ->storage('settings.taxonomy_item_uuids');

    expect($field->type())->toBe('checkbox_list')
        ->and($field->rulesValue())->toBe(['array', 'max:100'])
        ->and($field->optionValue('options_provider'))->toBe(PagesTestFieldOptionsProvider::class)
        ->and($field->optionValue('storage'))->toBe('settings.taxonomy_item_uuids');
});

it('describes conditional fields tabs and layout variants', function (): void {
    $condition = ['field' => 'content_source', 'value' => 'taxonomy'];

    expect(Field::checkboxList('taxonomy_item_uuids')->visibleWhen('content_source', 'taxonomy')->optionValue('visible_when'))
        ->toBe($condition)
        ->and(Tab::settings('Filter')->visibleWhen('content_source', 'taxonomy')->optionValue('visible_when'))
        ->toBe($condition)
        ->and(LayoutVariant::add('filtered')->visibleWhen('content_source', 'taxonomy')->optionValue('visible_when'))
        ->toBe($condition);
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

            Tab::livewire('External content', 'example.source-manager', 'source')
                ->parameters(['mode' => 'compact']),
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
        ->and($section->tab('source')?->optionValue('component'))->toBe('example.source-manager')
        ->and($section->tab('source')?->optionValue('parameters'))->toBe(['mode' => 'compact'])
        ->and($section->toArray()['tabs'])->toHaveCount(4);
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

it('accepts only genuine youtube hosts when parsing videos', function (): void {
    expect(YouTubeVideo::fromUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ'))
        ->not->toBeNull()
        ->and(YouTubeVideo::fromUrl('https://youtu.be/dQw4w9WgXcQ'))
        ->not->toBeNull()
        ->and(YouTubeVideo::fromUrl('https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ'))
        ->toBeNull()
        ->and(YouTubeVideo::fromUrl('https://evil.test/watch?v=dQw4w9WgXcQ'))
        ->toBeNull()
        ->and(YouTubeVideo::fromUrl('https://notyoutu.be/dQw4w9WgXcQ'))
        ->toBeNull();
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

it('generates a normalized slug through corexis', function (): void {
    $page = Page::query()->create([
        'title' => ['en' => 'Sanigen Page'],
    ]);

    expect($page->slug)->toBe('sanigen-page');
});

it('resolves team id through corexis tenant resolver first', function (): void {
    app()->bind(TenantResolver::class, PagesCorexisTenantResolverFake::class);
    PagesCorexisTenantResolverFake::$tenantId = 987;

    $page = Page::query()->create([
        'title' => ['en' => 'Corexis team page'],
    ]);

    expect($page->team_id)->toBe(987);
});

it('applies the corexis tenant scope and rejects cross-tenant model instances in actions', function (): void {
    app()->bind(TenantResolver::class, PagesCorexisTenantResolverFake::class);

    PagesCorexisTenantResolverFake::$tenantId = 10;
    $page = Page::query()->create(['title' => ['en' => 'Tenant ten']]);

    PagesCorexisTenantResolverFake::$tenantId = 20;
    Page::query()->create(['title' => ['en' => 'Tenant twenty']]);

    expect(Page::query()->count())->toBe(1)
        ->and(Page::query()->first()?->localized('title'))->toBe('Tenant twenty');

    $result = app(PublishPageAction::class)->handle($page);

    expect($result->failed())->toBeTrue()
        ->and($page->refresh()->isPublished())->toBeFalse();
});

it('does not accept team id from create action input', function (): void {
    app()->bind(TenantResolver::class, PagesCorexisTenantResolverFake::class);
    PagesCorexisTenantResolverFake::$tenantId = 987;

    $result = app(CreatePageAction::class)->handle([
        'team_id' => 123,
        'title' => ['en' => 'Scoped action page'],
    ]);

    expect($result->success)->toBeTrue()
        ->and($result->data->team_id)->toBe(987);
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

it('creates a tenant scoped subpage and exposes parent relationships', function (): void {
    app()->bind(TenantResolver::class, PagesCorexisTenantResolverFake::class);
    PagesCorexisTenantResolverFake::$tenantId = 987;

    $parent = Page::query()->create([
        'title' => ['en' => 'Products'],
        'is_home' => false,
    ]);

    $result = app(CreatePageAction::class)->handle([
        'title' => ['en' => 'Wood products'],
        'parent_uuid' => $parent->uuid,
    ]);

    expect($result->success)->toBeTrue()
        ->and($result->data->parent?->is($parent))->toBeTrue()
        ->and($parent->children()->first()?->is($result->data))->toBeTrue();
});

it('rejects cross tenant and second level page parents', function (): void {
    app()->bind(TenantResolver::class, PagesCorexisTenantResolverFake::class);

    PagesCorexisTenantResolverFake::$tenantId = 111;
    $foreignParent = Page::query()->create(['title' => ['en' => 'Foreign']]);

    PagesCorexisTenantResolverFake::$tenantId = 987;
    $parent = Page::query()->create(['title' => ['en' => 'Products']]);
    $child = Page::query()->create([
        'title' => ['en' => 'Wood products'],
        'parent_id' => $parent->getKey(),
    ]);

    $crossTenant = app(CreatePageAction::class)->handle([
        'title' => ['en' => 'Invalid child'],
        'parent_uuid' => $foreignParent->uuid,
    ]);
    $secondLevel = app(CreatePageAction::class)->handle([
        'title' => ['en' => 'Too deep'],
        'parent_uuid' => $child->uuid,
    ]);

    expect($crossTenant->failed())->toBeTrue()
        ->and($secondLevel->failed())->toBeTrue();
});

it('moves and reorders pages between navigation levels', function (): void {
    app()->bind(TenantResolver::class, PagesCorexisTenantResolverFake::class);
    PagesCorexisTenantResolverFake::$tenantId = 987;

    $firstParent = Page::query()->create(['title' => ['en' => 'Products'], 'sort_order' => 0]);
    $secondParent = Page::query()->create(['title' => ['en' => 'Services'], 'sort_order' => 1]);
    $firstChild = Page::query()->create([
        'title' => ['en' => 'Wood products'],
        'parent_id' => $firstParent->getKey(),
        'sort_order' => 0,
    ]);
    $secondChild = Page::query()->create([
        'title' => ['en' => 'Metal products'],
        'parent_id' => $firstParent->getKey(),
        'sort_order' => 1,
    ]);
    $serviceChild = Page::query()->create([
        'title' => ['en' => 'Consulting'],
        'parent_id' => $secondParent->getKey(),
        'sort_order' => 0,
    ]);

    $moved = app(MovePageAction::class)->handle($secondChild, $secondParent->uuid, 0);

    expect($moved->success)->toBeTrue()
        ->and($secondChild->refresh()->parent_id)->toBe($secondParent->getKey())
        ->and($firstParent->children()->pluck('uuid')->all())->toBe([$firstChild->uuid])
        ->and($secondParent->children()->pluck('uuid')->all())->toBe([$secondChild->uuid, $serviceChild->uuid]);

    $reordered = app(MovePageAction::class)->handle($serviceChild, $secondParent->uuid, 0);

    expect($reordered->success)->toBeTrue()
        ->and($secondParent->children()->pluck('uuid')->all())->toBe([$serviceChild->uuid, $secondChild->uuid]);

    $promoted = app(MovePageAction::class)->handle($secondChild, null, 1);

    expect($promoted->success)->toBeTrue()
        ->and($secondChild->refresh()->parent_id)->toBeNull()
        ->and(Page::query()->whereNull('parent_id')->ordered()->pluck('uuid')->all())
        ->toBe([$firstParent->uuid, $secondChild->uuid, $secondParent->uuid]);
});

it('rejects invalid page hierarchy moves', function (): void {
    app()->bind(TenantResolver::class, PagesCorexisTenantResolverFake::class);

    PagesCorexisTenantResolverFake::$tenantId = 987;
    $home = Page::query()->create(['title' => ['en' => 'Home'], 'is_home' => true]);
    $parent = Page::query()->create(['title' => ['en' => 'Products']]);
    $otherParent = Page::query()->create(['title' => ['en' => 'Services']]);
    Page::query()->create(['title' => ['en' => 'Child'], 'parent_id' => $parent->getKey()]);

    PagesCorexisTenantResolverFake::$tenantId = 111;
    $foreignParent = Page::query()->create(['title' => ['en' => 'Foreign']]);
    PagesCorexisTenantResolverFake::$tenantId = 987;

    expect(app(MovePageAction::class)->handle($home, $otherParent->uuid, 0)->failed())->toBeTrue()
        ->and(app(MovePageAction::class)->handle($parent, $otherParent->uuid, 0)->failed())->toBeTrue()
        ->and(app(MovePageAction::class)->handle($otherParent, $foreignParent->uuid, 0)->failed())->toBeTrue();

    PagesCorexisTenantResolverFake::$tenantId = 111;

    expect(app(MovePageAction::class)->handle($otherParent, null, 0)->failed())->toBeTrue();
});

it('normalizes both page groups when an existing form changes the parent', function (): void {
    app()->bind(TenantResolver::class, PagesCorexisTenantResolverFake::class);
    PagesCorexisTenantResolverFake::$tenantId = 987;

    $firstParent = Page::query()->create(['title' => ['en' => 'Products'], 'sort_order' => 0]);
    $secondParent = Page::query()->create(['title' => ['en' => 'Services'], 'sort_order' => 1]);
    $remainingChild = Page::query()->create([
        'title' => ['en' => 'Wood'],
        'parent_id' => $firstParent->getKey(),
        'sort_order' => 0,
    ]);
    $movingChild = Page::query()->create([
        'title' => ['en' => 'Metal'],
        'parent_id' => $firstParent->getKey(),
        'sort_order' => 7,
    ]);

    $result = app(UpdatePageAction::class)->handle($movingChild, [
        'title' => $movingChild->title,
        'status' => $movingChild->status,
        'parent_uuid' => $secondParent->uuid,
    ]);

    expect($result->success)->toBeTrue()
        ->and($remainingChild->refresh()->sort_order)->toBe(0)
        ->and($movingChild->refresh()->parent_id)->toBe($secondParent->getKey())
        ->and($movingChild->sort_order)->toBe(0);
});

it('promotes child pages when their parent is archived', function (): void {
    $parent = Page::query()->create(['title' => ['en' => 'Products']]);
    $child = Page::query()->create([
        'title' => ['en' => 'Wood products'],
        'parent_id' => $parent->getKey(),
    ]);

    $result = app(DeletePageAction::class)->handle($parent);

    expect($result->success)->toBeTrue()
        ->and($parent->refresh()->trashed())->toBeTrue()
        ->and($child->refresh()->parent_id)->toBeNull();
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

    expect($item->slug)->toBe('a-1');
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

    expect($result->success)->toBeFalse();
});

it('uses the active template as the public section creator allowlist', function (): void {
    config()->set('pages.section_types', [
        'services' => ['label' => 'Usluge iz host aplikacije'],
        'legacy' => ['label' => 'Stara sekcija'],
    ]);
    config()->set('template_engine.default_template', 'business');
    config()->set('template_engine.templates', [
        'business' => [
            'sections' => [
                'hero' => ['label' => 'Hero'],
                'services' => [
                    'label' => 'Services',
                    'metadata' => ['icon' => 'briefcase'],
                ],
                'private_notes' => [
                    'label' => 'Private notes',
                    'metadata' => ['creatable' => false],
                ],
            ],
        ],
    ]);

    app()->forgetInstance(TemplateRegistryContract::class);
    app()->forgetInstance(TemplateEngineContract::class);
    app()->forgetInstance('template-engine');
    app()->forgetInstance(AvailableSectionTypes::class);

    $page = Page::query()->create([
        'title' => ['en' => 'Business page'],
        'template' => 'business',
    ]);
    $available = app(AvailableSectionTypes::class)->forPage($page);

    expect(array_keys($available))->toBe(['services'])
        ->and($available['services']['label'])->toBe('Usluge iz host aplikacije')
        ->and($available['services']['icon'])->toBe('briefcase')
        ->and(app(CreateSectionAction::class)->handle($page, ['type' => 'services'])->success)->toBeTrue()
        ->and(app(CreateSectionAction::class)->handle($page, ['type' => 'legacy'])->success)->toBeFalse()
        ->and(app(CreateSectionAction::class)->handle($page, ['type' => 'private_notes'])->success)->toBeFalse();
});

it('rejects unsafe section and item links', function (): void {
    $page = Page::query()->create([
        'title' => ['en' => 'Safe links'],
    ]);

    $unsafeSection = app(CreateSectionAction::class)->handle($page, [
        'type' => 'hero',
        'button_url' => 'javascript:alert(1)',
    ]);
    $section = app(CreateSectionAction::class)->handle($page, [
        'type' => 'hero',
        'button_url' => '/kontakt',
    ]);
    $unsafeItem = app(CreateSectionItemAction::class)->handle($section->data, [
        'title' => ['en' => 'Unsafe item'],
        'url' => 'data:text/html,<script>alert(1)</script>',
    ]);

    expect($unsafeSection->success)->toBeFalse()
        ->and($unsafeSection->errors)->toHaveKey('button_url')
        ->and($section->success)->toBeTrue()
        ->and($unsafeItem->success)->toBeFalse()
        ->and($unsafeItem->errors)->toHaveKey('url');
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

    expect(Page::query()->forTenant(1)->count())->toBe(1);
});

it('action classes return action result', function (): void {
    $createPage = app(CreatePageAction::class)->handle([
        'title' => ['en' => 'Action page'],
        'status' => 'draft',
        'template' => 'classic',
    ]);

    expect($createPage)->toBeInstanceOf(ActionResult::class)
        ->and($createPage->success)->toBeTrue();

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

it('uses the corexis action result directly', function (): void {
    $result = ActionResult::success(__('Saved.'), ['id' => 10], 'saved');

    expect($result)->toBeInstanceOf(ActionResult::class)
        ->and($result->success)->toBeTrue()
        ->and($result->message)->toBe('Saved.')
        ->and($result->data)->toBe(['id' => 10])
        ->and($result->code)->toBe('saved');
});

it('dispatches domain events for successful page actions only', function (): void {
    Event::fake([PageCreated::class, PagePublished::class]);

    $failed = app(CreatePageAction::class)->handle([
        'title' => null,
    ]);

    expect($failed->success)->toBeFalse();
    Event::assertNotDispatched(PageCreated::class);

    $created = app(CreatePageAction::class)->handle([
        'title' => ['en' => 'Event page'],
        'status' => 'draft',
        'template' => 'classic',
    ]);

    expect($created->success)->toBeTrue();
    Event::assertDispatched(PageCreated::class);

    $published = app(PublishPageAction::class)->handle($created->data->uuid);

    expect($published->success)->toBeTrue();
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

    expect($result->success)->toBeTrue()
        ->and($page->refresh()->lock_version)->toBe(1);
    Event::assertDispatched(PageUpdated::class);

    Event::fake([PageUpdated::class]);

    $stale = app(UpdatePageAction::class)->handle($page->refresh(), [
        'title' => ['en' => 'Stale'],
        'status' => 'draft',
        'lock_version' => 0,
    ]);

    expect($stale->success)->toBeFalse()
        ->and($stale->code)->toBe('conflict.stale_model')
        ->and($page->refresh()->title)->toBe(['en' => 'Updated']);
    Event::assertNotDispatched(PageUpdated::class);
});

<?php

use Illuminate\Support\Facades\Schema;
use IvanBaric\Pages\Actions\CreatePageAction;
use IvanBaric\Pages\Actions\CreateSectionAction;
use IvanBaric\Pages\Actions\CreateSectionItemAction;
use IvanBaric\Pages\Actions\PublishPageAction;
use IvanBaric\Pages\Actions\ReorderSectionItemsAction;
use IvanBaric\Pages\Actions\ReorderSectionsAction;
use IvanBaric\Pages\Actions\UnpublishPageAction;
use IvanBaric\Pages\Data\ActionResult;
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

it('boots the package', function (): void {
    expect(config('pages.tables.pages'))->toBe('pages');
});

it('loads config', function (): void {
    expect(config('pages.templates.classic.label'))->toBe('Classic')
        ->and(config('pages.section_types.hero.label'))->toBe('Hero');
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

<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Livewire\Admin;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\CopySectionAction;
use IvanBaric\Pages\Actions\CreateSectionAction;
use IvanBaric\Pages\Actions\DeleteSectionAction;
use IvanBaric\Pages\Actions\MoveSectionAction;
use IvanBaric\Pages\Actions\ReorderSectionsAction;
use IvanBaric\Pages\Actions\ToggleSectionVisibilityAction;
use IvanBaric\Pages\Actions\UpdateSectionAction;
use IvanBaric\Pages\Admin\AdminSection;
use IvanBaric\Pages\Admin\AdminSectionRegistry;
use IvanBaric\Pages\Admin\LayoutVariant;
use IvanBaric\Pages\Admin\Tab;
use IvanBaric\Pages\Livewire\Forms\SectionSettingsForm;
use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Models\Section;
use IvanBaric\Pages\Support\AvailableSectionTypes;
use IvanBaric\Pages\Support\OnePageNavigation;
use IvanBaric\Pages\Support\PagesModels;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class PageShow extends Component
{
    #[Locked]
    public Page $page;

    #[Locked]
    public bool $embedded = false;

    #[Locked]
    public bool $publicActions = false;

    #[Locked]
    public ?string $publicActionDialog = null;

    #[Locked]
    public ?string $deletingUuid = null;

    #[Locked]
    public ?string $editingUuid = null;

    #[Locked]
    public ?string $copyingUuid = null;

    #[Locked]
    public ?string $duplicatingUuid = null;

    #[Locked]
    public ?string $movingUuid = null;

    public ?string $copyTargetPageUuid = null;

    public ?string $moveTargetPageUuid = null;

    public string $selectedSectionType = '';

    public string $selectedSectionCreatorKey = '';

    public SectionSettingsForm $form;

    public function mount(Page $page, bool $embedded = false, bool $publicActions = false): void
    {
        $pageModel = PagesModels::page();
        $page = $pageModel::query()
            ->where('uuid', $page->getAttribute('uuid'))
            ->first();

        abort_unless($page instanceof Page, 404);

        $this->page = $page;
        $this->embedded = $embedded;
        $this->publicActions = $publicActions;

        if ($this->isPublicActionHandler()) {
            $tenantId = corexis_tenant_id();

            abort_unless(corexis_actor_id() !== null && is_numeric($tenantId), 403);
            abort_unless((string) $this->page->getAttribute('team_id') === (string) $tenantId, 404);
            corexis_authorize('pages.update', $this->page);

            return;
        }

        $this->selectedSectionType = $this->firstAvailableSectionType();
        $this->selectedSectionCreatorKey = $this->firstAvailableSectionCreatorKey();
    }

    public function openSectionCreator(): void
    {
        if ($this->isPublicActionHandler()) {
            $this->publicActionDialog = 'create';
        }

        if (! $this->isAvailableSectionCreatorKey($this->selectedSectionCreatorKey)) {
            $this->selectedSectionCreatorKey = $this->firstAvailableSectionCreatorKey();
        }

        Flux::modal('section-create')->show();
    }

    #[On('pages-open-public-section-creator')]
    public function openPublicSectionCreator(): void
    {
        abort_unless($this->embedded && $this->publicActions, 404);

        $this->openSectionCreator();
    }

    #[On('pages-public-section-action')]
    public function handlePublicSectionAction(string $action, string $sectionUuid): void
    {
        abort_unless($this->embedded && $this->publicActions, 404);
        $section = $this->findSection($sectionUuid);

        if ($action === 'up' || $action === 'down') {
            $sections = $this->page->sections()->ordered()->get()->values();
            $index = $sections->search(fn (Section $candidate): bool => $candidate->is($section));

            if ($index === false) {
                return;
            }

            $target = $action === 'up' ? max(0, $index - 1) : min($sections->count() - 1, $index + 1);

            if ($target !== $index) {
                $this->reorderSection($sectionUuid, $target, app(ReorderSectionsAction::class));
            }

            return;
        }

        match ($action) {
            'copy' => $this->confirmCopy($sectionUuid),
            'move' => $this->confirmMove($sectionUuid),
            'archive' => $this->confirmDelete($sectionUuid),
            default => abort(404),
        };
    }

    public function cancelSectionCreator(): void
    {
        $this->closePublicActionDialog('create');

        if ($this->isPublicActionHandler()) {
            $this->selectedSectionType = '';
            $this->selectedSectionCreatorKey = '';
            unset($this->sectionCreatorSections, $this->sectionCreatorEntries, $this->selectedSectionDetails);

            return;
        }

        $this->selectedSectionType = $this->firstAvailableSectionType();
        $this->selectedSectionCreatorKey = $this->firstAvailableSectionCreatorKey();
        unset($this->selectedSectionDetails);
    }

    public function selectSectionCreatorEntry(string $key): void
    {
        if ($this->isAvailableSectionCreatorKey($key)) {
            $this->selectedSectionCreatorKey = $key;

            if (str_starts_with($key, 'section:')) {
                $this->selectedSectionType = substr($key, 8);
            }

            unset($this->selectedSectionDetails);
        }
    }

    public function selectSectionType(string $type): void
    {
        $this->selectSectionCreatorEntry('section:'.$type);
    }

    public function addSelectedSection(): void
    {
        $details = $this->selectedSectionDetails();

        if (($details['kind'] ?? null) !== 'section') {
            Flux::toast(variant: 'danger', text: __('Odaberite sekciju koju želite dodati.'));

            return;
        }

        $this->addSection((string) $details['key']);
    }

    public function addSection(string $type): void
    {
        $action = app(CreateSectionAction::class);

        if (! $this->isAvailableSectionType($type)) {
            Flux::toast(variant: 'danger', text: __('Odaberite sekciju koju želite dodati.'));

            return;
        }

        $result = $action->handle($this->page, [
            'type' => $type,
            'title' => [$this->currentLocaleCode() => $this->initialSectionTitle($type)],
            'description' => $this->initialSectionDescription($type),
            'is_visible' => true,
            'settings' => $this->initialSectionSettings($type),
        ]);

        if (! $this->resultSuccessful($result)) {
            Flux::toast(variant: 'danger', text: __('Sekciju nije moguće dodati.'));

            return;
        }

        if ($result->data instanceof Section) {
            $this->seedDemoItems($result->data, $type);
        }

        unset($this->sections, $this->sectionCreatorSections, $this->sectionCreatorEntries, $this->selectedSectionDetails);

        $this->closePublicActionDialog('create');
        Flux::modal('section-create')->close();
        Flux::toast(variant: 'success', text: __('Sekcija je dodana.'));
        $this->structureChanged();
    }

    public function toggle(string $uuid, ToggleSectionVisibilityAction $action): void
    {
        $section = $this->findSection($uuid);
        $result = $action->handle($section);

        unset($this->sections);
        $this->toastFromResult($result);
        $this->structureChanged();
    }

    public function reorderSection(string $uuid, int $position, ReorderSectionsAction $action): void
    {
        $sections = $this->page->sections()->ordered()->get();
        $moving = $sections->firstWhere('uuid', $uuid);

        if (! $moving instanceof Section) {
            return;
        }

        $sections = $sections->reject(fn (Section $current): bool => $current->is($moving))->values();
        $sections->splice(max(0, min($position, $sections->count())), 0, [$moving]);
        $result = $action->handle($this->page, $sections->pluck('uuid')->all());

        unset($this->sections);
        $this->toastFromResult($result);
        $this->structureChanged();
    }

    public function edit(string $uuid): void
    {
        $section = $this->findSection($uuid);

        $this->reset('editingUuid');
        $this->form->reset();
        $this->editingUuid = (string) $section->uuid;
        $this->form->fillFromSection($section);

        Flux::modal('section-form')->show();
    }

    public function cancelSectionForm(): void
    {
        $this->reset('editingUuid');
        $this->form->reset();
    }

    public function saveSection(UpdateSectionAction $action): void
    {
        try {
            $data = $this->form->data();
        } catch (ValidationException $exception) {
            $this->toastFromResult(ActionResult::error(__('Provjerite obavezna polja i pokušajte ponovno.')));

            throw $exception;
        }

        $section = $this->findSection((string) $this->editingUuid);
        $result = $action->handle($section, $data + [
            'type' => (string) $section->getAttribute('type'),
            'lock_version' => (int) $section->getAttribute('lock_version'),
        ]);

        if ($result->failed()) {
            $this->toastFromResult($result);

            return;
        }

        $this->editingUuid = null;
        unset($this->sections);

        Flux::modal('section-form')->close();
        Flux::toast(variant: 'success', text: __('Sekcija je uspješno spremljena.'));
        $this->structureChanged();
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = (string) $this->findSection($uuid)->uuid;
        $this->publicActionDialog = $this->isPublicActionHandler() ? 'archive' : null;
        Flux::modal('section-delete')->show();
    }

    public function cancelDelete(): void
    {
        $this->deletingUuid = null;
        $this->closePublicActionDialog('archive');
    }

    public function confirmCopy(string $uuid): void
    {
        $this->copyingUuid = (string) $this->findSection($uuid)->uuid;
        $this->copyTargetPageUuid = (string) $this->page->getAttribute('uuid');
        $this->publicActionDialog = $this->isPublicActionHandler() ? 'copy' : null;
        Flux::modal('section-copy')->show();
    }

    public function cancelCopy(): void
    {
        $this->copyingUuid = null;
        $this->copyTargetPageUuid = null;
        $this->closePublicActionDialog('copy');
    }

    public function confirmDuplicate(string $uuid): void
    {
        $this->duplicatingUuid = (string) $this->findSection($uuid)->uuid;
        Flux::modal('section-duplicate')->show();
    }

    public function cancelDuplicate(): void
    {
        $this->duplicatingUuid = null;
    }

    public function duplicateSection(CopySectionAction $action): void
    {
        $source = $this->findSection((string) $this->duplicatingUuid);
        $result = $action->handle($source, $this->page);

        if (! $this->resultSuccessful($result)) {
            $this->toastFromResult($result);

            return;
        }

        $this->duplicatingUuid = null;
        unset($this->sections);

        Flux::modal('section-duplicate')->close();
        $this->toastFromResult($result);
        $this->structureChanged();
    }

    public function confirmMove(string $uuid): void
    {
        $this->movingUuid = (string) $this->findSection($uuid)->uuid;
        $this->moveTargetPageUuid = $this->defaultMoveTargetPageUuid();
        $this->publicActionDialog = $this->isPublicActionHandler() ? 'move' : null;
        Flux::modal('section-move')->show();
    }

    public function cancelMove(): void
    {
        $this->movingUuid = null;
        $this->moveTargetPageUuid = null;
        $this->closePublicActionDialog('move');
    }

    public function copySection(CopySectionAction $action): void
    {
        $targetPage = $this->copyTargetPages()
            ->firstWhere('uuid', $this->copyTargetPageUuid);

        if (! $targetPage instanceof Page) {
            $this->toastFromResult(ActionResult::error(__('Odaberite stranicu na koju želite kopirati sekciju.')));

            return;
        }

        $source = $this->findSection((string) $this->copyingUuid);

        $result = $action->handle($source, $targetPage);

        if (! $this->resultSuccessful($result)) {
            $this->toastFromResult($result);

            return;
        }

        $this->copyingUuid = null;
        $this->copyTargetPageUuid = null;
        $this->closePublicActionDialog('copy');

        unset($this->sections);

        Flux::modal('section-copy')->close();
        $this->toastFromResult($result);
        if ($this->embedded) {
            $this->structureChanged();

            return;
        }
        $this->redirectRoute($this->pageShowRouteName(), ['page' => $targetPage->uuid], navigate: true);
    }

    public function moveSection(MoveSectionAction $action): void
    {
        if (! $this->movingUuid) {
            return;
        }

        $targetPage = $this->moveTargetPages()
            ->firstWhere('uuid', $this->moveTargetPageUuid);

        if (! $targetPage instanceof Page) {
            $this->toastFromResult(ActionResult::error(__('Odaberite stranicu na koju želite premjestiti sekciju.')));

            return;
        }

        $source = $this->findSection($this->movingUuid);
        $result = $action->handle($source, $targetPage);

        if (! $this->resultSuccessful($result)) {
            $this->toastFromResult($result);

            return;
        }

        $movedSectionUuid = (string) $source->getAttribute('uuid');
        $this->movingUuid = null;
        $this->moveTargetPageUuid = null;
        $this->closePublicActionDialog('move');

        unset($this->sections, $this->copyTargetPages, $this->moveTargetPages);

        Flux::modal('section-move')->close();
        $this->toastFromResult($result);
        if ($this->embedded) {
            $this->dispatch('pages-public-section-removed.'.$movedSectionUuid);
            $this->structureChanged(reload: false);

            return;
        }
        $this->redirectRoute($this->pageShowRouteName(), ['page' => $targetPage->uuid], navigate: true);
    }

    public function delete(DeleteSectionAction $action): void
    {
        $section = $this->findSection((string) $this->deletingUuid);
        $sectionUuid = (string) $section->getAttribute('uuid');
        $result = $action->handle($section);

        if ($result->failed()) {
            $this->toastFromResult($result);

            return;
        }

        $this->deletingUuid = null;
        $this->closePublicActionDialog('archive');

        unset($this->sections);

        Flux::modal('section-delete')->close();
        $this->toastFromResult($result);

        if ($this->embedded) {
            $this->dispatch('pages-public-section-removed.'.$sectionUuid);
        }

        $this->structureChanged(reload: false);
    }

    private function structureChanged(bool $reload = true): void
    {
        if ($this->embedded) {
            $this->dispatch('pages-public-structure-updated', reload: $reload);
        }
    }

    /** @return Collection<int, Section> */
    #[Computed]
    public function sections(): Collection
    {
        return $this->page->sections()->withCount('items')->orderBy('sort_order')->orderBy('created_at')->get();
    }

    /** @return Collection<int, Page> */
    #[Computed]
    public function copyTargetPages(): Collection
    {
        $pageModel = PagesModels::page();

        return $pageModel::query()
            ->ordered()
            ->get();
    }

    /** @return Collection<int, Page> */
    #[Computed]
    public function moveTargetPages(): Collection
    {
        return $this->copyTargetPages()
            ->reject(fn (Page $page): bool => (int) $page->getKey() === (int) $this->page->getKey())
            ->values();
    }

    #[Computed]
    public function onePageNavigationAvailable(): bool
    {
        $pageModel = PagesModels::page();
        $publicPages = $pageModel::query()
            ->published()
            ->ordered()
            ->get();

        if (! app(OnePageNavigation::class)->isAvailable($publicPages)) {
            return false;
        }

        return (string) data_get($publicPages->first(), 'uuid') === (string) $this->page->getAttribute('uuid');
    }

    #[Computed]
    public function sectionNavigationSettingsAvailable(): bool
    {
        if (! $this->onePageNavigationAvailable() || ! $this->editingUuid) {
            return false;
        }

        $section = $this->sections()->firstWhere('uuid', $this->editingUuid);

        return $section instanceof Section
            && app(OnePageNavigation::class)->canShowSection($section);
    }

    /** @return array<int, array{key: string, label: string, summary: string, description: string, icon: string, existing_count: int}> */
    #[Computed]
    public function sectionCreatorSections(): array
    {
        $registry = app(AdminSectionRegistry::class);
        $existingCounts = $this->sections()->countBy('type');

        return collect($this->sectionTypeOptions())
            ->reject(fn (array $config, string $type): bool => $this->isHiddenInSectionCreator($type))
            ->map(fn (array $config, string $type): array => [
                'key' => $type,
                'label' => $this->sectionTypeLabel($type),
                'summary' => $this->sectionTypeSummary($registry->get($type)),
                'description' => $this->sectionTypeDescription($registry->get($type)),
                'icon' => $this->sectionTypeIcon($type),
                'existing_count' => (int) ($existingCounts[$type] ?? 0),
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function sectionCreatorEntries(): array
    {
        $groups = $this->sectionCreatorGroups();
        $groupedTypes = collect($groups)
            ->flatMap(static fn (array $group): array => (array) $group['types'])
            ->values()
            ->all();
        $addedGroups = [];
        $entries = [];

        foreach (array_keys($this->sectionTypeOptions()) as $type) {
            if ($this->isHiddenInSectionCreator($type)) {
                continue;
            }

            $groupKey = $this->groupKeyForSectionType($type, $groups);

            if ($groupKey !== null) {
                if (! in_array($groupKey, $addedGroups, true)) {
                    $group = $groups[$groupKey];
                    $entries[] = [
                        'kind' => 'group',
                        'key' => 'group:'.$groupKey,
                        'label' => $group['label'],
                        'description' => $group['description'],
                        'icon' => $group['icon'],
                        'types' => $group['types'],
                    ];
                    $addedGroups[] = $groupKey;
                }

                continue;
            }

            if (in_array($type, $groupedTypes, true)) {
                continue;
            }

            $entries[] = [
                'kind' => 'section',
                'key' => 'section:'.$type,
                'section_type' => $type,
                'label' => $this->sectionTypeLabel($type),
                'description' => '',
                'icon' => $this->sectionTypeIcon($type),
            ];
        }

        return $entries;
    }

    /** @return array<string, mixed>|null */
    #[Computed]
    public function selectedSectionDetails(): ?array
    {
        $entries = collect($this->sectionCreatorEntries());
        $entry = $entries->firstWhere('key', $this->selectedSectionCreatorKey) ?? $entries->first();

        if (! is_array($entry)) {
            return null;
        }

        if (($entry['kind'] ?? null) === 'group') {
            return [
                'kind' => 'group',
                'key' => (string) $entry['key'],
                'label' => (string) $entry['label'],
                'panel_description' => (string) $entry['description'],
                'icon' => (string) $entry['icon'],
                'sections' => collect((array) $entry['types'])
                    ->filter(fn (string $type): bool => $this->isAvailableSectionType($type)
                        && ! $this->isHiddenInSectionCreator($type))
                    ->map(fn (string $type): array => $this->sectionCreatorDetailsForType($type))
                    ->filter()
                    ->values()
                    ->all(),
            ];
        }

        $type = (string) $entry['section_type'];

        return array_merge(['kind' => 'section'], $this->sectionCreatorDetailsForType($type));
    }

    /** @return array<string, mixed> */
    private function sectionCreatorDetailsForType(string $type): array
    {
        $definition = app(AdminSectionRegistry::class)->get($type);
        $existingCounts = $this->sections()->countBy('type');
        $variants = $this->layoutVariantsForSection($definition);
        $previewVariant = $variants[0] ?? [
            'value' => $type,
            'label' => $this->sectionTypeLabel($type),
            'description' => '',
            'options' => ['preview' => $this->previewForSectionType($type)],
        ];

        return [
            'key' => $type,
            'label' => $this->sectionTypeLabel($type),
            'summary' => $this->sectionTypeSummary($definition),
            'panel_description' => $this->sectionPanelDescription($definition, $this->sectionTypeSummary($definition)),
            'description' => $this->sectionTypeDescription($definition),
            'icon' => $this->sectionTypeIcon($type),
            'existing_count' => (int) ($existingCounts[$type] ?? 0),
            'tabs' => $this->tabsForSection($definition),
            'fields' => $this->fieldLabelsForSection($definition),
            'examples' => $this->examplesForSection($definition),
            'variants' => $variants,
            'preview_variant' => $previewVariant,
        ];
    }

    /** @return array<string, array<string, mixed>> */
    #[Computed]
    public function sectionTypes(): array
    {
        return config('pages.section_types', []);
    }

    public function pageIndexRouteName(): string
    {
        return (string) config('pages.admin_routes.page_index', 'admin.pages.index');
    }

    public function pageShowRouteName(): string
    {
        return (string) config('pages.admin_routes.page_show', 'admin.pages.show');
    }

    public function sectionShowRouteName(): string
    {
        return (string) config('pages.admin_routes.section_show', 'admin.sections.show');
    }

    public function render(): View
    {
        if ($this->isPublicActionHandler()) {
            return view('pages::livewire.public-site.page-actions-handler');
        }

        return view('pages::livewire.admin.page-show')
            ->layout('layouts.app', ['title' => $this->page->localized('title')]);
    }

    private function isPublicActionHandler(): bool
    {
        return $this->embedded && $this->publicActions;
    }

    private function closePublicActionDialog(string $dialog): void
    {
        if ($this->publicActionDialog === $dialog) {
            $this->publicActionDialog = null;
        }
    }

    private function findSection(string $uuid): Section
    {
        $section = $this->page->sections()->where('uuid', $uuid)->firstOrFail();

        return $section;
    }

    private function defaultMoveTargetPageUuid(): ?string
    {
        return $this->moveTargetPages()->first()?->uuid;
    }

    private function currentLocaleCode(): string
    {
        return (string) (corexis_locale_code() ?: config('pages.translatable.default_locale') ?: config('app.locale', 'hr'));
    }

    /** @return array<string, array<string, mixed>> */
    private function sectionTypeOptions(): array
    {
        return app(AvailableSectionTypes::class)->forPage($this->page);
    }

    private function firstAvailableSectionType(): string
    {
        return array_key_first($this->sectionTypeOptions()) ?: '';
    }

    private function firstAvailableSectionCreatorKey(): string
    {
        $entries = $this->sectionCreatorEntries();
        $entry = $entries[0] ?? null;

        return is_array($entry) ? (string) $entry['key'] : '';
    }

    private function isAvailableSectionType(string $type): bool
    {
        return array_key_exists($type, $this->sectionTypeOptions());
    }

    private function isHiddenInSectionCreator(string $type): bool
    {
        return (bool) app(AdminSectionRegistry::class)->get($type)?->optionValue('creator_hidden', false);
    }

    private function isAvailableSectionCreatorKey(string $key): bool
    {
        return collect($this->sectionCreatorEntries())->contains(
            static fn (array $entry): bool => (string) $entry['key'] === $key,
        );
    }

    /** @return array<string, array{label: string, description: string, icon: string, types: array<int, string>}> */
    private function sectionCreatorGroups(): array
    {
        $availableTypes = array_keys($this->sectionTypeOptions());

        return collect((array) config('pages.section_creator.groups', []))
            ->filter(static fn (mixed $group, mixed $key): bool => is_string($key)
                && is_array($group)
                && is_array($group['types'] ?? null))
            ->map(static fn (array $group): array => [
                'label' => __((string) ($group['label'] ?? '')),
                'description' => __((string) ($group['description'] ?? '')),
                'icon' => (string) ($group['icon'] ?? 'rectangle-stack'),
                'types' => array_values(array_filter(
                    $group['types'],
                    static fn (mixed $type): bool => is_string($type)
                        && in_array($type, $availableTypes, true),
                )),
            ])
            ->filter(static fn (array $group): bool => $group['types'] !== [])
            ->all();
    }

    /** @param array<string, array{types: array<int, string>}> $groups */
    private function groupKeyForSectionType(string $type, array $groups): ?string
    {
        foreach ($groups as $groupKey => $group) {
            if (in_array($type, $group['types'], true)) {
                return (string) $groupKey;
            }
        }

        return null;
    }

    private function sectionTypeLabel(string $type): string
    {
        $registryLabel = app(AdminSectionRegistry::class)->get($type)?->labelValue();

        if (filled($registryLabel)) {
            return (string) $registryLabel;
        }

        return __((string) data_get($this->sectionTypeOptions(), $type.'.label', str($type)->headline()));
    }

    private function initialSectionTitle(string $type): string
    {
        $demoTitle = app(AdminSectionRegistry::class)->get($type)?->optionValue('demo_title');

        return filled($demoTitle) ? (string) $demoTitle : $this->sectionTypeLabel($type);
    }

    /** @return array<string, string>|null */
    private function initialSectionDescription(string $type): ?array
    {
        $demoDescription = app(AdminSectionRegistry::class)->get($type)?->optionValue('demo_description');

        return filled($demoDescription) ? [$this->currentLocaleCode() => (string) $demoDescription] : null;
    }

    private function sectionTypeDescription(?AdminSection $definition): string
    {
        $creatorDescription = $definition?->optionValue('creator_description');

        if (filled($creatorDescription)) {
            return (string) $creatorDescription;
        }

        foreach ($definition?->tabsValue() ?? [] as $tab) {
            $description = (string) $tab->optionValue('description', '');

            if (filled($description)) {
                return $description;
            }
        }

        return __('Dodajte ovu sekciju na stranicu i zatim uredite njezin sadržaj.');
    }

    private function sectionTypeSummary(?AdminSection $definition): string
    {
        $creatorSummary = $definition?->optionValue('creator_summary');

        if (filled($creatorSummary)) {
            return (string) $creatorSummary;
        }

        return $this->sectionTypeDescription($definition);
    }

    private function sectionPanelDescription(?AdminSection $definition, string $fallback): string
    {
        $creatorPanelDescription = $definition?->optionValue('creator_panel_description');

        if (filled($creatorPanelDescription)) {
            return (string) $creatorPanelDescription;
        }

        return $fallback;
    }

    /** @return array<int, string> */
    private function examplesForSection(?AdminSection $definition): array
    {
        $examples = $definition?->optionValue('creator_examples', []);

        if (! is_array($examples)) {
            return [];
        }

        return collect($examples)
            ->map(static fn (mixed $example): string => trim((string) $example))
            ->filter()
            ->take(4)
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function initialSectionSettings(string $type): array
    {
        $definition = app(AdminSectionRegistry::class)->get($type);
        $settings = [
            'show_title' => true,
            'show_description' => true,
        ];

        $defaultLayout = $definition
            ?->layoutTab()
            ?->optionValue('default');

        if (is_string($defaultLayout) && $defaultLayout !== '') {
            $settings['layout_variant'] = $defaultLayout;
        }

        $demoSettings = $definition?->optionValue('demo_settings', []);

        return array_replace($settings, is_array($demoSettings) ? $demoSettings : []);
    }

    private function seedDemoItems(Section $section, string $type): void
    {
        if ($section->items()->exists()) {
            return;
        }

        $items = app(AdminSectionRegistry::class)->get($type)?->optionValue('demo_items', []);

        if (! is_array($items) || $items === []) {
            return;
        }

        $locale = $this->currentLocaleCode();

        foreach (array_values($items) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $title = trim((string) data_get($item, 'title', ''));

            if ($title === '') {
                continue;
            }

            $content = trim((string) data_get($item, 'content', data_get($item, 'description', '')));
            $subtitle = trim((string) data_get($item, 'subtitle', ''));
            $buttonText = trim((string) data_get($item, 'button_text', ''));
            $settings = (array) data_get($item, 'settings', []);

            $section->addItem([
                'title' => [$locale => $title],
                'subtitle' => $subtitle !== '' ? [$locale => $subtitle] : null,
                'content' => $content !== '' ? [$locale => $content] : null,
                'description' => $content !== '' ? [$locale => $content] : null,
                'icon' => filled(data_get($item, 'icon')) ? (string) data_get($item, 'icon') : null,
                'url' => filled(data_get($item, 'url')) ? (string) data_get($item, 'url') : null,
                'button_text' => $buttonText !== '' ? [$locale => $buttonText] : null,
                'button_url' => filled(data_get($item, 'button_url')) ? (string) data_get($item, 'button_url') : null,
                'settings' => $settings !== [] ? $settings : null,
                'is_visible' => (bool) data_get($item, 'is_visible', true),
                'sort_order' => is_numeric(data_get($item, 'sort_order')) ? (int) data_get($item, 'sort_order') : $index,
            ]);
        }
    }

    private function sectionTypeIcon(string $type): string
    {
        $configuredIcon = data_get($this->sectionTypeOptions(), $type.'.icon');

        if (is_string($configuredIcon) && $configuredIcon !== '') {
            return $configuredIcon;
        }

        return match ($type) {
            'hero' => 'rectangle-stack',
            'about', 'mission', 'vision', 'values' => 'document-text',
            'featured_values' => 'sparkles',
            'collaboration' => 'users',
            'features' => 'sparkles',
            'statistics' => 'chart-bar',
            'featured_products', 'all_products' => 'cube',
            'featured_news', 'latest_news', 'taxonomy_news' => 'newspaper',
            'testimonials' => 'chat-bubble-left-right',
            'gallery', 'gallery_grid', 'photo_gallery' => 'photo',
            'video' => 'play-circle',
            'calendar' => 'calendar-days',
            'partners', 'team' => 'users',
            'faq' => 'question-mark-circle',
            'how_to_order' => 'clipboard-document-list',
            'contact' => 'phone',
            'social_links' => 'share',
            default => 'rectangle-stack',
        };
    }

    /** @return array<int, array{key: string, label: string, type: string, icon: string}> */
    private function tabsForSection(?AdminSection $definition): array
    {
        return array_map(
            fn (Tab $tab): array => [
                'key' => $tab->key(),
                'label' => $tab->labelValue(),
                'type' => $tab->type(),
                'icon' => $this->iconForTab($tab),
            ],
            $definition?->tabsValue() ?? [],
        );
    }

    /** @return array<int, string> */
    private function fieldLabelsForSection(?AdminSection $definition): array
    {
        $tab = $definition?->itemsTab() ?? $definition?->settingsTab();

        if (! $tab instanceof Tab) {
            return [];
        }

        return collect($tab->fieldsValue())
            ->map(static fn ($field): string => (string) $field->labelValue())
            ->filter()
            ->take(6)
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutVariantsForSection(?AdminSection $definition): array
    {
        return array_map(
            static fn (LayoutVariant $variant): array => $variant->toArray(),
            $definition?->layoutTab()?->variantsValue() ?? [],
        );
    }

    private function iconForTab(Tab $tab): string
    {
        return match ($tab->type()) {
            'layout' => 'swatch',
            'settings' => 'cog-6-tooth',
            'source' => 'arrow-top-right-on-square',
            default => 'document-text',
        };
    }

    private function previewForSectionType(string $type): string
    {
        return match ($type) {
            'about' => 'about_split',
            'featured_values' => 'featured_values_strip',
            'collaboration' => 'collaboration_banner',
            'features' => 'features_mosaic',
            'statistics' => 'stats_cards',
            'featured_products', 'all_products' => 'products_cards',
            'featured_news', 'latest_news', 'taxonomy_news' => 'news_cards',
            'gallery', 'gallery_grid' => 'gallery_cards',
            'photo_gallery' => 'photo_gallery_grid',
            'video' => 'cards',
            'partners' => 'partners_cards',
            'faq' => 'faq_cards',
            'mission', 'vision', 'values', 'team' => 'story_media_right',
            'how_to_order' => 'order_cards',
            'contact' => 'contact_split',
            'social_links' => 'social_cards',
            default => 'cards',
        };
    }

    private function toastFromResult(mixed $result): void
    {
        Flux::toast(variant: $this->resultSuccessful($result) ? 'success' : 'danger', text: (string) ($result->message ?? __('Promjene su spremljene.')));
    }

    private function resultSuccessful(mixed $result): bool
    {
        return (bool) ($result->success ?? $result->success ?? false);
    }
}

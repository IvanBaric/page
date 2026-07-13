<?php

namespace IvanBaric\Pages\Livewire\Admin;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use IvanBaric\AdminUi\Support\HeroiconRegistry;
use IvanBaric\Corexis\Data\ActionResult as CorexisActionResult;
use IvanBaric\Pages\Actions\CreateSectionItemAction;
use IvanBaric\Pages\Actions\DeleteSectionItemAction;
use IvanBaric\Pages\Actions\ReorderSectionItemsAction;
use IvanBaric\Pages\Actions\ToggleSectionItemVisibilityAction;
use IvanBaric\Pages\Actions\UpdateSectionAction;
use IvanBaric\Pages\Actions\UpdateSectionItemAction;
use IvanBaric\Pages\Admin\Action;
use IvanBaric\Pages\Admin\AdminSection;
use IvanBaric\Pages\Admin\AdminSectionRegistry;
use IvanBaric\Pages\Admin\Field;
use IvanBaric\Pages\Admin\LayoutVariant;
use IvanBaric\Pages\Admin\Tab;
use IvanBaric\Pages\Livewire\Forms\ConfiguredSectionItemForm;
use IvanBaric\Pages\Models\Section;
use IvanBaric\Pages\Models\SectionItem;
use IvanBaric\Pages\Support\PagesModels;
use IvanBaric\Pages\Support\SectionItemGalleryImageSyncer;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class ConfiguredItemsEditor extends Component
{
    use WithFileUploads;

    #[Locked]
    public Section $section;

    #[Locked]
    public ?string $editingItemUuid = null;

    #[Locked]
    public ?string $deletingItemUuid = null;

    public ConfiguredSectionItemForm $form;

    public string $layoutVariant = '';

    #[Url(as: 'editorTab', except: '')]
    public string $tab = '';

    /** @var array<string, mixed> */
    public array $settingsForm = [];

    public function mount(Section $section): void
    {
        $this->section = $section;
        $sectionModel = PagesModels::section();
        $this->section = $sectionModel::query()->whereKey($section->getKey())->firstOrFail();

        $this->resetItemForm();
        $this->tab = $this->validTabKey($this->tab);

        $settings = (array) $this->section->getAttribute('settings');
        $this->layoutVariant = $this->normalizeLayoutVariant(
            data_get($settings, $this->layoutSettingsPath(), $this->layoutDefault()),
        );
        $this->settingsForm = $this->initialSettingsForm($settings);
        $this->loadInlineItemForm();
    }

    public function createItem(): void
    {
        if (! $this->canCreateItem()) {
            $this->toast(false, __('Maksimalan broj stavki je već dodan.'));

            return;
        }

        $this->resetItemForm();
        Flux::modal($this->modalName())->show();
    }

    public function editItem(string $uuid): void
    {
        $item = $this->findItem($uuid);

        $this->resetItemForm();
        $this->editingItemUuid = (string) $item->getAttribute('uuid');
        $this->form->fillFromModel($item);
        $this->configureItemForm();
        $this->form->fillCustomDataFromModel($item);

        Flux::modal($this->modalName())->show();
    }

    public function cancelItemForm(): void
    {
        if (! $this->usesInlineItemForm()) {
            $this->resetItemForm();
        }
    }

    public function saveItem(bool $showToast = true): void
    {
        if (! $this->editingItemUuid && ! $this->canCreateItem()) {
            if ($showToast) {
                $this->toast(false, __('Maksimalan broj stavki je već dodan.'));
            }

            return;
        }

        try {
            $this->applyHiddenTitleFallback();
            $data = $this->form->data();
        } catch (ValidationException $exception) {
            if ($showToast) {
                $this->toast(false, corexis_validation_toast_message(
                    $exception,
                    __('Provjerite obavezna polja i pokušajte ponovno.'),
                ));
            }

            throw $exception;
        }

        $item = $this->editingItemUuid ? $this->findItem($this->editingItemUuid) : null;
        $result = $this->executeConfiguredAction('save_item', [$this->section, $item, $data])
            ?? $this->saveItemFallback($item, $data);

        unset($this->items);

        if ($this->usesInlineItemForm()) {
            if ($this->resultSuccessful($result)) {
                $this->section = $this->section->refresh();
                $this->loadInlineItemForm();
            }
        } else {
            $this->resetItemForm();
            $this->dispatch('modal-close', name: $this->modalName());
        }

        if ($showToast) {
            $this->toastFromResult($result);
        }
    }

    public function toggleItem(string $uuid): void
    {
        $item = $this->findItem($uuid);
        $result = $this->executeConfiguredAction('toggle_item', [$item]) ?? $this->toggleItemFallback($item);

        unset($this->items);

        $this->toastFromResult($result);
    }

    public function reorderItem(string $uuid, int $position): void
    {
        $item = $this->findItem($uuid);
        $result = $this->executeConfiguredAction('reorder_item', [$item, $position]) ?? $this->reorderItemFallback($item, $position);

        unset($this->items);

        $this->toastFromResult($result);
    }

    public function confirmDeleteItem(string $uuid): void
    {
        $this->deletingItemUuid = (string) $this->findItem($uuid)->getAttribute('uuid');
        Flux::modal($this->deleteModalName())->show();
    }

    public function cancelDeleteItem(): void
    {
        $this->deletingItemUuid = null;
    }

    public function deleteItem(): void
    {
        $item = $this->findItem((string) $this->deletingItemUuid);
        $result = $this->executeConfiguredAction('delete_item', [$item]) ?? $this->deleteItemFallback($item);

        $this->deletingItemUuid = null;

        unset($this->items);
        $this->dispatch('modal-close', name: $this->deleteModalName());

        $this->toastFromResult($result);
    }

    public function removeImage(): void
    {
        $this->form->removeImage();
    }

    #[On('pages-save-section-editor')]
    public function saveAllChanges(): void
    {
        try {
            if ($this->usesInlineItemForm()) {
                $this->saveItem(showToast: false);
            }

            if ($this->hasLayoutTab() || $this->hasSettingsTab()) {
                $this->saveSettings(showToast: false);
            }
        } catch (ValidationException $exception) {
            $this->toast(false, corexis_validation_toast_message(
                $exception,
                __('Dogodila se pogreška prilikom spremanja podataka. Provjerite podatke i pokušajte ponovno.'),
            ));

            throw $exception;
        } finally {
            $this->dispatch('pages-save-finished');
        }

        $this->toast(true, __('Promjene su spremljene.'));
    }

    public function saveSettings(bool $showToast = true): void
    {
        $this->layoutVariant = $this->normalizeLayoutVariant($this->layoutVariant);
        $validatedSettings = $this->validatedSettingsForm();

        $settings = (array) $this->section->getAttribute('settings');
        data_set($settings, $this->layoutSettingsPath(), $this->layoutVariant);

        foreach ($validatedSettings as $key => $value) {
            $field = $this->settingsField($key);

            if (! $field instanceof Field) {
                continue;
            }

            data_set($settings, $this->settingsStoragePath($field), $this->normalizeSettingValue($field, $value));
        }

        $result = $this->executeConfiguredAction('save_section', [$this->section, ['settings' => $settings]])
            ?? $this->saveSectionFallback(['settings' => $settings]);

        $this->section = $this->section->refresh();
        $this->settingsForm = $this->initialSettingsForm((array) $this->section->getAttribute('settings'));

        $messageKey = in_array($this->tab, $this->settingsTabKeys(), true) ? 'settings_saved' : 'layout_saved';
        $message = $this->definition()?->message($messageKey, __('Postavke sekcije su spremljene.'));

        if ($showToast) {
            $this->toastFromResult($this->resultSuccessful($result) ? CorexisActionResult::success((string) $message) : $result);
        }
    }

    /** @return Collection<int, SectionItem> */
    #[Computed]
    public function items(): Collection
    {
        return $this->section->items()->orderBy('sort_order')->orderBy('created_at')->get();
    }

    public function modalName(): string
    {
        return 'section-item-form-'.$this->section->getAttribute('uuid');
    }

    public function deleteModalName(): string
    {
        return 'section-item-delete-'.$this->section->getAttribute('uuid');
    }

    public function imageUrl(): ?string
    {
        return $this->form->image;
    }

    /** @return array<int, array{key: string, label: string, type: string, icon: string}> */
    public function editorTabs(): array
    {
        return array_map(
            fn (Tab $tab): array => [
                'key' => $tab->key(),
                'label' => $tab->labelValue(),
                'type' => $tab->type(),
                'icon' => $this->iconForTab($tab),
            ],
            $this->definition()?->tabsValue() ?? [],
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function layoutVariants(): array
    {
        return array_map(
            static fn (LayoutVariant $variant): array => $variant->toArray(),
            $this->layoutTab()?->variantsValue() ?? [],
        );
    }

    public function emptyText(): string
    {
        return (string) ($this->itemsTab()?->optionValue('empty_text', __('Nema dodanih stavki.')) ?? __('Nema dodanih stavki.'));
    }

    public function addButtonLabel(): string
    {
        return (string) ($this->itemsTab()?->optionValue('add_label', __('Dodaj stavku')) ?? __('Dodaj stavku'));
    }

    public function canCreateItem(): bool
    {
        $max = (int) ($this->itemsTab()?->optionValue('max_items', 0) ?? 0);

        return $max <= 0 || $this->items()->count() < $max;
    }

    public function usesInlineItemForm(): bool
    {
        return (bool) ($this->itemsTab()?->optionValue('inline_form', false) ?? false);
    }

    public function formTitle(): string
    {
        $key = $this->editingItemUuid ? 'edit_form_title' : 'create_form_title';

        return (string) ($this->itemsTab()?->optionValue($key, $this->editingItemUuid ? __('Uredi stavku') : __('Dodaj stavku')) ?? ($this->editingItemUuid ? __('Uredi stavku') : __('Dodaj stavku')));
    }

    /** @return array<string, mixed> */
    public function itemsEditorConfig(): array
    {
        $tab = $this->itemsTab();
        $title = $tab?->field('title');
        $subtitle = $tab?->field('subtitle');
        $content = $tab?->field('content');
        $content ??= $tab?->field('description');
        $image = $tab?->field('image');
        $icon = $tab?->field('icon');
        $url = $tab?->field('url');
        $youtubeUrl = $tab?->field('youtube_url');
        $buttonLabel = $tab?->field('button_text') ?? $tab?->field('buttonLabel');
        $buttonUrl = $tab?->field('button_url') ?? $tab?->field('buttonUrl');
        $value = $tab?->field('meta_value');
        $valueSuffix = $tab?->field('meta_suffix');

        return [
            'heading' => $tab?->optionValue('heading', $this->definition()?->labelValue() ?? __('Stavke')),
            'description' => $tab?->optionValue('description', ''),
            'titleLabel' => $title?->labelValue() ?: __('Naziv'),
            'subtitleLabel' => $subtitle?->labelValue() ?: __('Podnaslov'),
            'contentLabel' => $content?->labelValue() ?: __('Sadržaj'),
            'subtitleType' => $subtitle?->type() ?: 'text',
            'subtitleRows' => (int) $subtitle?->optionValue('rows', 3),
            'showTitle' => $title instanceof Field,
            'showImage' => $image instanceof Field,
            'showIcon' => $icon instanceof Field,
            'showUrl' => $url instanceof Field,
            'showYoutubeUrl' => $youtubeUrl instanceof Field,
            'showButton' => $buttonLabel instanceof Field || $buttonUrl instanceof Field,
            'showValue' => $value instanceof Field,
            'showSubtitle' => $subtitle instanceof Field,
            'showContent' => $content instanceof Field,
            'showValueSuffix' => $valueSuffix instanceof Field,
            'showIconHelp' => $icon instanceof Field && (bool) $icon->optionValue('show_help', true),
            'iconPicker' => $icon instanceof Field && $this->iconPickerEnabled($icon),
            'iconOptions' => $icon instanceof Field ? $this->fieldOptions($icon) : [],
            'showSortOrder' => (bool) $tab?->optionValue('show_sort_order', false),
            'showVisibility' => (bool) $tab?->optionValue('show_visibility', true),
            'customFields' => $this->customItemFields($tab),
            'inlineForm' => $this->usesInlineItemForm(),
            'modalFlyout' => (bool) $tab?->optionValue('modal_flyout', false),
            'singleColumnFields' => (bool) $tab?->optionValue('single_column', false),
            'contentRows' => (int) $content?->optionValue('rows', 7),
            'imageUploadSize' => $this->imageUploadSize($image?->optionValue('size')),
            'imageFit' => $image?->optionValue('fit'),
            'imageLabel' => $image?->labelValue() ?: __('Slika ili logo'),
            'imageHelp' => $image?->optionValue('help', corexis_image_upload()->helpText()),
            'iconLabel' => $icon?->labelValue() ?: __('Ikona'),
            'urlLabel' => $url?->labelValue() ?: __('Web stranica'),
            'youtubeUrlLabel' => $youtubeUrl?->labelValue() ?: __('YouTube URL'),
            'buttonLabelLabel' => $buttonLabel?->labelValue() ?: __('Tekst gumba'),
            'buttonUrlLabel' => $buttonUrl?->labelValue() ?: __('URL gumba'),
            'valueLabel' => $value?->labelValue() ?: __('Vrijednost'),
            'valueSuffixLabel' => $valueSuffix?->labelValue() ?: __('Sufiks'),
            'itemModalDescription' => $tab?->optionValue('modal_description', __('Unesite podatke za ovu stavku.')),
            'inlineSubmitLabel' => $tab?->optionValue('inline_submit_label', __('Spremi')),
            'editActionLabel' => $tab?->action('edit')?->labelValue() ?: __('Uredi'),
            'deleteActionLabel' => $tab?->action('delete')?->labelValue() ?: __('Arhiviraj'),
            'deleteConfirmTitle' => $tab?->action('delete')?->optionValue('confirm_title', __('Arhivirati stavku?')),
            'deleteConfirmDescription' => $tab?->action('delete')?->optionValue('confirm_text', __('Stavka će se premjestiti u arhivu. Možete je kasnije vratiti iz Arhive.')),
        ];
    }

    public function layoutHeading(): string
    {
        return (string) ($this->layoutTab()?->optionValue('heading', __('Izgled sekcije')) ?? __('Izgled sekcije'));
    }

    public function layoutDescription(): string
    {
        return (string) ($this->layoutTab()?->optionValue('description', __('Odaberite kako će se sekcija prikazati na javnoj stranici.')) ?? __('Odaberite kako će se sekcija prikazati na javnoj stranici.'));
    }

    public function sourceHeading(): string
    {
        return (string) ($this->sourceTab()?->optionValue('heading', $this->section->localized('title') ?: $this->definition()?->labelValue() ?: __('Sadržaj')) ?? __('Sadržaj'));
    }

    public function sourceDescription(): string
    {
        return (string) ($this->sourceTab()?->optionValue('description', '') ?? '');
    }

    /** @return array<int, array{label: string, icon: string|null, variant: string, href: string}> */
    public function sourceActions(): array
    {
        $tab = $this->sourceTab();

        if (! $tab instanceof Tab) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (Action $action): ?array => $this->sourceActionConfig($action),
            $tab->actionsValue(),
        )));
    }

    public function settingsHeading(): string
    {
        return (string) ($this->settingsTab()?->optionValue('heading', __('Postavke sekcije')) ?? __('Postavke sekcije'));
    }

    public function settingsDescription(): string
    {
        return (string) ($this->settingsTab()?->optionValue('description', __('Uredite postavke prikaza za ovu sekciju.')) ?? __('Uredite postavke prikaza za ovu sekciju.'));
    }

    /** @return array<int, array<string, mixed>> */
    public function settingsFields(): array
    {
        $tab = $this->settingsTab();

        return $tab instanceof Tab ? $this->settingsFieldsForTab($tab) : [];
    }

    public function hasSourceTab(): bool
    {
        return $this->sourceTab() instanceof Tab;
    }

    public function hasItemsTab(): bool
    {
        return $this->itemsTab() instanceof Tab;
    }

    public function hasLayoutTab(): bool
    {
        return $this->layoutTab() instanceof Tab;
    }

    public function hasSettingsTab(): bool
    {
        return $this->settingsTabs() !== [];
    }

    /** @return array<int, array{key: string, heading: string, description: string, fields: array<int, array<string, mixed>>}> */
    public function settingsPanels(): array
    {
        return array_map(
            fn (Tab $tab): array => [
                'key' => $tab->key(),
                'heading' => (string) ($tab->optionValue('heading', __('Postavke sekcije')) ?? __('Postavke sekcije')),
                'description' => (string) ($tab->optionValue('description', __('Uredite postavke prikaza za ovu sekciju.')) ?? __('Uredite postavke prikaza za ovu sekciju.')),
                'fields' => $this->settingsFieldsForTab($tab),
            ],
            $this->settingsTabs(),
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function viewTabs(): array
    {
        return array_values(array_filter(array_map(
            static fn (Tab $tab): ?array => $tab->type() === 'view' ? [
                'key' => $tab->key(),
                'label' => $tab->labelValue(),
                'view' => (string) $tab->optionValue('view', ''),
                'heading' => (string) $tab->optionValue('heading', $tab->labelValue()),
                'description' => (string) $tab->optionValue('description', ''),
            ] : null,
            $this->definition()?->tabsValue() ?? [],
        )));
    }

    public function hasViewTabs(): bool
    {
        return $this->viewTabs() !== [];
    }

    public function sourceTabKey(): string
    {
        return $this->sourceTab()?->key() ?? 'content';
    }

    public function itemsTabKey(): string
    {
        return $this->itemsTab()?->key() ?? 'content';
    }

    public function layoutTabKey(): string
    {
        return $this->layoutTab()?->key() ?? 'design';
    }

    public function settingsTabKey(): string
    {
        return $this->settingsTab()?->key() ?? 'settings';
    }

    public function render(): View
    {
        return view('pages::livewire.admin.configured-items-editor');
    }

    private function resetItemForm(): void
    {
        $this->editingItemUuid = null;
        $this->form->resetForSection($this->section);
        $this->configureItemForm();
    }

    private function loadInlineItemForm(): void
    {
        if (! $this->usesInlineItemForm()) {
            return;
        }

        $item = $this->section->items()->orderBy('sort_order')->orderBy('created_at')->first();

        if ($item instanceof SectionItem) {
            $this->editingItemUuid = (string) $item->getAttribute('uuid');
            $this->form->fillFromModel($item);
            $this->configureItemForm();
            $this->form->fillCustomDataFromModel($item);

            return;
        }

        $this->resetItemForm();
    }

    private function configureItemForm(): void
    {
        $tab = $this->itemsTab();

        if (! $tab instanceof Tab) {
            $this->form->configureCustomFields([]);
            $this->form->configureValidation();

            return;
        }

        $rules = [];
        $attributes = [];
        $customFieldKeys = array_column($this->customItemFields($tab), 'key');

        foreach ($tab->fieldsValue() as $field) {
            $property = $this->formPropertyForField($field);

            $fieldRules = $this->rulesForField($field);

            if ($fieldRules !== []) {
                $rules[$property] = $fieldRules;
            }

            if ($field->labelValue() !== '') {
                $attributes[$property] = $field->labelValue();
            }
        }

        if (! $tab->field('title') instanceof Field) {
            $rules['title'] = ['nullable', 'string', 'max:255'];
        }

        $messages = array_filter([
            'required' => $this->definition()?->message('required'),
        ]);

        $this->form->configureCustomFields($customFieldKeys);
        $this->form->configureValidation($rules, $attributes, $messages);
    }

    private function findItem(string $uuid): SectionItem
    {
        $item = $this->section->items()->where('uuid', $uuid)->firstOrFail();

        return $item;
    }

    private function definition(): ?AdminSection
    {
        return app(AdminSectionRegistry::class)->for($this->section);
    }

    private function itemsTab(): ?Tab
    {
        return $this->definition()?->itemsTab();
    }

    private function layoutTab(): ?Tab
    {
        return $this->definition()?->layoutTab();
    }

    private function sourceTab(): ?Tab
    {
        return $this->definition()?->tabOfType('source');
    }

    private function settingsTab(): ?Tab
    {
        return $this->definition()?->settingsTab();
    }

    /** @return array<int, Tab> */
    private function settingsTabs(): array
    {
        return $this->definition()?->settingsTabs() ?? [];
    }

    private function applyHiddenTitleFallback(): void
    {
        if ($this->itemsTab()?->field('title') instanceof Field || trim($this->form->title) !== '') {
            return;
        }

        $this->form->title = $this->section->localized('title')
            ?: $this->definition()?->labelValue()
            ?: __('Stavka');
    }

    private function firstTabKey(): string
    {
        $tabs = array_values($this->definition()?->tabsValue() ?? []);

        return $tabs === [] ? 'content' : $tabs[0]->key();
    }

    private function validTabKey(?string $tab): string
    {
        $tab = (string) $tab;
        $keys = array_map(
            static fn (Tab $tab): string => $tab->key(),
            $this->definition()?->tabsValue() ?? [],
        );

        return in_array($tab, $keys, true) ? $tab : $this->firstTabKey();
    }

    private function iconForTab(Tab $tab): string
    {
        $icon = $tab->optionValue('icon');

        if (is_string($icon) && $icon !== '') {
            return $icon;
        }

        return match ($tab->type()) {
            'layout' => 'swatch',
            'settings' => 'cog-6-tooth',
            'source' => 'arrow-top-right-on-square',
            'view' => 'photo',
            default => 'document-text',
        };
    }

    private function normalizeLayoutVariant(mixed $variant): string
    {
        $allowed = $this->layoutTab()?->variantKeys() ?? [];

        if ($allowed === []) {
            return (string) $variant;
        }

        return in_array($variant, $allowed, true) ? (string) $variant : $this->layoutDefault();
    }

    private function layoutDefault(): string
    {
        return (string) ($this->layoutTab()?->optionValue('default', 'cards') ?? 'cards');
    }

    private function layoutSettingsPath(): string
    {
        $storage = (string) ($this->layoutTab()?->optionValue('storage', 'settings.layout_variant') ?? 'settings.layout_variant');

        return str_starts_with($storage, 'settings.') ? substr($storage, 9) : 'layout_variant';
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function initialSettingsForm(array $settings): array
    {
        $values = [];

        foreach ($this->settingsFieldsValue() as $field) {
            $values[$field->key()] = data_get($settings, $this->settingsStoragePath($field), $field->optionValue('default'));
        }

        return $values;
    }

    /** @return array<string, mixed> */
    private function validatedSettingsForm(): array
    {
        $fields = $this->settingsFieldsValue();

        if ($fields === []) {
            return [];
        }

        $rules = [];
        $attributes = [];

        foreach ($fields as $field) {
            $fieldRules = $this->rulesForField($field);

            if ($fieldRules !== []) {
                $rules['settingsForm.'.$field->key()] = $fieldRules;
            }

            if ($field->labelValue() !== '') {
                $attributes['settingsForm.'.$field->key()] = $field->labelValue();
            }
        }

        if ($rules !== []) {
            $this->validate(
                $rules,
                array_filter(['required' => $this->definition()?->message('required')]),
                $attributes,
            );
        }

        return $this->settingsForm;
    }

    /** @return array<int, Field> */
    private function settingsFieldsValue(): array
    {
        if ($this->settingsTabs() === []) {
            return [];
        }

        return array_merge(...array_map(
            static fn (Tab $tab): array => $tab->fieldsValue(),
            $this->settingsTabs(),
        ));
    }

    /** @return array<int, array<string, mixed>> */
    private function settingsFieldsForTab(Tab $tab): array
    {
        return array_map(
            fn (Field $field): array => [
                'key' => $field->key(),
                'type' => $field->type(),
                'label' => $field->labelValue(),
                'help' => $field->optionValue('help', ''),
                'options' => $this->fieldOptions($field),
                'display' => (string) $field->optionValue('display', ''),
                'rows' => (int) $field->optionValue('rows', 4),
                'picker' => $this->iconPickerEnabled($field),
            ],
            $tab->fieldsValue(),
        );
    }

    private function settingsField(string $key): ?Field
    {
        foreach ($this->settingsFieldsValue() as $field) {
            if ($field->key() === $key) {
                return $field;
            }
        }

        return null;
    }

    /** @return array<int, string> */
    private function settingsTabKeys(): array
    {
        return array_map(static fn (Tab $tab): string => $tab->key(), $this->settingsTabs());
    }

    private function settingsStoragePath(Field $field): string
    {
        $storage = (string) ($field->optionValue('storage', $field->key()) ?? $field->key());

        return str_starts_with($storage, 'settings.') ? substr($storage, 9) : $storage;
    }

    private function normalizeSettingValue(Field $field, mixed $value): mixed
    {
        if ($field->type() === 'icon' && $this->iconPickerEnabled($field)) {
            return filled($value) ? HeroiconRegistry::safe((string) $value) : null;
        }

        if ($field->type() === 'boolean') {
            return (bool) $value;
        }

        if ($field->type() === 'number') {
            return is_numeric($value) && str_contains((string) $value, '.')
                ? (float) $value
                : (int) $value;
        }

        if ($field->type() === 'select') {
            foreach ($this->fieldOptions($field) as $option) {
                if ((string) $option['value'] === (string) $value) {
                    return $option['value'];
                }
            }
        }

        return is_string($value) ? trim($value) : $value;
    }

    /** @return array<int, mixed> */
    private function rulesForField(Field $field): array
    {
        $rules = $field->rulesValue();

        if ($field->type() === 'icon' && $this->iconPickerEnabled($field)) {
            $rules = array_values(array_filter(
                $rules,
                static fn (string $rule): bool => ! str_starts_with($rule, 'max:'),
            ));
            $rules[] = 'in:'.implode(',', HeroiconRegistry::names());
        }

        if ($field->type() === 'icon' && ! $this->iconPickerEnabled($field) && $this->fieldOptions($field) !== []) {
            $rules = array_values(array_filter(
                $rules,
                static fn (string $rule): bool => ! str_starts_with($rule, 'max:'),
            ));

            $rules[] = 'in:'.implode(',', array_map(
                static fn (array $option): string => (string) $option['value'],
                $this->fieldOptions($field),
            ));
        }

        return $rules;
    }

    private function iconPickerEnabled(Field $field): bool
    {
        return $field->type() === 'icon' && (bool) $field->optionValue('picker', true);
    }

    /** @return array<int, array<string, mixed>> */
    private function fieldOptions(Field $field): array
    {
        return array_map(
            static function (mixed $key, mixed $option): array {
                if (is_array($option)) {
                    return array_replace($option, [
                        'value' => $option['value'] ?? $key,
                        'label' => (string) ($option['label'] ?? $option['value'] ?? $key),
                        'description' => (string) ($option['description'] ?? ''),
                    ]);
                }

                return [
                    'value' => is_int($key) ? $option : $key,
                    'label' => (string) $option,
                ];
            },
            array_keys((array) $field->optionValue('options', [])),
            (array) $field->optionValue('options', []),
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function customItemFields(?Tab $tab): array
    {
        if (! $tab instanceof Tab) {
            return [];
        }

        return array_values(array_map(
            fn (Field $field): array => [
                'key' => $field->key(),
                'type' => $field->type(),
                'label' => $field->labelValue(),
                'help' => $field->optionValue('help', ''),
                'options' => $this->fieldOptions($field),
                'rows' => (int) $field->optionValue('rows', 4),
                'picker' => $this->iconPickerEnabled($field),
            ],
            array_filter(
                $tab->fieldsValue(),
                fn (Field $field): bool => ! in_array($field->key(), $this->standardItemFieldKeys(), true),
            ),
        ));
    }

    /** @return array<int, string> */
    private function standardItemFieldKeys(): array
    {
        return [
            'title',
            'subtitle',
            'content',
            'description',
            'image',
            'icon',
            'url',
            'youtube_url',
            'button_text',
            'buttonLabel',
            'button_url',
            'buttonUrl',
            'meta_value',
            'meta_suffix',
            'meta_rating',
            'sort_order',
        ];
    }

    /** @return array{label: string, icon: string|null, variant: string, href: string}|null */
    private function sourceActionConfig(Action $action): ?array
    {
        $href = $action->optionValue('url');
        $route = $action->optionValue('route');

        if (! is_string($href) && is_string($route) && $route !== '') {
            $href = route($route, (array) $action->optionValue('route_parameters', []));
        }

        if (! is_string($href) || $href === '') {
            return null;
        }

        return [
            'label' => $action->labelValue(),
            'icon' => $action->iconValue(),
            'variant' => $action->variantValue() ?: 'ghost',
            'href' => $href,
        ];
    }

    private function imageUploadSize(mixed $size): string
    {
        return match ($size) {
            'small' => 'size-32',
            'medium', null => 'w-full aspect-video',
            'large' => 'w-full aspect-video',
            default => is_string($size) ? $size : 'w-full aspect-video',
        };
    }

    private function formPropertyForField(Field $field): string
    {
        return match ($field->key()) {
            'image' => 'imageUpload',
            'description' => 'content',
            'button_text' => 'buttonLabel',
            'button_url' => 'buttonUrl',
            'youtube_url' => 'youtubeUrl',
            'meta_value' => 'metaValue',
            'meta_suffix' => 'metaSuffix',
            'meta_rating' => 'metaRating',
            'sort_order' => 'sortOrder',
            default => property_exists($this->form, $field->key()) ? $field->key() : 'customData.'.$field->key(),
        };
    }

    /** @param array<int, mixed> $arguments */
    private function executeConfiguredAction(string $key, array $arguments): mixed
    {
        $class = config('pages.configured_items_editor.actions.'.$key);

        if (! is_string($class) || $class === '' || ! class_exists($class)) {
            return null;
        }

        $action = app($class);

        if (! is_object($action)) {
            return null;
        }

        if (method_exists($action, 'execute')) {
            return $action->execute(...$arguments);
        }

        if (method_exists($action, 'handle')) {
            return $action->handle(...$arguments);
        }

        return null;
    }

    /** @param array<string, mixed> $data */
    private function saveItemFallback(?SectionItem $item, array $data): CorexisActionResult
    {
        $upload = $data['_image_upload'] ?? null;
        $removeImage = (bool) ($data['_remove_image'] ?? false);
        unset($data['_image_upload'], $data['_remove_image']);

        if ($item instanceof SectionItem) {
            $result = app(UpdateSectionItemAction::class)->handle($item, $data + [
                'lock_version' => (int) $item->getAttribute('lock_version'),
            ]);

            if ($result->failed() || ! $result->data instanceof SectionItem) {
                return $result;
            }

            $this->syncItemImage($result->data, $upload, $removeImage);

            return CorexisActionResult::success(__('Stavka je spremljena.'), ['item' => $result->data->refresh()]);
        }

        $result = app(CreateSectionItemAction::class)->handle($this->section, $data + [
            'sort_order' => ((int) $this->section->items()->max('sort_order')) + 1,
        ]);

        if ($result->failed() || ! $result->data instanceof SectionItem) {
            return $result;
        }

        $created = $result->data;
        $this->syncItemImage($created, $upload, $removeImage);

        return CorexisActionResult::success(__('Stavka je dodana.'), ['item' => $created->refresh()]);
    }

    private function syncItemImage(SectionItem $item, mixed $upload, bool $removeImage): void
    {
        $field = $this->itemsTab()?->field('image');

        if (! $field instanceof Field || ! (bool) $field->optionValue('store_as_gallery_media', false)) {
            return;
        }

        $collection = (string) ($field->optionValue('media_collection', 'image') ?: 'image');

        app(SectionItemGalleryImageSyncer::class)->sync($item, $upload, $removeImage, $collection);
    }

    private function toggleItemFallback(SectionItem $item): CorexisActionResult
    {
        return app(ToggleSectionItemVisibilityAction::class)->handle($item);
    }

    private function reorderItemFallback(SectionItem $item, int $position): CorexisActionResult
    {
        $items = $item->section->items()->ordered()->get();
        $moving = $items->firstWhere('id', $item->getKey());

        if (! $moving instanceof SectionItem) {
            return CorexisActionResult::error(__('Stavka nije pronađena.'));
        }

        $items = $items->reject(fn (SectionItem $current): bool => $current->is($moving))->values();
        $items->splice(max(0, min($position, $items->count())), 0, [$moving]);

        return app(ReorderSectionItemsAction::class)->handle($item->section, $items->pluck('uuid')->all());
    }

    private function deleteItemFallback(SectionItem $item): CorexisActionResult
    {
        return app(DeleteSectionItemAction::class)->handle($item);
    }

    /** @param array<string, mixed> $data */
    private function saveSectionFallback(array $data): CorexisActionResult
    {
        $result = app(UpdateSectionAction::class)->handle($this->section, $data + [
            'type' => (string) $this->section->getAttribute('type'),
            'lock_version' => (int) $this->section->getAttribute('lock_version'),
        ]);

        return $result->failed()
            ? $result
            : CorexisActionResult::success(__('Sekcija je spremljena.'), ['section' => $this->section->refresh()]);
    }

    private function resultSuccessful(mixed $result): bool
    {
        return (bool) ($result->success ?? false);
    }

    private function resultMessage(mixed $result): string
    {
        return (string) ($result->message ?? __('Promjene su spremljene.'));
    }

    private function toastFromResult(mixed $result): void
    {
        $this->toast($this->resultSuccessful($result), $this->resultMessage($result));
    }

    private function toast(bool $success, string $message): void
    {
        Flux::toast(variant: $success ? 'success' : 'danger', text: $message);
    }
}

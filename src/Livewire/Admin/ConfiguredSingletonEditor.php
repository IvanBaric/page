<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Livewire\Admin;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use IvanBaric\Pages\Admin\AdminSection;
use IvanBaric\Pages\Admin\AdminSectionRegistry;
use IvanBaric\Pages\Admin\Field;
use IvanBaric\Pages\Admin\LayoutVariant;
use IvanBaric\Pages\Admin\Tab;
use IvanBaric\Pages\Support\TeamResolver;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

final class ConfiguredSingletonEditor extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $modelClass = '';

    #[Locked]
    public string $modelKeyName = '';

    #[Locked]
    public string $modelKey = '';

    #[Locked]
    public string $definitionKey = '';

    #[Url(as: 'editorTab', except: '')]
    public string $tab = '';

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var array<string, mixed> */
    public array $uploads = [];

    /** @var array<string, bool> */
    public array $removeImages = [];

    /** @var array<string, mixed> */
    public array $settingsForm = [];

    public string $layoutVariant = '';

    public function mount(Model $model, string $definitionKey): void
    {
        $this->authorizeModel($model);

        $this->modelClass = $model::class;
        $this->modelKeyName = $model->getKeyName();
        $this->modelKey = (string) $model->getKey();
        $this->definitionKey = $definitionKey;
        $this->tab = $this->validTabKey($this->tab);

        $this->fillFromModel();
    }

    public function save(): void
    {
        $this->saveFormData();
    }

    public function saveLayout(): void
    {
        $this->saveLayoutData();
    }

    public function saveSettings(): void
    {
        $this->saveSettingsData();
    }

    #[On('pages-save-singleton-editor')]
    public function saveAllChanges(): void
    {
        $layoutVariant = $this->layoutVariant;
        $settingsForm = $this->settingsForm;

        try {
            if ($this->hasFormTab()) {
                $this->saveFormData(showToast: false);
            }

            if ($this->hasLayoutTab()) {
                $this->layoutVariant = $layoutVariant;
                $this->saveLayoutData(showToast: false);
            }

            if ($this->hasSettingsTab()) {
                $this->settingsForm = $settingsForm;
                $this->saveSettingsData(showToast: false);
            }
        } catch (ValidationException $exception) {
            $this->toast(false, __('Dogodila se pogreška prilikom spremanja podataka. Provjerite podatke i pokušajte ponovno.'));

            throw $exception;
        }

        $this->toast(true, __('Promjene su spremljene.'));
    }

    public function removeImage(string $key): void
    {
        $this->uploads[$key] = null;
        $this->form[$key] = null;
        $this->removeImages[$key] = true;
    }

    public function imageUrl(string $key): ?string
    {
        if ((bool) ($this->removeImages[$key] ?? false)) {
            return null;
        }

        $field = $this->formTab()?->field($key);

        if ($field instanceof Field && (bool) $field->optionValue('store_as_gallery_media', false)) {
            $collection = (string) ($field->optionValue('media_collection', 'image') ?: 'image');
            $conversion = (string) ($field->optionValue('media_conversion', 'large') ?: '');
            $model = $this->model();

            if (method_exists($model, 'galleryImageUrl')) {
                return $model->galleryImageUrl($collection, $conversion);
            }

            return null;
        }

        $image = data_get($this->form, $key);

        if (! is_string($image) || $image === '') {
            return null;
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        return Storage::disk('public')->url($image);
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
    public function formFields(): array
    {
        return array_map(
            fn (Field $field): array => $this->fieldConfig($field),
            $this->formTab()?->fieldsValue() ?? [],
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function mainFormFields(): array
    {
        return array_values(array_filter($this->formFields(), static fn (array $field): bool => $field['type'] !== 'image'));
    }

    /** @return array<int, array<string, mixed>> */
    public function sidebarFormFields(): array
    {
        return array_values(array_filter($this->formFields(), static fn (array $field): bool => $field['type'] === 'image'));
    }

    public function hasFormTab(): bool
    {
        return $this->formTab() instanceof Tab;
    }

    public function hasLayoutTab(): bool
    {
        return $this->layoutTab() instanceof Tab;
    }

    public function hasSettingsTab(): bool
    {
        return $this->settingsTab() instanceof Tab;
    }

    public function formTabKey(): string
    {
        return $this->formTab()?->key() ?? 'content';
    }

    public function layoutTabKey(): string
    {
        return $this->layoutTab()?->key() ?? 'design';
    }

    public function settingsTabKey(): string
    {
        return $this->settingsTab()?->key() ?? 'settings';
    }

    public function formHeading(): string
    {
        return (string) ($this->formTab()?->optionValue('heading', $this->definition()?->labelValue() ?? __('Sadržaj')) ?? __('Sadržaj'));
    }

    public function formDescription(): string
    {
        return (string) ($this->formTab()?->optionValue('description', '') ?? '');
    }

    public function formSubmitLabel(): string
    {
        return (string) ($this->formTab()?->optionValue('submit_label', $this->definition()?->optionValue('submit_label', __('Spremi'))) ?? __('Spremi'));
    }

    public function layoutHeading(): string
    {
        return (string) ($this->layoutTab()?->optionValue('heading', __('Izgled')) ?? __('Izgled'));
    }

    public function layoutDescription(): string
    {
        return (string) ($this->layoutTab()?->optionValue('description', __('Odaberite izgled prikaza.')) ?? __('Odaberite izgled prikaza.'));
    }

    public function layoutSubmitLabel(): string
    {
        return (string) ($this->layoutTab()?->optionValue('submit_label', __('Spremi izgled')) ?? __('Spremi izgled'));
    }

    public function settingsHeading(): string
    {
        return (string) ($this->settingsTab()?->optionValue('heading', __('Postavke')) ?? __('Postavke'));
    }

    public function settingsDescription(): string
    {
        return (string) ($this->settingsTab()?->optionValue('description', __('Uredite postavke prikaza.')) ?? __('Uredite postavke prikaza.'));
    }

    public function settingsSubmitLabel(): string
    {
        return (string) ($this->settingsTab()?->optionValue('submit_label', __('Spremi postavke')) ?? __('Spremi postavke'));
    }

    /** @return array<int, array<string, mixed>> */
    public function settingsFields(): array
    {
        return array_map(
            fn (Field $field): array => $this->fieldConfig($field),
            $this->settingsTab()?->fieldsValue() ?? [],
        );
    }

    /** @return array<int, array{value: string, label: string, description: string, options: array<string, mixed>}> */
    public function layoutVariants(): array
    {
        return array_map(
            static fn (LayoutVariant $variant): array => $variant->toArray(),
            $this->layoutTab()?->variantsValue() ?? [],
        );
    }

    public function render(): View
    {
        return view('pages::livewire.admin.configured-singleton-editor');
    }

    private function saveFormData(bool $showToast = true): void
    {
        try {
            $validated = $this->validatedForm();
        } catch (ValidationException $exception) {
            if ($showToast) {
                $this->toast(false, __('Provjerite obavezna polja i pokušajte ponovno.'));
            }

            throw $exception;
        }

        $data = $this->storedData();

        foreach ($this->formTab()?->fieldsValue() ?? [] as $field) {
            $key = $field->key();
            $path = $this->fieldStoragePath($field);

            if ($field->type() === 'image') {
                if ((bool) $field->optionValue('store_as_gallery_media', false)) {
                    $this->syncModelGalleryImage($field);

                    if (! (bool) $field->optionValue('store_value', false)) {
                        continue;
                    }
                }

                data_set($data, $path, $this->storedImageValue($field));

                continue;
            }

            data_set($data, $path, $this->normalizeFieldValue($field, data_get($validated, 'form.'.$key)));
        }

        $this->saveStoredData($data);
        $this->fillFromModel();

        if ($showToast) {
            $this->toast(true, $this->definition()?->message('saved', __('Promjene su spremljene.')) ?? __('Promjene su spremljene.'));
        }
    }

    private function saveLayoutData(bool $showToast = true): void
    {
        $this->layoutVariant = $this->normalizeLayoutVariant($this->layoutVariant);

        $data = $this->storedData();
        data_set($data, $this->layoutStoragePath(), $this->layoutVariant);

        $this->saveStoredData($data);
        $this->fillFromModel();

        if ($showToast) {
            $this->toast(true, $this->definition()?->message('layout_saved', __('Izgled je spremljen.')) ?? __('Izgled je spremljen.'));
        }
    }

    private function saveSettingsData(bool $showToast = true): void
    {
        $validated = $this->validatedSettingsForm();
        $data = $this->storedData();

        foreach ($validated as $key => $value) {
            $field = $this->settingsTab()?->field($key);

            if (! $field instanceof Field) {
                continue;
            }

            data_set($data, $this->fieldStoragePath($field), $this->normalizeFieldValue($field, $value));
        }

        $this->saveStoredData($data);
        $this->fillFromModel();

        if ($showToast) {
            $this->toast(true, $this->definition()?->message('settings_saved', __('Postavke su spremljene.')) ?? __('Postavke su spremljene.'));
        }
    }

    private function fillFromModel(): void
    {
        $data = $this->storedData();
        $form = [];
        $uploads = [];
        $removeImages = [];

        foreach ($this->formTab()?->fieldsValue() ?? [] as $field) {
            $key = $field->key();
            $form[$key] = data_get($data, $this->fieldStoragePath($field), $this->defaultValue($field));

            if ($field->type() === 'image') {
                $uploads[$key] = null;
                $removeImages[$key] = false;
            }
        }

        $this->form = $form;
        $this->uploads = $uploads;
        $this->removeImages = $removeImages;
        $this->settingsForm = $this->initialSettingsForm($data);
        $this->layoutVariant = $this->normalizeLayoutVariant(data_get($data, $this->layoutStoragePath(), $this->layoutDefault()));
    }

    /** @return array<string, mixed> */
    private function validatedForm(): array
    {
        $rules = [];
        $attributes = [];

        foreach ($this->formTab()?->fieldsValue() ?? [] as $field) {
            if ($field->type() === 'image') {
                $rules['uploads.'.$field->key()] = $field->rulesValue();
            } else {
                $rules['form.'.$field->key()] = $field->rulesValue();
            }

            if ($field->labelValue() !== '') {
                $attributes[$field->type() === 'image' ? 'uploads.'.$field->key() : 'form.'.$field->key()] = $field->labelValue();
            }
        }

        return $this->validate($rules, array_filter([
            'required' => $this->definition()?->message('required'),
        ]), $attributes);
    }

    /** @return array<string, mixed> */
    private function validatedSettingsForm(): array
    {
        $rules = [];
        $attributes = [];

        foreach ($this->settingsTab()?->fieldsValue() ?? [] as $field) {
            $rules['settingsForm.'.$field->key()] = $field->rulesValue();

            if ($field->labelValue() !== '') {
                $attributes['settingsForm.'.$field->key()] = $field->labelValue();
            }
        }

        $validated = $this->validate($rules, array_filter([
            'required' => $this->definition()?->message('required'),
        ]), $attributes);

        return (array) data_get($validated, 'settingsForm', []);
    }

    /** @param array<string, mixed> $data */
    private function initialSettingsForm(array $data): array
    {
        $form = [];

        foreach ($this->settingsTab()?->fieldsValue() ?? [] as $field) {
            $form[$field->key()] = data_get($data, $this->fieldStoragePath($field), $this->defaultValue($field));
        }

        return $form;
    }

    private function storedImageValue(Field $field): ?string
    {
        $key = $field->key();
        $upload = $this->uploads[$key] ?? null;

        if ($upload) {
            return $upload->store((string) ($field->optionValue('directory', 'uploads') ?? 'uploads'), 'public');
        }

        if ((bool) ($this->removeImages[$key] ?? false)) {
            return null;
        }

        $image = data_get($this->form, $key);

        return is_string($image) && $image !== '' ? $image : null;
    }

    private function syncModelGalleryImage(Field $field): void
    {
        $model = $this->model();

        if (! method_exists($model, 'gallery')) {
            return;
        }

        $key = $field->key();
        $collection = (string) ($field->optionValue('media_collection', 'image') ?: 'image');
        $upload = $this->uploads[$key] ?? null;
        $removeImage = (bool) ($this->removeImages[$key] ?? false);
        $gallery = $model->gallery($collection);

        if ($removeImage && ! $upload instanceof TemporaryUploadedFile) {
            if ($gallery && method_exists($gallery, 'clearMediaCollection')) {
                $gallery->clearMediaCollection($collection);
                $gallery->delete();
            }

            return;
        }

        if (! $upload instanceof TemporaryUploadedFile || ! method_exists($model, 'getOrCreateGallery')) {
            return;
        }

        $title = $this->modelGalleryImageTitle($field, $model);
        $gallery = $model->getOrCreateGallery($collection, ['title' => $title]);
        $gallery->clearMediaCollection($collection);

        $media = $gallery
            ->addMedia($upload->getRealPath())
            ->usingFileName($upload->hashName())
            ->usingName(pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME) ?: $upload->hashName())
            ->withCustomProperties([
                'alt' => $title,
                'title' => $title,
                'caption' => '',
                'description' => '',
                'credit' => '',
                'source_url' => '',
                'license' => '',
                'is_decorative' => false,
            ])
            ->toMediaCollection($collection);

        $gallery->forceFill([
            'title' => $title,
            'featured_media_id' => $media->id,
        ])->save();
    }

    private function modelGalleryImageTitle(Field $field, Model $model): string
    {
        $configuredTitle = $field->optionValue('media_title');

        if (filled($configuredTitle)) {
            return (string) $configuredTitle;
        }

        if (method_exists($model, 'localized')) {
            $localizedName = $model->localized('name');

            if (filled($localizedName)) {
                return (string) $localizedName;
            }
        }

        return $field->labelValue() !== '' ? $field->labelValue() : __('Slika');
    }

    /** @return array<string, mixed> */
    private function storedData(): array
    {
        $model = $this->model();
        $storage = $this->storagePath();

        if (! str_contains($storage, '.')) {
            return (array) $model->getAttribute($storage);
        }

        [$attribute, $path] = explode('.', $storage, 2);

        return (array) data_get($model->getAttribute($attribute), $path, []);
    }

    /** @param array<string, mixed> $data */
    private function saveStoredData(array $data): void
    {
        $model = $this->model();
        $storage = $this->storagePath();

        if (! str_contains($storage, '.')) {
            $model->forceFill([$storage => $data])->save();

            return;
        }

        [$attribute, $path] = explode('.', $storage, 2);
        $value = (array) $model->getAttribute($attribute);
        data_set($value, $path, $data);

        $model->forceFill([$attribute => $value])->save();
    }

    private function model(): Model
    {
        /** @var class-string<Model> $class */
        $class = $this->modelClass;

        return $class::query()->where($this->modelKeyName, $this->modelKey)->firstOrFail();
    }

    private function definition(): ?AdminSection
    {
        return app(AdminSectionRegistry::class)->get($this->definitionKey);
    }

    private function formTab(): ?Tab
    {
        return $this->definition()?->tabOfType('form') ?? $this->definition()?->itemsTab();
    }

    private function layoutTab(): ?Tab
    {
        return $this->definition()?->layoutTab();
    }

    private function settingsTab(): ?Tab
    {
        return $this->definition()?->settingsTab();
    }

    private function storagePath(): string
    {
        return (string) ($this->definition()?->optionValue('storage', 'settings') ?? 'settings');
    }

    private function fieldStoragePath(Field $field): string
    {
        return (string) ($field->optionValue('storage', $field->key()) ?? $field->key());
    }

    private function layoutStoragePath(): string
    {
        return (string) ($this->layoutTab()?->optionValue('storage', 'layout_variant') ?? 'layout_variant');
    }

    private function layoutDefault(): string
    {
        return (string) ($this->layoutTab()?->optionValue('default', 'default') ?? 'default');
    }

    private function normalizeLayoutVariant(mixed $variant): string
    {
        $allowed = $this->layoutTab()?->variantKeys() ?? [];

        if ($allowed === []) {
            return (string) $variant;
        }

        return in_array($variant, $allowed, true) ? (string) $variant : $this->layoutDefault();
    }

    private function defaultValue(Field $field): mixed
    {
        $defaultFrom = $field->optionValue('default_from');
        $model = $this->model();

        if ($field->type() === 'select' && $field->optionValue('options_source') === 'published_pages') {
            $defaultPageKey = $field->optionValue('default_page_key');

            if (is_string($defaultPageKey) && $defaultPageKey !== '') {
                $defaultPageUuid = $this->publishedPageUuidFor($defaultPageKey);

                if ($defaultPageUuid !== null) {
                    return $defaultPageUuid;
                }
            }
        }

        if (is_string($defaultFrom) && $defaultFrom !== '') {
            if (method_exists($model, 'localized')) {
                $localized = $model->localized($defaultFrom);

                if (filled($localized)) {
                    return $localized;
                }
            }

            $value = data_get($model, $defaultFrom);

            if (filled($value)) {
                return $value;
            }
        }

        return $field->optionValue('default');
    }

    private function normalizeFieldValue(Field $field, mixed $value): mixed
    {
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
                    return $option['value'] === '' ? null : $option['value'];
                }
            }

            return null;
        }

        return is_string($value) ? trim($value) : $value;
    }

    /** @return array<string, mixed> */
    private function fieldConfig(Field $field): array
    {
        return [
            'key' => $field->key(),
            'type' => $field->type(),
            'label' => $field->labelValue(),
            'help' => $field->optionValue('help', ''),
            'rows' => (int) $field->optionValue('rows', 4),
            'size' => $this->imageUploadSize($field->optionValue('size')),
            'fit' => $field->optionValue('fit'),
            'options' => $this->fieldOptions($field),
        ];
    }

    /** @return array<int, array{value: mixed, label: string}> */
    private function fieldOptions(Field $field): array
    {
        if ($field->optionValue('options_source') === 'published_pages') {
            return $this->publishedPageOptions();
        }

        return array_values(array_map(
            static function (mixed $key, mixed $option): array {
                if (is_array($option)) {
                    return [
                        'value' => $option['value'] ?? $key,
                        'label' => (string) ($option['label'] ?? $option['value'] ?? $key),
                    ];
                }

                return [
                    'value' => is_int($key) ? $option : $key,
                    'label' => (string) $option,
                ];
            },
            array_keys((array) $field->optionValue('options', [])),
            (array) $field->optionValue('options', []),
        ));
    }

    /** @return array<int, array{value: string, label: string}> */
    private function publishedPageOptions(): array
    {
        $model = $this->model();
        $pageModel = config('pages.models.page', \IvanBaric\Pages\Models\Page::class);

        /** @var class-string<Model> $pageModel */
        $pages = $pageModel::query()
            ->forTeam(is_numeric($model->getAttribute('team_id')) ? (int) $model->getAttribute('team_id') : null)
            ->published()
            ->ordered()
            ->get();

        return collect([['value' => '', 'label' => __('Odaberite stranicu')]])
            ->merge($pages->map(function (Model $page): array {
                return [
                    'value' => (string) $page->getAttribute('uuid'),
                    'label' => method_exists($page, 'localized')
                        ? (string) $page->localized('title')
                        : (string) data_get($page, 'title', $page->getAttribute('slug')),
                ];
            }))
            ->values()
            ->all();
    }

    private function publishedPageUuidFor(string $pageKey): ?string
    {
        $model = $this->model();
        $pageModel = config('pages.models.page', \IvanBaric\Pages\Models\Page::class);

        /** @var class-string<Model> $pageModel */
        $hasPageKey = \Illuminate\Support\Facades\Schema::hasColumn((new $pageModel)->getTable(), 'page_key');
        $page = $pageModel::query()
            ->forTeam(is_numeric($model->getAttribute('team_id')) ? (int) $model->getAttribute('team_id') : null)
            ->published()
            ->where(function ($query) use ($hasPageKey, $pageKey): void {
                if ($hasPageKey) {
                    $query->where('page_key', $pageKey)->orWhere('slug', $pageKey);

                    return;
                }

                $query->where('slug', $pageKey);
            })
            ->first();

        return $page ? (string) $page->getAttribute('uuid') : null;
    }

    private function imageUploadSize(mixed $size): string
    {
        return match ($size) {
            'small' => 'size-32',
            'medium', null => 'w-full aspect-[4/3]',
            'large' => 'w-full aspect-video',
            default => is_string($size) ? $size : 'w-full aspect-[4/3]',
        };
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

    private function firstTabKey(): string
    {
        $tabs = $this->definition()?->tabsValue() ?? [];

        return $tabs[0]?->key() ?? 'content';
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

    private function authorizeModel(Model $model): void
    {
        $teamId = app(TeamResolver::class)->resolve();

        if ($teamId === null) {
            return;
        }

        abort_unless((int) $model->getAttribute('team_id') === (int) $teamId, 404);
    }

    private function toast(bool $success, string $message): void
    {
        Flux::toast(variant: $success ? 'success' : 'danger', text: $message);
    }
}

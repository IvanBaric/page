<?php

namespace IvanBaric\Pages\Livewire\Admin;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use IvanBaric\Pages\Actions\CreateSectionAction;
use IvanBaric\Pages\Actions\DeleteSectionAction;
use IvanBaric\Pages\Actions\ToggleSectionVisibilityAction;
use IvanBaric\Pages\Actions\UpdateSectionAction;
use IvanBaric\Pages\Models\Page;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class SectionsManager extends Component
{
    #[Locked]
    public Page $page;

    #[Locked]
    public ?string $editingUuid = null;

    public string $locale = 'en';

    public string $type = 'hero';

    /** @var array<string, string> */
    public array $title = ['en' => ''];

    /** @var array<string, string> */
    public array $subtitle = ['en' => ''];

    /** @var array<string, string> */
    public array $description = ['en' => ''];

    public bool $is_visible = true;

    public int $sort_order = 0;

    public ?int $lock_version = null;

    public function mount(Page $page): void
    {
        corexis_authorize('pages.sections.manage', $page);

        $this->page = $page;
        $this->locale = $this->currentLocaleCode();
        $this->resetForm();
    }

    public function edit(string $uuid): void
    {
        $section = $this->page->sections()->where('uuid', $uuid)->firstOrFail();
        $this->editingUuid = $section->uuid;
        $this->type = $section->type;
        $this->title = is_array($section->title) ? $section->title : [$this->locale => (string) $section->title];
        $this->subtitle = is_array($section->subtitle) ? $section->subtitle : [$this->locale => (string) $section->subtitle];
        $this->description = is_array($section->description) ? $section->description : [$this->locale => (string) $section->description];
        $this->is_visible = $section->is_visible;
        $this->sort_order = $section->sort_order;
        $this->lock_version = $section->getLockVersion();
    }

    public function save(): void
    {
        $payload = $this->payload();
        $result = $this->editingUuid
            ? app(UpdateSectionAction::class)->handle($this->editingUuid, $payload)
            : app(CreateSectionAction::class)->handle($this->page->uuid, $payload);

        if (! $result->success) {
            foreach ($result->errors as $field => $messages) {
                if (is_array($messages) && isset($messages[0]) && is_string($messages[0])) {
                    $this->addError((string) $field, $messages[0]);
                }
            }

            Flux::toast(variant: 'danger', text: $result->message);

            return;
        }

        $this->resetForm();
        Flux::toast(variant: 'success', text: $result->message);
    }

    public function toggle(string $uuid, ToggleSectionVisibilityAction $action): void
    {
        $result = $action->handle($this->page->sections()->where('uuid', $uuid)->firstOrFail());

        Flux::toast(variant: $result->success ? 'success' : 'danger', text: $result->message);
    }

    public function archive(string $uuid): void
    {
        $result = app(DeleteSectionAction::class)->handle($uuid);

        Flux::toast(variant: $result->success ? 'success' : 'danger', text: $result->message);
    }

    public function delete(string $uuid): void
    {
        $this->archive($uuid);
    }

    public function render(): View
    {
        return view('pages::livewire.admin.sections-manager', [
            'sections' => $this->page->sections()->ordered()->get(),
        ])->layout(config('pages.admin_ui.layout', 'layouts.app'), [
            'title' => __('Sekcije'),
        ]);
    }

    private function resetForm(): void
    {
        $this->editingUuid = null;
        $this->type = array_key_first(config('pages.section_types', [])) ?: 'hero';
        $this->title = [$this->locale => ''];
        $this->subtitle = [$this->locale => ''];
        $this->description = [$this->locale => ''];
        $this->is_visible = config('pages.defaults.section_visible', true);
        $this->sort_order = 0;
        $this->lock_version = null;
    }

    private function currentLocaleCode(): string
    {
        return corexis_locale_code() ?: config('app.locale', 'en');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'type' => $this->type,
            'title' => array_filter($this->title) === [] ? null : $this->title,
            'subtitle' => array_filter($this->subtitle) === [] ? null : $this->subtitle,
            'description' => array_filter($this->description) === [] ? null : $this->description,
            'is_visible' => $this->is_visible,
            'sort_order' => $this->sort_order,
            'lock_version' => $this->lock_version,
        ];
    }
}

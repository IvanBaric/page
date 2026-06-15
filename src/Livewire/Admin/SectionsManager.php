<?php

namespace IvanBaric\Pages\Livewire\Admin;

use Flux\Flux;
use IvanBaric\Pages\Actions\CreateSectionAction;
use IvanBaric\Pages\Actions\DeleteSectionAction;
use IvanBaric\Pages\Actions\UpdateSectionAction;
use IvanBaric\Pages\Models\Page;
use Livewire\Component;

final class SectionsManager extends Component
{
    public Page $page;

    public ?string $editingUuid = null;

    public string $locale = 'en';

    public string $type = 'hero';

    public array $title = ['en' => ''];

    public array $subtitle = ['en' => ''];

    public array $description = ['en' => ''];

    public bool $is_visible = true;

    public int $sort_order = 0;

    public function mount(Page $page): void
    {
        $this->page = $page;
        $this->locale = app()->getLocale();
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
    }

    public function save(): void
    {
        $payload = $this->payload();
        $result = $this->editingUuid
            ? app(UpdateSectionAction::class)->handle($this->editingUuid, $payload)
            : app(CreateSectionAction::class)->handle($this->page->uuid, $payload);

        if (! $result->successful) {
            foreach ($result->data?->messages() ?? [] as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            Flux::toast(variant: 'danger', text: $result->message);

            return;
        }

        $this->resetForm();
        Flux::toast(variant: 'success', text: $result->message);
    }

    public function toggle(string $uuid): void
    {
        $section = $this->page->sections()->where('uuid', $uuid)->firstOrFail();
        $section->isVisible() ? $section->hide() : $section->show();
    }

    public function delete(string $uuid): void
    {
        $result = app(DeleteSectionAction::class)->handle($uuid);

        Flux::toast(variant: $result->successful ? 'success' : 'danger', text: $result->message);
    }

    public function render()
    {
        return view('pages::livewire.admin.sections-manager', [
            'sections' => $this->page->sections()->ordered()->get(),
        ])->layout(config('pages.admin_ui.layout', 'layouts.app'), [
            'title' => __('Sections'),
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
        ];
    }
}

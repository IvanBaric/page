<?php

namespace IvanBaric\Pages\Livewire\Admin;

use Flux\Flux;
use IvanBaric\Pages\Actions\CreateSectionItemAction;
use IvanBaric\Pages\Actions\DeleteSectionItemAction;
use IvanBaric\Pages\Actions\UpdateSectionItemAction;
use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Models\Section;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class SectionItemsManager extends Component
{
    #[Locked]
    public Page $page;

    #[Locked]
    public Section $section;

    #[Locked]
    public ?string $editingUuid = null;

    public string $locale = 'en';

    public array $title = ['en' => ''];

    public array $description = ['en' => ''];

    public ?string $icon = null;

    public ?string $url = null;

    public bool $is_visible = true;

    public int $sort_order = 0;

    public ?int $lock_version = null;

    public function mount(Page $page, Section $section): void
    {
        corexis_authorize('pages.sections.manage', $section);

        abort_unless($section->page_id === $page->id, 404);

        $this->page = $page;
        $this->section = $section;
        $this->locale = $this->currentLocaleCode();
        $this->resetForm();
    }

    public function edit(string $uuid): void
    {
        $item = $this->section->items()->where('uuid', $uuid)->firstOrFail();
        $this->editingUuid = $item->uuid;
        $this->title = is_array($item->title) ? $item->title : [$this->locale => (string) $item->title];
        $this->description = is_array($item->description) ? $item->description : [$this->locale => (string) $item->description];
        $this->icon = $item->icon;
        $this->url = $item->url;
        $this->is_visible = $item->is_visible;
        $this->sort_order = $item->sort_order;
        $this->lock_version = method_exists($item, 'getLockVersion') ? $item->getLockVersion() : (int) ($item->lock_version ?? 0);
    }

    public function save(): void
    {
        $payload = $this->payload();
        $result = $this->editingUuid
            ? app(UpdateSectionItemAction::class)->handle($this->editingUuid, $payload)
            : app(CreateSectionItemAction::class)->handle($this->section->uuid, $payload);

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
        $item = $this->section->items()->where('uuid', $uuid)->firstOrFail();
        $item->isVisible() ? $item->hide() : $item->show();
    }

    public function archive(string $uuid): void
    {
        $result = app(DeleteSectionItemAction::class)->handle($uuid);

        Flux::toast(variant: $result->successful ? 'success' : 'danger', text: $result->message);
    }

    public function delete(string $uuid): void
    {
        $this->archive($uuid);
    }

    public function render()
    {
        return view('pages::livewire.admin.section-items-manager', [
            'items' => $this->section->items()->ordered()->get(),
        ])->layout(config('pages.admin_ui.layout', 'layouts.app'), [
            'title' => __('Section items'),
        ]);
    }

    private function resetForm(): void
    {
        $this->editingUuid = null;
        $this->title = [$this->locale => ''];
        $this->description = [$this->locale => ''];
        $this->icon = null;
        $this->url = null;
        $this->is_visible = config('pages.defaults.item_visible', true);
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
            'title' => array_filter($this->title) === [] ? null : $this->title,
            'description' => array_filter($this->description) === [] ? null : $this->description,
            'icon' => $this->icon,
            'url' => $this->url,
            'is_visible' => $this->is_visible,
            'sort_order' => $this->sort_order,
            'lock_version' => $this->lock_version,
        ];
    }
}

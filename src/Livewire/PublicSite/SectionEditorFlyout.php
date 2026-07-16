<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Livewire\PublicSite;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use IvanBaric\Pages\Models\Section;
use IvanBaric\Pages\Support\PagesModels;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

final class SectionEditorFlyout extends Component
{
    #[Locked]
    public ?string $sectionUuid = null;

    #[On('pages-open-public-section-editor')]
    public function openSectionEditor(string $sectionUuid): void
    {
        $this->cancelSectionEditor();

        abort_unless(Str::isUuid($sectionUuid), 404);

        $section = $this->findAuthorizedSection($sectionUuid);
        $this->sectionUuid = (string) $section->getAttribute('uuid');

        unset($this->section, $this->editorComponent);

        Flux::modal('public-section-editor')->show();
    }

    public function cancelSectionEditor(): void
    {
        $this->sectionUuid = null;
        unset($this->section, $this->editorComponent);
    }

    #[On('pages-section-editor-saved')]
    public function sectionEditorSaved(string $sectionUuid): void
    {
        if ($this->sectionUuid === null || ! hash_equals($this->sectionUuid, $sectionUuid)) {
            return;
        }

        $this->dispatch('pages-public-section-updated.'.$sectionUuid);
        Flux::modal('public-section-editor')->close();
        $this->cancelSectionEditor();
    }

    #[Computed]
    public function section(): ?Section
    {
        if ($this->sectionUuid === null) {
            return null;
        }

        return $this->findAuthorizedSection($this->sectionUuid)->load('page');
    }

    #[Computed]
    public function editorComponent(): string
    {
        $type = (string) $this->section?->getAttribute('type');
        $alias = (string) data_get(config('pages.section_editor_aliases', []), $type, $type);
        $editors = config('pages.section_editors', []);

        return (string) (
            data_get($editors, $alias)
            ?? data_get($editors, 'default')
            ?? 'admin.pages.sections.configured-items-editor'
        );
    }

    public function render(): View
    {
        return view('pages::livewire.public-site.section-editor-flyout');
    }

    private function findAuthorizedSection(string $sectionUuid): Section
    {
        abort_unless(corexis_actor_id() !== null, 403);

        $tenantId = corexis_tenant_id();
        abort_unless(is_numeric($tenantId), 403);

        $model = PagesModels::section();
        $section = $model::query()
            ->forTenant((int) $tenantId)
            ->where('uuid', $sectionUuid)
            ->first();

        abort_unless($section instanceof Section, 404);
        corexis_authorize('pages.sections.manage', $section);

        return $section;
    }
}

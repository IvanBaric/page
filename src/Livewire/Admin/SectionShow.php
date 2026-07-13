<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Livewire\Admin;

use Illuminate\Contracts\View\View;
use IvanBaric\Pages\Models\Section;
use IvanBaric\Pages\Support\PagesModels;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class SectionShow extends Component
{
    #[Locked]
    public Section $section;

    public function mount(Section $section): void
    {
        $sectionModel = PagesModels::section();
        $section = $sectionModel::query()
            ->where('uuid', $section->getAttribute('uuid'))
            ->first();

        abort_unless($section instanceof Section, 404);

        $this->section = $section->load('page');
    }

    #[Computed]
    public function editorComponent(): string
    {
        $type = (string) $this->section->getAttribute('type');
        $alias = (string) data_get(config('pages.section_editor_aliases', []), $type, $type);
        $editors = config('pages.section_editors', []);

        return (string) (
            data_get($editors, $alias)
            ?? data_get($editors, 'default')
            ?? 'admin.pages.sections.configured-items-editor'
        );
    }

    public function pageIndexRouteName(): string
    {
        return (string) config('pages.admin_routes.page_index', 'admin.pages.index');
    }

    public function pageShowRouteName(): string
    {
        return (string) config('pages.admin_routes.page_show', 'admin.pages.show');
    }

    public function render(): View
    {
        return view('pages::livewire.admin.section-show')
            ->layout('layouts.app', ['title' => $this->section->localized('title')]);
    }
}

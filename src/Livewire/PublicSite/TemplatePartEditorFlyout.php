<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Livewire\PublicSite;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use IvanBaric\Pages\Admin\AdminSectionRegistry;
use IvanBaric\Pages\Support\PagesConfigResolver;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

final class TemplatePartEditorFlyout extends Component
{
    #[Locked]
    public ?string $part = null;

    #[Locked]
    public string $editorTab = 'content';

    #[On('pages-open-public-template-part-editor')]
    public function openTemplatePartEditor(string $part, string $editorTab = 'content'): void
    {
        $this->cancelTemplatePartEditor();

        abort_unless($this->isAllowedPart($part), 404);

        $model = $this->findAuthorizedModel();
        corexis_authorize('pages.update', $model);

        $this->part = $part;
        $this->editorTab = $this->resolveEditorTab($editorTab);
        unset($this->model);

        Flux::modal('public-template-part-editor')->show();
    }

    public function cancelTemplatePartEditor(): void
    {
        $this->part = null;
        $this->editorTab = 'content';
        unset($this->model);
    }

    #[On('pages-singleton-editor-saved')]
    public function templatePartEditorSaved(string $definitionKey): void
    {
        if ($this->part === null || ! hash_equals($this->definitionKey(), $definitionKey)) {
            return;
        }

        $this->dispatch('pages-public-template-part-updated.'.$this->part);
        unset($this->model);
    }

    #[Computed]
    public function model(): ?Model
    {
        return $this->part === null ? null : $this->findAuthorizedModel();
    }

    #[Computed]
    public function title(): string
    {
        return match ($this->part) {
            'header' => __('Zaglavlje'),
            'footer' => __('Podnožje'),
            'sections' => __('Sekcije'),
            default => __('Uredi dio stranice'),
        };
    }

    public function definitionKey(): string
    {
        abort_unless($this->part !== null && $this->isAllowedPart($this->part), 404);

        return (string) config('pages.admin_index.template_parts.'.$this->part.'.definition_key', 'template_'.$this->part);
    }

    public function render(): View
    {
        return view('pages::livewire.public-site.template-part-editor-flyout');
    }

    private function findAuthorizedModel(): Model
    {
        abort_unless(corexis_actor_id() !== null, 403);

        $tenantId = corexis_tenant_id();
        abort_unless(is_numeric($tenantId), 403);

        $modelClass = PagesConfigResolver::singletonModel();
        abort_unless($modelClass !== null, 404);

        $model = new $modelClass;
        $query = $modelClass::query();

        if (Schema::hasColumn($model->getTable(), 'team_id')) {
            $query->where('team_id', (int) $tenantId);
        }

        $activeScope = (string) config('pages.admin_index.singleton_active_scope', 'active');

        if ($activeScope !== '' && method_exists($modelClass, 'scope'.ucfirst($activeScope))) {
            $query->{$activeScope}();
        }

        $resolved = $query->first();
        abort_unless($resolved instanceof Model, 404);
        corexis_authorize('pages.update', $resolved);

        return $resolved;
    }

    private function isAllowedPart(string $part): bool
    {
        return in_array($part, array_keys((array) config('pages.admin_index.template_parts', [])), true);
    }

    private function resolveEditorTab(string $requestedTab): string
    {
        $definition = app(AdminSectionRegistry::class)->get($this->definitionKey());
        $tabs = $definition?->tabsValue() ?? [];
        $allowedTabs = array_map(static fn ($tab): string => $tab->key(), $tabs);

        if (in_array($requestedTab, $allowedTabs, true)) {
            return $requestedTab;
        }

        return $allowedTabs[0] ?? 'content';
    }
}

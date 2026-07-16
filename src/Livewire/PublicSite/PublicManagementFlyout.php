<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Livewire\PublicSite;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use IvanBaric\Pages\Support\PublicManagementRegistry;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class PublicManagementFlyout extends Component
{
    #[Locked]
    public ?string $panel = null;

    public function mount(): void
    {
        $tenantId = corexis_tenant_id();
        $hasTenant = (is_int($tenantId) && $tenantId > 0)
            || (is_string($tenantId) && trim($tenantId) !== '' && trim($tenantId) !== '0');

        abort_unless(corexis_actor_id() !== null && $hasTenant, 403);
    }

    /** @return array<string, string> */
    protected function getListeners(): array
    {
        return [
            (string) config('pages.public_management.event', 'pages-open-public-management') => 'open',
        ];
    }

    public function open(string $panel, PublicManagementRegistry $registry): void
    {
        $definition = $registry->get($panel);
        abort_unless($definition !== null, 404);

        if ($definition->permission !== null) {
            corexis_authorize($definition->permission, corexis_tenant_id());
        }

        if ($definition->event !== null) {
            $this->dispatch($definition->event);

            return;
        }

        $this->panel = $definition->key;
        Flux::modal((string) config('pages.public_management.modal_name', 'public-management'))->show();
    }

    public function render(PublicManagementRegistry $registry): View
    {
        $definition = $this->panel === null ? null : $registry->get($this->panel);

        if ($definition?->permission !== null) {
            corexis_authorize($definition->permission, corexis_tenant_id());
        }

        return view('pages::livewire.public-site.public-management-flyout', [
            'definition' => $definition,
            'panelData' => $definition === null ? [] : $registry->dataFor($definition),
        ]);
    }
}

<section class="w-full space-y-8">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Pages') }}</flux:heading>
            <flux:subheading>{{ __('Manage reusable CMS pages and page sections.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" :href="route(config('pages.admin.name_prefix', 'admin.pages.').'create')" wire:navigate>
            {{ __('Create page') }}
        </flux:button>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" type="search" />
        <flux:select wire:model.live="status" :label="__('Status')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (config('pages.statuses', []) as $value => $statusConfig)
                <flux:select.option :value="$value">{{ __($statusConfig['label'] ?? $value) }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="template" :label="__('Template')">
            <flux:select.option value="">{{ __('All templates') }}</flux:select.option>
            @foreach (config('pages.templates', []) as $value => $templateConfig)
                <flux:select.option :value="$value">{{ __($templateConfig['label'] ?? $value) }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>
    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Template') }}</flux:table.column>
            <flux:table.column>{{ __('Home') }}</flux:table.column>
            <flux:table.column>{{ __('Published') }}</flux:table.column>
            <flux:table.column>{{ __('Sort') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->pages as $page)
                <flux:table.row :key="$page->uuid">
                    <flux:table.cell>
                        <div class="grid gap-1">
                            <flux:link :href="route(config('pages.admin.name_prefix', 'admin.pages.').'edit', ['page' => $page->uuid])" wire:navigate>{{ $page->localized('title') }}</flux:link>
                            <flux:text class="text-xs">{{ $page->slug }}</flux:text>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ __(config('pages.statuses.'.$page->status.'.label', $page->status)) }}</flux:table.cell>
                    <flux:table.cell>{{ __(config('pages.templates.'.$page->template.'.label', $page->template)) }}</flux:table.cell>
                    <flux:table.cell>{{ $page->is_home ? __('Yes') : __('No') }}</flux:table.cell>
                    <flux:table.cell>{{ $page->published_at?->format('Y-m-d H:i') ?? __('Not published') }}</flux:table.cell>
                    <flux:table.cell>{{ $page->sort_order }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:button size="sm" :href="route(config('pages.admin.name_prefix', 'admin.pages.').'edit', ['page' => $page->uuid])" wire:navigate>{{ __('Edit') }}</flux:button>
                            <flux:button size="sm" :href="route(config('pages.admin.name_prefix', 'admin.pages.').'sections', ['page' => $page->uuid])" wire:navigate>{{ __('Sections') }}</flux:button>
                            <flux:button size="sm" wire:click="publish('{{ $page->uuid }}')">{{ $page->isPublished() ? __('Unpublish') : __('Publish') }}</flux:button>
                            <flux:button size="sm" variant="danger" wire:confirm="{{ __('Delete this page?') }}" wire:click="delete('{{ $page->uuid }}')">{{ __('Delete') }}</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">{{ __('No pages found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    {{ $this->pages->links() }}
</section>

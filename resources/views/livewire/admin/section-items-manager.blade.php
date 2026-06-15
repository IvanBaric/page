<section class="w-full space-y-8">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Section items') }}</flux:heading>
            <flux:subheading>{{ $section->localized('title') ?: __(config('pages.section_types.'.$section->type.'.label', $section->type)) }}</flux:subheading>
        </div>
        <flux:button :href="route(config('pages.admin.name_prefix', 'admin.pages.').'sections', ['page' => $page->uuid])" wire:navigate>
            {{ __('Back') }}
        </flux:button>
    </div>
    <form wire:submit="save" class="space-y-12">
        <div class="grid gap-6 md:grid-cols-2">
            <flux:input wire:model="title.{{ $locale }}" :label="__('Title')" type="text" />
            <flux:input wire:model="icon" :label="__('Icon')" type="text" />
            <flux:input wire:model="url" :label="__('URL')" type="text" />
            <flux:input wire:model="sort_order" :label="__('Sort order')" type="number" min="0" />
            <flux:textarea wire:model="description.{{ $locale }}" :label="__('Description')" rows="3" />
            <flux:checkbox wire:model="is_visible" :label="__('Visible')" />
        </div>
        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">
                {{ $editingUuid ? __('Update item') : __('Add item') }}
            </flux:button>
        </div>
    </form>
    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Icon') }}</flux:table.column>
            <flux:table.column>{{ __('Visible') }}</flux:table.column>
            <flux:table.column>{{ __('Sort') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($items as $item)
                <flux:table.row :key="$item->uuid">
                    <flux:table.cell>{{ $item->localized('title') ?: __('Untitled item') }}</flux:table.cell>
                    <flux:table.cell>{{ $item->icon }}</flux:table.cell>
                    <flux:table.cell>{{ $item->is_visible ? __('Yes') : __('No') }}</flux:table.cell>
                    <flux:table.cell>{{ $item->sort_order }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:button size="sm" wire:click="edit('{{ $item->uuid }}')">{{ __('Edit') }}</flux:button>
                            <flux:button size="sm" wire:click="toggle('{{ $item->uuid }}')">{{ $item->is_visible ? __('Hide') : __('Show') }}</flux:button>
                            <flux:button size="sm" variant="danger" wire:confirm="{{ __('Delete this item?') }}" wire:click="delete('{{ $item->uuid }}')">{{ __('Delete') }}</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">{{ __('No items found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>

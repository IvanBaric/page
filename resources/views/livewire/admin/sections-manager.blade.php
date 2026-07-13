<section class="w-full space-y-8">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Sections') }}</flux:heading>
            <flux:subheading>{{ $page->localized('title') }}</flux:subheading>
        </div>
        <flux:button :href="route(config('pages.admin.name_prefix', 'admin.pages.').'index')" wire:navigate>
            {{ __('Back') }}
        </flux:button>
    </div>
    <form wire:submit="save" wire:loading.class="admin-panel-content-loading" wire:target="save" class="relative space-y-12">
        <x-admin-ui::loading-overlay target="save" :text="__('Spremanje...')" />

        <div class="grid gap-6 md:grid-cols-2">
            <flux:select wire:model="type" variant="listbox" :label="__('Section type')">
                @foreach (config('pages.section_types', []) as $value => $typeConfig)
                    <flux:select.option :value="$value">{{ __($typeConfig['label'] ?? $value) }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="sort_order" :label="__('Sort order')" type="number" min="0" />
            <flux:input wire:model="title.{{ $locale }}" :label="__('Title')" type="text" />
            <flux:input wire:model="subtitle.{{ $locale }}" :label="__('Subtitle')" type="text" />
            <flux:textarea wire:model="description.{{ $locale }}" :label="__('Description')" rows="3" />
            <flux:checkbox wire:model="is_visible" :label="__('Visible')" />
        </div>
        <div class="flex items-center gap-3">
            <x-admin-ui::submit-button target="save">
                {{ $editingUuid ? __('Update section') : __('Add section') }}
            </x-admin-ui::submit-button>
        </div>
    </form>
    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Type') }}</flux:table.column>
            <flux:table.column>{{ __('Visible') }}</flux:table.column>
            <flux:table.column>{{ __('Sort') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($sections as $section)
                <flux:table.row :key="$section->uuid">
                    <flux:table.cell>{{ $section->localized('title') ?: __('Untitled section') }}</flux:table.cell>
                    <flux:table.cell>{{ __(config('pages.section_types.'.$section->type.'.label', $section->type)) }}</flux:table.cell>
                    <flux:table.cell>{{ $section->is_visible ? __('Yes') : __('No') }}</flux:table.cell>
                    <flux:table.cell>{{ $section->sort_order }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:button size="sm" wire:click="edit('{{ $section->uuid }}')">{{ __('Edit') }}</flux:button>
                            <flux:button size="sm" :href="route(config('pages.admin.name_prefix', 'admin.pages.').'sections.items', ['page' => $page->uuid, 'section' => $section->uuid])" wire:navigate>{{ __('Items') }}</flux:button>
                            <flux:button size="sm" wire:click="toggle('{{ $section->uuid }}')">{{ $section->is_visible ? __('Hide') : __('Show') }}</flux:button>
                            <flux:button size="sm" variant="danger" wire:confirm="{{ __('Arhivirati sekciju?') }}" wire:click="archive('{{ $section->uuid }}')">{{ __('Arhiviraj') }}</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">{{ __('No sections found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>

<section class="w-full space-y-8">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ $page?->exists ? __('Uredi stranicu') : __('Nova stranica') }}</flux:heading>
            <flux:subheading>{{ __('Koristite istu strukturu stranice za svaki javni izgled.') }}</flux:subheading>
        </div>
        <flux:button :href="route(config('pages.admin.name_prefix', 'admin.pages.').'index')" wire:navigate>
            {{ __('Natrag') }}
        </flux:button>
    </div>
    <form wire:submit="save" wire:loading.class="admin-panel-content-loading" wire:target="save" class="relative space-y-12">
        <x-admin-ui::loading-overlay target="save" :text="__('Spremanje...')" />

        <div class="grid gap-6">
            <flux:input wire:model="title.{{ $locale }}" :label="__('Naslov')" type="text" data-required autofocus />
            <flux:textarea wire:model="excerpt.{{ $locale }}" :label="__('Kratki opis')" rows="3" />
            <flux:textarea wire:model="content.{{ $locale }}" :label="__('Sadržaj')" rows="10" />
        </div>
        <div class="grid gap-6 md:grid-cols-2">
            <flux:select wire:model="status" variant="listbox" :label="__('Status')" data-required>
                @foreach (config('pages.statuses', []) as $value => $statusConfig)
                    <flux:select.option :value="$value">{{ __($statusConfig['label'] ?? $value) }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="template" variant="listbox" :label="__('Javni izgled')">
                @foreach (config('pages.templates', []) as $value => $templateConfig)
                    <flux:select.option :value="$value">{{ __($templateConfig['label'] ?? $value) }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="published_at" :label="__('Datum objave')" type="datetime-local" />
            <flux:input wire:model="sort_order" :label="__('Redoslijed')" type="number" min="0" />
            <flux:checkbox wire:model="is_home" :label="__('Naslovnica')" />
            <flux:checkbox wire:model="is_published" :label="__('Objavljeno')" />
        </div>
        <div class="flex items-center gap-3">
            <x-admin-ui::submit-button target="save">
                {{ __('Spremi') }}
            </x-admin-ui::submit-button>
            <flux:button :href="route(config('pages.admin.name_prefix', 'admin.pages.').'index')" wire:navigate>
                {{ __('Odustani') }}
            </flux:button>
        </div>
    </form>
</section>

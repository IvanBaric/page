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
            @if (! $is_home)
                <flux:select wire:model="parent_uuid" :label="__('Nadređena stranica')" :description="__('Odaberite razinu i punu putanju u izborniku.')">
                    @include('pages::livewire.partials.hierarchy-parent-options', ['options' => $this->parentPageOptions])
                </flux:select>
            @endif
            <flux:checkbox wire:model.live="is_home" :label="__('Naslovnica')" />
            <flux:checkbox wire:model="is_published" :label="__('Objavljeno')" />
        </div>
        @if (! $is_home)
            <div class="grid gap-5 border-y border-zinc-200 py-6 dark:border-zinc-800">
                <flux:radio.group wire:model.live="navigation_type" variant="segmented" :label="__('Vrsta stavke izbornika')">
                    <flux:radio value="page" :label="__('Stranica')" icon="document-text" />
                    <flux:radio value="url" :label="__('Poveznica')" icon="link" />
                </flux:radio.group>

                @if ($navigation_type === 'url')
                    <flux:input wire:model="navigation_url" :label="__('URL poveznice')" :description="__('Unesite punu web adresu, relativnu putanju, e-poštu ili telefonsku poveznicu.')" placeholder="https://primjer.hr" inputmode="url" data-required />
                    <flux:checkbox wire:model="navigation_new_tab" :label="__('Otvori u novoj kartici')" />
                @endif
            </div>
        @endif
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

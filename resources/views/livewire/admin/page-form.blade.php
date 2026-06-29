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
    <form wire:submit="save" class="space-y-12">
        <div class="grid gap-6">
            <flux:input wire:model="title.{{ $locale }}" :label="__('Naslov')" type="text" required autofocus />
            <flux:textarea wire:model="excerpt.{{ $locale }}" :label="__('Kratki opis')" rows="3" />
            <flux:textarea wire:model="content.{{ $locale }}" :label="__('Sadržaj')" rows="10" />
        </div>
        <div class="grid gap-6 md:grid-cols-2">
            <flux:select wire:model="status" :label="__('Status')">
                @foreach (config('pages.statuses', []) as $value => $statusConfig)
                    <flux:select.option :value="$value">{{ __($statusConfig['label'] ?? $value) }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="template" :label="__('Javni izgled')">
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
            <flux:button variant="primary" type="submit">
                {{ __('Spremi') }}
            </flux:button>
            <flux:button :href="route(config('pages.admin.name_prefix', 'admin.pages.').'index')" wire:navigate>
                {{ __('Odustani') }}
            </flux:button>
        </div>
    </form>
</section>

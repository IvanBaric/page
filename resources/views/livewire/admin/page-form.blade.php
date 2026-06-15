<section class="w-full space-y-8">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ $page?->exists ? __('Edit page') : __('Create page') }}</flux:heading>
            <flux:subheading>{{ __('Use one generic page structure for every template.') }}</flux:subheading>
        </div>
        <flux:button :href="route(config('pages.admin.name_prefix', 'admin.pages.').'index')" wire:navigate>
            {{ __('Back') }}
        </flux:button>
    </div>
    <form wire:submit="save" class="space-y-12">
        <div class="grid gap-6">
            <flux:input wire:model="title.{{ $locale }}" :label="__('Title')" type="text" required autofocus />
            <flux:textarea wire:model="excerpt.{{ $locale }}" :label="__('Excerpt')" rows="3" />
            <flux:textarea wire:model="content.{{ $locale }}" :label="__('Content')" rows="10" />
        </div>
        <div class="grid gap-6 md:grid-cols-2">
            <flux:select wire:model="status" :label="__('Status')">
                @foreach (config('pages.statuses', []) as $value => $statusConfig)
                    <flux:select.option :value="$value">{{ __($statusConfig['label'] ?? $value) }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="template" :label="__('Template')">
                @foreach (config('pages.templates', []) as $value => $templateConfig)
                    <flux:select.option :value="$value">{{ __($templateConfig['label'] ?? $value) }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="published_at" :label="__('Published at')" type="datetime-local" />
            <flux:input wire:model="sort_order" :label="__('Sort order')" type="number" min="0" />
            <flux:checkbox wire:model="is_home" :label="__('Home page')" />
            <flux:checkbox wire:model="is_published" :label="__('Published')" />
        </div>
        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">
                {{ __('Save') }}
            </flux:button>
            <flux:button :href="route(config('pages.admin.name_prefix', 'admin.pages.').'index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</section>

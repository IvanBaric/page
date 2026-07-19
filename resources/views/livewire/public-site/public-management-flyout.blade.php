<div x-on:niva-public-theme-updated.window="window.location.reload()">
    <x-admin-ui::action-loading target="__dispatch" :text="__('pages::pages.opening')" />

    <flux:modal
        :name="(string) config('pages.public_management.modal_name', 'public-management')"
        flyout
        variant="floating"
        :class="(string) config('pages.public_management.modal_class', 'w-full md:w-2xl xl:w-5xl')"
    >
        @if ($definition)
            <div class="mb-4 flex items-center justify-between gap-3 border-b border-zinc-200 pb-4 dark:border-zinc-800">
                <div class="flex min-w-0 items-center gap-3">
                    <flux:icon :icon="$definition->icon" class="size-5 shrink-0 text-zinc-500" />
                    <flux:heading size="lg" class="min-w-0 truncate">{{ $definition->translatedTitle() }}</flux:heading>
                </div>

                @if ($definition->key === 'website')
                    <x-admin-ui::submit-button target="save" form="public-website-theme-form" class="shrink-0">
                        {{ __('Spremi promjene') }}
                    </x-admin-ui::submit-button>
                @endif
            </div>

            @if ($definition->component)
                @livewire($definition->component, $definition->parameters, key('public-management-'.$definition->key))
            @elseif ($definition->view)
                @include($definition->view, $panelData)
            @endif
        @endif
    </flux:modal>
</div>

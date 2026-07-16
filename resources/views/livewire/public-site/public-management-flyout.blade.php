<div>
    <x-admin-ui::action-loading target="__dispatch" :text="__('pages::pages.opening')" />

    <flux:modal
        :name="(string) config('pages.public_management.modal_name', 'public-management')"
        flyout
        variant="floating"
        :class="(string) config('pages.public_management.modal_class', 'w-full md:w-2xl xl:w-5xl')"
    >
        @if ($definition)
            <div class="mb-4 flex items-center gap-3 border-b border-zinc-200 pb-4 dark:border-zinc-800">
                <flux:icon :icon="$definition->icon" class="size-5 text-zinc-500" />
                <flux:heading size="lg">{{ $definition->translatedTitle() }}</flux:heading>
            </div>

            @if ($definition->component)
                @livewire($definition->component, $definition->parameters, key('public-management-'.$definition->key))
            @elseif ($definition->view)
                @include($definition->view, $panelData)
            @endif
        @endif
    </flux:modal>
</div>

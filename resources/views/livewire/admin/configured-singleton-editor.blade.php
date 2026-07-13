@php
    $activeEditorTab = collect($this->editorTabs())->firstWhere('key', $tab) ?? $this->editorTabs()[0] ?? [
        'label' => __('Sadržaj'),
        'heading' => __('Sadržaj'),
        'description' => '',
    ];
@endphp

<div
    class="space-y-6"
    x-init="$dispatch('pages-editor-tab-changed', @js(['title' => $activeEditorTab['label'], 'description' => $activeEditorTab['description']]))"
>
    <flux:tab.group>
        <flux:tabs wire:model.live="tab" variant="segmented" scrollable>
            @foreach ($this->editorTabs() as $editorTab)
                <flux:tab
                    :name="$editorTab['key']"
                    :icon="$editorTab['icon']"
                    x-on:click="$dispatch('pages-editor-tab-changed', @js(['title' => $editorTab['label'], 'description' => $editorTab['description']]))"
                >
                    {{ $editorTab['label'] }}
                </flux:tab>
            @endforeach
        </flux:tabs>

        @if ($this->hasFormTab())
            <flux:tab.panel :name="$this->formTabKey()" class="pt-6">
                @php($hasSidebarFields = $this->sidebarFormFields() !== [])

                <form
                    wire:submit="save"
                    wire:loading.class="admin-panel-content-loading"
                    wire:target="save,saveAllChanges,uploads"
                    @class(['relative min-w-0', 'max-w-3xl' => ! $hasSidebarFields])
                >
                    <x-admin-ui::loading-overlay target="save,saveAllChanges,uploads" :text="__('Spremanje...')" />

                    <section class="admin-panel overflow-hidden">
                        <div class="border-b border-zinc-200 bg-zinc-50 px-6 py-5 dark:border-zinc-800 dark:bg-zinc-900/60">
                            <h2 class="admin-panel-title">{{ $this->formHeading() }}</h2>
                            <p class="admin-panel-description mt-1">{{ $this->formDescription() }}</p>
                        </div>

                        <div @class(['grid min-w-0 gap-6 p-4 sm:p-6', 'lg:grid-cols-[minmax(0,1fr)_22rem]' => $hasSidebarFields])>
                            <div @class(['min-w-0 space-y-5', 'max-w-2xl' => ! $hasSidebarFields])>
                                @foreach ($this->mainFormFields() as $field)
                                    @include('pages::livewire.admin.partials.configured-field-control', [
                                        'field' => $field,
                                        'wireModel' => 'form.'.$field['key'],
                                        'booleanControl' => 'switch',
                                    ])
                                @endforeach
                            </div>

                            @if ($hasSidebarFields)
                                <aside class="min-w-0 space-y-5">
                                    @foreach ($this->sidebarFormFields() as $field)
                                        <x-admin-ui::media-upload
                                            wire-model="uploads.{{ $field['key'] }}"
                                            :file="$uploads[$field['key']] ?? null"
                                            :existing-url="$this->imageUrl($field['key'])"
                                            :label="$field['label']"
                                            :help="$field['help']"
                                            :size="$field['size']"
                                            :fit="$field['fit']"
                                            :confirm-remove="true"
                                            :delete-immediately="true"
                                            remove-action="removeImage('{{ $field['key'] }}')"
                                        />
                                    @endforeach

                                </aside>
                            @endif
                        </div>
                    </section>

                </form>
            </flux:tab.panel>
        @endif

        @if ($this->hasLayoutTab())
            <flux:tab.panel :name="$this->layoutTabKey()" class="pt-6">
                <section class="admin-panel">
                    <div class="admin-panel-header">
                        <div>
                            <h2 class="admin-panel-title">{{ $this->layoutHeading() }}</h2>
                            <p class="admin-panel-description">{{ $this->layoutDescription() }}</p>
                        </div>
                    </div>

                    <form wire:submit="saveLayout" wire:loading.class="admin-panel-content-loading" wire:target="saveLayout,saveAllChanges" class="relative min-w-0 space-y-6 p-4 sm:p-6">
                        <x-admin-ui::loading-overlay target="saveLayout,saveAllChanges" :text="__('Spremanje...')" />
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            @foreach ($this->layoutVariants() as $variant)
                                <label @class([
                                    'cursor-pointer rounded-xl border p-4 transition',
                                    'border-pink-500 bg-pink-50 ring-1 ring-pink-200 dark:border-pink-400 dark:bg-pink-500/10' => $layoutVariant === $variant['value'],
                                    'border-zinc-200 bg-white hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700' => $layoutVariant !== $variant['value'],
                                ])>
                                    <input type="radio" wire:model.live="layoutVariant" value="{{ $variant['value'] }}" class="sr-only">

                                    <span class="mb-4 block h-28 overflow-hidden rounded-lg bg-pink-50 p-3 ring-1 ring-pink-200 dark:bg-zinc-900 dark:ring-white/10">
                                        @include('pages::livewire.admin.partials.layout-variant-preview', ['variant' => $variant])
                                    </span>

                                    <span class="block text-sm font-semibold text-zinc-950 dark:text-white">{{ $variant['label'] }}</span>
                                    <span class="mt-1 block text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ $variant['description'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </form>
                </section>
            </flux:tab.panel>
        @endif

        @if ($this->hasSettingsTab())
            <flux:tab.panel :name="$this->settingsTabKey()" class="pt-6">
                @include('pages::livewire.admin.partials.configured-settings-panel')
            </flux:tab.panel>
        @endif
    </flux:tab.group>
</div>

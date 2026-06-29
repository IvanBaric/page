<div class="space-y-6">
    <flux:tab.group>
        <flux:tabs wire:model.live="tab" variant="segmented">
            @foreach ($this->editorTabs() as $editorTab)
                <flux:tab :name="$editorTab['key']" :icon="$editorTab['icon']">{{ $editorTab['label'] }}</flux:tab>
            @endforeach
        </flux:tabs>

        @if ($this->hasFormTab())
            <flux:tab.panel :name="$this->formTabKey()" class="pt-6">
                @php($hasSidebarFields = $this->sidebarFormFields() !== [])

                <form
                    wire:submit="save"
                    wire:loading.class="admin-panel-content-loading"
                    wire:target="save,uploads"
                    @class(['relative', 'max-w-3xl' => ! $hasSidebarFields])
                >
                    <x-admin-ui::loading-overlay target="save,uploads" :text="__('Spremam promjene...')" />

                    <section class="admin-panel overflow-hidden">
                        <div class="border-b border-zinc-200 bg-zinc-50 px-6 py-5 dark:border-zinc-800 dark:bg-zinc-900/60">
                            <h2 class="admin-panel-title">{{ $this->formHeading() }}</h2>
                            <p class="admin-panel-description mt-1">{{ $this->formDescription() }}</p>
                        </div>

                        <div @class(['grid gap-6 p-6', 'lg:grid-cols-[minmax(0,1fr)_22rem]' => $hasSidebarFields])>
                            <div @class(['space-y-5', 'max-w-2xl' => ! $hasSidebarFields])>
                                @foreach ($this->mainFormFields() as $field)
                                    @if ($field['type'] === 'textarea')
                                        <flux:textarea wire:model="form.{{ $field['key'] }}" :label="$field['label']" :rows="$field['rows']" />
                                    @elseif ($field['type'] === 'select')
                                        <flux:select wire:model="form.{{ $field['key'] }}" :label="$field['label']">
                                            @foreach ($field['options'] as $option)
                                                <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    @elseif ($field['type'] === 'boolean')
                                        <flux:switch wire:model="form.{{ $field['key'] }}" :label="$field['label']" />
                                    @else
                                        <flux:input wire:model="form.{{ $field['key'] }}" :label="$field['label']" :type="$field['type'] === 'url' ? 'url' : ($field['type'] === 'number' ? 'number' : 'text')" />
                                    @endif
                                @endforeach
                            </div>

                            @if ($hasSidebarFields)
                                <aside class="space-y-5">
                                    @foreach ($this->sidebarFormFields() as $field)
                                        <x-admin-ui::media-upload
                                            wire-model="uploads.{{ $field['key'] }}"
                                            :file="$uploads[$field['key']] ?? null"
                                            :existing-url="$this->imageUrl($field['key'])"
                                            :label="$field['label']"
                                            :help="$field['help']"
                                            :size="$field['size']"
                                            :fit="$field['fit']"
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

                    <form wire:submit="saveLayout" class="space-y-6 p-6">
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
                <section class="admin-panel">
                    <div class="admin-panel-header">
                        <div>
                            <h2 class="admin-panel-title">{{ $this->settingsHeading() }}</h2>
                            <p class="admin-panel-description">{{ $this->settingsDescription() }}</p>
                        </div>
                    </div>
                    <form wire:submit="saveSettings" class="space-y-6 p-6">
                        <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach ($this->settingsFields() as $field)
                                <div class="grid gap-4 py-4 first:pt-0 last:pb-0 md:grid-cols-[minmax(0,1fr)_18rem] md:items-center">
                                    <div>
                                        <h3 class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $field['label'] }}</h3>
                                        @if ($field['help'] !== '')
                                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $field['help'] }}</p>
                                        @endif
                                    </div>
                                    @if ($field['type'] === 'select')
                                        <flux:select wire:model="settingsForm.{{ $field['key'] }}" class="w-full">
                                            @foreach ($field['options'] as $option)
                                                <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    @elseif ($field['type'] === 'boolean')
                                        <flux:checkbox wire:model="settingsForm.{{ $field['key'] }}" :label="$field['label']" />
                                    @elseif ($field['type'] === 'textarea')
                                        <flux:textarea wire:model="settingsForm.{{ $field['key'] }}" :rows="$field['rows']" class="w-full" />
                                    @else
                                        <flux:input wire:model="settingsForm.{{ $field['key'] }}" :type="$field['type'] === 'number' ? 'number' : 'text'" class="w-full" />
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </form>
                </section>
            </flux:tab.panel>
        @endif
    </flux:tab.group>
</div>

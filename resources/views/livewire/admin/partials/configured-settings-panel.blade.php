@php
    $settingsPanel ??= [
        'heading' => $this->settingsHeading(),
        'description' => $this->settingsDescription(),
        'fields' => $this->settingsFields(),
    ];
@endphp

<section class="admin-panel">
    <div class="admin-panel-header">
        <div>
            <h2 class="admin-panel-title">{{ $settingsPanel['heading'] }}</h2>
            <p class="admin-panel-description">
                {{ $settingsPanel['description'] }}
            </p>
        </div>
    </div>

    <form wire:submit="saveSettings" wire:loading.class="admin-panel-content-loading" wire:target="saveSettings,saveAllChanges" class="relative min-w-0 space-y-6 p-4 sm:p-6">
        <x-admin-ui::loading-overlay target="saveSettings,saveAllChanges" :text="__('Spremanje...')" />
        <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
            @foreach ($settingsPanel['fields'] as $field)
                @php
                    $isFieldRequired = in_array('required', array_map('strval', (array) ($field['rules'] ?? [])), true);
                @endphp

                @if ($field['type'] === 'select' && ($field['display'] ?? '') === 'preview_cards')
                    <div class="py-4 first:pt-0 last:pb-0">
                        <div>
                            <h3 class="text-sm font-semibold text-zinc-950 dark:text-white">
                                {{ $field['label'] }}
                                @if ($isFieldRequired)
                                    <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                                @endif
                            </h3>
                            @if ($field['help'] !== '')
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $field['help'] }}</p>
                            @endif
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            @foreach ($field['options'] as $option)
                                @php
                                    $optionVariant = [
                                        'value' => $option['value'],
                                        'label' => $option['label'],
                                        'description' => (string) ($option['description'] ?? ''),
                                        'options' => [
                                            'preview' => (string) ($option['preview'] ?? data_get($option, 'options.preview', $option['value'])),
                                        ],
                                    ];
                                    $selectedValue = (string) data_get($this->settingsForm, $field['key']);
                                @endphp

                                <label @class([
                                    'cursor-pointer rounded-xl border p-4 transition',
                                    'border-pink-500 bg-pink-50 ring-1 ring-pink-200 dark:border-pink-400 dark:bg-pink-500/10' => $selectedValue === (string) $option['value'],
                                    'border-zinc-200 bg-white hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700' => $selectedValue !== (string) $option['value'],
                                ])>
                                    <input type="radio" wire:model.live="settingsForm.{{ $field['key'] }}" value="{{ $option['value'] }}" class="sr-only">

                                    <span class="relative mb-4 block h-28 overflow-hidden rounded-lg bg-pink-50 p-3 ring-1 ring-pink-200 dark:bg-zinc-900 dark:ring-white/10">
                                        @include('pages::livewire.admin.partials.layout-variant-preview', ['variant' => $optionVariant])
                                    </span>

                                    <span class="block text-sm font-semibold text-zinc-950 dark:text-white">{{ $option['label'] }}</span>
                                    @if (($option['description'] ?? '') !== '')
                                        <span class="mt-1 block text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ $option['description'] }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="grid gap-4 py-4 first:pt-0 last:pb-0 md:grid-cols-[minmax(0,1fr)_18rem] md:items-center">
                        <div>
                            <h3 class="text-sm font-semibold text-zinc-950 dark:text-white">
                                {{ $field['label'] }}
                                @if ($isFieldRequired)
                                    <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                                @endif
                            </h3>
                            @if ($field['help'] !== '')
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $field['help'] }}</p>
                            @endif
                        </div>

                        @if ($field['type'] === 'select')
                            <flux:select wire:model="settingsForm.{{ $field['key'] }}" variant="listbox" class="w-full">
                                @foreach ($field['options'] as $option)
                                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @elseif ($field['type'] === 'icon' && (bool) ($field['picker'] ?? true))
                            <livewire:admin-ui.icon-picker
                                wire:model="settingsForm.{{ $field['key'] }}"
                                :label="$field['label']"
                                :nullable="true"
                            />
                        @elseif ($field['type'] === 'boolean')
                            <flux:checkbox wire:model="settingsForm.{{ $field['key'] }}" :label="$field['label']" />
                        @elseif ($field['type'] === 'textarea')
                            <flux:textarea wire:model="settingsForm.{{ $field['key'] }}" :rows="$field['rows']" class="w-full" />
                        @else
                            <flux:input wire:model="settingsForm.{{ $field['key'] }}" :type="in_array($field['type'], ['date', 'time', 'number'], true) ? $field['type'] : 'text'" class="w-full" />
                        @endif
                    </div>
                @endif
            @endforeach
        </div>

    </form>
</section>

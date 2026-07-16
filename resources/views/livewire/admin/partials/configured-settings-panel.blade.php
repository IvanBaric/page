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
        @if (collect($settingsPanel['fields'])->contains('key', 'content_source') && $this->hasHiddenDirectGalleryMedia())
            @php
                $hiddenPhotos = $this->hiddenDirectGalleryMedia();
            @endphp
            <flux:callout variant="warning" icon="exclamation-triangle">
                <flux:callout.heading>{{ __('Sekcija još ima izravno vezane fotografije') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ trans_choice('{1} Jedna fotografija ostaje spremljena uz sekciju, ali se ne prikazuje dok koristite postojeće galerije.|[2,*] :count fotografija ostaje spremljeno uz sekciju, ali se ne prikazuju dok koristite postojeće galerije.', count($hiddenPhotos), ['count' => count($hiddenPhotos)]) }}
                </flux:callout.text>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach (array_slice($hiddenPhotos, 0, 6) as $photo)
                        <span class="rounded-md bg-amber-100 px-2 py-1 text-xs font-medium text-amber-900 dark:bg-amber-500/15 dark:text-amber-100">{{ $photo['name'] }}</span>
                    @endforeach
                    @if (count($hiddenPhotos) > 6)
                        <span class="rounded-md bg-amber-100 px-2 py-1 text-xs font-medium text-amber-900 dark:bg-amber-500/15 dark:text-amber-100">{{ __('i još :count', ['count' => count($hiddenPhotos) - 6]) }}</span>
                    @endif
                </div>
            </flux:callout>
        @endif
        <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
            @foreach ($settingsPanel['fields'] as $field)
                @php
                    $isFieldRequired = in_array('required', array_map('strval', (array) ($field['rules'] ?? [])), true);
                @endphp

                @continue(! ($field['visible'] ?? true))

                @if ($field['type'] === 'checkbox_list')
                    @php
                        $selectedValues = array_map('strval', (array) data_get($this->settingsForm, $field['key'], []));
                        $optionGroups = collect($field['options'])->groupBy(
                            fn (array $option): string => (string) ($option['group_key'] ?? $option['group_label'] ?? __('Opcije')),
                        );
                    @endphp

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

                        <div class="mt-4 space-y-2">
                            @forelse ($optionGroups as $groupKey => $groupOptions)
                                @php
                                    $firstOption = $groupOptions->first();
                                    $groupLabel = (string) ($firstOption['group_label'] ?? $groupKey);
                                    $groupDescription = (string) ($firstOption['group_description'] ?? '');
                                    $selectedCount = $groupOptions->whereIn('value', $selectedValues)->count();
                                @endphp

                                <details class="group overflow-hidden rounded-lg border border-zinc-200 bg-white open:border-pink-200 open:bg-pink-50/30 dark:border-zinc-800 dark:bg-zinc-950 dark:open:border-pink-500/30 dark:open:bg-pink-500/5" @if ($selectedCount > 0) open @endif>
                                    <summary class="flex cursor-pointer list-none items-center gap-3 px-4 py-3 marker:hidden">
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500 group-open:bg-pink-100 group-open:text-pink-700 dark:bg-zinc-900 dark:text-zinc-400 dark:group-open:bg-pink-500/15 dark:group-open:text-pink-300">
                                            <flux:icon :name="($firstOption['group_type'] ?? '') === 'tags' ? 'tag' : 'folder'" class="size-4" />
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-semibold text-zinc-950 dark:text-white">{{ $groupLabel }}</span>
                                            @if ($groupDescription !== '')
                                                <span class="mt-0.5 block text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ $groupDescription }}</span>
                                            @endif
                                        </span>
                                        @if ($selectedCount > 0)
                                            <span class="rounded-full bg-pink-100 px-2 py-0.5 text-xs font-semibold text-pink-700 dark:bg-pink-500/15 dark:text-pink-300">{{ $selectedCount }}</span>
                                        @endif
                                        <flux:icon name="chevron-down" class="size-4 shrink-0 text-zinc-400 transition group-open:rotate-180" />
                                    </summary>

                                    <div class="grid gap-2 border-t border-zinc-200 px-4 py-4 dark:border-zinc-800 sm:grid-cols-2 xl:grid-cols-3">
                                        @foreach ($groupOptions as $option)
                                            <label class="flex cursor-pointer items-start gap-3 rounded-lg px-3 py-2.5 hover:bg-white dark:hover:bg-zinc-900">
                                                <flux:checkbox wire:model="settingsForm.{{ $field['key'] }}" value="{{ $option['value'] }}" class="mt-0.5" />
                                                <span class="min-w-0">
                                                    <span class="block text-sm font-medium text-zinc-900 dark:text-white">{{ $option['label'] }}</span>
                                                    @if (($option['description'] ?? '') !== '')
                                                        <span class="mt-0.5 block text-xs text-zinc-500 dark:text-zinc-400">{{ $option['description'] }}</span>
                                                    @endif
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </details>
                            @empty
                                <x-corexis::public-empty-state
                                    icon="tag"
                                    :title="__('Nema dostupnih taxonomy vrijednosti.')"
                                    :description="__('Dodajte kategorije ili oznake u modulu Objave pa ih zatim odaberite ovdje.')"
                                    :compact="true"
                                />
                            @endforelse
                        </div>
                    </div>
                @elseif ($field['type'] === 'select' && ($field['display'] ?? '') === 'preview_cards')
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
                            @if ($field['reactive'] ?? false)
                                <flux:select wire:model.live="settingsForm.{{ $field['key'] }}" variant="listbox" class="w-full">
                                    @foreach ($field['options'] as $option)
                                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @else
                                <flux:select wire:model="settingsForm.{{ $field['key'] }}" variant="listbox" class="w-full">
                                    @foreach ($field['options'] as $option)
                                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @endif
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

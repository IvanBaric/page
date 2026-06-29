<section class="admin-panel">
    <div class="admin-panel-header">
        <div>
            <h2 class="admin-panel-title">{{ $this->settingsHeading() }}</h2>
            <p class="admin-panel-description">
                {{ $this->settingsDescription() }}
            </p>
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
                        <flux:input wire:model="settingsForm.{{ $field['key'] }}" :type="in_array($field['type'], ['date', 'time', 'number'], true) ? $field['type'] : 'text'" class="w-full" />
                    @endif
                </div>
            @endforeach
        </div>

    </form>
</section>

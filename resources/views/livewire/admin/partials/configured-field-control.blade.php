@php
    $field = $field ?? [];
    $wireModel = (string) ($wireModel ?? '');
    $fieldType = (string) ($field['type'] ?? 'text');
    $fieldKey = (string) ($field['key'] ?? '');
    $booleanControl = (string) ($booleanControl ?? 'checkbox');
    $inputType = in_array($fieldType, ['date', 'time', 'number', 'url'], true) ? $fieldType : 'text';
    $isRequired = in_array('required', array_map('strval', (array) ($field['rules'] ?? [])), true);
@endphp

@if ($fieldKey !== '' && $wireModel !== '')
    @if ($fieldType === 'textarea')
        @if ($isRequired)
            <flux:textarea wire:model="{{ $wireModel }}" :label="$field['label']" :rows="$field['rows']" data-required />
        @else
            <flux:textarea wire:model="{{ $wireModel }}" :label="$field['label']" :rows="$field['rows']" />
        @endif
    @elseif ($fieldType === 'select')
        @if ($isRequired)
            <flux:select wire:model="{{ $wireModel }}" variant="listbox" :label="$field['label']" class="w-full" data-required>
                @foreach ($field['options'] as $option)
                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>
        @else
            <flux:select wire:model="{{ $wireModel }}" variant="listbox" :label="$field['label']" class="w-full">
                @foreach ($field['options'] as $option)
                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif
    @elseif ($fieldType === 'boolean')
        @if ($booleanControl === 'switch')
            <flux:switch wire:model="{{ $wireModel }}" :label="$field['label']" />
        @else
            <flux:checkbox wire:model="{{ $wireModel }}" :label="$field['label']" />
        @endif
    @elseif ($fieldType === 'icon' && (bool) ($field['picker'] ?? true))
        <livewire:admin-ui.icon-picker
            wire:model="{{ $wireModel }}"
            :label="$field['label']"
            :nullable="true"
        />
    @else
        @if ($isRequired)
            <flux:input wire:model="{{ $wireModel }}" :label="$field['label']" :type="$inputType" data-required />
        @else
            <flux:input wire:model="{{ $wireModel }}" :label="$field['label']" :type="$inputType" />
        @endif
    @endif
@endif

<flux:select.option value="">{{ __('Glavni izbornik') }}</flux:select.option>
@foreach ($options as $option)
    <flux:select.option :value="$option['uuid']">
        {{ str_repeat('-', max(0, (int) $option['resulting_depth'] - 1)).' '.$option['label'] }}
    </flux:select.option>
@endforeach

@php
    $inlineForm = $inlineForm ?? false;
    $showFormHeader = $showFormHeader ?? ! $inlineForm;
    $showCancel = $showCancel ?? ! $inlineForm;
    $customFields = $customFields ?? [];
    $submitLabel = $submitLabel ?? __('Spremi');
    $subtitleType = $subtitleType ?? 'text';
    $subtitleRows = $subtitleRows ?? 3;
    $usesSidebarGrid = $hasSidebar && ($inlineForm || ! $modalFlyout);
@endphp

<form wire:submit="saveItem" wire:loading.class="admin-panel-content-loading" wire:target="saveItem,saveAllChanges" @class(['relative min-w-0', 'space-y-8' => ! $inlineForm, 'space-y-6' => $inlineForm])>
    <x-admin-ui::loading-overlay target="saveItem,saveAllChanges" :text="__('Spremanje...')" />
    @if ($showFormHeader)
        <div>
            <flux:heading size="lg">{{ $this->formTitle() }}</flux:heading>
            <flux:text class="mt-2">{{ $itemModalDescription }}</flux:text>
        </div>
    @endif

    <div @class(['grid min-w-0 gap-6', 'lg:grid-cols-[minmax(0,1fr)_22rem]' => $usesSidebarGrid])>
        <div class="min-w-0 space-y-5">
            @if ($showTitle && $showSubtitle && ! $singleColumnFields)
                <div @class(['grid gap-5', 'md:grid-cols-2' => $subtitleType !== 'textarea'])>
                    <flux:input wire:model="form.title" :label="$titleLabel" data-required />
                    @if ($subtitleType === 'textarea')
                        <flux:textarea wire:model="form.subtitle" :label="$subtitleLabel" :rows="$subtitleRows" />
                    @else
                        <flux:input wire:model="form.subtitle" :label="$subtitleLabel" />
                    @endif
                </div>
            @elseif ($showTitle && $showSubtitle)
                <flux:input wire:model="form.title" :label="$titleLabel" data-required />
                @if ($subtitleType === 'textarea')
                    <flux:textarea wire:model="form.subtitle" :label="$subtitleLabel" :rows="$subtitleRows" />
                @else
                    <flux:input wire:model="form.subtitle" :label="$subtitleLabel" />
                @endif
            @elseif ($showTitle)
                <flux:input wire:model="form.title" :label="$titleLabel" data-required />
            @elseif ($showSubtitle)
                @if ($subtitleType === 'textarea')
                    <flux:textarea wire:model="form.subtitle" :label="$subtitleLabel" :rows="$subtitleRows" />
                @else
                    <flux:input wire:model="form.subtitle" :label="$subtitleLabel" />
                @endif
            @endif

            @if ($customFields !== [])
                <div @class([
                    'grid gap-5',
                    'md:grid-cols-2' => ! $singleColumnFields && count($customFields) > 1,
                ])>
                    @foreach ($customFields as $field)
                        @php
                            $fieldType = (string) ($field['type'] ?? 'text');
                            $fieldKey = (string) ($field['key'] ?? '');
                            $isWideField = in_array($fieldType, ['textarea'], true);
                        @endphp

                        @if ($fieldKey !== '')
                            <div @class(['md:col-span-2' => $isWideField && ! $singleColumnFields])>
                                @include('pages::livewire.admin.partials.configured-field-control', [
                                    'field' => $field,
                                    'wireModel' => 'form.customData.'.$fieldKey,
                                    'booleanControl' => 'checkbox',
                                ])

                                @if (($field['help'] ?? '') !== '')
                                    <p class="mt-2 text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ $field['help'] }}</p>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            @if ($showContent)
                <flux:textarea wire:model="form.content" :label="$contentLabel" :rows="$contentRows" />
            @endif

            @if ($showYoutubeUrl)
                <flux:input wire:model="form.youtubeUrl" :label="$youtubeUrlLabel" type="url" />
            @endif
        </div>

        @if ($hasSidebar)
            <aside class="min-w-0 space-y-5">
                @if ($showImage)
                    <x-admin-ui::media-upload
                        wire-model="form.imageUpload"
                        :file="$form->imageUpload"
                        :existing-url="$this->imageUrl()"
                        :label="$imageLabel"
                        :help="$imageHelp"
                        :size="$imageUploadSize"
                        :fit="$imageFit"
                        remove-action="removeImage"
                    />
                @endif

                @if ($showValue)
                    <flux:input wire:model="form.metaValue" :label="$valueLabel" />
                    @if ($showValueSuffix)
                        <flux:input wire:model="form.metaSuffix" :label="$valueSuffixLabel" />
                    @endif
                @endif

                @if ($showIcon)
                    <div>
                        @if ($iconPicker)
                            <livewire:admin-ui.icon-picker
                                wire:model="form.icon"
                                :label="$iconLabel"
                                :nullable="true"
                            />
                        @elseif (! empty($iconOptions))
                            <flux:select wire:model="form.icon" variant="listbox" :label="$iconLabel">
                                @foreach ($iconOptions as $option)
                                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            <flux:input wire:model="form.icon" :label="$iconLabel" />
                        @endif

                    </div>
                @endif

                @if ($showUrl)
                    <flux:input wire:model="form.url" :label="$urlLabel" type="url" />
                @endif

                @if ($showButton)
                    <flux:input wire:model="form.buttonLabel" :label="$buttonLabelLabel" />
                    <flux:input wire:model="form.buttonUrl" :label="$buttonUrlLabel" />
                @endif

                @if ($showSortOrder)
                    <flux:input wire:model="form.sortOrder" :label="__('Redoslijed')" type="number" min="0" />
                @endif

                @if ($showVisibility)
                    <flux:switch wire:model="form.visible" :checked="(bool) $form->visible" :label="__('Stavka je vidljiva')" />
                @endif
            </aside>
        @endif
    </div>

    @unless ($inlineForm)
        <div class="flex justify-end gap-2">
            @if ($showCancel)
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button>
                </flux:modal.close>
            @endif
            <x-admin-ui::submit-button target="saveItem">{{ $submitLabel }}</x-admin-ui::submit-button>
        </div>
    @endunless
</form>

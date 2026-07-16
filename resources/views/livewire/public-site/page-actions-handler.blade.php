<div data-public-page-actions-handler>
    @teleport('body')
        <div>
            <x-admin-ui::action-loading target="__dispatch" :text="__('Učitavanje...')" />

            <flux:modal name="section-create" x-on:close="$wire.cancelSectionCreator()" class="w-[calc(100vw-2rem)] max-w-6xl lg:w-[72rem]">
                @if ($publicActionDialog === 'create')
                    @php($selectedSection = $this->selectedSectionDetails)

                    <form wire:submit="addSelectedSection" wire:loading.class="admin-panel-content-loading" wire:target="addSelectedSection" class="relative flex h-[min(82vh,38rem)] flex-col gap-5 overflow-hidden sm:gap-6">
                        <x-admin-ui::loading-overlay target="addSelectedSection" :text="__('Spremanje...')" />

                        <div class="shrink-0">
                            <flux:heading size="lg">{{ __('Dodaj sekciju') }}</flux:heading>
                            <flux:text class="mt-2">{{ __('Odaberite sekciju koju želite dodati na stranicu.') }}</flux:text>
                        </div>

                        <div class="grid min-h-0 flex-1 grid-rows-[auto_minmax(0,1fr)] gap-4 lg:grid-cols-[18rem_minmax(0,1fr)] lg:grid-rows-none lg:gap-5">
                            <div class="min-h-0 overflow-x-auto rounded-xl border border-zinc-200 bg-zinc-50 p-2 dark:border-zinc-800 dark:bg-zinc-900/70 lg:overflow-x-hidden lg:overflow-y-auto">
                                <div class="flex gap-1 lg:grid">
                                    @forelse ($this->sectionCreatorEntries as $option)
                                        <button
                                            type="button"
                                            wire:click="selectSectionCreatorEntry('{{ $option['key'] }}')"
                                            @class([
                                                'block shrink-0 cursor-pointer whitespace-nowrap rounded-lg px-3 py-2.5 text-left text-sm font-semibold leading-5 transition lg:w-full lg:whitespace-normal',
                                                'bg-white text-zinc-950 shadow-sm ring-1 ring-pink-200 dark:bg-zinc-950 dark:text-white dark:ring-pink-500/40' => $selectedSectionCreatorKey === $option['key'],
                                                'text-zinc-700 hover:bg-white hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-950 dark:hover:text-white' => $selectedSectionCreatorKey !== $option['key'],
                                            ])
                                        >
                                            <span class="block">{{ $option['label'] }}</span>
                                        </button>
                                    @empty
                                        <div class="px-4 py-8 text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                                            {{ __('Nema dostupnih tipova sekcija.') }}
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="min-h-0 overflow-y-auto rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950 sm:p-5">
                                @if ($selectedSection)
                                    <div class="flex h-full min-h-0 flex-col gap-5">
                                        <div class="shrink-0 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="flex min-w-0 items-start gap-4">
                                                <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-xl bg-pink-50 text-pink-700 ring-1 ring-pink-200 dark:bg-pink-500/10 dark:text-pink-300 dark:ring-pink-500/30">
                                                    <flux:icon :name="$selectedSection['icon']" class="size-5" />
                                                </span>

                                                <div class="min-w-0">
                                                    <h3 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ $selectedSection['label'] }}</h3>

                                                    @if (filled($selectedSection['panel_description'] ?? null))
                                                        <p class="mt-1 max-w-2xl text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $selectedSection['panel_description'] }}</p>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="hidden sm:block">
                                                @if (($selectedSection['kind'] ?? null) === 'section')
                                                    <flux:button type="submit" variant="primary" icon="plus" class="justify-center" wire:loading.attr="disabled" wire:target="addSelectedSection">
                                                        {{ __('Dodaj sekciju') }}
                                                    </flux:button>
                                                @endif
                                            </div>
                                        </div>

                                        @if (($selectedSection['kind'] ?? null) === 'group')
                                            <div class="min-h-0 overflow-y-auto pr-1">
                                                <div class="grid gap-3">
                                                    @foreach ($selectedSection['sections'] as $groupSection)
                                                        <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-900/60 sm:flex-row sm:items-center sm:justify-between">
                                                            <div class="flex min-w-0 items-start gap-3">
                                                                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-lg bg-white text-pink-700 ring-1 ring-zinc-200 dark:bg-zinc-950 dark:text-pink-300 dark:ring-zinc-800">
                                                                    <flux:icon :name="$groupSection['icon']" class="size-4" />
                                                                </span>

                                                                <div class="min-w-0">
                                                                    <h4 class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $groupSection['label'] }}</h4>

                                                                    @if (filled($groupSection['panel_description'] ?? null))
                                                                        <p class="mt-1 max-w-2xl text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $groupSection['panel_description'] }}</p>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <flux:button type="button" wire:click="addSection('{{ $groupSection['key'] }}')" variant="primary" icon="plus" class="w-full shrink-0 justify-center sm:w-auto" wire:loading.attr="disabled" wire:target="addSection">
                                                                {{ __('Dodaj sekciju') }}
                                                            </flux:button>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <flux:button type="submit" variant="primary" icon="plus" class="w-full justify-center sm:hidden" wire:loading.attr="disabled" wire:target="addSelectedSection">
                                                {{ __('Dodaj sekciju') }}
                                            </flux:button>
                                        @endif
                                    </div>
                                @else
                                    <div class="flex h-full min-h-80 items-center justify-center text-center">
                                        <div>
                                            <div class="mx-auto inline-flex size-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                                                <flux:icon name="rectangle-stack" class="size-5" />
                                            </div>
                                            <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Nema odabrane sekcije.') }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </form>
                @endif
            </flux:modal>

            <flux:modal name="section-copy" x-on:close="$wire.cancelCopy()" class="max-w-lg">
                @if ($publicActionDialog === 'copy')
                    <form wire:submit="copySection" wire:loading.class="admin-panel-content-loading" wire:target="copySection" class="relative space-y-6">
                        <x-admin-ui::loading-overlay target="copySection" :text="__('Spremanje...')" />

                        <div>
                            <flux:heading size="lg">{{ __('Kopirati sekciju?') }}</flux:heading>
                            <flux:text class="mt-2">{{ __('Kopija zadržava izgled, slike i tekstove. Nakon kopiranja možete promijeniti sadržaj na odabranoj stranici.') }}</flux:text>
                        </div>

                        <flux:select wire:model="copyTargetPageUuid" variant="listbox" :label="__('Stranica')" :placeholder="__('Odaberite stranicu...')">
                            @foreach ($this->copyTargetPages as $targetPage)
                                <flux:select.option value="{{ $targetPage->uuid }}">
                                    {{ $targetPage->localized('title') ?: __('Neimenovana stranica') }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <div class="flex justify-end gap-2">
                            <flux:modal.close>
                                <flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button>
                            </flux:modal.close>
                            <x-admin-ui::submit-button target="copySection" icon="document-duplicate">{{ __('Kopiraj na stranicu') }}</x-admin-ui::submit-button>
                        </div>
                    </form>
                @endif
            </flux:modal>

            <flux:modal name="section-move" x-on:close="$wire.cancelMove()" class="max-w-lg">
                @if ($publicActionDialog === 'move')
                    <form wire:submit="moveSection" wire:loading.class="admin-panel-content-loading" wire:target="moveSection" class="relative space-y-6">
                        <x-admin-ui::loading-overlay target="moveSection" :text="__('Spremanje...')" />

                        <div>
                            <flux:heading size="lg">{{ __('Premjestiti sekciju?') }}</flux:heading>
                            <flux:text class="mt-2">{{ __('Sekcija će se ukloniti s ove stranice i premjestiti na kraj odabrane stranice. Sav sadržaj, slike i postavke ostaju isti.') }}</flux:text>
                        </div>

                        @if ($this->moveTargetPages->isNotEmpty())
                            <flux:select wire:model="moveTargetPageUuid" variant="listbox" :label="__('Stranica')" :placeholder="__('Odaberite stranicu...')">
                                @foreach ($this->moveTargetPages as $targetPage)
                                    <flux:select.option value="{{ $targetPage->uuid }}">
                                        {{ $targetPage->localized('title') ?: __('Neimenovana stranica') }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            <div class="rounded-lg bg-zinc-50 p-4 text-sm leading-6 text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-800">
                                {{ __('Nema druge stranice na koju možete premjestiti ovu sekciju. Prvo izradite novu stranicu.') }}
                            </div>
                        @endif

                        <div class="flex justify-end gap-2">
                            <flux:modal.close>
                                <flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button>
                            </flux:modal.close>
                            @if ($this->moveTargetPages->isNotEmpty())
                                <x-admin-ui::submit-button target="moveSection" icon="arrow-right">{{ __('Premjesti na stranicu') }}</x-admin-ui::submit-button>
                            @endif
                        </div>
                    </form>
                @endif
            </flux:modal>

            <flux:modal name="section-delete" x-on:close="$wire.cancelDelete()" class="max-w-lg">
                @if ($publicActionDialog === 'archive')
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">{{ __('Arhivirati sekciju?') }}</flux:heading>
                            <flux:text class="mt-2">{{ __('Sekcija će se premjestiti u arhivu zajedno sa svojim stavkama. Možete je kasnije vratiti iz Arhive.') }}</flux:text>
                        </div>

                        <div class="flex justify-end gap-2">
                            <flux:modal.close>
                                <flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button>
                            </flux:modal.close>
                            <flux:button wire:click="delete" wire:loading.attr="disabled" wire:target="delete" type="button" variant="danger" icon="archive-box">
                                {{ __('Arhiviraj sekciju') }}
                            </flux:button>
                        </div>
                    </div>
                @endif
            </flux:modal>
        </div>
    @endteleport
</div>

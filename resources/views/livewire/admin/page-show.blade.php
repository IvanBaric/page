<section class="admin-page">
    <div class="admin-page-header">
        <div class="admin-page-header-copy">
            <div class="mb-6">
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route($this->pageIndexRouteName())" wire:navigate>{{ __('Stranice') }}</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ $page->localized('title') }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            </div>
            <h1 class="admin-page-title">{{ $page->localized('title') }}</h1>
            <flux:text class="admin-page-description">{{ __('Složite sadržaj stranice, promijenite redoslijed sekcija, kopirajte ili premjestite postojeće blokove.') }}</flux:text>
        </div>
        <div class="admin-page-actions">
            <flux:modal.trigger name="section-create">
                <flux:button type="button" wire:click="openSectionCreator" variant="primary" icon="plus">{{ __('Dodaj sekciju') }}</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <x-admin-ui::panel loading loading-target="toggle,reorderSection,addSelectedSection,copySection,moveSection,delete" loading-text="{{ __('Ažuriram sekcije...') }}">
        <div class="admin-panel-header">
            <div>
                <h2 class="admin-panel-title">{{ __('Sekcije') }}</h2>
                <p class="admin-panel-description">{{ __('Posložite blokove stranice i otvorite sekciju koju želite urediti.') }}</p>
            </div>
        </div>

        @if ($this->sections->isEmpty())
            <div class="px-6 py-14 text-center">
                <div class="mx-auto inline-flex size-12 items-center justify-center rounded-full bg-accent/10 text-accent-content ring-1 ring-accent/15 dark:bg-accent/15 dark:text-accent-content dark:ring-accent/25">
                    <flux:icon name="rectangle-stack" class="size-5" />
                </div>
                <h3 class="mt-4 text-base font-semibold text-zinc-950 dark:text-white">{{ __('Stranica je spremna za sadržaj') }}</h3>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-zinc-500 dark:text-zinc-400">{{ __('Dodajte prvu sekciju, zatim uredite njezin sadržaj, izgled i redoslijed na stranici.') }}</p>
                <flux:modal.trigger name="section-create">
                    <flux:button type="button" wire:click="openSectionCreator" class="mt-5" variant="primary" icon="plus">
                        {{ __('Dodaj prvu sekciju') }}
                    </flux:button>
                </flux:modal.trigger>
            </div>
        @else
            <div wire:sort="reorderSection" class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($this->sections as $section)
                    <article wire:key="section-{{ $section->uuid }}" wire:sort:item="{{ $section->uuid }}" class="grid grid-cols-1 gap-4 px-5 py-4 transition hover:bg-zinc-50/80 dark:hover:bg-white/[0.03] lg:grid-cols-[3rem_minmax(0,1fr)_7rem_9rem_6rem] lg:items-center">
                        <flux:tooltip :content="__('Povucite za promjenu redoslijeda')">
                            <span wire:sort:handle class="inline-flex size-10 cursor-grab items-center justify-center rounded-md bg-zinc-100 text-zinc-500 ring-1 ring-zinc-950/5 transition hover:bg-zinc-200 hover:text-zinc-700 active:cursor-grabbing dark:bg-zinc-900 dark:ring-white/10 dark:hover:bg-zinc-800">
                                <flux:icon name="bars-3" class="size-4" />
                            </span>
                        </flux:tooltip>
                        <div class="min-w-0">
                            <a href="{{ route($this->sectionShowRouteName(), ['section' => $section->uuid]) }}" wire:navigate class="block truncate text-[15px] font-semibold text-zinc-950 transition hover:text-accent dark:text-white">
                                {{ $section->localized('title') ?: __(data_get($this->sectionTypes, $section->type.'.label', $section->type)) }}
                            </a>
                            @if ($section->localized('description'))
                                <p class="mt-1 line-clamp-2 text-[13px] leading-5 text-zinc-500 dark:text-zinc-400">{{ $section->localized('description') }}</p>
                            @endif
                        </div>
                        <div class="text-sm tabular-nums text-zinc-600 dark:text-zinc-300">{{ trans_choice('{0} bez stavki|{1} :count stavka|[2,*] :count stavki', $section->items_count, ['count' => $section->items_count]) }}</div>
                        <div wire:sort:ignore class="min-w-0">
                            <flux:tooltip :content="$section->is_visible ? __('Sakrij sekciju na javnoj stranici') : __('Prikaži sekciju na javnoj stranici')">
                                <flux:switch wire:click="toggle('{{ $section->uuid }}')" :checked="(bool) $section->is_visible" :label="''" :aria-label="$section->is_visible ? __('Uključeno') : __('Isključeno')" />
                            </flux:tooltip>
                        </div>
                        <div wire:sort:ignore class="flex flex-wrap items-center gap-2 lg:justify-end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" :aria-label="__('Akcije')" />

                                <flux:menu>
                                    <flux:modal.trigger name="section-form">
                                        <flux:menu.item as="button" type="button" wire:click="edit('{{ $section->uuid }}')" icon="cog-6-tooth">
                                            {{ __('Uredi') }}
                                        </flux:menu.item>
                                    </flux:modal.trigger>
                                    <flux:modal.trigger name="section-copy">
                                        <flux:menu.item as="button" type="button" wire:click="confirmCopy('{{ $section->uuid }}')" icon="document-duplicate">
                                            {{ __('Kopiraj') }}
                                        </flux:menu.item>
                                    </flux:modal.trigger>
                                    <flux:modal.trigger name="section-move">
                                        <flux:menu.item as="button" type="button" wire:click="confirmMove('{{ $section->uuid }}')" icon="arrow-right">
                                            {{ __('Premjesti') }}
                                        </flux:menu.item>
                                    </flux:modal.trigger>
                                    <flux:menu.separator />
                                    <flux:modal.trigger name="section-delete">
                                        <flux:menu.item as="button" type="button" wire:click="confirmDelete('{{ $section->uuid }}')" icon="archive-box" variant="danger">
                                            {{ __('Arhiviraj') }}
                                        </flux:menu.item>
                                    </flux:modal.trigger>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </x-admin-ui::panel>

    <flux:modal name="section-create" class="w-[calc(100vw-2rem)] max-w-6xl lg:w-[72rem]">
        @php($selectedSection = $this->selectedSectionDetails)

        <form wire:submit="addSelectedSection" class="flex h-[min(78vh,38rem)] flex-col gap-6 overflow-hidden">
            <div class="shrink-0">
                <div>
                    <flux:heading size="lg">{{ __('Dodaj sekciju') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Odaberite sekciju koju želite dodati na stranicu.') }}</flux:text>
                </div>
            </div>

            <div class="grid min-h-0 flex-1 grid-rows-[12rem_minmax(0,1fr)] gap-5 lg:grid-cols-[18rem_minmax(0,1fr)] lg:grid-rows-none">
                <div class="min-h-0 overflow-y-auto rounded-xl border border-zinc-200 bg-zinc-50 p-2 dark:border-zinc-800 dark:bg-zinc-900/70">
                    <div class="grid gap-1">
                        @forelse ($this->sectionCreatorEntries as $option)
                            <button
                                type="button"
                                wire:click="selectSectionCreatorEntry('{{ $option['key'] }}')"
                                @class([
                                    'block w-full cursor-pointer rounded-lg px-3 py-2.5 text-left text-sm font-semibold leading-5 transition',
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

                <div class="min-h-0 overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-950">
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
    </flux:modal>

    <flux:modal name="section-form" flyout variant="floating" class="md:w-lg">
        <form wire:submit="saveSection" class="space-y-12">
            <div>
                <flux:heading size="lg">{{ __('Uredi sekciju') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Uredite naziv i opis sekcije.') }}</flux:text>
            </div>

            <div class="grid gap-5">
                <flux:input wire:model="form.title" :label="__('Naziv')" />
                <flux:textarea wire:model="form.description" :label="__('Opis')" rows="4" />
            </div>

            <flux:fieldset>
                <flux:legend>{{ __('Prikaz na javnoj stranici') }}</flux:legend>

                <div class="space-y-4">
                    <flux:switch
                        wire:model="form.showTitle"
                        :label="__('Prikaži naziv sekcije')"
                        :description="__('Kada je uključeno, naziv sekcije prikazuje se iznad sadržaja na javnoj stranici.')"
                    />

                    <flux:separator variant="subtle" />

                    <flux:switch
                        wire:model="form.showDescription"
                        :label="__('Prikaži opis sekcije')"
                        :description="__('Kada je uključeno, opis sekcije prikazuje se ispod naziva sekcije na javnoj stranici.')"
                    />
                </div>
            </flux:fieldset>

            @if ($this->sectionNavigationSettingsAvailable)
                <flux:fieldset>
                    <flux:legend>{{ __('Izbornik naslovnice') }}</flux:legend>

                    <div class="space-y-4">
                        <flux:switch
                            wire:model.live="form.showInNavigation"
                            :label="__('Prikaži u izborniku')"
                            :description="__('Kada je objavljena samo Naslovnica, ova sekcija može biti poveznica u glavnom izborniku.')"
                        />

                        @if ($form->showInNavigation)
                            <div class="space-y-2">
                                <flux:input
                                    wire:model="form.navigationLabel"
                                    :label="__('Naziv u izborniku')"
                                    :placeholder="__('Naziv sekcije')"
                                />
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('Ako ostavite prazno, koristi se naziv sekcije.') }}
                                </flux:text>
                            </div>
                        @endif
                    </div>
                </flux:fieldset>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ __('Spremi postavke') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="section-copy" class="max-w-lg">
        <form wire:submit="copySection" class="space-y-6">
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
                <flux:button type="submit" variant="primary" icon="document-duplicate">{{ __('Kopiraj na stranicu') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="section-move" class="max-w-lg">
        <form wire:submit="moveSection" class="space-y-6">
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
                    <flux:button type="submit" variant="primary" icon="arrow-right">{{ __('Premjesti na stranicu') }}</flux:button>
                @endif
            </div>
        </form>
    </flux:modal>

    <flux:modal name="section-delete" class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Arhivirati sekciju?') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Sekcija će se premjestiti u arhivu zajedno sa svojim stavkama. Možete je kasnije vratiti iz Arhive.') }}</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button>
                </flux:modal.close>
                <flux:button wire:click="delete" type="button" variant="danger" icon="archive-box">{{ __('Arhiviraj sekciju') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>

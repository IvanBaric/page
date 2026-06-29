<section class="admin-panel">
    <div class="admin-panel-header">
        <div>
            <h2 class="admin-panel-title">{{ $this->sourceHeading() }}</h2>
            @if ($this->sourceDescription() !== '')
                <p class="admin-panel-description">
                    {{ $this->sourceDescription() }}
                </p>
            @endif
        </div>
    </div>

    @if ($this->sourceActions() !== [])
        <div class="flex flex-wrap gap-2 p-6">
            @foreach ($this->sourceActions() as $action)
                <flux:button :href="$action['href']" wire:navigate :variant="$action['variant']" :icon="$action['icon']">
                    {{ $action['label'] }}
                </flux:button>
            @endforeach
        </div>
    @endif
</section>

<div class="grid gap-4">
    <flux:radio.group wire:model.live="{{ $typeProperty }}" variant="segmented" :label="__('Vrsta stavke izbornika')">
        <flux:radio value="page" :label="__('Stranica')" icon="document-text" />
        <flux:radio value="url" :label="__('Poveznica')" icon="link" />
    </flux:radio.group>

    @if ($currentType === 'url')
        <flux:input wire:model="{{ $urlProperty }}" :label="__('URL poveznice')" :description="__('Puna web adresa, relativna putanja, e-pošta ili telefonska poveznica.')" placeholder="https://primjer.hr" inputmode="url" data-required />
        <flux:checkbox wire:model="{{ $newTabProperty }}" :label="__('Otvori u novoj kartici')" />
    @endif
</div>

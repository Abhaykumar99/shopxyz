<x-filament-panels::page>
    <form wire:submit="save" class="fi-form grid gap-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit" size="lg" icon="heroicon-m-check">
                Save settings
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

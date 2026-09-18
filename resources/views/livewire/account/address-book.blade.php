<div class="flex flex-col gap-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-ink-soft">We deliver within {{ $shop->deliveryArea ?? 'our area' }}.</p>
        <x-ui.button icon="plus" wire:click="create" loading="create">Add address</x-ui.button>
    </div>

    @if ($addresses === [])
        <x-ui.card>
            <x-ui.empty-state icon="map-pin" title="No saved addresses">
                Save an address to check out faster next time.
                <x-slot:action>
                    <x-ui.button icon="plus" wire:click="create">Add address</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <ul class="grid gap-3 md:grid-cols-2">
            @foreach ($addresses as $address)
                <li wire:key="address-{{ $address->id }}">
                    <x-ui.card class="flex h-full flex-col gap-4" padding="lg">
                        <x-shop.address-card
                            :name="$address->name"
                            :label="$address->label"
                            :phone="$address->formattedPhone()"
                            :lines="$address->lines()"
                            :pincode="$address->pincode"
                            :is-default="$address->isDefault"
                            class="grow"
                        />
                        @unless ($shop->servesPincode($address->pincode))
                            <p class="text-sm font-medium text-danger">We don't deliver to this pincode yet.</p>
                        @endunless
                        <div class="flex flex-wrap gap-2 border-t border-line pt-3">
                            <x-ui.button variant="ghost" size="sm" icon="pencil" wire:click="edit('{{ $address->id }}')" aria-label="Edit {{ $address->label }} address">Edit</x-ui.button>
                            <x-ui.button variant="ghost" size="sm" icon="trash" wire:click="confirmDelete('{{ $address->id }}')" aria-label="Delete {{ $address->label }} address">Delete</x-ui.button>
                            @unless ($address->isDefault)
                                <x-ui.button variant="ghost" size="sm" icon="check" wire:click="makeDefault('{{ $address->id }}')" loading="makeDefault('{{ $address->id }}')" class="ms-auto">Make default</x-ui.button>
                            @endunless
                        </div>
                    </x-ui.card>
                </li>
            @endforeach
        </ul>
    @endif

    <x-ui.modal name="address-form" :title="$addressForm->id ? 'Edit address' : 'New address'" sheet max-width="lg">
        <form wire:submit="save" id="address-book-form" novalidate>
            <x-shop.address-fields model="addressForm" :labels="$labels" :states="$states" />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'address-form')">Cancel</x-ui.button>
            <x-ui.button type="submit" form="address-book-form" loading="save">Save address</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="delete-address" title="Delete this address?" max-width="sm">
        @if ($deleting)
            <x-shop.address-card :name="$deleting->name" :label="$deleting->label" :phone="$deleting->formattedPhone()" :lines="$deleting->lines()" :pincode="$deleting->pincode" />
            @if ($deleting->isDefault && count($addresses) > 1)
                <p class="mt-3 text-sm text-ink-soft">This is your default address. Your next saved address becomes the default.</p>
            @endif
        @endif
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'delete-address')">Keep address</x-ui.button>
            <x-ui.button variant="danger" wire:click="delete" loading="delete">Delete address</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>

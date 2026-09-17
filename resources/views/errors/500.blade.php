<x-layouts::error title="Something went wrong on our side" code="500">
    We've been notified. Please try again in a few minutes{{ $shop->phone ? ', or call us on '.$shop->phone.' to place your order' : '' }}.
    <x-slot:actions>
        <x-ui.button :href="url('/')" icon="house">Go to the shop</x-ui.button>
    </x-slot:actions>
</x-layouts::error>

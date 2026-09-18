<x-layouts::error title="We couldn't find that page" code="404">
    The product or page may have moved, or the link has a typo.
    <x-slot:actions>
        <x-ui.button :href="url('/')" icon="house">Go to the shop</x-ui.button>
        <x-ui.button :href="url('/search')" variant="secondary" icon="search">Search products</x-ui.button>
    </x-slot:actions>
</x-layouts::error>

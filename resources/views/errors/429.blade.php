<x-layouts::error title="Too many attempts" code="429">
    Please wait a minute and try again.
    <x-slot:actions>
        <x-ui.button :href="url('/')" icon="house">Go to the shop</x-ui.button>
    </x-slot:actions>
</x-layouts::error>

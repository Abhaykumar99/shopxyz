<x-layouts::error title="Your session expired" code="419">
    For your security, the page timed out. Go back, refresh the page and try again.
    <x-slot:actions>
        <x-ui.button :href="url()->previous() === url()->current() ? url('/') : url()->previous()" icon="arrow-left">Go back</x-ui.button>
    </x-slot:actions>
</x-layouts::error>

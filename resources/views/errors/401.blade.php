<x-layouts::error title="Please sign in first" code="401">
    This page needs you to be signed in.
    <x-slot:actions>
        <x-ui.button :href="route('auth.login')" icon="user">Sign in</x-ui.button>
        <x-ui.button :href="url('/')" variant="secondary" icon="house">Go to the shop</x-ui.button>
    </x-slot:actions>
</x-layouts::error>

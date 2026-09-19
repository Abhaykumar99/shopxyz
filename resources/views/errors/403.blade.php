<x-layouts::error title="This page isn't yours to open" code="403">
    You are signed in, but this part of the site belongs to someone else — the shop's
    own pages and the delivery panel each have their own sign-in.
    <x-slot:actions>
        <x-ui.button :href="url('/')" icon="house">Go to the shop</x-ui.button>
        <x-ui.button :href="route('account.profile')" variant="secondary" icon="user">Your account</x-ui.button>
    </x-slot:actions>
</x-layouts::error>

<x-layouts::error title="We'll be back shortly" code="503">
    The shop is being updated. Please check back in a few minutes{{ $shop->whatsappLink() ? ', or message us on WhatsApp' : '' }}.
    @if ($shop->whatsappLink())
        <x-slot:actions>
            <x-ui.button :href="$shop->whatsappLink()" icon="message-circle">Message us</x-ui.button>
        </x-slot:actions>
    @endif
</x-layouts::error>

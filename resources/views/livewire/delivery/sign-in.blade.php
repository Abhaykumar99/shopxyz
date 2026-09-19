<div class="flex min-h-dvh flex-col justify-center bg-mist px-4 py-10">
    <main id="main" class="mx-auto flex w-full max-w-sm flex-col gap-5">
        <div class="flex flex-col items-center gap-3 text-center">
            <span class="flex size-14 items-center justify-center rounded-full bg-brand text-white">
                <x-ui.icon name="bike" :size="28" />
            </span>
            <h1 class="text-3xl">Delivery panel</h1>
            <p class="text-ink-soft">{{ $shop->name }}. Sign in with the number the shop gave you.</p>
        </div>

        <x-ui.card padding="lg" class="flex flex-col gap-4">
            <form wire:submit="submit" class="flex flex-col gap-4" novalidate>
                <x-ui.input
                    label="Mobile number"
                    name="phone"
                    wire:model="phone"
                    type="tel"
                    prefix="+91"
                    inputmode="numeric"
                    autocomplete="username"
                    maxlength="14"
                    required
                />
                <div x-data="{ show: false }" class="flex flex-col gap-1.5">
                    <x-ui.input
                        label="Password"
                        name="password"
                        wire:model="password"
                        x-bind:type="show ? 'text' : 'password'"
                        type="password"
                        autocomplete="current-password"
                        maxlength="72"
                        required
                    />
                    <button type="button" x-on:click="show = ! show" class="self-start text-sm font-semibold text-brand hover:underline">
                        <span x-text="show ? 'Hide password' : 'Show password'">Show password</span>
                    </button>
                </div>
                <x-ui.button type="submit" size="lg" block loading="submit">Sign in</x-ui.button>
            </form>

            <p class="border-t border-line pt-4 text-sm text-ink-soft">
                Forgotten your password? Call the shop
                @if ($shop->phone)
                    on <a href="tel:{{ preg_replace('/\s+/', '', $shop->phone) }}" class="figures font-semibold text-brand">{{ $shop->phone }}</a>
                @endif
                and they will set a new one.
            </p>
        </x-ui.card>

        {{-- Local only: the seeded partner's credentials, so the panel can be
             reviewed without asking the shop for a real account. --}}
        @if (app()->environment('local') && $seededPartner)
            <x-ui.alert tone="info" title="Preview account">
                <span class="figures">{{ \App\Support\IndianPhone::format($seededPartner) }}</span>
                with the password <span class="figures font-semibold">{{ \Database\Seeders\UserSeeder::DELIVERY_PASSWORD }}</span>.
            </x-ui.alert>
        @endif

        <p class="text-center text-sm text-ink-soft">
            Are you a customer? <x-ui.link :href="route('shop.home')">Go to the shop</x-ui.link>
        </p>
    </main>
</div>

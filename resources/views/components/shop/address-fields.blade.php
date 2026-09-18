{{--
    Address fields bound to an AddressForm on the parent Livewire component.
    `model`: the form property name, e.g. "addressForm".
--}}
@props([
    'model' => 'addressForm',
    'labels' => ['Home', 'Work', 'Other'],
    'states' => [],
])

<div {{ $attributes->class('grid gap-4 sm:grid-cols-2') }}>
    <fieldset class="sm:col-span-2">
        <legend class="mb-2 text-sm font-semibold">Save as</legend>
        <div class="flex flex-wrap gap-2">
            @foreach ($labels as $label)
                <label class="inline-flex min-h-10 cursor-pointer items-center rounded-full border border-line bg-surface px-4 text-sm font-medium has-checked:border-brand has-checked:bg-brand-tint has-checked:text-brand-dark has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-brand">
                    <input type="radio" value="{{ $label }}" wire:model="{{ $model }}.label" class="sr-only">
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </fieldset>

    <x-ui.input label="Full name" :name="$model.'.name'" wire:model="{{ $model }}.name" autocomplete="name" maxlength="80" required />
    <x-ui.input label="Mobile number" :name="$model.'.phone'" wire:model="{{ $model }}.phone" type="tel" prefix="+91" inputmode="numeric" autocomplete="tel-national" maxlength="14" required />
    <x-ui.input label="House or flat number, building" :name="$model.'.line1'" wire:model="{{ $model }}.line1" autocomplete="address-line1" maxlength="120" required class="sm:col-span-2" />
    <x-ui.input label="Street, area or colony" :name="$model.'.line2'" wire:model="{{ $model }}.line2" autocomplete="address-line2" maxlength="120" class="sm:col-span-2" />
    <x-ui.input label="Landmark" :name="$model.'.landmark'" wire:model="{{ $model }}.landmark" maxlength="80" hint="For example: near the water tank" />
    <x-ui.input label="Pincode" :name="$model.'.pincode'" wire:model.blur="{{ $model }}.pincode" inputmode="numeric" autocomplete="postal-code" maxlength="6" required />
    <x-ui.input label="City or town" :name="$model.'.city'" wire:model="{{ $model }}.city" autocomplete="address-level2" maxlength="60" required />
    <x-ui.select label="State" :name="$model.'.state'" wire:model="{{ $model }}.state" autocomplete="address-level1" :options="$states" required />

    <x-ui.checkbox :id="$model.'-default'" label="Make this my default address" wire:model="{{ $model }}.isDefault" class="sm:col-span-2" />
</div>

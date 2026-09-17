{{--
    Filter controls bound to the FiltersCatalog trait. Rendered in the desktop
    sidebar and inside the phone filter sheet (`idPrefix` keeps ids unique).
--}}
@props([
    'brands' => [],
    'priceRanges' => [],
    'idPrefix' => 'filter',
])

<div {{ $attributes->class('flex flex-col gap-6') }}>
    <fieldset class="flex flex-col gap-2">
        <legend class="mb-2 font-semibold">Availability</legend>
        <x-ui.checkbox :id="$idPrefix.'-in-stock'" label="In stock only" wire:model.live="inStock" />
    </fieldset>

    <fieldset class="flex flex-col gap-2">
        <legend class="mb-2 font-semibold">Price</legend>
        <label class="flex items-center gap-3">
            <input type="radio" value="" wire:model.live="price" name="{{ $idPrefix }}-price" class="size-5 accent-brand">
            <span>Any price</span>
        </label>
        @foreach ($priceRanges as $key => [$label])
            <label class="flex items-center gap-3" wire:key="{{ $idPrefix }}-price-{{ $key }}">
                <input type="radio" value="{{ $key }}" wire:model.live="price" name="{{ $idPrefix }}-price" class="size-5 accent-brand">
                <span>{{ $label }}</span>
            </label>
        @endforeach
    </fieldset>

    @if (count($brands) > 1)
        <fieldset class="flex flex-col gap-2">
            <legend class="mb-2 font-semibold">Brand</legend>
            @foreach ($brands as $brand)
                <x-ui.checkbox
                    wire:key="{{ $idPrefix }}-brand-{{ \Illuminate\Support\Str::slug($brand) }}"
                    :id="$idPrefix.'-brand-'.\Illuminate\Support\Str::slug($brand)"
                    :label="$brand"
                    :value="$brand"
                    wire:model.live="brands"
                />
            @endforeach
        </fieldset>
    @endif
</div>

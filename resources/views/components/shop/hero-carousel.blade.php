{{--
    Desktop hero carousel (lg and up). Auto-advances every 6 s, pauses on hover,
    keyboard focus or the pause button, and never auto-plays for people who
    prefer reduced motion (WCAG 2.2.2).

    Slides are the admin's banner rows (ADR-024): `banners` is a collection of
    `App\Models\Banner`, and each is rendered with `<x-shop.banner-panel>`.
--}}
@props([
    'banners',
    'interval' => 6000,
])

@php($count = $banners->count())

@if ($count > 0)
    <section
        aria-roledescription="carousel"
        aria-label="Featured"
        wire:ignore
        x-data="{
            index: 0,
            count: {{ $count }},
            interval: {{ (int) $interval }},
            elapsed: 0,
            paused: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            held: false,
            last: performance.now(),
            init() {
                if (this.count < 2) { this.paused = true; return; }
                setInterval(() => {
                    const now = performance.now();
                    const delta = Math.min(now - this.last, 1000);
                    this.last = now;
                    if (this.paused || this.held || document.hidden) return;
                    this.elapsed += delta;
                    if (this.elapsed >= this.interval) this.next();
                }, 100);
            },
            go(i) { this.index = (i + this.count) % this.count; this.elapsed = 0; },
            next() { this.go(this.index + 1); },
            prev() { this.go(this.index - 1); },
        }"
        x-on:mouseenter="held = true"
        x-on:mouseleave="held = false"
        x-on:focusin="held = true"
        x-on:focusout="if (! $el.contains($event.relatedTarget)) held = false"
        x-on:keydown.right.prevent="next()"
        x-on:keydown.left.prevent="prev()"
        {{ $attributes->class('relative overflow-hidden rounded-sheet') }}
    >
        <div class="grid" x-bind:aria-live="paused ? 'polite' : 'off'">
            @foreach ($banners as $i => $banner)
                <div
                    wire:key="hero-{{ $banner->id }}"
                    role="group"
                    aria-roledescription="slide"
                    aria-label="{{ $i + 1 }} of {{ $count }}: {{ $banner->eyebrow ?? $banner->title }}"
                    class="col-start-1 row-start-1 grid transition duration-500 ease-out"
                    x-bind:class="index === {{ $i }} ? 'opacity-100 translate-x-0' : 'pointer-events-none opacity-0 translate-x-6'"
                    x-bind:inert="index !== {{ $i }}"
                    x-bind:aria-hidden="(index !== {{ $i }}).toString()"
                    @if ($i > 0) inert aria-hidden="true" style="opacity: 0" x-init="$el.style.opacity = ''" @endif
                >
                    @php($showcase = app(\App\Support\Home\HomeContent::class)->showcase($banner))
                    <x-shop.banner-panel :banner="$banner" layout="hero" class="min-h-[27rem] pb-24" :heading="$i === 0 ? 'h1' : 'h2'">
                        @if ($showcase->isNotEmpty())
                            <x-slot:showcase>
                                <x-shop.hero-showcase :products="$showcase" />
                            </x-slot:showcase>
                        @endif
                    </x-shop.banner-panel>
                </div>
            @endforeach
        </div>

        @if ($count > 1)
            {{-- Controls --}}
            <div class="absolute inset-x-0 bottom-0 flex items-center gap-3 px-12 pb-6">
                <button
                    type="button"
                    x-on:click="paused = ! paused; elapsed = 0"
                    x-bind:aria-label="paused ? 'Play slides' : 'Pause slides'"
                    class="flex size-10 items-center justify-center rounded-full bg-white/90 text-ink shadow-overlay hover:bg-white"
                >
                    <svg x-show="! paused" aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16" rx="1" /><rect x="14" y="4" width="4" height="16" rx="1" /></svg>
                    <svg x-show="paused" x-cloak aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M7 4.5v15a1 1 0 0 0 1.5.86l12.5-7.5a1 1 0 0 0 0-1.72L8.5 3.64A1 1 0 0 0 7 4.5Z" /></svg>
                </button>

                <div class="flex h-10 items-center rounded-full bg-ink/70 px-2" role="group" aria-label="Choose a slide">
                    @foreach ($banners as $i => $banner)
                        <button
                            type="button"
                            wire:key="dot-{{ $banner->id }}"
                            x-on:click="go({{ $i }})"
                            aria-label="Show slide {{ $i + 1 }}: {{ $banner->eyebrow ?? $banner->title }}"
                            x-bind:aria-current="index === {{ $i }} ? 'true' : 'false'"
                            class="group flex h-10 min-w-6 items-center justify-center px-1"
                        >
                            <span
                                aria-hidden="true"
                                class="relative block h-2.5 overflow-hidden rounded-full bg-white/50 transition-[width] duration-300 group-hover:bg-white/70"
                                x-bind:class="index === {{ $i }} ? 'w-12' : 'w-2.5'"
                            >
                                <span
                                    class="absolute inset-y-0 left-0 rounded-full bg-white"
                                    x-bind:style="index === {{ $i }} ? `width: ${paused ? 100 : Math.min(100, elapsed / interval * 100)}%` : 'width: 0'"
                                ></span>
                            </span>
                        </button>
                    @endforeach
                </div>

                <div class="ms-auto flex gap-2">
                    <button type="button" x-on:click="prev()" aria-label="Previous slide" class="flex size-10 items-center justify-center rounded-full bg-white/90 text-ink shadow-overlay hover:bg-white">
                        <x-ui.icon name="chevron-left" />
                    </button>
                    <button type="button" x-on:click="next()" aria-label="Next slide" class="flex size-10 items-center justify-center rounded-full bg-white/90 text-ink shadow-overlay hover:bg-white">
                        <x-ui.icon name="chevron-right" />
                    </button>
                </div>
            </div>
        @endif
    </section>
@endif

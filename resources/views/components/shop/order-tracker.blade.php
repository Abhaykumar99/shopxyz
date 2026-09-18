{{--
    Order progress as tags on a string. Each step:
    ['label' => 'Packed', 'time' => 'Today, 11:20 am', 'state' => 'done'|'current'|'upcoming', 'note' => null]
    Pass `failed` to show the current step in the danger colour (cancelled / delivery failed).
--}}
@props([
    'steps' => [],
    'failed' => false,
])

<ol {{ $attributes->class('relative flex flex-col') }}>
    @foreach ($steps as $step)
        @php
            $state = $step['state'] ?? 'upcoming';
            $isLast = $loop->last;
        @endphp
        <li @class(['relative flex gap-3', 'pb-5' => ! $isLast]) @if ($state === 'current') aria-current="step" @endif>
            {{-- the string --}}
            @unless ($isLast)
                <span aria-hidden="true" @class([
                    'absolute top-7 bottom-0 left-[0.6875rem] w-0.5',
                    'bg-pistachio' => $state === 'done',
                    'bg-line' => $state !== 'done',
                ])></span>
            @endunless

            {{-- the tag --}}
            <span aria-hidden="true" @class([
                'relative z-10 mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full',
                'bg-pistachio text-white' => $state === 'done',
                'bg-brand text-white ring-4 ring-brand-tint' => $state === 'current' && ! $failed,
                'bg-danger text-white ring-4 ring-danger-tint' => $state === 'current' && $failed,
                'border-2 border-line-strong bg-surface' => $state === 'upcoming',
            ])>
                @if ($state === 'done')
                    <x-ui.icon name="check" :size="14" />
                @elseif ($state === 'current')
                    <x-ui.icon :name="$failed ? 'x' : ($step['icon'] ?? 'truck')" :size="14" />
                @endif
            </span>

            <div class="flex min-w-0 flex-col">
                <p @class([
                    'leading-snug',
                    'tag-shape inline-flex h-8 items-center self-start pe-3 font-display text-lg font-bold text-white' => $state === 'current',
                    'bg-brand' => $state === 'current' && ! $failed,
                    'bg-danger' => $state === 'current' && $failed,
                    'font-medium text-ink' => $state === 'done',
                    'text-ink-soft' => $state === 'upcoming',
                ])>
                    <span class="sr-only">{{ match ($state) { 'done' => 'Completed:', 'current' => 'Current step:', default => 'Next:' } }}</span>
                    {{ $step['label'] }}
                </p>
                @if (! empty($step['time']))
                    <p class="figures text-sm text-ink-soft">{{ $step['time'] }}</p>
                @endif
                @if (! empty($step['note']))
                    <div class="mt-2 text-sm text-ink">{{ $step['note'] }}</div>
                @endif
            </div>
        </li>
    @endforeach
</ol>

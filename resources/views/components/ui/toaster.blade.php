{{--
    Global toast area, placed once per layout. Show a toast with:
      Livewire:  $this->dispatch('toast', message: 'Added to bag', tone: 'success');
      Alpine/JS: window.dispatchEvent(new CustomEvent('toast', { detail: { message: '...', tone: 'success' } }))
    Flash a toast after a redirect with session('toast').
--}}
@php($flash = session('toast'))

<div
    x-data="{
        toasts: [],
        add({ message, tone = 'info' }) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, message, tone });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
    }"
    x-on:toast.window="add($event.detail)"
    @if ($flash) x-init="add(@js(is_array($flash) ? $flash : ['message' => $flash]))" @endif
    aria-live="polite"
    class="pointer-events-none fixed inset-x-0 bottom-20 z-50 flex flex-col items-center gap-2 px-4 lg:bottom-6"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            class="pointer-events-auto flex w-full max-w-sm animate-pop items-center gap-3 rounded-card bg-ink px-4 py-3 text-white shadow-overlay"
            role="status"
        >
            <span
                aria-hidden="true"
                class="size-2.5 shrink-0 rounded-full"
                :class="{
                    'bg-marigold': toast.tone === 'warning',
                    'bg-danger-tint': toast.tone === 'danger',
                    'bg-pistachio-tint': toast.tone === 'success',
                    'bg-info-tint': toast.tone === 'info',
                }"
            ></span>
            <p class="grow" x-text="toast.message"></p>
            <button type="button" class="-me-1 rounded-full p-1 hover:bg-white/15" x-on:click="remove(toast.id)" aria-label="Dismiss">
                <x-ui.icon name="x" :size="18" />
            </button>
        </div>
    </template>
</div>

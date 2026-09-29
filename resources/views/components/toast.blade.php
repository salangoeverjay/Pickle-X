{{-- Listens for Livewire $this->dispatch('toast', message: '...') and session('toast') after redirects.
     Slides in, stays 2.8s, then slides out before it unmounts; a new toast swaps keyframes so the entrance replays. --}}
<div
    x-data="{ message: @js(session('toast')), out: false, n: 0, timer: null, outTimer: null,
        show(m) {
            this.message = m; this.out = false; this.n++;
            clearTimeout(this.timer); clearTimeout(this.outTimer);
            this.timer = setTimeout(() => { this.out = true; this.outTimer = setTimeout(() => { this.message = null; this.out = false }, 300) }, 2800);
        } }"
    x-init="message && show(message)"
    x-on:toast.window="show($event.detail.message)"
    class="pointer-events-none fixed inset-x-0 bottom-22.5 z-90 flex justify-center px-4 lg:bottom-7"
>
    <template x-if="message">
        <div role="status" aria-live="polite" x-bind:class="{ 'px-toast-b': n % 2 === 0, 'px-toast-out': out }" class="px-toast flex max-w-140 items-center gap-2.5 rounded-full bg-ink py-2.5 pr-4.5 pl-2.5 text-sm leading-[1.35] font-semibold text-bg shadow-[0_20px_40px_-12px_rgba(0,0,0,.45)]">
            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-lime text-on-lime"><x-icon name="check" :size="16" :stroke="2.4" /></span>
            <span x-text="message"></span>
        </div>
    </template>
</div>

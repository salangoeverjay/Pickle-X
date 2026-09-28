{{-- Split auth layout: $aside is the desktop-only brand panel, default slot is the form column. --}}
<div class="flex min-h-dvh animate-screen">
    <aside {{ $aside->attributes->class('relative hidden shrink-0 flex-col justify-between overflow-hidden px-11 pt-9 pb-11 lg:sticky lg:top-0 lg:flex lg:h-dvh') }}>
        {{ $aside }}
    </aside>

    <div id="main" tabindex="-1" class="flex min-w-0 flex-1 flex-col">
        <div class="flex items-center justify-between gap-3 px-4 py-2.5 lg:px-8 lg:py-6">
            <a href="{{ route('home') }}" wire:navigate class="hidden h-11 items-center gap-1.5 rounded-full pr-3.5 pl-2 text-sm font-semibold text-ink2 transition-colors hover:bg-surface2 hover:text-ink lg:inline-flex">
                <x-icon name="chevron-left" :size="18" />Back to home
            </a>
            <a href="{{ route('home') }}" wire:navigate aria-label="PickleX home" class="text-ink lg:hidden">
                <x-logo class="text-[25px]" />
            </a>
            <x-theme-toggle />
        </div>

        {{ $slot }}

        <p class="m-0 shrink-0 px-4 pb-5.5 text-center text-xs text-ink3">PickleX is made by <strong class="font-bold text-ink2">CreativeX Techlabs Inc.</strong></p>
    </div>
</div>

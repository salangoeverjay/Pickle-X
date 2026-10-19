{{--
    Court artwork shared by Log in and Reset password. Default: the desktop brand
    panel's contents (use inside <x-slot:aside>). :strip="true": the mobile court strip.
--}}
@props(['title' => null, 'text' => null, 'strip' => false])
@php($players = [['BT', '#E2D2FF'], ['ML', '#FFEFA3'], ['RT', '#CDEBB0'], ['KS', '#BCD9F7']])

@if ($strip)
    <div class="relative mx-4 mt-1 h-37.5 shrink-0 md:mx-auto md:mt-3 md:aspect-[37/20] md:h-auto md:w-[calc(100%-4rem)] md:max-w-140 lg:hidden">
        <x-court class="absolute inset-0 rounded-[22px]" player-class="size-7.5 text-[10px]" :players="$players" />
        <span class="absolute top-2.5 left-2.5 inline-flex h-6 items-center gap-1.5 rounded-full bg-black/35 px-2.25 font-mono text-[10px] tracking-widest text-white">
            <span class="size-1.5 animate-live rounded-full bg-lime"></span>COURT 3, <x-live-timer />
        </span>
    </div>
@else
    <a href="{{ route('home') }}" wire:navigate aria-label="PickleX home" class="relative self-start">
        <x-logo class="text-[28px]" mark="currentColor" />
    </a>

    <div class="relative aspect-[37/20] w-full max-w-190 transform-[perspective(1200px)_rotateX(24deg)_rotateZ(-6deg)]">
        <x-court class="absolute inset-0 rounded-[22px] shadow-[0_40px_60px_-30px_rgba(0,0,0,.6)]" player-class="size-10.5 text-[13px]" :players="$players" />
    </div>

    <div class="relative flex flex-col gap-3.5">
        <span class="inline-flex h-7.5 items-center gap-2 self-start rounded-full bg-black/22 px-3 font-mono text-[11px] tracking-widest">
            <span class="size-1.75 animate-live rounded-full bg-lime"></span>COURT 3, <x-live-timer />
        </span>
        <h2 class="m-0 font-display text-[46px] leading-[.98] font-extrabold tracking-[-0.04em] text-balance">{{ $title }}</h2>
        <p class="m-0 max-w-110 text-base leading-[1.55] text-white/90">{{ $text }}</p>
    </div>
@endif

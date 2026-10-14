{{-- Eyebrow + h1 (+ optional description and actions slot on the right). --}}
@props(['eyebrow', 'title', 'description' => null])

<div class="flex flex-wrap items-end justify-between gap-4">
    <div class="flex flex-col gap-1.5">
        <span class="font-mono text-xs tracking-[.12em] text-ink3 uppercase">{{ $eyebrow }}</span>
        <h1 class="m-0 font-display text-[32px] leading-none font-extrabold tracking-[-0.04em] md:text-[38px] xl:text-[44px]">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 mb-0 max-w-140 text-ink2">{{ $description }}</p>
        @endif
    </div>
    {{ $slot }}
</div>

{{-- Initials in a colored circle. Size it with classes, e.g. class="size-10 text-[13px]". --}}
@props(['name', 'color'])

<span {{ $attributes->merge(['class' => 'flex shrink-0 items-center justify-center rounded-full font-extrabold text-on-lime']) }} style="background: {{ $color }}">{{ initials($name) }}</span>

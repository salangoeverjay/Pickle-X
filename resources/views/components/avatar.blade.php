{{-- Profile picture, or initials on a colored circle. Size it with classes, e.g. class="size-10 text-[13px]". --}}
@props(['name', 'color', 'src' => null])

@if ($src)
    <img src="{{ $src }}" alt="" loading="lazy" decoding="async" {{ $attributes->merge(['class' => 'aspect-square max-w-none shrink-0 rounded-full object-cover']) }} style="background: {{ $color }}">
@else
    <span {{ $attributes->merge(['class' => 'flex shrink-0 items-center justify-center rounded-full font-extrabold text-on-lime']) }} style="background: {{ $color }}">{{ initials($name) }}</span>
@endif

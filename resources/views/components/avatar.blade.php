{{-- Profile picture, or initials on a colored circle. Size it with classes, e.g. class="size-10 text-[13px]".
     frame: avatar border classes (User::frameClass()). Pictures get a wrapper, since an <img> can't draw the ring. --}}
@props(['name', 'color', 'src' => null, 'frame' => ''])

@if ($src && $frame)
    <span {{ $attributes->merge(['class' => "block aspect-square shrink-0 rounded-full {$frame}"]) }} style="background: {{ $color }}">
        <img src="{{ $src }}" alt="" loading="lazy" decoding="async" class="size-full rounded-full object-cover">
    </span>
@elseif ($src)
    <img src="{{ $src }}" alt="" loading="lazy" decoding="async" {{ $attributes->merge(['class' => 'aspect-square max-w-none shrink-0 rounded-full object-cover']) }} style="background: {{ $color }}">
@else
    <span {{ $attributes->merge(['class' => trim("flex shrink-0 items-center justify-center rounded-full font-extrabold text-on-lime {$frame}")]) }} style="background: {{ $color }}">{{ initials($name) }}</span>
@endif

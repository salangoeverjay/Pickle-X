{{-- Photo when there is one (JPEG); initials otherwise. Email clients don't render the SVG preset avatars. --}}
@props(['name', 'color' => '#BCD9F7', 'src' => null, 'size' => 34, 'ring' => null])
@php($src = $src && ! str_ends_with(parse_url($src, PHP_URL_PATH) ?? '', '.svg') ? $src : null)
@php($border = $ring ? "border: 3px solid {$ring};" : '')
@if ($src)
    <img src="{{ $src }}" width="{{ $size }}" height="{{ $size }}" alt="" style="display: block; width: {{ $size }}px; height: {{ $size }}px; border-radius: 50%; background: {{ $color }}; {{ $border }}">
@else
    <div style="width: {{ $size }}px; height: {{ $size }}px; line-height: {{ $size }}px; border-radius: 50%; background: {{ $color }}; color: #12170F; text-align: center; font-weight: 800; font-size: {{ round($size * .32) }}px; {{ $border }}">{{ initials($name) }}</div>
@endif

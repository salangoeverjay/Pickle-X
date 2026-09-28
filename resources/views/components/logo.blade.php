{{-- Wordmark scales with font-size: pass a text-[..] class. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5 font-display font-extrabold leading-none tracking-[-0.04em]']) }}>
    <span>pickl</span>
    <svg class="size-[1.15em] shrink-0 mx-px translate-y-px" viewBox="0 0 48 48" aria-hidden="true">
        <circle cx="24" cy="15.5" r="10" fill="#F59A3E" fill-opacity=".88"/>
        <circle cx="31.36" cy="19.75" r="10" fill="#E5508A" fill-opacity=".88"/>
        <circle cx="31.36" cy="28.25" r="10" fill="#7C62DA" fill-opacity=".88"/>
        <circle cx="24" cy="32.5" r="10" fill="#3F86E8" fill-opacity=".88"/>
        <circle cx="16.64" cy="28.25" r="10" fill="#2FBFC7" fill-opacity=".88"/>
        <circle cx="16.64" cy="19.75" r="10" fill="#C3EA3B" fill-opacity=".88"/>
        <g fill="#FFFFFF">
            @foreach ([0, 60, 120, 180, 240, 300] as $deg)
                <path transform="rotate({{ $deg }} 24 24)" d="M24 24C22.2 21.4 22.2 18 24 15.4C25.8 18 25.8 21.4 24 24Z"/>
            @endforeach
            <circle cx="24" cy="24" r="1.8"/>
        </g>
    </svg>
    <span>x</span>
</span>

{{-- Court scene per error code (decorative; the wrapper carries the description). --}}
@php($mono = 'Barlow Condensed, Arial Narrow, sans-serif')
<svg width="100%" height="100%" viewBox="0 0 560 420" preserveAspectRatio="xMidYMid meet" aria-hidden="true" class="block">
    <rect x="60" y="50" width="440" height="320" style="fill: var(--color-court)" />
    <rect x="209.6" y="50" width="140.8" height="320" style="fill: var(--color-court-in)" />
    <g fill="none" stroke="#FFFFFF" stroke-opacity=".92" stroke-width="4"><rect x="60" y="50" width="440" height="320" /><path d="M209.6 50v320M350.4 50v320M60 210h149.6M350.4 210h149.6" /></g>
    <rect x="277" y="28" width="6" height="364" rx="3" fill="#F4F4EC" />

    @switch($code)
        @case('403')
            <path d="M40 300 C 150 280 410 280 520 300" fill="none" stroke="#DDF84E" stroke-width="5" stroke-linecap="round" />
            <g fill="#FFFFFF"><circle cx="40" cy="300" r="9" /><circle cx="520" cy="300" r="9" /></g>
            <g class="motion-safe:animate-[float_2.4s_ease-in-out_infinite]"><circle cx="280" cy="216" r="54" fill="#12170F" /><path d="M258 212h44v34h-44zM265 212v-12a15 15 0 0 1 30 0v12" fill="none" stroke="#DDF84E" stroke-width="6" stroke-linejoin="round" /><circle cx="280" cy="229" r="4" fill="#DDF84E" /></g>
            <g transform="translate(200 92)"><rect width="160" height="38" rx="10" fill="#DDF84E" stroke="#12170F" stroke-width="3" /><text x="80" y="25" text-anchor="middle" font-family="{{ $mono }}" font-size-adjust="0.58" font-size="14" font-weight="700" letter-spacing="3" fill="#12170F">RESERVED</text></g>
            @break
        @case('419')
            <circle cx="96" cy="112" r="20" fill="#FFEFA3" stroke="#FFFFFF" stroke-width="4" />
            <circle cx="430" cy="150" r="20" fill="#E2D2FF" stroke="#FFFFFF" stroke-width="4" />
            <rect x="208" y="104" width="24" height="16" rx="4" fill="#12170F" />
            <circle cx="220" cy="196" r="74" fill="#FFFFFF" stroke="#12170F" stroke-width="7" />
            <path d="M220 196 L220 122 A74 74 0 0 1 284 159 Z" fill="#DDF84E" />
            <path d="M220 132v10M284 196h-10M220 260v-10M156 196h10" stroke="#12170F" stroke-width="4" stroke-linecap="round" />
            <g class="origin-[220px_196px] motion-safe:animate-[spin_4s_steps(8)_infinite]"><path d="M220 196 L220 142" stroke="#12170F" stroke-width="6" stroke-linecap="round" /></g>
            <circle cx="220" cy="196" r="8" fill="#12170F" />
            <g transform="translate(146 296)"><rect width="148" height="36" rx="18" fill="#12170F" /><text x="74" y="24" text-anchor="middle" font-family="{{ $mono }}" font-size-adjust="0.58" font-size="14" font-weight="700" letter-spacing="3" fill="#DDF84E">TIME OUT</text></g>
            @break
        @case('429')
            <path d="M96 110h56M70 170h72M110 230h60M84 290h70M130 350h44M180 140h40M196 260h36" stroke="#FFFFFF" stroke-opacity=".55" stroke-width="3" stroke-linecap="round" />
            <g fill="#DDF84E" stroke="#12170F" stroke-opacity=".25" stroke-width="1.5">
                @foreach ([[164, 110], [154, 170], [182, 230], [166, 290], [186, 350], [232, 140], [244, 260], [252, 196], [226, 320]] as [$x, $y])
                    <circle cx="{{ $x }}" cy="{{ $y }}" r="11" />
                @endforeach
            </g>
            <circle cx="420" cy="136" r="22" fill="#CDEBB0" stroke="#FFFFFF" stroke-width="4" />
            <g transform="translate(356 186)"><rect width="128" height="36" rx="18" fill="#FFFFFF" /><text x="64" y="24" text-anchor="middle" font-family="{{ $mono }}" font-size-adjust="0.58" font-size="12" font-weight="700" letter-spacing="1.5" fill="#12170F">ONE AT A TIME</text></g>
            @break
        @case('500')
            <path d="M271 40v340M289 40v340" stroke="#12170F" stroke-opacity=".35" stroke-width="1.5" />
            <g stroke="#12170F" stroke-opacity=".35" stroke-width="1.5">
                @for ($y = 60; $y <= 360; $y += 20)
                    <path d="M271 {{ $y }}h18" />
                @endfor
            </g>
            <path d="M100 330 Q 190 120 262 176" fill="none" stroke="#FFFFFF" stroke-opacity=".75" stroke-width="3" stroke-dasharray="2 10" stroke-linecap="round" />
            <path d="M248 146l-14-14M246 204l-16 12M300 142l14-16M304 202l16 12M280 130v-18" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round" />
            <circle cx="276" cy="176" r="18" fill="#DDF84E" stroke="#12170F" stroke-opacity=".3" stroke-width="1.5" />
            <g fill="#12170F" fill-opacity=".35"><circle cx="270" cy="170" r="2.5" /><circle cx="282" cy="172" r="2.5" /><circle cx="274" cy="183" r="2.5" /></g>
            <circle cx="100" cy="330" r="20" fill="#FFD3A3" stroke="#FFFFFF" stroke-width="4" />
            <g transform="translate(372 88)"><rect width="118" height="36" rx="18" fill="#FFFFFF" /><text x="59" y="24" text-anchor="middle" font-family="{{ $mono }}" font-size-adjust="0.58" font-size="14" font-weight="700" letter-spacing="3" fill="#12170F">FAULT</text></g>
            @break
        @case('503')
            <defs><pattern id="er-hz" width="28" height="28" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><rect width="14" height="28" fill="#12170F" /></pattern></defs>
            <g transform="rotate(-9 280 210)"><rect x="-40" y="176" width="640" height="46" fill="#DDF84E" /><rect x="-40" y="176" width="640" height="46" fill="url(#er-hz)" fill-opacity=".9" /><rect x="170" y="182" width="220" height="34" rx="6" fill="#DDF84E" stroke="#12170F" stroke-width="3" /><text x="280" y="205" text-anchor="middle" font-family="{{ $mono }}" font-size-adjust="0.58" font-size="14" font-weight="700" letter-spacing="3" fill="#12170F">COURTS CLOSED</text></g>
            @foreach ([[100, 350], [430, 116]] as [$x, $y])
                <g><path d="M{{ $x }} {{ $y }} l18 -52 l18 52z" fill="#F59A3E" stroke="#12170F" stroke-width="3" stroke-linejoin="round" /><path d="M{{ $x + 8 }} {{ $y - 20 }}h20" stroke="#FFFFFF" stroke-width="5" /><rect x="{{ $x - 8 }}" y="{{ $y - 2 }}" width="52" height="8" rx="3" fill="#12170F" /></g>
            @endforeach
            <g class="motion-safe:animate-[float_2.4s_ease-in-out_infinite]"><rect x="170" y="290" width="92" height="40" rx="20" fill="#FFFFFF" stroke="#12170F" stroke-width="3" /><path d="M262 310h30v-40" fill="none" stroke="#FFFFFF" stroke-width="6" stroke-linecap="round" /><path d="M188 310h56" stroke="#12170F" stroke-opacity=".3" stroke-width="3" stroke-dasharray="6 6" /></g>
            @break
        @default
            {{-- 404: a ball flying past the lines and landing out. --}}
            <circle cx="410" cy="128" r="20" fill="#BCD9F7" stroke="#FFFFFF" stroke-width="4" />
            <path d="M392 118 Q 150 -30 42 390" fill="none" stroke="#FFFFFF" stroke-opacity=".75" stroke-width="3" stroke-dasharray="2 10" stroke-linecap="round" />
            <circle cx="40" cy="396" r="26" fill="none" stroke="#DDF84E" stroke-opacity=".5" stroke-width="3" />
            <circle cx="40" cy="396" r="12" fill="#DDF84E" stroke="#12170F" stroke-opacity=".25" stroke-width="1.5" />
            <g transform="translate(78 382)"><rect width="78" height="30" rx="15" fill="#FFFFFF" /><text x="39" y="20" text-anchor="middle" font-family="{{ $mono }}" font-size-adjust="0.58" font-size="13" font-weight="700" letter-spacing="2" fill="#12170F">OUT</text></g>
    @endswitch
</svg>

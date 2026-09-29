{{-- Email CTA. variant: primary (lime), secondary (outline), dark. --}}
@props(['href', 'variant' => 'primary'])
@php($style = match ($variant) {
    'secondary' => 'background: transparent; color: #12170F; border: 1.5px solid #C8C6B8; padding: 15px 24px; font-size: 15px;',
    'dark' => 'background: #12170F; color: #FFFFFF; border: 0; padding: 13px 20px; font-size: 15px;',
    default => 'background: #D3F347; color: #12170F; border: 2px solid #12170F; padding: 15px 30px; font-size: 16px; box-shadow: 4px 4px 0 #12170F;',
})
<a href="{{ $href }}" class="em-btn {{ $variant === 'secondary' ? 'em-ink em-line' : '' }} {{ $variant === 'dark' ? 'em-ink-bg' : '' }}" style="display: inline-block; {{ $style }} border-radius: 999px; font-family: 'Instrument Sans', -apple-system, 'Segoe UI', Helvetica, Arial, sans-serif; font-weight: 700; line-height: 1.2; text-decoration: none; mso-padding-alt: 0;">{{ $slot }}</a>

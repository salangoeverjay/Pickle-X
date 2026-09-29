{{-- Four stat tiles in a row (2 × 2 on phones). $stats = [[label, value, color?], ...] --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 22px;">
    <tr>
        @foreach ($stats as [$label, $value, $color])
            <td class="em-half" width="25%" valign="top" style="padding: {{ $loop->first ? '0 5px 0 0' : ($loop->last ? '0 0 0 5px' : '0 5px') }};">
                <div class="em-soft" style="margin-bottom: 10px; padding: 14px; border-radius: 16px; background: #F6F5EE;">
                    <p class="em-ink3" style="margin: 0 0 2px; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-size: 10px; letter-spacing: .1em; text-transform: uppercase; color: #5B6257;">{{ $label }}</p>
                    <p class="{{ $color ? 'em-lime-text' : 'em-ink' }}" style="margin: 0; font-family: 'Bricolage Grotesque', 'Arial Black', Helvetica, Arial, sans-serif; font-weight: 800; font-size: 26px; letter-spacing: -0.03em; color: {{ $color ?: '#12170F' }}; white-space: nowrap;">{{ $value }}</p>
                </div>
            </td>
        @endforeach
    </tr>
</table>

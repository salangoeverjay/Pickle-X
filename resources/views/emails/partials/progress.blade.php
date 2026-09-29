{{-- "9 of 18 joined" with a bar. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 8px;">
    <tr>
        <td class="em-ink" style="font-weight: 700; color: #12170F;">{{ $count }} of {{ $cap }} joined</td>
        <td align="right" class="em-ink3" style="font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-size: 12px; color: #5B6257;">{{ max(0, $cap - $count) }} {{ Str::plural('spot', max(0, $cap - $count)) }} left</td>
    </tr>
</table>
<div class="em-line" style="height: 8px; border-radius: 4px; background: #DEDCD0; overflow: hidden; line-height: 0; font-size: 0;"><div class="em-ink-bg" style="width: {{ min(100, round($count * 100 / max(1, $cap))) }}%; height: 8px; border-radius: 4px; background: #12170F;"></div></div>

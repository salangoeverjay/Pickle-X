{{-- One open play as a list row: date tile, title, details, a note on the right. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="em-line" style="margin: 0 0 10px; border: 1px solid #DEDCD0; border-radius: 16px;">
    <tr>
        <td style="padding: 12px 0 12px 14px; width: 50px;">
            <div class="em-soft" style="width: 50px; padding: 6px 0; border-radius: 14px; background: #F6F5EE; border: 1px solid #DEDCD0; text-align: center; line-height: 1.1;">
                <span class="em-ink3" style="display: block; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-size: 9px; letter-spacing: .1em; color: #5B6257;">{{ strtoupper($play->starts_at->format('M')) }}</span>
                <span class="em-ink" style="display: block; font-family: 'Bricolage Grotesque', 'Arial Black', Helvetica, Arial, sans-serif; font-weight: 800; font-size: 21px; color: #12170F;">{{ $play->starts_at->format('j') }}</span>
                <span class="em-ink3" style="display: block; font-size: 9px; color: #5B6257;">{{ $play->starts_at->format('D') }}</span>
            </div>
        </td>
        <td style="padding: 12px 14px;">
            <p class="em-ink" style="margin: 0; font-weight: 700; color: #12170F;">{{ $play->title }}</p>
            <p class="em-ink3" style="margin: 0; font-size: 13px; color: #5B6257;">{{ $play->club }} · {{ $play->timeRange() }} · {{ $play->feeLabel() }}</p>
        </td>
        <td align="right" class="em-lime-text" style="padding: 12px 14px 12px 0; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size-adjust: 0.58; font-variant-numeric: tabular-nums; font-size: 12px; font-weight: 600; color: #496800; white-space: nowrap;">{{ $right }}</td>
    </tr>
</table>
